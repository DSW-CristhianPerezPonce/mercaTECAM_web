# ADR-0002: Carrito en el navegador

**Estado**: Aceptada

## Contexto

- No hay usuarios, sesiones ni pedidos en el alcance de HU-01 a HU-04.
- La función en Vercel no tiene estado entre peticiones.
- El navegador puede bloquear el almacenamiento.

## Decisión

El carrito vive en `localStorage` bajo la clave `mercatecam.carrito.v1`, guardando solo `{id: cantidad}` y con máximo 50 productos (`assets/js/carrito.js:7-8`, `:36`). Hay fallback en memoria (`:16`). Nombres, precios y disponibilidad se releen de `GET /api/carrito?ids=` (`:278`; `api/index.php:25-33`, que solo acepta ids enteros completos), las cantidades se recortan a la disponibilidad y los productos agotados o retirados se descartan con aviso (`:288-302`). Al llegar la respuesta se vuelve a leer el carrito guardado, porque pudo cambiar en otra pestaña mientras tanto (`:286`).

**Actualización (commit `5593565`)**: la página `/carrito` se mantiene al día con la fuente de verdad local. El evento `storage` (`:356`) sincroniza lista y total, no solo el contador, y pide al servidor los productos que aún no conoce. El evento `pageshow` (`:364`) recarga los datos cuando el navegador restaura la página desde el `bfcache`. Las filas se actualizan en su lugar (`:250`) para no perder el foco del teclado.

## Alternativas consideradas

| Alternativa | Pros | Contras |
|---|---|---|
| Carrito en sesión PHP | Reglas en servidor | Requiere cookie y almacenamiento de sesión; en serverless exigiría base o Redis |
| Carrito en tabla de la base | Persistente y multi-dispositivo | Exige identidad o token anónimo, más escrituras |
| Guardar precios en el cliente | Menos peticiones | Precios obsoletos, manipulables |

## Consecuencias

- **Positivas**: sin estado en servidor, sin login, funciona con poca infraestructura; los datos sensibles (precio) siempre vienen del servidor.
- **Negativas**: las invariantes (tope, total) solo existen en JS (`assets/js/carrito.js:80-107`, `:140-158`); el carrito no sigue a la persona entre dispositivos.
- **Riesgos**: al construir el checkout, el servidor debe revalidar cantidades, disponibilidad y precio; el carrito del cliente no es de fiar. El 50 está duplicado en `api/index.php:29` y en `assets/js/carrito.js:8`.
