<div v-show="!loading && tab === 'invoices'">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div class="supplier-section-help mb-2 mb-md-0">
            Consulta facturas enviadas y su estado de revisión. Si una factura fue rechazada, adjunta la corrección o abre un ticket.
        </div>
        <button class="btn btn-primary btn-sm" @click="newInvoice">
            <i class="bi bi-plus-lg"></i> Registrar factura
        </button>
    </div>
    <table class="table table-bordered table-hover portal-table supplier-responsive-table">
        <thead>
            <tr>
                <th>Factura</th>
                <th>OC relacionada</th>
                <th>UUID</th>
                <th>Estado de revisión</th>
                <th>Total</th>
                <th>Saldo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="invoice in invoices" :key="invoice.id">
                <td>
                    <span class="supplier-mobile-card-label">Factura</span>
                    <div class="supplier-row-title">{{ invoice.folio }}</div>
                    <span class="supplier-status-note">{{ invoice.invoice_date || '-' }}</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">OC relacionada</span>
                    {{ invoice.order_folio || 'Sin OC' }}
                </td>
                <td class="small">
                    <span class="supplier-mobile-card-label">UUID</span>
                    {{ invoice.uuid || '-' }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Estado de revisión</span>
                    <span class="badge" :class="statusClass(invoice.validation_status)">{{ validationLabel(invoice.validation_status) }}</span>
                    <span class="supplier-status-note">{{ validationHelp(invoice.validation_status) }}</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Total</span>
                    {{ invoice.currency_code }} {{ money(invoice.total) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Saldo</span>
                    {{ invoice.currency_code }} {{ money(invoice.balance_due) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Acciones</span>
                    <div class="supplier-row-actions">
                        <button class="btn btn-xs btn-outline-primary" @click="openInvoice(invoice)">Adjuntar XML/PDF/evidencia</button>
                        <a class="btn btn-xs btn-outline-warning" v-if="invoice.validation_status === 'rejected'" href="<?php echo Uri::create('proveedores/helpdesk'); ?>">Abrir ticket</a>
                    </div>
                </td>
            </tr>
            <tr v-if="invoices.length === 0">
                <td colspan="7">
                    <div class="supplier-empty-state">Aún no hay facturas registradas. Puedes registrar una factura general o facturar directamente una orden de compra.</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
