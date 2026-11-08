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
        </div>
    </header>

    <main class="contenedor">
        <?= $contenido ?>
    </main>

    <footer class="pie">
        <div class="contenedor">mercaTECAM · E-commerce agroecológico</div>
    </footer>
</body>
</html>
