<?php
/**
 * Vista de compatibilidad.
 *
 * La pantalla de compras del portal de proveedores vive en:
 * fuel/app/views/proveedores/compras/index.php
 */
echo View::forge('proveedores/compras/index', [
    'portal_code' => isset($portal_code) ? $portal_code : 'proveedores',
]);
