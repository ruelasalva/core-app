            <div v-show="!loading && tab === 'receipts'">
                <button class="btn btn-primary btn-sm mb-3" @click="newReceipt"><i class="bi bi-plus-lg"></i> Nuevo contrarecibo</button>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Proveedor</th><th>Estado</th><th>Pago programado</th><th>Total</th><th>Pago</th><th>Facturas</th></tr></thead>
                    <tbody>
                        <tr v-for="receipt in receipts" :key="receipt.id">
                            <td><strong>{{ receipt.folio }}</strong></td>
                            <td>{{ receipt.party_name || '-' }}</td>
                            <td><span class="badge" :class="statusClass(receipt.status)">{{ statusLabel(receipt.status) }}</span></td>
                            <td>{{ receipt.scheduled_payment_date || '-' }}</td>
                            <td>{{ receipt.currency_code }} {{ money(receipt.total) }}</td>
                            <td>{{ receipt.payment_folio || '-' }}</td>
                            <td><div v-for="item in receipt.items" class="small">{{ item.invoice_folio }} - {{ money(item.amount) }}</div></td>
                        </tr>
                        <tr v-if="receipts.length === 0"><td colspan="7" class="text-center text-muted">Sin contrarecibos.</td></tr>
                    </tbody>
                </table>
            </div>
