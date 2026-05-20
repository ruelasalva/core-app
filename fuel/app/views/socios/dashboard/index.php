<?php echo View::forge('portales/dashboard/index', [
    'portal_code' => isset($portal_code) ? $portal_code : 'socios',
    'portal_label' => isset($portal_label) ? $portal_label : 'Socios',
]); ?>
