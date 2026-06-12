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
</style>

<div class="portal-page-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="h4 mb-1">Portal de proveedores</h1>
            <p class="text-muted mb-0">Consulta órdenes, registra facturas y adjunta evidencias de entrega o cumplimiento.</p>
        </div>
        <div class="portal-page-actions mt-3 mt-md-0">
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-cart-check mr-1"></i> Compras
            </a>
            <a href="<?php echo Uri::create('proveedores/cfdi'); ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-file-earmark-text mr-1"></i> CFDI
            </a>
            <a href="<?php echo Uri::create('proveedores/contracts'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-file-earmark-ruled mr-1"></i> Contratos
            </a>
            <a href="<?php echo Uri::create('proveedores/helpdesk'); ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-life-preserver mr-1"></i> Abrir ticket
            </a>
        </div>
    </div>
</div>

<div class="portal-panel">
    <div class="portal-panel-header">
        <h2 class="h6 mb-0">Flujo operativo</h2>
    </div>
    <div class="portal-panel-body">
        <p class="text-muted mb-3">Revisa tu orden de compra, registra tu factura y adjunta XML/PDF o evidencias. Compras validará la información y, si procede, emitirá contrarecibo y programación de pago.</p>
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

<div class="portal-kpi-grid">
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Órdenes</div>
            <div class="portal-kpi-value">Compras</div>
            <div class="text-muted small">Consulta órdenes asignadas.</div>
        </div>
        <i class="bi bi-cart-check portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Facturas</div>
            <div class="portal-kpi-value">Registro</div>
            <div class="text-muted small">Envía facturas para revisión.</div>
        </div>
        <i class="bi bi-receipt portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Evidencias</div>
            <div class="portal-kpi-value">Adjuntos</div>
            <div class="text-muted small">Carga XML, PDF o comprobantes.</div>
        </div>
        <i class="bi bi-folder2-open portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Soporte</div>
            <div class="portal-kpi-value">Tickets</div>
            <div class="text-muted small">Da seguimiento con compras.</div>
        </div>
        <i class="bi bi-chat-dots portal-kpi-icon"></i>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="portal-panel h-100">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Operación de compras</h2>
            </div>
            <div class="portal-panel-body">
                <p class="text-muted">Revisa órdenes, registra facturas contra OC, consulta contrarecibos y adjunta evidencias solicitadas.</p>
                <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="btn btn-primary btn-sm">
                    Entrar a compras
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="portal-panel h-100">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Documentos y comunicación</h2>
            </div>
            <div class="portal-panel-body">
                <p class="text-muted">Consulta CFDI visibles, contratos relacionados y tickets de soporte activos desde un portal controlado.</p>
                <div class="portal-page-actions">
                    <a href="<?php echo Uri::create('proveedores/cfdi'); ?>" class="btn btn-outline-primary btn-sm">CFDI</a>
                    <a href="<?php echo Uri::create('proveedores/contracts'); ?>" class="btn btn-outline-secondary btn-sm">Contratos</a>
                    <a href="<?php echo Uri::create('proveedores/helpdesk'); ?>" class="btn btn-outline-warning btn-sm">Tickets</a>
                </div>
            </div>
        </div>
    </div>
</div>
