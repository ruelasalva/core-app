<?php
/**
 * Vista de compatibilidad.
 *
 * El perfil comun de portales vive en:
 * fuel/app/views/portales/perfil/index.php
 */
echo View::forge('portales/perfil/index', [
    'portal_code' => isset($portal_code) ? $portal_code : Uri::segment(1),
    'party' => isset($party) ? $party : null,
]);
