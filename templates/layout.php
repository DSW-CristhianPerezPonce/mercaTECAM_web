<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · mercaTECAM</title>
    <meta name="description" content="<?= e($descripcion ?? 'Productos agroecológicos directo del campesino y sus asociaciones.') ?>">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <header class="encabezado">
        <div class="contenedor encabezado__fila">
            <a class="marca" href="/">merca<span>TECAM</span></a>
            <p class="encabezado__lema">Del campo agroecológico a tu mesa</p>
            <a class="carrito-enlace" href="/carrito">
                <span aria-hidden="true">🧺</span> Carrito
                <span class="carrito-enlace__contador" data-carrito-contador aria-label="productos en el carrito">0</span>
            </a>
        </div>
    </header>

    <main class="contenedor">
        <?= $contenido ?>
    </main>

    <footer class="pie">
        <div class="contenedor">mercaTECAM · E-commerce agroecológico</div>
    </footer>
    <div class="aviso-carrito" role="status" aria-live="polite" data-carrito-aviso hidden></div>
    <script src="/assets/js/carrito.js" defer></script>
</body>
</html>
