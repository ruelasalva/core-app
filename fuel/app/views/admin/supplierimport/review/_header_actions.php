    <div class="row mb-3">
        <div class="col-md-7">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                Revisi&oacute;n de filas cargadas a staging. Aprobar o rechazar no crea productos reales.
            </div>
        </div>
        <div class="col-md-5 text-md-right mt-2 mt-md-0">
            <a href="<?php echo Uri::create('admin/supplierimport'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a importaciones
            </a>
            <button type="button" class="btn btn-sm btn-success ml-1" :disabled="selectedIds.length === 0 || loadingAction" @click="approveSelected">
                <i class="bi bi-check2-circle"></i> Aprobar seleccionados
            </button>
            <button type="button" class="btn btn-sm btn-danger ml-1" :disabled="selectedIds.length === 0 || loadingAction" @click="rejectSelected">
                <i class="bi bi-x-circle"></i> Rechazar seleccionados
            </button>
            <button type="button" class="btn btn-sm btn-primary ml-1" :disabled="loadingAction" @click="applyApproved">
                <i class="bi bi-box-seam"></i> Crear productos aprobados
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary ml-1" :disabled="loadingAction" @click="downloadImages">
                <i class="bi bi-image"></i> Descargar im&aacute;genes
            </button>
        </div>
    </div>
