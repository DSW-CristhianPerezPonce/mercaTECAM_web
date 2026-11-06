<section class="catalogo">
    <div class="catalogo__cabecera">
        <h1>Catálogo de productos</h1>
        <p><?= count($productos) ?> productos de nuestros productores</p>
    </div>

    <?php if ($productos === []): ?>
        <div class="vacio">
            <p>Aún no hay productos publicados.</p>
        </div>
    <?php else: ?>
        <ul class="cuadricula" role="list">
            <?php foreach ($productos as $producto): ?>
                <li><?= render('partials/product-card', ['p' => $producto]) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
