<div id="app-sales">
    <?php
    $capture_page = !empty($capture_page);
    $initial_view = Input::get('view', 'quotes');
    if (!in_array($initial_view, ['quotes', 'orders', 'deliveries'], true)) {
        $initial_view = 'quotes';
    }
    $capture_mode = Input::get('mode', 'quote') === 'prequote' ? 'prequote' : 'quote';
    $no_image_svg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="360" height="260" viewBox="0 0 360 260"><rect width="360" height="260" fill="#eef3f7"/><path d="M72 178h216l-64-82-48 60-34-44-70 66z" fill="#cbd5e1"/><circle cx="130" cy="86" r="24" fill="#cbd5e1"/><text x="180" y="226" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" fill="#64748b">Sin imagen</text></svg>');
    ?>
    <?php echo View::forge('admin/sales/_styles'); ?>
    <?php if (!$capture_page): ?>
    <?php echo View::forge('admin/sales/_summary'); ?>
    <?php echo View::forge('admin/sales/_toolbar'); ?>
    <?php echo View::forge('admin/sales/_quotes_table'); ?>
    <?php echo View::forge('admin/sales/_orders_table'); ?>
    <?php echo View::forge('admin/sales/_deliveries_table'); ?>
    <?php endif; ?>

    <?php echo View::forge('admin/sales/_quote_form_modal', ['capture_page' => $capture_page]); ?>
    <?php echo View::forge('admin/sales/_quote_detail_modal'); ?>
    <?php echo View::forge('admin/sales/_fulfillment_modal'); ?>
</div>

<?php echo View::forge('admin/sales/_scripts', ['initial_view' => $initial_view, 'capture_mode' => $capture_mode, 'capture_page' => $capture_page, 'no_image_svg' => $no_image_svg]); ?>
