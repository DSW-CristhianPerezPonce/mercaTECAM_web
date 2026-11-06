-- mercaTECAM · Modelo de datos (HU-01, HU-02)
-- Aplicar con:  php scripts/migrar.php   (idempotente)

-- HU-02: búsqueda por nombre sin importar tildes ("limon" encuentra "Limón")
CREATE EXTENSION IF NOT EXISTS unaccent;

CREATE TABLE IF NOT EXISTS categorias (
    id      SERIAL PRIMARY KEY,
    slug    VARCHAR(40)  NOT NULL UNIQUE,
    nombre  VARCHAR(80)  NOT NULL
);

CREATE TABLE IF NOT EXISTS productores (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL UNIQUE,
    municipio   VARCHAR(100),
    asociacion  VARCHAR(150)
);

CREATE TABLE IF NOT EXISTS productos (
    id                 SERIAL PRIMARY KEY,
    nombre             VARCHAR(150)  NOT NULL,
    descripcion        TEXT,
    imagen             VARCHAR(255),                       -- ruta relativa, p. ej. /assets/productos/tomate.jpg
    precio_referencia  NUMERIC(12,2) NOT NULL CHECK (precio_referencia >= 0),  -- en COP
    unidad             VARCHAR(30)   NOT NULL,             -- kg, libra, unidad, atado, frasco...
    disponibilidad     INTEGER       NOT NULL DEFAULT 0 CHECK (disponibilidad >= 0),
    categoria_id       INTEGER       NOT NULL REFERENCES categorias(id),
    productor_id       INTEGER       NOT NULL REFERENCES productores(id),
    activo             BOOLEAN       NOT NULL DEFAULT TRUE,
    creado_en          TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    UNIQUE (nombre, productor_id)
);

CREATE INDEX IF NOT EXISTS idx_productos_categoria ON productos(categoria_id);

-- Categorías definidas en HU-02 + frutas y aromáticas (agregadas al cargar los productos reales, 05/11/2026)
INSERT INTO categorias (slug, nombre) VALUES
    ('procesados', 'Productos procesados'),
    ('verduras',   'Verduras'),
    ('hortalizas', 'Hortalizas'),
    ('artesanias', 'Artesanías'),
    ('frutas',     'Frutas'),
    ('aromaticas', 'Aromáticas')
ON CONFLICT (slug) DO NOTHING;
