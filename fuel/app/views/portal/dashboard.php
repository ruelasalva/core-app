<?php
/**
 * Vista de compatibilidad.
 *
 * El dashboard comun de portales vive en:
 * fuel/app/views/portales/dashboard/index.php
 */
echo View::forge('portales/dashboard/index', [
    'portal_code' => isset($portal_code) ? $portal_code : Uri::segment(1),
    'portal_label' => isset($portal_label) ? $portal_label : ucfirst((string) Uri::segment(1)),
]);
