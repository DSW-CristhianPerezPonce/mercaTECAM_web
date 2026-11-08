# Revisión de código limpio

## 1. Resumen y puntaje

| Fuente | Puntaje | Qué mide |
|---|---|---|
| Métricas (`../assets/metricas.json`, `puntaje_general`) | **9.3 / 10** | Complejidad, tamaño y ciclos entre módulos. |
| **Ajustado por lectura** | **8.0 / 10** | Añade lo que las métricas no ven. |

**Por qué se baja 1.3 puntos** (hechos): duplicación PHP/JS (`precio_cop` en `src/helpers.php:13` frente a `precio()` en `assets/js/carrito.js:48`); unas 105 líneas de script en ámbito global en el importador (`scripts/importar_productos.php:19-124`); estado oculto con `extract()` y parámetro por referencia `&$errores` (`src/helpers.php:51`, `scripts/importar_productos.php:216`); y ausencia de tests.

**Limitaciones de la medición** (hechos): lizard solo analizó 5 archivos; no detecta dependencias PHP por `require` (el grafo sale sin aristas, así que "sin ciclos" no es evidencia fuerte); reportó mal los parámetros de `prepararImagen` (1 en vez de 6). Cifras disponibles: 53 funciones, CCN medio 2.91, 2 funciones con CCN > 10, 2 con NLOC > 40.

![Radar de calidad](../assets/radar-calidad.png)

*El radar viene de las métricas automáticas (9.3), no del puntaje ajustado.*

## 2. Hallazgos priorizados

Formato: severidad | archivo:línea | principio | por qué importa | propuesta.

### Alta

- alta | `scripts/importar_productos.php:19-124` | SRP, testabilidad | ~105 líneas globales mezclan lectura, validación, SQL y fotos; no se puede probar sin ejecutar todo (las funciones se declaran después del `exit`) | dividir en funciones y un `main` (ver refactor 5).
- alta | `scripts/importar_productos.php:216` | SRP, parámetro de salida | `prepararImagen` tiene 6 parámetros incluido `&$errores`, CCN 11, anidamiento; si `imagecreatefrom*` devuelve false cae a `copy` sin aviso (hecho según el informe de revisión; la rama sin GD de `:255-258` sí avisa del peso de la foto) | devolver `{ruta, avisos}` (refactor 4).
- alta | `scripts/importar_productos.php:75-118` | Robustez | las fotos se escriben dentro de la transacción y no hay `try/catch` con `rollBack` visible | refactor 5.

### Media

- media | `src/ProductRepository.php:53`, `:131`, `:154` (y ~`:100`) | DRY | casteos de tipos repetidos en cada método | método privado de tipado (refactor 2).
- media | `api/index.php:16`, `:27`, `:41`, `~:57` | DRY | `new ProductRepository(Database::connection())` en 4 ramas; en `/carrito` no hace falta conexión | repositorio perezoso (refactor 1).
- media | `assets/js/carrito.js:77-104` | SRP | el manejador de clic lee DOM, valida, calcula, guarda y avisa (CCN 10) | función pura `calcularAgregado` (refactor 3).
- media | `assets/js/carrito.js:139-187`, `:109` | Tamaño | `pintar` ~49 líneas e `iniciarPaginaCarrito` ~130 líneas | extraer funciones de render.
- media | `src/helpers.php:49-51` | Estado oculto | `extract()` deja la plantilla ver `$template` y `$vars` | pasar un array explícito o documentar variables esperadas en cada plantilla.
- media | `api/index.php:84-91` | Cohesión | HTML de error embebido en el controlador; el mensaje "No pudimos cargar el catálogo" aparece aunque falle otra ruta | plantilla de error con mensaje genérico.
- media | `src/helpers.php:13` frente a `assets/js/carrito.js:48` | DRY entre lenguajes | formatear COP en PHP y en JS (`Intl es-CO`) puede redondear distinto (hipótesis) | prueba de contraste con valores límite; si no, devolver el texto formateado desde el servidor.
- media | `src/Database.php:12` | SRP | `connection()` con ~41 NLOC y CCN 7 mezcla parseo de URL, opciones de Neon y PDO | extraer `dsnDesdeUrl()` (`:44-47` es el bloque de Neon).

### Baja

- baja | `api/index.php:26` y `assets/js/carrito.js:8` | Números mágicos | el 50 está repetido sin vínculo | documentar y probar que ambos coinciden, o exponerlo desde `/api/carrito`.
- baja | `src/ProductRepository.php:69`, `:115` | Números mágicos | `2147483647` duplicado | constante `ID_MAXIMO`.
- baja | `scripts/importar_productos.php:95-96` | Claridad | `?? null ?: null` es difícil de leer y trata "0" como vacío | `vacioANulo()` (refactor 6).
- baja | `scripts/importar_productos.php:177` | Heurística sin tests | `parsearNumero` decide qué es "1.500" (miles o decimal) | tests de tabla.
- baja | `scripts/importar_productos.php:257` | Número mágico | `300*1024` | constante nombrada.
- baja | `router.php:7` | Defensivo | `$_SERVER['REQUEST_URI']` sin `?? '/'`, a diferencia de `api/index.php:7` | añadir el valor por defecto.
- baja | varios | Convención | mezcla de snake_case (helpers) y camelCase (métodos) | decidir y documentar.
- baja | plantillas de catálogo, ficha y carrito | DRY | la regla "agotado" se repite en 3 plantillas | un helper o parcial.

## 3. Complejidad: tres casos críticos

![Complejidad por función](../assets/complejidad.png)

*Gráfica generada por lizard sobre 5 archivos; lo que no se midió no está en la gráfica.*

1. **`parsearNumero`** (`scripts/importar_productos.php:177`, CCN 12): complejidad esencial, no accidental, porque debe interpretar formatos colombianos de números ("12.500", "$12,500", "12.500,50", "3,5"). Está documentada, pero sin tests no se puede refactorizar con confianza. Prioridad: añadir una tabla de casos antes de tocarla.
2. **`prepararImagen`** (`scripts/importar_productos.php:216`, CCN 11): cascada de responsabilidades (URL o archivo local, decodificar, orientar, redimensionar, comprimir, escribir). Hay un bug latente: si la decodificación devuelve false, se copia el archivo sin avisar.
3. **Manejador de clic** (`assets/js/carrito.js:77-104`, CCN 10): cuatro responsabilidades en una función anónima. Es el mejor candidato a función pura porque sus reglas (tope de 50, tope de disponibilidad) son lo más probable que se rompa.

## 4. Lo que está bien hecho

- **Seguridad básica sólida**: consultas preparadas, escape de LIKE, `e()` en salida (`src/ProductRepository.php:30-31`, `src/helpers.php:7`, `src/Database.php:54-56`).
- **Entradas defendidas**: id validado con regex y rango (`api/index.php:~58`, `src/ProductRepository.php:69`), `query_param` con límite de longitud (`src/helpers.php:58`).
- **Carrito prudente**: guarda solo `{id: cantidad}`, fallback en memoria si el almacenamiento está bloqueado, sincroniza entre pestañas (`assets/js/carrito.js:16`, `:36`, `:244`).
- **Disciplina de tipos**: `strict_types` y clases `final` (`src/Database.php:4`, `src/ProductRepository.php:4`, `declare` en la línea 2 de cada archivo de `src/`).
- **Importador operable**: idempotente con `ON CONFLICT`, tolera BOM y Windows-1252, y usa códigos de salida 0/1/2.
- **Progressive enhancement** (`templates/partials/boton-agregar.php:11`).
