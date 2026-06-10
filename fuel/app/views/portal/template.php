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
        .portal-header { border-top: 4px solid var(--portal-primary); box-shadow: 0 6px 18px rgba(15, 23, 42, .06); }
        .portal-header .container { gap: .75rem; }
        .portal-title { color: var(--portal-primary); }
        .portal-nav { flex: 1 1 auto; min-width: 0; overflow-x: auto; white-space: nowrap; scrollbar-width: thin; }
        .portal-nav .nav-link { border-radius: 999px; font-size: .88rem; padding: .42rem .68rem; margin: 0 .08rem; }
        .portal-nav .nav-link.active { background: rgba(13, 110, 253, .1); color: var(--portal-primary); font-weight: 700; }
        .portal-user-nav { flex: 0 0 auto; }
        .portal-page-hero {
            background: linear-gradient(135deg, rgba(13, 110, 253, .1), rgba(255, 255, 255, .95));
            border: 1px solid #e5e9f0;
            border-left: 4px solid var(--portal-primary);
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .07);
            padding: 1.35rem;
            margin-bottom: 1rem;
        }
        .portal-page-hero h1 { font-weight: 800; color: #172033; }
        .portal-page-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
        .portal-panel {
            background: #fff;
            border: 1px solid #e5e9f0;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .portal-panel-header {
            padding: .9rem 1rem;
            border-bottom: 1px solid #edf1f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .portal-panel-body { padding: 1rem; }
        .portal-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }
        .portal-kpi {
            background: #fff;
            border: 1px solid #e5e9f0;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
            min-height: 104px;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            gap: .75rem;
        }
        .portal-kpi-label { color: #64748b; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
        .portal-kpi-value { font-size: 1.45rem; font-weight: 800; color: #172033; line-height: 1.2; word-break: break-word; }
        .portal-kpi-icon { color: var(--portal-primary); font-size: 1.7rem; opacity: .8; }
        .portal-empty {
            min-height: 96px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 1rem;
        }
        .portal-table th { color: #475569; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; border-top: 0; }
        .portal-tabs .nav-link { border-radius: 999px; color: #475569; }
        .portal-tabs .nav-link.active { background: var(--portal-primary); color: #fff; border-color: var(--portal-primary); }
        @media (max-width: 767.98px) {
            .portal-header .container { align-items: flex-start; }
            .portal-nav { order: 3; width: 100%; padding-bottom: .25rem; }
            .portal-user-nav { margin-left: auto; }
            .portal-page-hero { padding: 1rem; }
            .portal-page-actions .btn { width: 100%; }
            .portal-panel-header { align-items: flex-start; }
        }
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
            <ul class="navbar-nav portal-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == '' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code); ?>">Inicio</a>
                </li>
                <?php if ($portal_code === 'clientes'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'estado-cuenta' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/estado-cuenta'); ?>">Estado</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'cfdi' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/cfdi'); ?>">CFDI</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'contracts' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/contracts'); ?>">Contratos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'quotes' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/quotes'); ?>">Cotizaciones</a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'cfdi' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/cfdi'); ?>">CFDI</a>
                </li>
                <?php endif; ?>
                <?php if ($portal_code === 'revendedores'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'clientes' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/perfil#clientes'); ?>">Clientes</a>
                </li>
                <?php endif; ?>
                <?php if ($portal_code === 'proveedores'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'compras' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/compras'); ?>">Compras</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'contracts' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/contracts'); ?>">Mis Contratos</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'helpdesk' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/helpdesk'); ?>">Tickets</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo Uri::segment(2) == 'perfil' ? 'active' : ''; ?>" href="<?php echo Uri::create($portal_code.'/perfil'); ?>">Perfil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?php echo Uri::create($portal_code.'/logout'); ?>">Salir</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto portal-user-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button">
                        <i class="bi bi-person-circle"></i> <?php echo e($user_name); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="<?php echo Uri::create($portal_code.'/perfil'); ?>">
                            <i class="bi bi-gear mr-1"></i> Mi perfil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?php echo Uri::create($portal_code.'/logout'); ?>">
                            <i class="bi bi-box-arrow-right mr-1"></i> Salir
                        </a>
                    </div>
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
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': data[window.coreAppCsrfKey] },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    };
};
window.coreAppJson = function(response) {
    return response.json().then(function(json) {
        if (json && json.csrf_token) {
            window.coreAppCsrfToken = json.csrf_token;
        }
        if (!response.ok) {
            throw json;
        }
        return json;
    });
};

if ('serviceWorker' in navigator && navigator.serviceWorker.getRegistrations) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        registrations.forEach(function(registration) {
            if (registration.scope.indexOf('/admin/') === -1) {
                registration.unregister();
            }
        });
    }).catch(function() {});
}
</script>
</body>
</html>
