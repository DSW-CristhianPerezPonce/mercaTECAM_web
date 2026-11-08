<section class="carrito" data-carrito>
    <h1>Carrito de compras</h1>

    <noscript>
        <p class="vacio">Para usar el carrito necesitas activar JavaScript en tu navegador.</p>
    </noscript>

    <p class="carrito__cargando" data-carrito-estado>Cargando tu carrito…</p>

    <div class="vacio" data-carrito-vacio hidden>
        <p class="vacio__titulo">Tu carrito está vacío.</p>
        <p><a href="/">Explora el catálogo</a> y agrega productos agroecológicos.</p>
    </div>

    <div class="carrito__contenido" data-carrito-contenido hidden>
        <ul class="carrito__lista" role="list" data-carrito-lista></ul>

        <aside class="carrito__resumen" aria-labelledby="titulo-resumen">
            <h2 id="titulo-resumen">Resumen</h2>
            <dl>
                <div><dt>Productos</dt><dd data-carrito-unidades>0</dd></div>
                <div class="carrito__total"><dt>Total de referencia</dt><dd data-carrito-total aria-live="polite">$ 0</dd></div>
            </dl>
            <p class="carrito__nota">Los precios son de referencia y los confirma cada productor.</p>
            <button type="button" class="boton-secundario" data-carrito-vaciar>Vaciar carrito</button>
            <p><a href="/">← Seguir comprando</a></p>
        </aside>
    </div>
</section>

<template id="plantilla-item-carrito">
    <li class="item-carrito">
        <img class="item-carrito__imagen" alt="" width="96" height="72" loading="lazy"
             onerror="this.onerror=null;this.src='<?= IMAGEN_POR_DEFECTO ?>'">
        <div class="item-carrito__info">
            <a class="item-carrito__nombre" href="#"></a>
            <p class="item-carrito__detalle"></p>
            <p class="item-carrito__aviso" hidden></p>
        </div>
        <div class="cantidad" role="group">
            <button type="button" class="cantidad__boton" data-accion="bajar" aria-label="Disminuir cantidad">−</button>
            <input class="cantidad__campo" type="number" min="1" step="1" inputmode="numeric" data-accion="fijar">
            <button type="button" class="cantidad__boton" data-accion="subir" aria-label="Aumentar cantidad">+</button>
        </div>
        <p class="item-carrito__subtotal"></p>
        <button type="button" class="item-carrito__quitar" data-accion="quitar">Quitar</button>
    </li>
</template>
