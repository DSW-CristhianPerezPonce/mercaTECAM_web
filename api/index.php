<?php
declare(strict_types=1);

// Punto de entrada único (Vercel enruta aquí todo lo que no es archivo estático).
require_once dirname(__DIR__) . '/src/bootstrap.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Filtros del catálogo (HU-02): ?categoria=<slug>&q=<texto>
$categoria = query_param('categoria', 40);
$busqueda  = query_param('q', 100);

try {
    if ($method === 'GET' && $path === '/api/productos') {
        $repo = new ProductRepository(Database::connection());
        json_response([
            'filtros' => ['categoria' => $categoria, 'q' => $busqueda],
            'data'    => $repo->listarCatalogo($categoria, $busqueda),
        ]);
        return;
    }

    // HU-04: datos vigentes de los productos del carrito (?ids=1,2,3). El carrito vive en el navegador.
    if ($method === 'GET' && $path === '/api/carrito') {
        $ids = array_slice(array_map('intval', explode(',', query_param('ids', 500) ?? '')), 0, 50);
        $repo = new ProductRepository(Database::connection());
        json_response(['data' => $repo->obtenerParaCarrito($ids)]);
        return;
    }

    if ($method === 'GET' && $path === '/carrito') {
        echo render('layout', [
            'titulo'    => 'Carrito de compras',
            'contenido' => render('carrito'),
        ]);
        return;
    }

    if ($method === 'GET' && ($path === '/' || $path === '/catalogo')) {
        $repo = new ProductRepository(Database::connection());
        echo render('layout', [
            'titulo'    => 'Catálogo de productos',
            'contenido' => render('catalog', [
                'productos'  => $repo->listarCatalogo($categoria, $busqueda),
                'categorias' => $repo->categoriasConConteo(),
                'categoria'  => $categoria,
                'busqueda'   => $busqueda,
            ]),
        ]);
        return;
    }

    // HU-03: ficha del producto (/producto/7). Ids no numéricos caen en el 404 general.
    if ($method === 'GET' && preg_match('#^/producto/([1-9]\d{0,9})$#', $path, $m)) {
        $repo = new ProductRepository(Database::connection());
        $producto = $repo->obtenerPorId((int) $m[1]);

        if ($producto === null) {
            http_response_code(404);
            echo render('layout', [
                'titulo'    => 'Producto no encontrado',
                'contenido' => render('producto-no-encontrado'),
            ]);
            return;
        }

        echo render('layout', [
            'titulo'      => $producto['nombre'],
            'descripcion' => $producto['descripcion'],
            'contenido'   => render('producto', ['p' => $producto]),
        ]);
        return;
    }

    http_response_code(404);
    echo render('layout', [
        'titulo'    => 'Página no encontrada',
        'contenido' => '<section class="vacio"><h1>Página no encontrada</h1><p><a href="/">Volver al catálogo</a></p></section>',
    ]);
} catch (Throwable $ex) {
    error_log('[mercaTECAM] ' . $ex->getMessage());
    if (str_starts_with($path, '/api/')) {
        json_response(['error' => 'Error interno del servidor'], 500);
        return;
    }
    http_response_code(500);
    echo render('layout', [
        'titulo'    => 'Error',
        'contenido' => '<section class="vacio"><h1>Tenemos un problema</h1><p>No pudimos cargar el catálogo. Intenta de nuevo en unos minutos.</p></section>',
    ]);
}
