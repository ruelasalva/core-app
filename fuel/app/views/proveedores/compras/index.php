<style>
    .supplier-flow-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
        gap: .65rem;
    }
    .supplier-flow-steps span {
        border: 1px solid #dbe7e4;
        border-radius: 999px;
        background: #f8fffc;
        color: #334155;
        padding: .55rem .75rem;
        font-size: .86rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: .45rem;
        min-height: 42px;
    }
    .supplier-flow-steps strong {
        width: 24px;
        height: 24px;
        border-radius: 999px;
        background: var(--portal-primary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        flex: 0 0 auto;
    }
    .supplier-row-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
    }
    .supplier-status-note {
        display: block;
        color: #64748b;
        font-size: .78rem;
        margin-top: .2rem;
    }
</style>

<div id="app-portal-purchases">
    <div class="portal-page-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 mb-1">Compras y evidencias</h1>
                <p class="text-muted mb-0">Revisa tu orden de compra, registra tu factura y adjunta XML/PDF o evidencias. Compras validará la información y, si procede, emitirá contrarecibo y programación de pago.</p>
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

    <div class="portal-panel">
        <div class="portal-panel-header">
            <h2 class="h6 mb-0">Flujo operativo</h2>
        </div>
        <div class="portal-panel-body">
            <div class="supplier-flow-steps">
                <span><strong>1</strong> Orden de compra</span>
                <span><strong>2</strong> Factura</span>
                <span><strong>3</strong> Evidencia</span>
                <span><strong>4</strong> Revisión</span>
                <span><strong>5</strong> Contrarecibo</span>
                <span><strong>6</strong> Pago</span>
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
