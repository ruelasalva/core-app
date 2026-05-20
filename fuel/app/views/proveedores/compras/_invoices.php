<div v-show="!loading && tab === 'invoices'">
    <button class="btn btn-primary btn-sm mb-3" @click="newInvoice">
        <i class="bi bi-plus-lg"></i> Subir factura
    </button>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Folio</th>
                <th>OC</th>
                <th>UUID</th>
                <th>Validacion</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="invoice in invoices" :key="invoice.id">
                <td><strong>{{ invoice.folio }}</strong></td>
                <td>{{ invoice.order_folio || '-' }}</td>
                <td class="small">{{ invoice.uuid || '-' }}</td>
                <td><span class="badge" :class="statusClass(invoice.validation_status)">{{ statusLabel(invoice.validation_status) }}</span></td>
                <td>{{ invoice.currency_code }} {{ money(invoice.total) }}</td>
                <td>
                    <button class="btn btn-xs btn-outline-primary" @click="openInvoice(invoice)">Adjuntar</button>
                </td>
            </tr>
            <tr v-if="invoices.length === 0">
                <td colspan="6" class="text-center text-muted">Sin facturas registradas.</td>
            </tr>
        </tbody>
    </table>
</div>
