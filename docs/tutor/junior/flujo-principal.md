# Flujo principal: del clic a la página

Dos recorridos: ver el catálogo (`GET /catalogo`) y pulsar "Agregar al carrito".

## 1. Mapa del recorrido

```mermaid
flowchart TD
    nav["Navegador"] --> front["api/index.php"]
    front --> boot["bootstrap carga src"]
    front --> repo["ProductRepository"]
    repo --> db[("Neon PostgreSQL")]
    front --> vista["render de plantillas"]
    vista --> html["HTML al navegador"]
    html --> js["carrito.js activa botones"]
```

*Hecho: toda petición pasa por `api/index.php` (`vercel.json`, bloque `routes`). `bootstrap.php` carga las clases (`src/bootstrap.php:23-25`).*
*Después de recibir el HTML, `carrito.js` (cargado con `defer`, `templates/layout.php:30`) activa los botones.*

## 2. Secuencia de `GET /catalogo`

```mermaid
sequenceDiagram
    participant U as Navegador
    participant I as api/index.php
    participant R as ProductRepository
    participant D as PostgreSQL
    participant T as Plantillas
    U->>I: GET /catalogo?categoria=verduras&q=tomate
    I->>R: listarCatalogo y categoriasConConteo
    R->>D: SELECT con parametros
    D-->>R: filas
    R-->>I: arreglos de productos
    I->>T: render layout y catalog
    T-->>U: HTML completo
```

*Los filtros llegan por la URL y se limpian con `query_param()` (`src/helpers.php:58`). La búsqueda ignora tildes y mayúsculas.*

## 3. Paso a paso

1. **Vercel recibe la petición** y, como no es un archivo estático, la reenvía a `api/index.php` (`vercel.json`; en local lo hace `router.php:7-11`).
2. **Arranque**: `require_once` de `src/bootstrap.php` (`api/index.php:5`), que lee `.env` si existe y carga las clases (`src/bootstrap.php:7-25`).
3. **Se leen los filtros** `categoria` y `q`, recortados a 40 y 100 caracteres (`api/index.php:11-12`).
4. **Se elige la rama**: para `/` o `/catalogo` entra al `if` de `api/index.php:40`.
5. **Se abre la conexión** (una sola por proceso, patrón singleton: `src/Database.php:12-16`) y se crea el repositorio (`api/index.php:41`).
6. **Se consultan los productos activos** con SQL preparado; el nombre se compara con `unaccent` e `ILIKE` (`src/ProductRepository.php:18`, `:30`).
7. **Se arma el HTML**: `render()` abre la plantilla con los datos (`src/helpers.php:49-55`), primero `catalog` y luego `layout` (`api/index.php:42-49`).
8. **Si algo falla**, el `catch` registra el error y muestra "No pudimos cargar el catálogo" (`api/index.php:81-91`).

## 4. Secuencia de "Agregar al carrito"

```mermaid
sequenceDiagram
    participant U as Persona
    participant B as Boton agregar
    participant J as carrito.js
    participant L as localStorage
    participant A as GET api/carrito
    U->>B: clic
    B->>J: evento click
    J->>L: leer carrito
    J->>J: aplica topes de disponibilidad y 50 productos
    J->>L: guardar id y cantidad
    J-->>U: aviso en pantalla
    U->>A: abre la pagina carrito
    A-->>J: nombres precios y stock vigentes
```

*Hecho: el botón trae `data-agregar`, `data-max` y `data-nombre` (`templates/partials/boton-agregar.php:7-9`) y está `disabled` hasta que el JS lo activa (`:11`).*
*El servidor no participa al agregar: solo se consulta cuando se abre la página del carrito.*

## 5. Paso a paso del botón

1. `activarBotones()` recorre los botones y les pone un manejador de clic (`assets/js/carrito.js:73`, `:77`).
2. Al hacer clic se lee el id, el máximo y la cantidad (1, o la del campo de la ficha) (`assets/js/carrito.js:78-85`).
3. Se lee el carrito guardado (`assets/js/carrito.js:16`). Si ya hay 50 productos distintos, se avisa y se detiene (`:88-91`).
4. La nueva cantidad se limita a la disponibilidad declarada (`:93`). Si no cambia, avisa que ya tienes todo (`:94-97`).
5. Se guarda solo `{id: cantidad}` en `localStorage` (`:98-99`; `guardar` está en `:36`).
6. Se actualiza el contador (`:53`) y se muestra un mensaje (`avisar`, `:63`).
7. En `/carrito`, el JS pide datos vigentes a `/api/carrito` (`:216`) y elimina productos agotados o retirados con un aviso (`:224-236`).

## Cierre

### Buenas prácticas visibles aquí

1. Una sola puerta de entrada reduce los sitios donde olvidar una validación (`api/index.php:5`).
2. Al servidor solo se le aceptan hasta 50 ids (`api/index.php:26`).
3. El precio nunca se confía al navegador: se vuelve a leer del servidor (`assets/js/carrito.js:216`).

### Pregunta de comprobación

Si un producto se agota mientras está en tu carrito, ¿qué ve la persona y quién se lo avisa? (Respuesta abajo.)

### Ejercicio

Cambia el límite de 50 productos distintos a 30. ¿En cuántos archivos tuviste que editar? (Pista: `api/index.php:26` y `assets/js/carrito.js:8`.) Anota qué problema te sugiere eso.

### Respuesta

El JS lo elimina del carrito al abrir `/carrito` y muestra un aviso (`assets/js/carrito.js:224-236`). El número 50 está repetido en PHP y JS sin vínculo entre ambos: es una deuda anotada en [deuda-tecnica.md](../senior/deuda-tecnica.md).
