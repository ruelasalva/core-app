<div v-show="!loading && tab === 'invoices'">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div class="text-muted mb-2 mb-md-0">
            Consulta facturas enviadas y su estado de revisión por compras.
        </div>
        <button class="btn btn-primary btn-sm" @click="newInvoice">
            <i class="bi bi-plus-lg"></i> Subir factura
        </button>
    </div>
    <table class="table table-bordered table-hover portal-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>OC</th>
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
                    <strong>{{ invoice.folio }}</strong>
                    <span class="supplier-status-note">{{ invoice.invoice_date || '-' }}</span>
                </td>
                <td>{{ invoice.order_folio || 'Sin OC' }}</td>
                <td class="small">{{ invoice.uuid || '-' }}</td>
                <td>
                    <span class="badge" :class="statusClass(invoice.validation_status)">{{ validationLabel(invoice.validation_status) }}</span>
                    <span class="supplier-status-note">{{ validationHelp(invoice.validation_status) }}</span>
                </td>
                <td>{{ invoice.currency_code }} {{ money(invoice.total) }}</td>
                <td>{{ invoice.currency_code }} {{ money(invoice.balance_due) }}</td>
                <td>
                    <div class="supplier-row-actions">
                        <button class="btn btn-xs btn-outline-primary" @click="openInvoice(invoice)">Adjuntar XML/PDF</button>
                        <button class="btn btn-xs btn-outline-warning" v-if="invoice.validation_status === 'rejected'" @click="openEvidence('purchase_invoice', invoice.id, invoice.folio)">Adjuntar corrección</button>
                    </div>
                </td>
            </tr>
            <tr v-if="invoices.length === 0">
                <td colspan="7"><div class="portal-empty">Sin facturas registradas. Usa "Subir factura" o factura directamente una OC para iniciar la revisión.</div></td>
            </tr>
        </tbody>
    </table>
</div>
