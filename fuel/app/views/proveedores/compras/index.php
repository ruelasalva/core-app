<style>
    .supplier-flow-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
        gap: .65rem;
    }
    .supplier-flow-steps a {
        border: 1px solid #dbe7e4;
        border-radius: 8px;
        background: #f8fffc;
        color: #334155;
        padding: .6rem .75rem;
        font-size: .86rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: .45rem;
        min-height: 48px;
        text-decoration: none;
        transition: border-color .15s ease, transform .15s ease;
    }
    .supplier-flow-steps a:hover {
        border-color: var(--portal-primary);
        color: #172033;
        text-decoration: none;
        transform: translateY(-1px);
    }
    .supplier-flow-steps strong {
        width: 24px;
        height: 24px;
        border-radius: 50%;
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
    .supplier-row-title {
        font-weight: 700;
        color: #172033;
    }
    .supplier-status-note {
        display: block;
        color: #64748b;
        font-size: .78rem;
        margin-top: .2rem;
    }
    .supplier-section-help {
        border: 1px solid #e2ebe8;
        border-radius: 8px;
        background: #fbfefd;
        color: #64748b;
        padding: .75rem .9rem;
    }
    .supplier-mobile-card-label {
        display: none;
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .supplier-empty-state {
        border: 1px dashed #cbd5d1;
        border-radius: 8px;
        padding: 1rem;
        color: #64748b;
        background: #fbfefd;
    }
    @media (max-width: 767.98px) {
        .supplier-responsive-table thead {
            display: none;
        }
        .supplier-responsive-table,
        .supplier-responsive-table tbody,
        .supplier-responsive-table tr,
        .supplier-responsive-table td {
            display: block;
            width: 100%;
        }
        .supplier-responsive-table tr {
            border: 1px solid #e2ebe8;
            border-radius: 8px;
            margin-bottom: .75rem;
            padding: .65rem;
            background: #fff;
        }
        .supplier-responsive-table td {
            border: 0 !important;
            padding: .35rem 0 !important;
        }
        .supplier-mobile-card-label {
            display: block;
            margin-bottom: .1rem;
        }
        .supplier-row-actions .btn,
        .portal-page-actions .btn {
            width: 100%;
        }
    }
</style>

<div id="app-portal-purchases">
    <div class="portal-page-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 mb-1">Compras y evidencias</h1>
                <p class="text-muted mb-0">Sigue el proceso de OC a pago: revisa órdenes de compra, registra facturas, adjunta evidencias y consulta contrarecibos.</p>
            </div>
            <div class="portal-page-actions mt-3 mt-md-0">
                <button class="btn btn-primary btn-sm" @click="newInvoice">
                    <i class="bi bi-plus-lg mr-1"></i> Registrar factura
                </button>
                <button class="btn btn-outline-primary btn-sm" @click="startEvidence">
                    <i class="bi bi-paperclip mr-1"></i> Adjuntar evidencia
                </button>
                <a href="<?php echo Uri::create('proveedores/helpdesk'); ?>" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-life-preserver mr-1"></i> Abrir ticket
                </a>
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
                <a href="#" @click.prevent="tab = 'orders'"><strong>1</strong> Orden de compra</a>
                <a href="#" @click.prevent="tab = 'invoices'"><strong>2</strong> Factura</a>
                <a href="#" @click.prevent="tab = 'documents'"><strong>3</strong> Evidencia</a>
                <a href="#" @click.prevent="tab = 'invoices'"><strong>4</strong> Revisión</a>
                <a href="#" @click.prevent="tab = 'receipts'"><strong>5</strong> Contrarecibo</a>
                <a href="#" @click.prevent="tab = 'receipts'"><strong>6</strong> Pago</a>
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
