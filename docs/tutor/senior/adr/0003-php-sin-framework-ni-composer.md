# ADR-0003: PHP 8 puro, sin framework ni Composer

**Estado**: Aceptada

## Contexto

- Equipo pequeño con foco en entregar historias de usuario de un tablero Scrum.
- Despliegue en Vercel con el runtime comunitario `vercel-php@0.7.4` (PHP 8.3), también usado en local.
- El código se carga con `require_once` explícitos (`src/bootstrap.php:23-25`).

## Decisión

PHP 8 sin framework ni gestor de dependencias, con PDO y PostgreSQL. Se compensa con disciplina: `strict_types` en cada archivo de `src/` (línea 2), clases `final` (`src/Database.php:4`, `src/ProductRepository.php:4`), consultas preparadas (`src/Database.php:54-56`) y escape con `e()` (`src/helpers.php:7`).

## Alternativas consideradas

| Alternativa | Pros | Contras |
|---|---|---|
| Laravel o Symfony | Router, validación, ORM, tests integrados | Más peso y curva; adaptación al runtime de Vercel por confirmar |
| Slim u otro micro-framework | Router y middleware | Requiere Composer y carpeta `vendor` en el despliegue |
| Composer solo para autoload | Sin `require` manuales | Introduce proceso de build |

## Consecuencias

- **Positivas**: cero dependencias de terceros que actualizar; arranque ligero; el equipo entiende todo el código.
- **Negativas**: sin router, sin validación ni plantillas del framework (`extract` en `src/helpers.php:51`); sin herramientas de test estándar; todo hay que escribirlo (parser de `.env`, `src/bootstrap.php:7-21`).
- **Riesgos**: reinventar utilidades con errores sutiles; el coste crece con el tamaño. Punto de revisión sugerido: cuando se agregue autenticación o pedidos (criterio del autor).
