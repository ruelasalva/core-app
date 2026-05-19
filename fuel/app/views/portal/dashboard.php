<?php $current_portal_code = isset($portal_code) ? $portal_code : Uri::segment(1); ?>
<div class="card">
    <div class="card-body">
        <h1 class="h4 mb-2">Portal <?php echo e($portal_label); ?></h1>
        <p class="text-muted mb-0">
            Acceso validado por usuario, tercero y portal. Este dashboard es la base para construir las operaciones especificas.
        </p>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <div class="card card-primary card-outline h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-life-preserver h4 mb-0 mr-2 text-primary"></i>
                    <h2 class="h6 mb-0">Helpdesk</h2>
                </div>
                <p class="text-muted small">Crea tickets de soporte, revisa respuestas y da seguimiento a solicitudes.</p>
                <a href="<?php echo Uri::create($current_portal_code.'/helpdesk'); ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> Entrar
                </a>
            </div>
        </div>
    </div>
    <?php if ($current_portal_code === 'proveedores'): ?>
    <div class="col-md-4">
        <div class="card card-success card-outline h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-cart-check h4 mb-0 mr-2 text-success"></i>
                    <h2 class="h6 mb-0">Compras</h2>
                </div>
                <p class="text-muted small">Consulta ordenes de compra, registra facturas y adjunta evidencias.</p>
                <a href="<?php echo Uri::create($current_portal_code.'/compras'); ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-arrow-right"></i> Entrar
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-4">
        <div class="card card-info card-outline h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-person-lines-fill h4 mb-0 mr-2 text-info"></i>
                    <h2 class="h6 mb-0">Mi cuenta</h2>
                </div>
                <?php if ($current_portal_code === 'proveedores'): ?>
                <p class="text-muted small">Actualiza datos, bodegas de entrega, contactos y documentos como constancia u opinion de cumplimiento.</p>
                <?php elseif ($current_portal_code === 'revendedores'): ?>
                <p class="text-muted small">Administra datos comerciales y da de alta clientes propios para cotizarles.</p>
                <?php else: ?>
                <p class="text-muted small">Revisa credito, direcciones de entrega y contactos autorizados para recibir mercancia.</p>
                <?php endif; ?>
                <a href="<?php echo Uri::create($current_portal_code.'/perfil'); ?>" class="btn btn-info btn-sm">
                    <i class="bi bi-arrow-right"></i> Entrar
                </a>
            </div>
        </div>
    </div>
</div>
