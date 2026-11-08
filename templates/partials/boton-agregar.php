<?php
// HU-04: botón «Agregar al carrito». Lo activa assets/js/carrito.js (sin JS queda deshabilitado).
// $cantidadDesde (opcional): selector de un campo de cantidad cuyo valor se agrega.
$agotado = $p['disponibilidad'] <= 0;
?>
<button type="button" class="boton-agregar"
        data-agregar="<?= (int) $p['id'] ?>"
        data-max="<?= (int) $p['disponibilidad'] ?>"
        data-nombre="<?= e($p['nombre']) ?>"
        <?= isset($cantidadDesde) ? 'data-cantidad-desde="' . e($cantidadDesde) . '"' : '' ?>
        disabled
        <?= $agotado ? 'data-agotado' : '' ?>>
    <?= $agotado ? 'Agotado' : 'Agregar al carrito' ?>
</button>
