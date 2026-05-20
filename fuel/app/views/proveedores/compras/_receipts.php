<div v-show="!loading && tab === 'receipts'">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Estado</th>
                <th>Pago programado</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="receipt in receipts" :key="receipt.id">
                <td><strong>{{ receipt.folio }}</strong></td>
                <td><span class="badge" :class="statusClass(receipt.status)">{{ statusLabel(receipt.status) }}</span></td>
                <td>{{ receipt.scheduled_payment_date || '-' }}</td>
                <td>{{ receipt.currency_code }} {{ money(receipt.total) }}</td>
                <td>
                    <button class="btn btn-xs btn-outline-primary" @click="openEvidence('purchase_receipt', receipt.id, receipt.folio)">Evidencia</button>
                </td>
            </tr>
            <tr v-if="receipts.length === 0">
                <td colspan="5" class="text-center text-muted">Sin contrarecibos.</td>
            </tr>
        </tbody>
    </table>
</div>
