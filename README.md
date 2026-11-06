# mercaTECAM – E-commerce agroecológico

Tienda en línea que conecta a campesinos y asociaciones agroecológicas con compradores.
Tablero Scrum: https://trello.com/b/buYuwTPh/mercatecam-e-commerce-agroecol%C3%B3gico

**Stack:** PHP 8.3 (sin framework, PDO) · PostgreSQL · Despliegue en Vercel (runtime `vercel-php`) + Neon.

## Requisitos locales

- PHP 8.1+ con extensiones `pdo_pgsql`, `mbstring` y (recomendado) `gd`
- PostgreSQL 14+ (o una base en Neon)

En `php.ini` deben estar activas: `extension=pdo_pgsql`, `extension=mbstring`, `extension=gd`.

## Puesta en marcha

```bash
cp .env.example .env                      # y ajuste DATABASE_URL
php scripts/migrar.php                    # crea las tablas (no requiere psql)
php scripts/importar_productos.php data/productos_importar.csv data/fotos
php -S localhost:8000 router.php          # http://localhost:8000
```

## Cargar productos

1. Copie `data/productos_plantilla.csv` a `data/productos.csv` y llénelo (Excel → *Guardar como CSV UTF-8*).
2. Ponga las fotos en `data/fotos/` con el mismo nombre de archivo de la columna `imagen`.
3. Ejecute `php scripts/importar_productos.php`. Las fotos se copian (reducidas a 800 px si hay GD) a `assets/productos/`, que **sí** se sube al repositorio.

Categorías válidas: `procesados`, `verduras`, `hortalizas`, `artesanias`, `frutas`, `aromaticas`.

Los productos reales iniciales (Asociación La Tulpa) están en `data/productos_importar.csv`, convertido desde el inventario original `data/productos.csv`.

## Rutas

| Ruta | Descripción |
|------|-------------|
| `/` | Catálogo de productos (HU-01) |
| `/?categoria=frutas&q=fresa` | Catálogo filtrado por categoría y/o nombre (HU-02; sin distinguir mayúsculas ni tildes) |
| `/api/productos` | JSON con los productos activos; acepta los mismos `categoria` y `q` |

## Despliegue (Vercel + Neon)

1. Cree la base en Neon y ejecute `php scripts/migrar.php` y el importador apuntando a ella.
2. Importe el repositorio de GitHub en Vercel.
3. En *Settings → Environment Variables* agregue `DATABASE_URL` con la cadena de Neon (`?sslmode=require`).
