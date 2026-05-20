<?php
/**
 * Vista de compatibilidad.
 *
 * El portal de clientes vive en:
 * fuel/app/views/clientes/cotizaciones/index.php
 */
echo View::forge('clientes/cotizaciones/index', [
    'party' => isset($party) ? $party : null,
    'initial_tab' => isset($initial_tab) ? $initial_tab : 'account',
]);
