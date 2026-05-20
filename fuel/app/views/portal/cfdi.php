<?php
/**
 * Vista de compatibilidad.
 *
 * El visor comun de CFDI de portales vive en:
 * fuel/app/views/portales/cfdi/index.php
 */
echo View::forge('portales/cfdi/index', [
    'portal_code' => isset($portal_code) ? $portal_code : Uri::segment(1),
    'portal_direction' => isset($portal_direction) ? $portal_direction : Uri::segment(1),
    'portal_title' => isset($portal_title) ? $portal_title : 'CFDI del portal',
]);
