<div v-show="!loading && tab === 'receipts'">
    <div class="mb-3 text-muted">
        El contrarecibo confirma que compras aceptó la documentación y preparó la programación de pago cuando aplique.
    </div>
    <table class="table table-bordered table-hover portal-table">
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
                <td>
                    <strong>{{ receipt.folio }}</strong>
                    <span class="supplier-status-note">{{ receipt.issue_date || '-' }}</span>
                </td>
                <td><span class="badge" :class="statusClass(receipt.status)">{{ statusLabel(receipt.status) }}</span></td>
                <td>
                    <span v-if="receipt.scheduled_payment_date" class="badge badge-info">{{ receipt.scheduled_payment_date }}</span>
                    <span v-else class="text-muted">Pendiente de programación</span>
                </td>
                <td>{{ receipt.currency_code }} {{ money(receipt.total) }}</td>
                <td>
                    <div class="supplier-row-actions">
                        <button class="btn btn-xs btn-outline-primary" @click="openEvidence('purchase_receipt', receipt.id, receipt.folio)">Adjuntar evidencia</button>
                    </div>
                </td>
            </tr>
            <tr v-if="receipts.length === 0">
                <td colspan="5"><div class="portal-empty">Sin contrarecibos disponibles. Aparecerán cuando compras valide la factura y genere el documento.</div></td>
            </tr>
        </tbody>
    </table>
</div>
