<?php
$total = count($productos);
$hayFiltros = $categoria !== null || $busqueda !== null;
$nombreCategoria = null;
foreach ($categorias as $c) {
    if ($c['slug'] === $categoria) {
        $nombreCategoria = $c['nombre'];
    }
}
?>
<section class="catalogo">
    <div class="catalogo__cabecera">
        <h1>Catálogo de productos</h1>
    </div>

    <!-- HU-02: búsqueda por nombre (conserva la categoría elegida) -->
    <form class="buscador" action="/" method="get" role="search">
        <?php if ($categoria !== null): ?>
            <input type="hidden" name="categoria" value="<?= e($categoria) ?>">
        <?php endif; ?>
        <label class="visualmente-oculto" for="q">Buscar productos por nombre</label>
        <input class="buscador__campo" type="search" id="q" name="q"
               value="<?= e($busqueda) ?>" placeholder="Buscar por nombre: fresa, café, limón…"
               maxlength="100" autocomplete="off">
        <button class="buscador__boton" type="submit">Buscar</button>
    </form>

    <!-- HU-02: filtros por categoría (conservan la búsqueda escrita) -->
    <nav class="filtros" aria-label="Filtrar por categoría">
        <a class="filtro<?= $categoria === null ? ' filtro--activo' : '' ?>"
           href="<?= e(url_catalogo(null, $busqueda)) ?>"
           <?= $categoria === null ? 'aria-current="page"' : '' ?>>Todas</a>
        <?php foreach ($categorias as $c): ?>
            <?php $activo = $c['slug'] === $categoria; ?>
            <a class="filtro<?= $activo ? ' filtro--activo' : '' ?>"
               href="<?= e(url_catalogo($c['slug'], $busqueda)) ?>"
               <?= $activo ? 'aria-current="page"' : '' ?>>
                <?= e($c['nombre']) ?> <span class="filtro__total"><?= $c['total'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <p class="catalogo__resumen" aria-live="polite">
        <?= $total ?> <?= $total === 1 ? 'producto' : 'productos' ?>
        <?php if ($nombreCategoria !== null): ?> en <strong><?= e($nombreCategoria) ?></strong><?php endif; ?>
        <?php if ($busqueda !== null): ?> que coinciden con «<strong><?= e($busqueda) ?></strong>»<?php endif; ?>
        <?php if ($hayFiltros): ?> · <a href="/">Quitar filtros</a><?php endif; ?>
    </p>

    <?php if ($total === 0): ?>
        <div class="vacio">
            <?php if ($hayFiltros): ?>
                <p class="vacio__titulo">No encontramos productos con esos filtros.</p>
                <p>Revisa cómo escribiste el nombre, prueba con otra categoría o
                    <a href="/">mira todo el catálogo</a>.</p>
            <?php else: ?>
                <p class="vacio__titulo">Aún no hay productos publicados.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <ul class="cuadricula" role="list">
            <?php foreach ($productos as $producto): ?>
                <li><?= render('partials/product-card', ['p' => $producto]) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
