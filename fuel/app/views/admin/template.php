<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <title>Core-App ERP | Dashboard</title>
    <link rel="manifest" href="<?php echo Uri::base(false); ?>manifest.json">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <?php echo Asset::css('adminlte.min.css'); ?>
    <?php echo Asset::css('all.min.css'); // FontAwesome ?>
    <?php echo Asset::css('bootstrap-icons.css'); ?>
    <?php echo Asset::css('dataTables.bootstrap4.min.css'); ?>
    <?php if (in_array(Uri::segment(2), ['frontend', 'help'])): ?>
    <link rel="stylesheet" href="<?php echo Uri::base(false); ?>assets/vendor/admin/codemirror/lib/codemirror.css">
    <link rel="stylesheet" href="<?php echo Uri::base(false); ?>assets/vendor/admin/grapesjs/css/grapes.min.css">
    <?php endif; ?>
    
    <style>
        .sidebar-dark-primary { background-color: #343a40; }
        .nav-link.active { background-color: #007bff !important; }
    </style>
    <?php echo Asset::js('vue.min.js'); ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="bi bi-list"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown" id="app-notifications" :class="{ show: open }">
                <a class="nav-link" href="#" role="button" @click.prevent="toggle">
                    <i class="bi bi-bell"></i>
                    <span v-if="count > 0" class="badge badge-danger navbar-badge">{{ count }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" :class="{ show: open }">
                    <span class="dropdown-header">{{ count }} notificaciones</span>
                    <div class="dropdown-divider"></div>
                    <a v-for="item in items" :key="item.recipient_id" href="#" class="dropdown-item" @click.prevent="openNotification(item)">
                        <i :class="item.icon"></i>
                        <span class="ml-2">{{ item.title }}</span>
                        <span class="float-right text-muted text-sm">{{ item.created_at }}</span>
                        <div class="text-muted text-sm mt-1">{{ item.message }}</div>
                    </a>
                    <span v-if="items.length === 0" class="dropdown-item text-muted">Sin notificaciones pendientes</span>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?php echo Uri::create('logout'); ?>">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="#" class="brand-link text-center">
            <span class="brand-text font-weight-light fw-bold">CORE-APP <b>ERP</b></span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block">Hola, <?php echo $user_name; ?></a>
                </div>
            </div>

            <nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin'); ?>" class="nav-link <?php echo (Uri::segment(2) == '') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-speedometer2"></i>
                <p>Inicio</p>
            </a>
        </li>

        <li class="nav-header">OPERACION</li>
        <?php if ($menu['commerce']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/commerce'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'commerce') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-box-seam"></i>
                <p>Productos y precios</p>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($menu['sales']): ?>
        <?php $sales_open = (Uri::segment(2) == 'sales'); $sales_view = Input::get('view', 'quotes'); ?>
        <li class="nav-item has-treeview <?php echo $sales_open ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $sales_open ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-receipt"></i>
                <p>Ventas<i class="right bi bi-chevron-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item"><a href="<?php echo Uri::create('admin/sales', [], ['view' => 'quotes']); ?>" class="nav-link <?php echo $sales_open && $sales_view == 'quotes' ? 'active' : ''; ?>"><i class="bi bi-circle nav-icon"></i><p>Cotizaciones</p></a></li>
                <li class="nav-item"><a href="<?php echo Uri::create('admin/sales', [], ['view' => 'orders']); ?>" class="nav-link <?php echo $sales_open && $sales_view == 'orders' ? 'active' : ''; ?>"><i class="bi bi-circle nav-icon"></i><p>Pedidos</p></a></li>
                <li class="nav-item"><a href="<?php echo Uri::create('admin/sales', [], ['view' => 'deliveries']); ?>" class="nav-link <?php echo $sales_open && $sales_view == 'deliveries' ? 'active' : ''; ?>"><i class="bi bi-circle nav-icon"></i><p>Entregas</p></a></li>
            </ul>
        </li>
        <?php endif; ?>
        <?php if ($menu['inventory']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/inventory'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'inventory') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-boxes"></i>
                <p>Inventario</p>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($menu['purchases']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/purchases'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'purchases') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-cart-check"></i>
                <p>Compras</p>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($menu['billing']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/billing'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'billing') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-receipt-cutoff"></i>
                <p>Facturaci&oacute;n CFDI</p>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($menu['payments']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/payments'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'payments') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-bank"></i>
                <p>Bancos y pagos</p>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($menu['accounting']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/accounting'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'accounting') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-journal-check"></i>
                <p>Contabilidad</p>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-header">RELACIONES</li>
        <?php if ($menu['parties']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/parties'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'parties') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-person-vcard"></i>
                <p>Clientes y proveedores</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['portals']): ?>
        <?php $portal_section = Input::get('section', 'user_links'); ?>
        <?php $portal_open = (Uri::segment(2) == 'portals'); ?>
        <li class="nav-item has-treeview <?php echo $portal_open ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo $portal_open ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-door-open"></i>
                <p>
                    Portales
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview" style="display: <?php echo $portal_open ? 'block' : 'none'; ?>;">
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/portals').'?section=user_links'; ?>" class="nav-link <?php echo ($portal_open && $portal_section == 'user_links') ? 'active' : ''; ?>">
                        <i class="bi bi-person-lock nav-icon"></i>
                        <p>Accesos</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/portals').'?section=profiles'; ?>" class="nav-link <?php echo ($portal_open && $portal_section == 'profiles') ? 'active' : ''; ?>">
                        <i class="bi bi-door-open nav-icon"></i>
                        <p>Perfiles</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/portals').'?section=branding'; ?>" class="nav-link <?php echo ($portal_open && $portal_section == 'branding') ? 'active' : ''; ?>">
                        <i class="bi bi-palette nav-icon"></i>
                        <p>Branding</p>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <?php if ($menu['documents']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/documents'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'documents') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-folder2-open"></i>
                <p>Documentos</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['helpdesk']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/helpdesk'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'helpdesk') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-life-preserver"></i>
                <p>Helpdesk</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['calendar']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/calendar'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'calendar') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-calendar3"></i>
                <p>Calendario</p>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-header">FISCAL Y CATALOGOS</li>
        <?php if ($menu['sat']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/sat'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'sat') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-receipt"></i>
                <p>SAT y CFDI</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/cfdi'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'cfdi') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-search"></i>
                <p>Auditoria SAT</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['catalogs']): ?>
        <?php $catalog_group = Input::get('group', 'general'); ?>
        <?php $catalog_open = (Uri::segment(2) == 'catalogs'); ?>
        <li class="nav-item has-treeview <?php echo $catalog_open ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (Uri::segment(2) == 'catalogs') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-collection"></i>
                <p>
                    Catalogos base
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview" style="display: <?php echo $catalog_open ? 'block' : 'none'; ?>;">
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/catalogs').'?group=general'; ?>" class="nav-link <?php echo (Uri::segment(2) == 'catalogs' && $catalog_group == 'general') ? 'active' : ''; ?>">
                        <i class="bi bi-grid nav-icon"></i>
                        <p>Generales</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/catalogs').'?group=financial'; ?>" class="nav-link <?php echo (Uri::segment(2) == 'catalogs' && $catalog_group == 'financial') ? 'active' : ''; ?>">
                        <i class="bi bi-currency-exchange nav-icon"></i>
                        <p>Bancos y monedas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/catalogs').'?group=fiscal'; ?>" class="nav-link <?php echo (Uri::segment(2) == 'catalogs' && $catalog_group == 'fiscal') ? 'active' : ''; ?>">
                        <i class="bi bi-percent nav-icon"></i>
                        <p>Impuestos y retenciones</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/catalogs').'?group=logistics'; ?>" class="nav-link <?php echo (Uri::segment(2) == 'catalogs' && $catalog_group == 'logistics') ? 'active' : ''; ?>">
                        <i class="bi bi-truck nav-icon"></i>
                        <p>Logistica</p>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <li class="nav-header">SITIO E INTEGRACIONES</li>
        <?php if ($menu['web']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/web'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'web') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-globe2"></i>
                <p>Web y tracking</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['legal']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/legal'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'legal') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-file-earmark-check"></i>
                <p>Legal y privacidad</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['communications']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/communications'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'communications') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-chat-square-dots"></i>
                <p>Correos y avisos</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['integrations']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/integrations'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'integrations') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-plug"></i>
                <p>Integraciones</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['frontend']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/frontend'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'frontend') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-window"></i>
                <p>Sitio publico</p>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-header">CONTROL</li>
        <?php if ($menu['audit']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/audit'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'audit') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-shield-check"></i>
                <p>Auditoria</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['help']): ?>
        <li class="nav-item">
            <a href="<?php echo Uri::create('admin/help'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'help') ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-question-circle"></i>
                <p>Ayuda</p>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($menu['users'] || $menu['acl'] || $menu['config']): ?>
        <?php $admin_open = in_array(Uri::segment(2), ['users', 'permissions', 'config']); ?>
        <li class="nav-item has-treeview <?php echo $admin_open ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(Uri::segment(2), ['users', 'permissions', 'config'])) ? 'active' : ''; ?>">
                <i class="nav-icon bi bi-shield-lock"></i>
                <p>
                    Administración
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview" style="display: <?php echo $admin_open ? 'block' : 'none'; ?>;">
                <?php if ($menu['users']): ?>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/users'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'users') ? 'active' : ''; ?>">
                        <i class="bi bi-people nav-icon"></i>
                        <p>Usuarios</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($menu['acl']): ?>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/permissions'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'permissions') ? 'active' : ''; ?>">
                        <i class="bi bi-key nav-icon text-danger"></i>
                        <p>Grupos y Permisos</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($menu['config']): ?>
                <li class="nav-item">
                    <a href="<?php echo Uri::create('admin/config'); ?>" class="nav-link <?php echo (Uri::segment(2) == 'config') ? 'active' : ''; ?>">
                        <i class="bi bi-gear nav-icon"></i>
                        <p>Configuración</p>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

    </ul>
</nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1><?php echo isset($title) ? $title : 'Panel de Control'; ?></h1>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php echo $content; ?>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">v1.0</div>
        <strong>&copy; <?php echo date('Y'); ?> Core-App.</strong> Todos los derechos reservados.
    </footer>
</div>

<?php echo Asset::js('jquery.min.js'); ?>
<?php echo Asset::js('bootstrap.bundle.min.js'); ?>

<?php echo Asset::js('jquery.dataTables.min.js'); ?>

<?php echo Asset::js('dataTables.bootstrap4.min.js'); ?>

<?php echo Asset::js('adminlte.min.js'); ?>
<?php echo Asset::js('chart.umd.js'); ?>
<?php echo Asset::js('core-offline.js'); ?>
<?php if (in_array((string) Uri::segment(2), ['', 'calendar'])): ?>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/fullcalendar/index.global.min.js"></script>
<?php endif; ?>
<?php if (in_array(Uri::segment(2), ['frontend', 'help'])): ?>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/ckeditor5-build-classic/ckeditor.js"></script>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/ckeditor5-build-classic/translations/es.js"></script>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/codemirror/lib/codemirror.js"></script>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/codemirror/mode/css/css.js"></script>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/codemirror/mode/xml/xml.js"></script>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/codemirror/mode/javascript/javascript.js"></script>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="<?php echo Uri::base(false); ?>assets/vendor/admin/grapesjs/js/grapes.min.js"></script>
<?php endif; ?>

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

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?php echo Uri::base(false); ?>sw.js').catch(function() {});
    });
}

new Vue({
    el: '#app-notifications',
    data: {
        count: 0,
        items: [],
        open: false
    },
    mounted: function() {
        this.load();
        setInterval(this.load, 60000);
        document.addEventListener('click', this.closeFromOutside);
    },
    methods: {
        toggle: function() {
            this.open = !this.open;
            this.load();
        },
        closeFromOutside: function(event) {
            if (!this.$el.contains(event.target)) {
                this.open = false;
            }
        },
        load: function() {
            fetch('<?php echo Uri::create('admin/notifications/data'); ?>')
                .then(function(res) { return res.json(); })
                .then(data => {
                    if (data.error) return;
                    this.count = data.count || 0;
                    this.items = data.items || [];
                });
        },
        openNotification: function(item) {
            fetch('<?php echo Uri::create('admin/notifications/mark_read'); ?>', {
                ...window.coreAppFetchOptions({ recipient_id: item.recipient_id })
            }).then(() => {
                this.open = false;
                this.load();
                if (item.url) {
                    window.location.href = item.url;
                }
            });
        }
    }
});
</script>

</body>
</html>
