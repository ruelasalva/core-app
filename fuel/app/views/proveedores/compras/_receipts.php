<div v-show="!loading && tab === 'receipts'">
    <div class="supplier-section-help mb-3">
        El contrarecibo confirma que compras aceptó la documentación y preparó la programación de pago cuando aplique.
    </div>
    <table class="table table-bordered table-hover portal-table supplier-responsive-table">
        <thead>
            <tr>
                <th>Contrarecibo</th>
                <th>Relacionado con</th>
                <th>Estado</th>
                <th>Pago programado</th>
                <th>Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="receipt in receipts" :key="receipt.id">
                <td>
                    <span class="supplier-mobile-card-label">Contrarecibo</span>
                    <div class="supplier-row-title">{{ receipt.folio }}</div>
                    <span class="supplier-status-note">Emitido: {{ receipt.issue_date || '-' }}</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Relacionado con</span>
                    <span class="text-muted">Relación no disponible en portal</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Estado</span>
                    <span class="badge" :class="statusClass(receipt.status)">{{ statusLabel(receipt.status) }}</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Pago programado</span>
                    <span v-if="receipt.scheduled_payment_date" class="badge badge-info">{{ receipt.scheduled_payment_date }}</span>
                    <span v-else class="text-muted">Pendiente de programación</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Total</span>
                    {{ receipt.currency_code }} {{ money(receipt.total) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Acciones</span>
                    <div class="supplier-row-actions">
                        <button class="btn btn-xs btn-outline-primary" @click="openEvidence('purchase_receipt', receipt.id, receipt.folio)">Adjuntar evidencia</button>
                    </div>
                </td>
            </tr>
            <tr v-if="receipts.length === 0">
                <td colspan="6">
                    <div class="supplier-empty-state">Aún no hay contrarecibos disponibles. Aparecerán cuando compras valide la factura y genere el documento.</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
