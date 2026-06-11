<div id="app-portal-purchases">
    <div class="portal-page-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 mb-1">Compras y evidencias</h1>
                <p class="text-muted mb-0">Consulta órdenes de compra, registra facturas y adjunta documentos solicitados.</p>
            </div>
            <div class="portal-page-actions mt-3 mt-md-0">
                <button class="btn btn-primary btn-sm" @click="newInvoice">
                    <i class="bi bi-plus-lg mr-1"></i> Subir factura
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="load" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span>
                    Actualizar
                </button>
            </div>
        </div>
    </div>

    <?php echo View::forge('proveedores/compras/_summary'); ?>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="portal-panel">
        <?php echo View::forge('proveedores/compras/_tabs'); ?>
        <div class="portal-panel-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Cargando compras...</p>
            </div>

            <?php echo View::forge('proveedores/compras/_orders'); ?>
            <?php echo View::forge('proveedores/compras/_invoices'); ?>
            <?php echo View::forge('proveedores/compras/_receipts'); ?>
            <?php echo View::forge('proveedores/compras/_documents'); ?>
        </div>
    </div>

    <?php echo View::forge('proveedores/compras/_modals'); ?>
</div>

<?php echo View::forge('proveedores/compras/_scripts', ['portal_code' => $portal_code]); ?>
