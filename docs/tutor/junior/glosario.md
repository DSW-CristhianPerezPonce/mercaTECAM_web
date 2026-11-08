# Glosario

## Términos del negocio

| Término | Definición en una línea | Dónde aparece |
|---|---|---|
| Producto | Artículo que un productor ofrece en el catálogo. | `database/schema.sql:32` (UNIQUE nombre + productor) |
| Precio de referencia | Valor orientativo en COP; no es un precio vinculante (hipótesis, pendiente de confirmar con negocio). | `database/schema.sql:25`, `src/helpers.php:13` |
| Unidad | Cómo se vende: libra, frasco, unidad (en el Excel se llama "Presentación"). | columna `unidad` de `data/productos_importar.csv` |
| Disponibilidad declarada | Cuántas unidades dice tener el productor; no es inventario real. | `database/schema.sql:27` |
| Agotado | Producto con disponibilidad 0; se calcula, no se guarda. | `templates/partials/boton-agregar.php:12` |
| Activo | Producto visible en el catálogo; los demás se consideran retirados. | `src/ProductRepository.php:20` |
| Categoría y slug | Grupo del producto y su nombre para URL (por ejemplo `verduras`). Hay 6. | `database/schema.sql:9`, `:43-50` |
| Productor | Persona o finca que ofrece el producto. | `database/schema.sql:15` |
| Asociación | Organización del productor; hoy es texto libre. | columna `asociacion` de `data/productos_importar.csv` |
| Ficha técnica | Especificaciones, prácticas agroecológicas y valor agregado de un producto. | `database/schema.sql:38-40` (aprox.) |
| Carrito | Lista `{id: cantidad}` guardada en el navegador, máximo 50 productos. | `assets/js/carrito.js:7-8` |

## Términos técnicos

| Término | Definición en una línea | Dónde aparece |
|---|---|---|
| Front controller | Un único archivo recibe todas las peticiones y reparte el trabajo. | `api/index.php:5` |
| Vercel | Servicio donde se publica la aplicación. | `vercel.json` |
| vercel-php | Extensión comunitaria para correr PHP en Vercel. | `vercel.json` (runtime `vercel-php@0.7.4`) |
| Neon | Servicio de PostgreSQL en la nube. | `src/Database.php:44-47` |
| PDO | Librería de PHP para hablar con bases de datos. | `src/Database.php:54-56` |
| Consulta preparada | SQL con huecos que se llenan aparte, para evitar inyección SQL. | `src/ProductRepository.php:30-31` |
| Repositorio | Clase que concentra las consultas de un tipo de dato. | `src/ProductRepository.php:6` |
| Singleton | Un objeto que se crea una sola vez y se reutiliza. | `src/Database.php:6`, `:14-16` |
| Plantilla | Archivo HTML con PHP para mostrar datos. | `src/helpers.php:49` |
| Escapar | Convertir caracteres peligrosos en texto inofensivo antes de mostrarlos. | `src/helpers.php:7` |
| unaccent | Extensión de PostgreSQL que ignora las tildes al buscar. | `database/schema.sql:5` |
| ILIKE | Comparación de texto sin distinguir mayúsculas. | `src/ProductRepository.php:30` |
| localStorage | Pequeño almacén del navegador que sobrevive al cerrar la pestaña. | `assets/js/carrito.js:7` |
| Progressive enhancement | La página sirve sin JS y el JS añade mejoras. | `templates/layout.php:30` |
| Upsert | Insertar si no existe o actualizar si existe; permite repetir importaciones. | `scripts/importar_productos.php:75-118` (transacción de importación) |
| Migración | Script que crea o cambia tablas. | `database/schema.sql` |
| CSV | Archivo de texto con columnas separadas por `;`. | `data/productos_importar.csv` |
| CCN | Complejidad ciclomática: número de caminos posibles dentro de una función. | `../assets/metricas.json` |
