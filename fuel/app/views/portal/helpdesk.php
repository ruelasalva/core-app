<?php
/**
 * Vista de compatibilidad.
 *
 * El helpdesk comun de portales vive en:
 * fuel/app/views/portales/helpdesk/index.php
 */
echo View::forge('portales/helpdesk/index', [
    'portal_code' => isset($portal_code) ? $portal_code : Uri::segment(1),
    'party' => isset($party) ? $party : null,
]);
