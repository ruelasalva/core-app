<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(isset($title) ? $title : $portal_name); ?> | Core-App</title>
    <?php echo Asset::css('adminlte.min.css'); ?>
    <?php echo Asset::css('bootstrap-icons.css'); ?>
    <?php echo Asset::js('vue.min.js'); ?>
    <style>
        :root {
            --portal-primary: <?php echo $branding ? e($branding->primary_color) : '#0d6efd'; ?>;
            --portal-secondary: <?php echo $branding ? e($branding->secondary_color) : '#343a40'; ?>;
        }
        .portal-header { border-top: 4px solid var(--portal-primary); }
        .portal-title { color: var(--portal-primary); }
        <?php echo $branding ? $branding->custom_css : ''; ?>
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white portal-header">
        <div class="container">
            <a href="<?php echo Uri::create($portal_code); ?>" class="navbar-brand">
                <span class="brand-text font-weight-bold portal-title">
                    <?php echo e($branding && $branding->display_name ? $branding->display_name : $portal_name); ?>
                </span>
            </a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == '' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code); ?>">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'helpdesk' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/helpdesk'); ?>">Helpdesk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'cfdi' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/cfdi'); ?>">CFDI</a>
                </li>
                <?php if ($portal_code === 'clientes'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'quotes' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/quotes'); ?>">Cotizaciones</a>
                </li>
                <?php endif; ?>
                <?php if ($portal_code === 'proveedores'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'compras' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/compras'); ?>">Compras</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link"><?php echo e($user_name); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?php echo Uri::create($portal_code.'/logout'); ?>">Salir</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="content pt-4">
            <div class="container">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</div>
<?php echo Asset::js('jquery.min.js'); ?>
<?php echo Asset::js('bootstrap.bundle.min.js'); ?>
<?php echo Asset::js('adminlte.min.js'); ?>
<?php echo Security::js_fetch_token(); ?>
<script>
window.coreAppCsrfKey = <?php echo json_encode(Config::get('security.csrf_token_key', 'fuel_csrf_token')); ?>;
window.coreAppCsrfToken = <?php echo json_encode(Security::fetch_token()); ?>;
window.fuel_csrf_token = function() {
    return window.coreAppCsrfToken || '';
};
window.coreAppFetchOptions = function(data) {
    data = data || {};
    data[window.coreAppCsrfKey] = fuel_csrf_token();

    return {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': data[window.coreAppCsrfKey] },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    };
};
</script>
</body>
</html>
