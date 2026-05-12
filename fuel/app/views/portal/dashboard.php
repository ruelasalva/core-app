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
</div>
