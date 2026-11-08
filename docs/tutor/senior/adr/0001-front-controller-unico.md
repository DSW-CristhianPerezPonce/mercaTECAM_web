# ADR-0001: Front controller único

**Estado**: Aceptada

## Contexto

- Vercel con `vercel-php` expone funciones PHP; el equipo quiere una sola (`vercel.json`, bloque `functions`).
- Hay que servir archivos estáticos sin pasar por PHP y que lo demás llegue a la aplicación (`vercel.json`, `routes`).
- En local se necesita el mismo comportamiento con `php -S` (`router.php:7-11`).

## Decisión

`api/index.php` es el único punto de entrada; las rutas son ramas `if` por método y ruta (`api/index.php:15`, `:25`, `:35`, `:43`, `:58`), con un `try/catch Throwable` global (`api/index.php:84-94`) y un 404 final (~`:76`).

## Alternativas consideradas

| Alternativa | Pros | Contras |
|---|---|---|
| Un archivo PHP por página | Simple de leer | Varias funciones serverless, arranque repetido, `bootstrap` por archivo |
| Micro-framework con router | Rutas declarativas, middleware | Requiere Composer (ver [ADR-0003](0003-php-sin-framework-ni-composer.md)) |
| Tabla de rutas propia | Cumple Open/Closed | Código extra hoy innecesario |

## Consecuencias

- **Positivas**: un único lugar para errores y arranque; paridad local y producción.
- **Negativas**: cada ruta añade un `if`; el `catch` muestra "No pudimos cargar el catálogo" para cualquier ruta que falle (`api/index.php:93`); se instancia el repositorio 4 veces.
- **Riesgos**: crecimiento descontrolado de `api/index.php` (92 líneas hoy). Umbral sugerido para pasar a tabla de rutas: unas 8 a 10 ramas (criterio del autor, no medido).
