    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                Staging de cat&aacute;logos de proveedor. Solo lectura: no crea productos, precios ni inventario.
            </div>
        </div>
        <div class="col-md-4 text-md-right mt-2 mt-md-0">
            <a href="<?php echo Uri::create('admin/supplierimport/review'); ?>" class="btn btn-sm btn-outline-success mr-2">
                <i class="bi bi-check2-square"></i> Revisar staging
            </a>
            <button type="button" class="btn btn-sm btn-primary mr-2" @click="openUploadModal">
                <i class="bi bi-upload"></i> Importar CSV
            </button>
            <a href="<?php echo Uri::create('admin/supplierimport/csv_template'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download"></i> Descargar plantilla CSV
            </a>
        </div>
    </div>
