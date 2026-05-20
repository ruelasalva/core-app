<?php echo View::forge('portales/cfdi/index', [
    'portal_code' => isset($portal_code) ? $portal_code : 'clientes',
    'portal_direction' => isset($portal_direction) ? $portal_direction : 'customer',
    'portal_title' => isset($portal_title) ? $portal_title : 'CFDI de cliente',
]); ?>
