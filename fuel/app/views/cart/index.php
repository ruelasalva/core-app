<style>
    .cart-band { padding: 56px 0; background: #f4f7fa; border-bottom: 1px solid #dde3ea; }
    .cart-shell { width: min(1100px, calc(100% - 32px)); margin: 0 auto; }
    .cart-title h1 { margin: 0; font-size: clamp(2rem, 4vw, 3.4rem); line-height: 1; }
    .cart-title p { margin: 12px 0 0; color: #657084; }
    .cart-panel { margin-top: 28px; border: 1px solid #dde3ea; border-radius: 8px; background: #fff; overflow: hidden; }
    .cart-flow-note { margin: 12px 20px 0; border: 1px solid #fed7aa; border-radius: 8px; background: #fff7ed; color: #9a3412; padding: 12px 14px; font-weight: 700; }
    .cart-section-heading { padding: 18px 20px 8px; border-bottom: 1px solid #edf2f7; }
    .cart-section-heading h2 { margin: 0; font-size: 1.05rem; color: #172033; }
    .cart-section-heading p { margin: 6px 0 0; color: #657084; }
    .cart-alert { margin-top: 18px; border-radius: 6px; padding: 11px 12px; }
    .cart-alert.success { background: #dcfce7; color: #166534; }
    .cart-alert.error { background: #fee2e2; color: #991b1b; }
    .cart-table { width: 100%; border-collapse: collapse; }
    .cart-table th, .cart-table td { padding: 14px; border-bottom: 1px solid #e5ebf1; text-align: left; vertical-align: middle; }
    .cart-table th { background: #f8fafc; color: #334155; font-size: .88rem; text-transform: uppercase; }
    .cart-table input { width: 90px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; }
    .cart-product strong { display: block; color: #172033; }
    .cart-product span { color: #657084; font-size: .9rem; }
    .cart-total { display: flex; justify-content: flex-end; padding: 20px; }
    .cart-total-box { min-width: 280px; }
    .cart-total-row { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 8px; color: #334155; }
    .cart-total-row strong { color: #172033; font-size: 1.2rem; }
    .cart-notes { padding: 0 20px 18px; }
    .cart-notes label { display: block; margin-bottom: 6px; color: #334155; font-weight: 800; }
    .cart-notes textarea { width: 100%; min-height: 86px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; font: inherit; }
    .cart-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; padding: 18px 20px; background: #f8fafc; }
    .cart-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; border: 1px solid #0f766e; border-radius: 6px; padding: 9px 14px; color: #0f766e; font-weight: 800; background: #fff; cursor: pointer; }
    .cart-btn.primary { background: #0f766e; color: #fff; }
    .cart-btn.whatsapp { border-color: #25d366; background: #25d366; color: #102014; }
    .cart-btn.danger { border-color: #b91c1c; color: #b91c1c; }
    .cart-empty { padding: 34px; color: #657084; }
    @media (max-width: 760px) {
        .cart-table, .cart-table tbody, .cart-table tr, .cart-table td { display: block; width: 100%; }
        .cart-table thead { display: none; }
        .cart-table td { border-bottom: 0; padding: 10px 14px; display: flex; justify-content: space-between; gap: 14px; }
        .cart-table td::before { content: attr(data-label); color: #657084; font-weight: 800; }
        .cart-table td.cart-product { display: block; }
        .cart-table td.cart-product::before { display: block; margin-bottom: 6px; }
        .cart-table tr { border: 1px solid #e5ebf1; border-radius: 8px; margin: 10px; padding: 8px 0; background: #fff; }
        .cart-total { justify-content: stretch; }
        .cart-total-box { width: 100%; }
        .cart-actions { display: grid; grid-template-columns: 1fr; }
        .cart-actions > div { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .cart-btn { width: 100%; }
    }
</style>

<?php
$whatsapp_items = array();
foreach (!empty($items) ? $items : array() as $item) {
    $whatsapp_items[] = array(
        'name' => $item->name,
        'sku' => $item->sku,
        'quantity' => $item->quantity,
    );
}
$cart_whatsapp_url = class_exists('Helper_Core_Web') && !empty($whatsapp_items)
    ? Helper_Core_Web::whatsapp_url('cart', array('items' => $whatsapp_items, 'url' => Uri::current()))
    : '';
?>

<section class="cart-band">
    <div class="cart-shell">
        <div class="cart-title">
            <h1>Carrito</h1>
            <p>Tu carrito se enviará como solicitud. Un asesor confirmará precio, disponibilidad y forma de pago.</p>
        </div>

        <?php if (!empty($success)): ?>
        <div class="cart-alert success"><?php echo e($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
        <div class="cart-alert error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="cart-panel">
            <?php if (!empty($items)): ?>
            <?php echo Form::open(['action' => 'carrito/actualizar', 'method' => 'post']); ?>
                <?php echo Form::csrf(); ?>
                <div class="cart-section-heading">
                    <h2>Productos en carrito</h2>
                    <p>Actualiza cantidades o continúa explorando el catálogo.</p>
                </div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="cart-product" data-label="Producto">
                                <strong><?php echo e($item->name); ?></strong>
                                <span><?php echo e($item->sku); ?></span>
                            </td>
                            <td data-label="Precio"><?php echo e($item->currency_code); ?> <?php echo number_format((float) $item->unit_price, 2); ?></td>
                            <td data-label="Cantidad">
                                <?php echo Form::input('quantity['.(int) $item->id.']', (float) $item->quantity, ['type' => 'number', 'step' => '1', 'min' => '0']); ?>
                            </td>
                            <td data-label="Total"><?php echo e($item->currency_code); ?> <?php echo number_format((float) $item->line_total, 2); ?></td>
                            <td data-label="Acción"><a class="cart-btn danger" href="<?php echo e(Uri::create('carrito/quitar/'.$item->id)); ?>">Quitar</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-total">
                    <div class="cart-total-box">
                        <div class="cart-total-row"><span>Subtotal</span><strong><?php echo e($cart->currency_code); ?> <?php echo number_format((float) $cart->subtotal, 2); ?></strong></div>
                        <div class="cart-total-row"><span>Total</span><strong><?php echo e($cart->currency_code); ?> <?php echo number_format((float) $cart->total, 2); ?></strong></div>
                    </div>
                </div>

                <div class="cart-actions">
                    <div>
                        <button class="cart-btn" type="submit">Actualizar carrito</button>
                        <a class="cart-btn danger" href="<?php echo e(Uri::create('carrito/vaciar')); ?>">Vaciar</a>
                    </div>
                </div>
            <?php echo Form::close(); ?>

            <?php echo Form::open(['action' => 'carrito/checkout', 'method' => 'post', 'data-track-form' => 'quote_request']); ?>
                <?php echo Form::csrf(); ?>
                <div class="cart-section-heading">
                    <h2>Opciones para continuar</h2>
                    <p>Envía tus productos a un asesor para confirmar precio, disponibilidad o condiciones especiales.</p>
                </div>
                <div class="cart-flow-note">
                    Tu carrito se enviará como solicitud. Un asesor confirmará precio, disponibilidad y forma de pago.
                </div>
                <div class="cart-notes">
                    <label>Notas para cotización</label>
                    <?php echo Form::textarea('customer_notes', '', ['placeholder' => 'Comentarios, requerimientos de entrega o datos adicionales.']); ?>
                </div>
                <div class="cart-actions">
                    <a class="cart-btn" href="<?php echo e(Uri::create('productos')); ?>">Seguir comprando</a>
                    <?php if ($cart_whatsapp_url !== ''): ?>
                    <a class="cart-btn whatsapp" data-track-event="whatsapp_cart" href="<?php echo e($cart_whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">Enviar por WhatsApp</a>
                    <?php endif; ?>
                    <button class="cart-btn primary" type="submit">Solicitar cotización</button>
                </div>
            <?php echo Form::close(); ?>
            <?php else: ?>
            <div class="cart-empty">
                Tu carrito está vacío. <a href="<?php echo e(Uri::create('productos')); ?>">Explorar productos</a>.
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
