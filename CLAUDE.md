# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

mercaTECAM: e-commerce for agroecological farmers/associations. Work is driven by a Scrum board in Trello (board `buYuwTPh`); each user story card (HU-xx) has two checklists: "Criterios de aceptación" and "Tareas técnicas". Implement exactly the technical tasks of the story in progress and verify against its acceptance criteria. UI text and code identifiers are in Spanish.

Stack decided by the team: plain PHP 8 (no framework, no Composer) + PDO + PostgreSQL, deployed on Vercel via the community `vercel-php` runtime with the database on Neon.

## Commands

```bash
php -S localhost:8000 router.php                     # local server
php scripts/migrar.php                               # apply database/schema.sql (idempotent, no psql needed)
php scripts/importar_productos.php [csv] [fotosDir]  # import products from Excel CSV (idempotent upsert)
php -l <file>                                        # syntax check (no test suite yet)
```

## Architecture

- **Single entry point**: `api/index.php` is the only PHP function on Vercel; `vercel.json` serves static files first (`handle: filesystem`) then rewrites everything else to it. `router.php` reproduces that behavior for `php -S`. New pages/endpoints are added as branches in `api/index.php`.
- `src/bootstrap.php` loads `.env` (local only; Vercel uses project env vars) and requires the rest of `src/`. `Database::connection()` parses `DATABASE_URL` (adds the Neon `endpoint` option for `ep-*` hosts).
- Data access goes through repository classes (`src/ProductRepository.php`); the HTML page and the JSON endpoint share the same query. Catalog filters come from the query string (`categoria`, `q`, read with `query_param()`); name search uses the PostgreSQL `unaccent` extension + `ILIKE` with LIKE wildcards escaped. Filter links are built with `url_catalogo()` so category and search combine. The UI works without JavaScript.
- Views: `render('template', $vars)` from `src/helpers.php` renders `templates/*.php`; always escape with `e()`. Prices are COP, formatted with `precio_cop()`.
- Static files must live under `/assets/` (product photos in `assets/productos/`, committed to git). Don't use `is_file` on assets at runtime — on Vercel they're not inside the PHP function; missing images fall back to `assets/img/sin-foto.svg` via `onerror`.
- Product page `/producto/:id` (HU-03) is matched by regex in `api/index.php`; `ProductRepository::obtenerPorId()` returns null for ids outside PostgreSQL INTEGER range (avoids a 500). `especificaciones`, `practicas_agroecologicas`, `valor_agregado` are newline-separated lists (render with `lineas()`); in the CSV they are `|`-separated. Their content must come from a real source (producer inventory, redenjambre.com) — never invent agroecological claims about real producers.
- Cart (HU-04) lives in the browser: `assets/js/carrito.js` stores only `{id: cantidad}` in `localStorage` (key `mercatecam.carrito.v1`, in-memory fallback if storage is blocked). Names/prices/stock are always re-read from `GET /api/carrito?ids=` (`ProductRepository::obtenerParaCarrito()`), quantities are capped at `disponibilidad`, and missing/sold-out items are dropped with a notice. `/carrito` is a JS-rendered shell (`templates/carrito.php`). Product cards are an `<article>` with an inner link plus `partials/boton-agregar.php` (a button can't be inside an `<a>`); the button is `disabled` until JS activates it.
- Schema: `categorias` (slugs: procesados, verduras, hortalizas, artesanias — from HU-02 — plus frutas, aromaticas added for the real catalog), `productores`, `productos` (unique on nombre+productor_id).
