<article class="tarjeta">
    <div class="tarjeta__imagen">
        <img src="<?= e(imagen_producto($p['imagen'])) ?>"
             alt="<?= e($p['nombre']) ?>"
             width="400" height="300"
             loading="lazy" decoding="async"
             onerror="this.onerror=null;this.src='<?= IMAGEN_POR_DEFECTO ?>'">
        <span class="etiqueta etiqueta--<?= e($p['categoria_slug']) ?>"><?= e($p['categoria']) ?></span>
    </div>
    <div class="tarjeta__cuerpo">
        <h2 class="tarjeta__nombre"><?= e($p['nombre']) ?></h2>
        <p class="tarjeta__productor"><?= e($p['productor']) ?><?= $p['municipio'] ? ' · ' . e($p['municipio']) : '' ?></p>
        <p class="tarjeta__precio">
            <?= precio_cop($p['precio_referencia']) ?>
            <span>/ <?= e($p['unidad']) ?></span>
        </p>
        <small class="tarjeta__nota">Precio de referencia</small>
    </div>
</article>
