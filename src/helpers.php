<?php
declare(strict_types=1);

const IMAGEN_POR_DEFECTO = '/assets/img/sin-foto.svg';

/** Escapa texto para HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Formato de precio en pesos colombianos: $ 12.500 */
function precio_cop(float $valor): string
{
    return '$ ' . number_format($valor, 0, ',', '.');
}

/** Devuelve la imagen del producto o la imagen por defecto si no existe. */
function imagen_producto(?string $ruta): string
{
    if ($ruta === null || $ruta === '') {
        return IMAGEN_POR_DEFECTO;
    }
    // Si el archivo no existe, el atributo onerror de la tarjeta muestra la imagen por defecto.
    // (En Vercel los estáticos no viven dentro de la función PHP, por eso no se usa is_file.)
    return $ruta;
}

/** Renderiza una plantilla de /templates con variables. */
function render(string $template, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require ROOT_DIR . "/templates/$template.php";
    return (string) ob_get_clean();
}

/** Lee un parámetro GET como texto limpio (sin espacios extremos, longitud máxima). Vacío → null. */
function query_param(string $nombre, int $maximo): ?string
{
    $valor = $_GET[$nombre] ?? null;
    if (!is_string($valor)) {
        return null;
    }
    $valor = trim(mb_substr($valor, 0, $maximo));
    return $valor === '' ? null : $valor;
}

/** URL del catálogo con los filtros dados (omite los vacíos). */
function url_catalogo(?string $categoria, ?string $busqueda): string
{
    $params = array_filter(['categoria' => $categoria, 'q' => $busqueda], static fn ($v) => $v !== null && $v !== '');
    return '/' . ($params ? '?' . http_build_query($params) : '');
}

function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
