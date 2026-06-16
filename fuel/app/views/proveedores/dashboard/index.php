<?php
$party = isset($party) ? $party : null;
$summary = isset($summary) && is_array($summary) ? $summary : [];
$orders = isset($orders) && is_array($orders) ? $orders : [];
$invoices = isset($invoices) && is_array($invoices) ? $invoices : [];
$receipts = isset($receipts) && is_array($receipts) ? $receipts : [];
$documents = isset($documents) && is_array($documents) ? $documents : [];
$tickets = isset($tickets) && is_array($tickets) ? $tickets : [];

$supplier_name = $party && !empty($party->name) ? (string) $party->name : 'Proveedor';

$money = function ($amount, $currency = 'MXN') {
    return '$'.number_format((float) $amount, 2).' '.e((string) ($currency ?: 'MXN'));
};

$date_label = function ($value) {
    if ($value === null || $value === '' || $value === '0000-00-00') {
        return '-';
    }
    if (is_numeric($value)) {
        return date('d/m/Y', (int) $value);
    }
    $time = strtotime((string) $value);
    return $time ? date('d/m/Y', $time) : e((string) $value);
};

$status_label = function ($value) {
    $labels = [
        'draft' => 'Borrador',
        'open' => 'Abierta',
        'pending' => 'Pendiente',
        'submitted' => 'Enviada',
        'review' => 'En revisión',
        'in_review' => 'En revisión',
        'validated' => 'Validada',
        'approved' => 'Aprobada',
        'accepted' => 'Aceptada',
        'rejected' => 'Rechazada',
        'issued' => 'Emitido',
        'scheduled' => 'Programado',
        'paid' => 'Pagado',
        'closed' => 'Cerrada',
        'cancelled' => 'Cancelada',
        'canceled' => 'Cancelada',
    ];
    $key = strtolower(trim((string) $value));
    return e(\Arr::get($labels, $key, $key !== '' ? $value : 'Sin estado'));
};

$entity_label = function ($value) {
    $labels = [
        'purchase_order' => 'OC',
        'purchase_invoice' => 'Factura',
        'purchase_receipt' => 'Contrarecibo',
    ];
    return e(\Arr::get($labels, (string) $value, (string) $value));
};
?>

<style>
    .supplier-dashboard-stepper {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(148px, 1fr));
        gap: .65rem;
    }
    .supplier-dashboard-step {
        border: 1px solid #d7e6e2;
        border-radius: 8px;
        background: #f8fffc;
        color: #334155;
        padding: .65rem .75rem;
        display: flex;
        align-items: center;
        gap: .55rem;
        min-height: 48px;
        text-decoration: none;
        transition: border-color .15s ease, transform .15s ease;
    }
    .supplier-dashboard-step:hover {
        border-color: var(--portal-primary);
        color: #172033;
        text-decoration: none;
        transform: translateY(-1px);
    }
    .supplier-dashboard-step strong {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--portal-primary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        flex: 0 0 auto;
    }
    .supplier-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem;
    }
    .supplier-action-card {
        border: 1px solid #e2ebe8;
        border-radius: 8px;
        background: #fff;
        padding: .8rem;
        color: #172033;
        display: flex;
        align-items: center;
        gap: .7rem;
        text-decoration: none;
        min-height: 70px;
    }
    .supplier-action-card:hover {
        border-color: var(--portal-primary);
        color: #172033;
        text-decoration: none;
    }
    .supplier-action-card i {
        color: var(--portal-primary);
        font-size: 1.35rem;
        flex: 0 0 auto;
    }
    .supplier-recent-list {
        display: flex;
        flex-direction: column;
        gap: .65rem;
    }
    .supplier-recent-item {
        border-bottom: 1px solid #edf2f1;
        padding-bottom: .65rem;
    }
    .supplier-recent-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .supplier-recent-title {
        font-weight: 700;
        color: #172033;
        margin-bottom: .15rem;
    }
    .supplier-recent-meta {
        color: #64748b;
        font-size: .84rem;
        line-height: 1.45;
    }
    .supplier-empty {
        border: 1px dashed #cbd5d1;
        border-radius: 8px;
        padding: 1rem;
        color: #64748b;
        background: #fbfefd;
        font-size: .9rem;
    }
    @media (max-width: 575.98px) {
        .supplier-action-card,
        .supplier-dashboard-step {
            min-height: 56px;
        }
    }
</style>

<div class="portal-page-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="h4 mb-1">Portal de proveedores</h1>
            <p class="mb-1 font-weight-semibold"><?php echo e($supplier_name); ?></p>
            <p class="text-muted mb-0">Consulta órdenes, registra facturas, adjunta evidencias y da seguimiento a contrarecibos.</p>
        </div>
        <div class="portal-page-actions mt-3 mt-md-0">
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-cart-check mr-1"></i> Compras
            </a>
            <a href="<?php echo Uri::create('proveedores/helpdesk'); ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-life-preserver mr-1"></i> Abrir ticket
            </a>
        </div>
    </div>
</div>

<div class="portal-kpi-grid">
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Órdenes activas</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'active_orders', 0); ?></div>
        </div>
        <i class="bi bi-cart-check portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Facturas enviadas</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'sent_invoices', 0); ?></div>
        </div>
        <i class="bi bi-receipt portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Pendientes de revisión</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'pending_invoices', 0); ?></div>
        </div>
        <i class="bi bi-hourglass-split portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Facturas validadas</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'validated_invoices', 0); ?></div>
        </div>
        <i class="bi bi-check2-circle portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Contrarecibos emitidos</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'issued_receipts', 0); ?></div>
        </div>
        <i class="bi bi-receipt-cutoff portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Pagos programados</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'scheduled_payments', 0); ?></div>
        </div>
        <i class="bi bi-calendar-check portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Evidencias/documentos</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'documents', 0); ?></div>
        </div>
        <i class="bi bi-folder2-open portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Tickets abiertos</div>
            <div class="portal-kpi-value"><?php echo (int) \Arr::get($summary, 'open_tickets', 0); ?></div>
        </div>
        <i class="bi bi-chat-dots portal-kpi-icon"></i>
    </div>
</div>

<div class="portal-panel">
    <div class="portal-panel-header">
        <h2 class="h6 mb-0">Flujo operativo</h2>
    </div>
    <div class="portal-panel-body">
        <p class="text-muted mb-3">Revisa tu orden de compra, registra tu factura y adjunta XML/PDF o evidencias. Compras validará la información y, si procede, emitirá contrarecibo y programación de pago.</p>
        <div class="supplier-dashboard-stepper">
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-dashboard-step"><strong>1</strong> Orden de compra</a>
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-dashboard-step"><strong>2</strong> Factura</a>
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-dashboard-step"><strong>3</strong> Evidencia</a>
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-dashboard-step"><strong>4</strong> Revisión</a>
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-dashboard-step"><strong>5</strong> Contrarecibo</a>
            <a href="<?php echo Uri::create('proveedores/helpdesk'); ?>" class="supplier-dashboard-step"><strong>6</strong> Pago</a>
        </div>
    </div>
</div>

<div class="portal-panel">
    <div class="portal-panel-header">
        <h2 class="h6 mb-0">Acciones principales</h2>
    </div>
    <div class="portal-panel-body">
        <div class="supplier-action-grid">
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-action-card">
                <i class="bi bi-cart-check"></i>
                <span><strong>Ver órdenes de compra</strong><br><small class="text-muted">Consulta OC asignadas.</small></span>
            </a>
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-action-card">
                <i class="bi bi-receipt"></i>
                <span><strong>Registrar factura</strong><br><small class="text-muted">Captura factura contra OC.</small></span>
            </a>
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-action-card">
                <i class="bi bi-paperclip"></i>
                <span><strong>Adjuntar evidencia</strong><br><small class="text-muted">Carga XML, PDF o evidencia.</small></span>
            </a>
            <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="supplier-action-card">
                <i class="bi bi-receipt-cutoff"></i>
                <span><strong>Ver contrarecibos</strong><br><small class="text-muted">Revisa pagos programados.</small></span>
            </a>
            <a href="<?php echo Uri::create('proveedores/helpdesk'); ?>" class="supplier-action-card">
                <i class="bi bi-life-preserver"></i>
                <span><strong>Abrir ticket</strong><br><small class="text-muted">Solicita apoyo a compras.</small></span>
            </a>
            <a href="<?php echo Uri::create('proveedores/perfil'); ?>" class="supplier-action-card">
                <i class="bi bi-person-circle"></i>
                <span><strong>Mi perfil</strong><br><small class="text-muted">Consulta tus datos de acceso.</small></span>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="portal-panel h-100">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Últimas órdenes</h2>
                <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="btn btn-outline-primary btn-sm">Ver compras</a>
            </div>
            <div class="portal-panel-body">
                <?php if (empty($orders)): ?>
                    <div class="supplier-empty">Aún no hay órdenes de compra visibles para este proveedor.</div>
                <?php else: ?>
                    <div class="supplier-recent-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="supplier-recent-item">
                                <div class="supplier-recent-title"><?php echo e(\Arr::get($order, 'folio', 'OC')); ?></div>
                                <div class="supplier-recent-meta">
                                    Fecha: <?php echo $date_label(\Arr::get($order, 'order_date', '')); ?> ·
                                    Estado: <?php echo $status_label(\Arr::get($order, 'status', '')); ?> ·
                                    Total: <?php echo $money(\Arr::get($order, 'total', 0), \Arr::get($order, 'currency_code', 'MXN')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="portal-panel h-100">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Últimas facturas</h2>
                <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="btn btn-outline-primary btn-sm">Registrar factura</a>
            </div>
            <div class="portal-panel-body">
                <?php if (empty($invoices)): ?>
                    <div class="supplier-empty">Aún no hay facturas enviadas para revisión.</div>
                <?php else: ?>
                    <div class="supplier-recent-list">
                        <?php foreach ($invoices as $invoice): ?>
                            <div class="supplier-recent-item">
                                <div class="supplier-recent-title"><?php echo e(\Arr::get($invoice, 'folio', 'Factura')); ?></div>
                                <div class="supplier-recent-meta">
                                    OC: <?php echo e(\Arr::get($invoice, 'order_folio', '-')); ?> ·
                                    Revisión: <?php echo $status_label(\Arr::get($invoice, 'validation_status', '')); ?> ·
                                    Total: <?php echo $money(\Arr::get($invoice, 'total', 0), \Arr::get($invoice, 'currency_code', 'MXN')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="portal-panel h-100">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Próximos pagos programados</h2>
                <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="btn btn-outline-primary btn-sm">Ver contrarecibos</a>
            </div>
            <div class="portal-panel-body">
                <?php if (empty($receipts)): ?>
                    <div class="supplier-empty">Aún no hay contrarecibos o pagos programados visibles.</div>
                <?php else: ?>
                    <div class="supplier-recent-list">
                        <?php foreach ($receipts as $receipt): ?>
                            <div class="supplier-recent-item">
                                <div class="supplier-recent-title"><?php echo e(\Arr::get($receipt, 'folio', 'Contrarecibo')); ?></div>
                                <div class="supplier-recent-meta">
                                    Programación: <?php echo $date_label(\Arr::get($receipt, 'scheduled_payment_date', '')); ?> ·
                                    Estado: <?php echo $status_label(\Arr::get($receipt, 'status', '')); ?> ·
                                    Total: <?php echo $money(\Arr::get($receipt, 'total', 0), \Arr::get($receipt, 'currency_code', 'MXN')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="portal-panel h-100">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Últimos documentos/evidencias</h2>
                <a href="<?php echo Uri::create('proveedores/compras'); ?>" class="btn btn-outline-primary btn-sm">Adjuntar evidencia</a>
            </div>
            <div class="portal-panel-body">
                <?php if (empty($documents)): ?>
                    <div class="supplier-empty">Aún no hay documentos o evidencias cargadas.</div>
                <?php else: ?>
                    <div class="supplier-recent-list">
                        <?php foreach ($documents as $document): ?>
                            <div class="supplier-recent-item">
                                <div class="supplier-recent-title">
                                    <?php if (!empty($document['download_url'])): ?>
                                        <a href="<?php echo e($document['download_url']); ?>" target="_blank" rel="noopener"><?php echo e(\Arr::get($document, 'title') ?: \Arr::get($document, 'filename') ?: \Arr::get($document, 'original_name', 'Documento')); ?></a>
                                    <?php else: ?>
                                        <?php echo e(\Arr::get($document, 'title') ?: \Arr::get($document, 'filename') ?: \Arr::get($document, 'original_name', 'Documento')); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="supplier-recent-meta">
                                    Relación: <?php echo $entity_label(\Arr::get($document, 'entity_type', '')); ?> ·
                                    Tipo: <?php echo e(\Arr::get($document, 'document_type', '-')); ?> ·
                                    Fecha: <?php echo $date_label(\Arr::get($document, 'created_at', '')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="portal-panel h-100">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Tickets recientes</h2>
                <a href="<?php echo Uri::create('proveedores/helpdesk'); ?>" class="btn btn-outline-warning btn-sm">Abrir ticket</a>
            </div>
            <div class="portal-panel-body">
                <?php if (empty($tickets)): ?>
                    <div class="supplier-empty">Aún no hay tickets recientes. Si necesitas apoyo, abre un ticket para compras.</div>
                <?php else: ?>
                    <div class="supplier-recent-list">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="supplier-recent-item">
                                <div class="supplier-recent-title"><?php echo e(\Arr::get($ticket, 'subject', 'Ticket #'.\Arr::get($ticket, 'id', ''))); ?></div>
                                <div class="supplier-recent-meta">
                                    Estado: <?php echo $status_label(\Arr::get($ticket, 'status_label', \Arr::get($ticket, 'status', ''))); ?> ·
                                    Creado: <?php echo e(\Arr::get($ticket, 'created_at', '-')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
