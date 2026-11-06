<?php
declare(strict_types=1);

/**
 * Importa productos desde un CSV (exportado de Excel) a PostgreSQL y copia sus fotos.
 *
 * Uso:  php scripts/importar_productos.php [data/productos.csv] [data/fotos]
 *
 * Columnas (separador ; o ,):
 *   nombre;categoria;precio_referencia;unidad;disponibilidad;productor;municipio;asociacion;imagen;descripcion
 *   - categoria: procesados | verduras | hortalizas | artesanias
 *   - imagen: nombre del archivo dentro de la carpeta de fotos (p. ej. tomate.jpg) o una URL https
 *
 * Es idempotente: si el producto (nombre + productor) ya existe, lo actualiza.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

const ANCHO_MAX = 800;          // px; las fotos más grandes se reducen (requiere extensión GD)
const CALIDAD_JPG = 80;

$csvPath   = $argv[1] ?? ROOT_DIR . '/data/productos.csv';
$fotosDir  = rtrim($argv[2] ?? ROOT_DIR . '/data/fotos', '/\\');
$destDir   = ROOT_DIR . '/assets/productos';

if (!is_file($csvPath)) {
    fwrite(STDERR, "No se encontró el CSV: $csvPath\n");
    exit(1);
}

$db = Database::connection();
$categorias = $db->query('SELECT slug, id FROM categorias')->fetchAll(PDO::FETCH_KEY_PAIR);

[$header, $rows] = leerCsv($csvPath);
$requeridas = ['nombre', 'categoria', 'precio_referencia', 'unidad', 'disponibilidad', 'productor'];
foreach ($requeridas as $col) {
    if (!in_array($col, $header, true)) {
        fwrite(STDERR, "Falta la columna obligatoria '$col'. Columnas encontradas: " . implode(', ', $header) . "\n");
        exit(1);
    }
}

$upsertProductor = $db->prepare(<<<SQL
    INSERT INTO productores (nombre, municipio, asociacion) VALUES (:nombre, :municipio, :asociacion)
    ON CONFLICT (nombre) DO UPDATE SET municipio = EXCLUDED.municipio, asociacion = EXCLUDED.asociacion
    RETURNING id
    SQL);

$upsertProducto = $db->prepare(<<<SQL
    INSERT INTO productos (nombre, descripcion, imagen, precio_referencia, unidad, disponibilidad, categoria_id, productor_id)
    VALUES (:nombre, :descripcion, :imagen, :precio, :unidad, :disponibilidad, :categoria_id, :productor_id)
    ON CONFLICT (nombre, productor_id) DO UPDATE SET
        descripcion = EXCLUDED.descripcion,
        imagen = EXCLUDED.imagen,
        precio_referencia = EXCLUDED.precio_referencia,
        unidad = EXCLUDED.unidad,
        disponibilidad = EXCLUDED.disponibilidad,
        categoria_id = EXCLUDED.categoria_id,
        activo = TRUE
    SQL);

if (!is_dir($destDir)) {
    mkdir($destDir, 0775, true);
}

$ok = 0;
$errores = [];
$db->beginTransaction();

foreach ($rows as $i => $fila) {
    $linea = $i + 2; // +1 encabezado, +1 base 1
    $r = array_combine($header, array_pad($fila, count($header), ''));
    $r = array_map('trim', $r);

    $slugCategoria = normalizar($r['categoria']);
    if (!isset($categorias[$slugCategoria])) {
        $errores[] = "Línea $linea: categoría '{$r['categoria']}' no válida (use: " . implode(', ', array_keys($categorias)) . ')';
        continue;
    }
    $precio = parsearNumero($r['precio_referencia']);
    if ($r['nombre'] === '' || $precio === null || $r['unidad'] === '' || $r['productor'] === '') {
        $errores[] = "Línea $linea: nombre, precio_referencia, unidad y productor son obligatorios.";
        continue;
    }

    $upsertProductor->execute([
        'nombre'     => $r['productor'],
        'municipio'  => $r['municipio'] ?? null ?: null,
        'asociacion' => $r['asociacion'] ?? null ?: null,
    ]);
    $productorId = (int) $upsertProductor->fetchColumn();

    $imagen = prepararImagen($r['imagen'] ?? '', $r['nombre'], $fotosDir, $destDir, $linea, $errores);

    $upsertProducto->execute([
        'nombre'         => $r['nombre'],
        'descripcion'    => ($r['descripcion'] ?? '') ?: null,
        'imagen'         => $imagen,
        'precio'         => $precio,
        'unidad'         => $r['unidad'],
        'disponibilidad' => max(0, (int) parsearNumero($r['disponibilidad'] ?: '0')),
        'categoria_id'   => $categorias[$slugCategoria],
        'productor_id'   => $productorId,
    ]);
    $ok++;
}

$db->commit();

echo "Productos importados/actualizados: $ok\n";
foreach ($errores as $err) {
    echo "  ⚠ $err\n";
}
exit($errores === [] ? 0 : 2);

// ---------------------------------------------------------------------------

function leerCsv(string $path): array
{
    $contenido = file_get_contents($path);
    $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);          // BOM UTF-8
    if (!mb_check_encoding($contenido, 'UTF-8')) {                          // Excel en Windows-1252
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
    }
    $primeraLinea = strtok($contenido, "\n");
    $sep = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';

    $fh = fopen('php://memory', 'r+');
    fwrite($fh, $contenido);
    rewind($fh);

    $header = array_map(fn ($h) => normalizar($h), fgetcsv($fh, null, $sep, '"', ''));
    $rows = [];
    while (($fila = fgetcsv($fh, null, $sep, '"', '')) !== false) {
        if (count(array_filter($fila, fn ($v) => trim((string) $v) !== '')) > 0) {
            $rows[] = $fila;
        }
    }
    fclose($fh);
    return [$header, $rows];
}

/** minúsculas, sin tildes, espacios a guion bajo: "Artesanías" -> "artesanias" */
function normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return preg_replace('/\s+/', '_', $texto);
}

function slug(string $texto): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', normalizar($texto)), '-');
}

/**
 * Acepta "12.500", "$12,500", "12500", "12.500,50", "12,500.50", "3,5".
 * Con ambos separadores, el último es el decimal; con uno solo, si agrupa de a 3 dígitos es de miles.
 */
function parsearNumero(string $valor): ?float
{
    $limpio = preg_replace('/[^\d,.\-]/', '', $valor);
    if ($limpio === '') {
        return null;
    }
    $tienePunto = str_contains($limpio, '.');
    $tieneComa  = str_contains($limpio, ',');

    if ($tienePunto && $tieneComa) {
        $decimal = strrpos($limpio, ',') > strrpos($limpio, '.') ? ',' : '.';
        $miles   = $decimal === ',' ? '.' : ',';
        $limpio  = str_replace([$miles, $decimal], ['', '.'], $limpio);
    } elseif ($tienePunto || $tieneComa) {
        $sep = $tienePunto ? '.' : ',';
        $limpio = preg_match('/^-?\d{1,3}(' . preg_quote($sep, '/') . '\d{3})+$/', $limpio)
            ? str_replace($sep, '', $limpio)   // separador de miles
            : str_replace(',', '.', $limpio);  // separador decimal
    }
    return is_numeric($limpio) ? (float) $limpio : null;
}

/** Las fotos de celular guardan la rotación en EXIF; GD la ignora, así que se aplica aquí. */
function corregirOrientacion(GdImage $img, string $ruta): GdImage
{
    if (!function_exists('exif_read_data')) {
        return $img;
    }
    $exif = @exif_read_data($ruta);
    $rotar = match ((int) ($exif['Orientation'] ?? 1)) {
        3 => 180,
        6 => -90,
        8 => 90,
        default => 0,
    };
    return $rotar === 0 ? $img : imagerotate($img, $rotar, 0);
}

/** Copia (y reduce si es grande) la foto a /assets/productos. Devuelve la ruta pública o null. */
function prepararImagen(string $imagen, string $nombre, string $fotosDir, string $destDir, int $linea, array &$errores): ?string
{
    if ($imagen === '') {
        $errores[] = "Línea $linea: '$nombre' no tiene foto; se mostrará la imagen por defecto.";
        return null;
    }
    if (preg_match('#^https?://#', $imagen)) {
        return $imagen;
    }

    $origen = $fotosDir . DIRECTORY_SEPARATOR . $imagen;
    if (!is_file($origen)) {
        $errores[] = "Línea $linea: no se encontró la foto '$imagen' en $fotosDir.";
        return null;
    }

    $ext = strtolower(pathinfo($origen, PATHINFO_EXTENSION));
    $base = slug($nombre);

    if (extension_loaded('gd') && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $src = match ($ext) {
            'png'  => imagecreatefrompng($origen),
            'webp' => imagecreatefromwebp($origen),
            default => imagecreatefromjpeg($origen),
        };
        if ($src !== false) {
            $src = corregirOrientacion($src, $origen);
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w > ANCHO_MAX) {
                $nh = (int) round($h * ANCHO_MAX / $w);
                $dst = imagecreatetruecolor(ANCHO_MAX, $nh);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, ANCHO_MAX, $nh, $w, $h);
                $src = $dst;
            }
            imagejpeg($src, "$destDir/$base.jpg", CALIDAD_JPG);
            return "/assets/productos/$base.jpg";
        }
    }

    // Sin GD: copia tal cual y avisa si pesa mucho.
    copy($origen, "$destDir/$base.$ext");
    if (filesize($origen) > 300 * 1024) {
        $errores[] = "Línea $linea: la foto '$imagen' pesa más de 300 KB; active la extensión GD para optimizarla.";
    }
    return "/assets/productos/$base.$ext";
}
