<?php echo View::forge('portales/perfil/index', [
    'portal_code' => isset($portal_code) ? $portal_code : 'revendedores',
    'party' => isset($party) ? $party : null,
]); ?>
