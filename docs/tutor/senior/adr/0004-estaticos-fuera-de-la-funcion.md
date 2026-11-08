# ADR-0004: Archivos estáticos fuera de la función PHP

**Estado**: Aceptada

## Contexto

- En Vercel, los estáticos los sirve la capa de archivos; no están dentro del paquete de la función PHP.
- Las fotos de productos son parte del contenido y se versionan en git.
- `vercel.json` sirve primero el sistema de archivos y reescribe el resto (`routes`).

## Decisión

Los estáticos viven bajo `/assets/` (fotos en `assets/productos/`) y nunca se consultan desde PHP en tiempo de ejecución. No se usa `is_file` sobre assets en producción; en local, `router.php:7-11` solo lo usa para decidir si deja pasar un archivo. Las imágenes faltantes caen a `assets/img/sin-foto.svg` por `onerror` (`templates/partials/product-card.php:9`) y el helper `imagen_producto()` arma la ruta (`src/helpers.php:19`).

## Alternativas consideradas

| Alternativa | Pros | Contras |
|---|---|---|
| Comprobar existencia con `is_file` | Mostrar el fallback desde el servidor | Falla en Vercel: los archivos no están en la función |
| Almacenamiento de objetos (S3, Blob) | Fotos fuera del repo | Más infraestructura y credenciales |
| Servir fotos desde PHP | Control total | Gasta invocaciones y tiempo de la función |

## Consecuencias

- **Positivas**: simple, económico; la CDN sirve las fotos; el fallback funciona sin servidor.
- **Negativas**: las fotos binarias crecen el repositorio; el importador copia fotos con rutas locales (`scripts/importar_productos.php:216`), por lo que, hipótesis, importar contra producción no publica fotos sin un nuevo deploy.
- **Riesgos**: **hipótesis a verificar**: con `handle: filesystem` podrían quedar expuestos como estáticos otros archivos del repositorio (por ejemplo `/src` o `/data`); comprobarlo en el despliegue. Hay un riesgo de rutas rotas si la foto no se versiona.
