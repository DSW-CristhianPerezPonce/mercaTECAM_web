# Refactors propuestos

Seis refactors ordenados por relación valor/esfuerzo. No hay suite de tests: la columna "Comprobación" indica qué correr a mano o qué test escribir primero. Los fragmentos "Después" son **propuestas**, no código existente.

## 1. Repositorio único y perezoso en el front controller

**Antes** (`api/index.php:16`, `:27`, `:41`, ~`:57`):

```php
$repo = new ProductRepository(Database::connection());
```

Se repite en 4 ramas.

**Después** (propuesta):

```php
$repo = static function (): ProductRepository {
    static $r = null;
    return $r ??= new ProductRepository(Database::connection());
};
// uso: $repo()->listarCatalogo($categoria, $busqueda)
```

- **Principio**: DRY y KISS; la creación perezosa evita abrir conexión en rutas que no la necesitan (`/carrito` es solo un shell, `api/index.php:32`).
- **Riesgo**: bajo. El `static` dentro de la closure se comparte solo en esa closure.
- **Comprobación**: abrir `/carrito` con `DATABASE_URL` inválida (debería responder 200) y `/` con la URL válida.
- **Esfuerzo**: S.

## 2. Tipado de filas en un método privado y constante de id máximo

**Antes** (`src/ProductRepository.php:53-57` y `:131-136`):

```php
$r['id']                = (int) $r['id'];
$r['precio_referencia'] = (float) $r['precio_referencia'];
$r['disponibilidad']    = (int) $r['disponibilidad'];
```

Se repite, y el carrito añade `$r['imagen'] = imagen_producto($r['imagen']);`. Además `2147483647` aparece en `:69` y `:115`.

**Después** (propuesta):

```php
private const ID_MAXIMO = 2147483647;

private static function hidratar(array $r): array
{
    $r['id']                = (int) $r['id'];
    $r['precio_referencia'] = (float) $r['precio_referencia'];
    $r['disponibilidad']    = (int) $r['disponibilidad'];
    return $r;
}
// carrito: return array_map(fn ($r) => self::hidratar($r) + ['imagen' => imagen_producto($r['imagen'])], ...);
```

(Ojo: con `+` gana el arreglo de la izquierda; si se usa `array_merge`, gana el de la derecha. Escoge según el caso y prueba la salida.)

- **Principio**: DRY. De paso conviene mover `imagen_producto` fuera del repositorio (la capa de datos no debe conocer la presentación, `src/ProductRepository.php:131`), por ejemplo hidratando la imagen en el front controller.
- **Riesgo**: cambia el tipo o el orden de claves del JSON. Hoy solo `/api/carrito` normaliza la imagen; unificarla cambiaría `/api/productos`.
- **Comprobación**: guardar antes y después la salida de `/api/productos` y `/api/carrito?ids=1,2` y comparar con `diff`.
- **Esfuerzo**: S.

## 3. Función pura para agregar al carrito

**Antes** (`assets/js/carrito.js:88-104`, dentro del manejador de clic `:77`): lee DOM, valida, calcula y avisa en la misma función (CCN 10).

**Después** (propuesta):

```js
function calcularAgregado(carrito, id, cantidad, max) {
    if (!carrito[id] && Object.keys(carrito).length >= MAX_PRODUCTOS) {
        return { estado: 'lleno' };
    }
    var actual = carrito[id] || 0;
    var nueva = Math.min(actual + cantidad, max);
    if (nueva <= actual) { return { estado: 'sin-cupo' }; }
    return { estado: nueva - actual < cantidad ? 'parcial' : 'ok', nueva: nueva };
}
```

El manejador solo lee el DOM, llama a la función y traduce el estado en un aviso.

- **Principio**: SRP; separa regla de negocio de efectos.
- **Riesgo**: textos de aviso distintos si se cambia algún caso.
- **Comprobación**: test de tabla (carrito vacío, con 50 productos, tope exacto, tope parcial) ejecutable con Node sin navegador; luego prueba manual en `/` y en la ficha con campo de cantidad.
- **Esfuerzo**: M.

## 4. Dividir `prepararImagen`

**Antes** (`scripts/importar_productos.php:216`):

```php
function prepararImagen(string $imagen, string $nombre, string $fotosDir, string $destDir, int $linea, array &$errores): ?string
```

Seis parámetros, uno de salida; ramas para vacío, URL, archivo inexistente, GD, orientación y la copia final sin GD (`:255-258`).

**Después** (propuesta): funciones pequeñas (`resolverOrigen`, `optimizar`, `copiarSinGd`) y retorno `['ruta' => ?string, 'avisos' => list<string>]`; el llamador concatena avisos. Además, si `imagecreatefrom*` devuelve false, avisar en lugar de copiar en silencio.

- **Principio**: SRP, eliminar parámetro de salida.
- **Riesgo**: cambia el contenido de las fotos publicadas.
- **Comprobación**: importar con una foto corrupta, una URL https y sin GD (`php -n scripts/importar_productos.php ...`); verificar rutas y avisos antes y después.
- **Esfuerzo**: M.

## 5. Transacción con rollback y fotos fuera de la escritura

**Antes** (`scripts/importar_productos.php:75` `beginTransaction`, `:118` `commit`): el bucle llama a `prepararImagen` (~`:100`), que escribe fotos al disco, dentro de la transacción. No hay `try/catch` con `rollBack` explícito en esas líneas; si una fila lanza excepción, la conexión se cierra al terminar el script (hipótesis: PostgreSQL revierte la transacción abierta), pero las fotos ya copiadas quedan en `assets/productos/` y no hay mensaje claro de qué fila falló.

**Después** (propuesta):

```php
// 1a pasada: preparar fotos (efecto en disco), 2a pasada: transacción solo con SQL
$db->beginTransaction();
try {
    foreach ($filas as $f) { /* solo upserts */ }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
```

- **Principio**: SRP y atomicidad.
- **Riesgo**: cambio de orden; una foto puede quedar sin producto (inocuo, es idempotente).
- **Comprobación**: CSV con una fila de precio negativo al final (el CHECK de `database/schema.sql:25` lo rechaza) y verificar que no queda ninguna fila nueva.
- **Esfuerzo**: M.

## 6. Helper `vacioANulo()`

**Antes** (`scripts/importar_productos.php:95-96`):

```php
'municipio'  => $r['municipio'] ?? null ?: null,
'asociacion' => $r['asociacion'] ?? null ?: null,
```

**Después** (propuesta):

```php
function vacioANulo(?string $v): ?string
{
    $v = $v === null ? '' : trim($v);
    return $v === '' ? null : $v;
}
```

- **Principio**: KISS y claridad. Corrige un caso límite: `?:` trata el texto `"0"` como vacío.
- **Riesgo**: bajo; un municipio llamado "0" pasaría a conservarse (no es realista).
- **Comprobación**: importar un CSV con celdas vacías, con espacios y con "0"; revisar `municipio` y `asociacion`.
- **Esfuerzo**: S.

## Cierre

### Mini reto

Refactoriza el punto 3 (`calcularAgregado`) y comprueba que los tests siguen pasando. Como todavía no hay suite, el reto incluye escribir primero los cuatro casos de la tabla con Node (`node --test`) contra el código actual copiado a un archivo temporal, y después repetirlos contra la función extraída.

### Tabla de esfuerzo

| Refactor | Esfuerzo | Beneficio |
|---|---|---|
| 1. Repositorio único y perezoso | S | Menos duplicación; `/carrito` no depende de la base |
| 2. Tipado en método privado y `ID_MAXIMO` | S | Una sola fuente de verdad del contrato JSON |
| 3. `calcularAgregado` pura | M | Reglas del carrito testeables sin navegador |
| 4. Dividir `prepararImagen` | M | Importador legible; elimina fallo silencioso |
| 5. Transacción con rollback | M | Importaciones atómicas |
| 6. `vacioANulo()` | S | Código claro y sin caso "0" erróneo |
