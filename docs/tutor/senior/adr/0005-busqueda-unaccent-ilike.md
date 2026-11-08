# ADR-0005: Búsqueda por nombre con `unaccent` e `ILIKE`

**Estado**: Aceptada

## Contexto

- HU-02 pide buscar por nombre; los nombres están en español con tildes.
- Neon ofrece PostgreSQL estándar con extensiones.
- El catálogo es pequeño por ahora (cantidad exacta: pendiente de confirmar).

## Decisión

Se activa la extensión `unaccent` (`database/schema.sql:5`) y se busca con `unaccent(p.nombre) ILIKE '%' || unaccent(:busqueda) || '%' ESCAPE '\'` (`src/ProductRepository.php:30`). Los comodines `%`, `_` y `\` del usuario se escapan con `addcslashes` (`src/ProductRepository.php:31`) y el valor va como parámetro. El texto se limita a 100 caracteres (`api/index.php:12`, `src/helpers.php:58`).

## Alternativas consideradas

| Alternativa | Pros | Contras |
|---|---|---|
| `LIKE` simple | Sin extensión | Distingue tildes y mayúsculas |
| Índice trigram (`pg_trgm`) | Acelera `ILIKE '%x%'` | Más configuración; hay que medir si hace falta |
| Búsqueda de texto completo | Relevancia y stemming | Complejo para nombres cortos |
| Filtrar en PHP o JS | Sin SQL especial | Trae todo el catálogo |

## Consecuencias

- **Positivas**: búsqueda tolerante a tildes y mayúsculas con una línea de SQL; resistente a inyección y a comodines accidentales.
- **Negativas**: el patrón `%x%` con `unaccent` no usa índice normal y escanea la tabla; el resultado sale ordenado por nombre, no por relevancia.
- **Riesgos**: degradación al crecer el catálogo (hipótesis: no medida). `unaccent` debe estar disponible en cada base; el esquema ya lo crea, pero un rol sin permiso podría fallar al migrar.
