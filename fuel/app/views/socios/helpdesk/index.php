<?php echo View::forge('portales/helpdesk/index', [
    'portal_code' => isset($portal_code) ? $portal_code : 'socios',
    'party' => isset($party) ? $party : null,
]); ?>
