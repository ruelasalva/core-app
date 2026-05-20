<?php echo View::forge('portales/perfil/index', [
    'portal_code' => isset($portal_code) ? $portal_code : 'proveedores',
    'party' => isset($party) ? $party : null,
]); ?>
