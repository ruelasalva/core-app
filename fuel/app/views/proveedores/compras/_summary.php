<div class="portal-kpi-grid">
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">OC abiertas</div>
            <div class="portal-kpi-value">{{ openOrders }}</div>
            <div class="text-muted small">Órdenes disponibles para seguimiento.</div>
        </div>
        <i class="bi bi-cart-check portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Facturas en revisión</div>
            <div class="portal-kpi-value">{{ pendingInvoices }}</div>
            <div class="text-muted small">Facturas recibidas por compras.</div>
        </div>
        <i class="bi bi-hourglass-split portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Facturas validadas</div>
            <div class="portal-kpi-value">{{ validatedInvoices }}</div>
            <div class="text-muted small">Listas para contrarecibo si aplica.</div>
        </div>
        <i class="bi bi-check2-circle portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Contrarecibos</div>
            <div class="portal-kpi-value">{{ issuedReceipts }}</div>
            <div class="text-muted small">Documentos emitidos para pago.</div>
        </div>
        <i class="bi bi-receipt-cutoff portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Pagos programados</div>
            <div class="portal-kpi-value">{{ scheduledReceipts }}</div>
            <div class="text-muted small">Contrarecibos con fecha de pago.</div>
        </div>
        <i class="bi bi-calendar-check portal-kpi-icon"></i>
    </div>
    <div class="portal-kpi">
        <div>
            <div class="portal-kpi-label">Evidencias</div>
            <div class="portal-kpi-value">{{ evidenceCount }}</div>
            <div class="text-muted small">Archivos cargados en el portal.</div>
        </div>
        <i class="bi bi-folder2-open portal-kpi-icon"></i>
    </div>
</div>
