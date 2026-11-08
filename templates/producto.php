<?php
$agotado = $p['disponibilidad'] <= 0;
$secciones = [
    'Especificaciones técnicas' => lineas($p['especificaciones']),
    'Prácticas agroecológicas'  => lineas($p['practicas_agroecologicas']),
    'Valor agregado'            => lineas($p['valor_agregado']),
];
?>
<article class="ficha">
    <nav class="migas" aria-label="Ruta de navegación">
        <a href="/">Catálogo</a>
        <span aria-hidden="true">›</span>
        <a href="<?= e(url_catalogo($p['categoria_slug'], null)) ?>"><?= e($p['categoria']) ?></a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= e($p['nombre']) ?></span>
    </nav>

    <div class="ficha__principal">
        <div class="ficha__imagen<?= $agotado ? ' ficha__imagen--agotado' : '' ?>">
            <img src="<?= e(imagen_producto($p['imagen'])) ?>"
                 alt="<?= e($p['nombre']) ?>"
                 width="800" height="600" decoding="async"
                 onerror="this.onerror=null;this.src='<?= IMAGEN_POR_DEFECTO ?>'">
            <?php if ($agotado): ?><span class="sello-agotado">Agotado</span><?php endif; ?>
        </div>

        <div class="ficha__resumen">
            <span class="etiqueta etiqueta--estatica etiqueta--<?= e($p['categoria_slug']) ?>"><?= e($p['categoria']) ?></span>
            <h1 class="ficha__nombre"><?= e($p['nombre']) ?></h1>

            <dl class="ficha__datos">
                <div>
                    <dt>Precio de referencia</dt>
                    <dd class="ficha__precio"><?= precio_cop($p['precio_referencia']) ?></dd>
                </div>
                <div>
                    <dt>Unidad de venta</dt>
                    <dd><?= e($p['unidad']) ?></dd>
                </div>
                <div>
                    <dt>Disponibilidad declarada</dt>
                    <dd>
                        <span class="disponibilidad<?= $agotado ? ' disponibilidad--agotado' : '' ?>">
                            <?= e(texto_disponibilidad($p['disponibilidad'])) ?>
                        </span>
                    </dd>
                </div>
            </dl>

            <?php if ($p['descripcion']): ?>
                <p class="ficha__descripcion"><?= e($p['descripcion']) ?></p>
            <?php endif; ?>

            <section class="productor" aria-labelledby="titulo-productor">
                <h2 id="titulo-productor">Productor</h2>
                <p class="productor__nombre"><?= e($p['productor']) ?></p>
                <?php if ($p['municipio'] || $p['asociacion']): ?>
                    <p class="productor__detalle">
                        <?= e(implode(' · ', array_filter([$p['municipio'], $p['asociacion']]))) ?>
                    </p>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <div class="ficha__secciones">
        <?php foreach ($secciones as $titulo => $items): ?>
            <section class="ficha__seccion">
                <h2><?= e($titulo) ?></h2>
                <?php if ($items === []): ?>
                    <p class="pendiente">Información pendiente del productor.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($items as $item): ?>
                            <li><?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>

    <p class="ficha__volver"><a href="/">← Volver al catálogo</a></p>
</article>
