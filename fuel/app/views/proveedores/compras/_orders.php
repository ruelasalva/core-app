<div v-show="!loading && tab === 'orders'">
    <div class="supplier-section-help mb-3">
        Revisa tus órdenes autorizadas. Desde aquí puedes facturar una OC, adjuntar evidencia de entrega o consultar las partidas autorizadas.
    </div>
    <table class="table table-bordered table-hover portal-table supplier-responsive-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Facturado</th>
                <th>Pendiente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="order in orders" :key="order.id">
                <td>
                    <span class="supplier-mobile-card-label">Folio</span>
                    <div class="supplier-row-title">{{ order.folio }}</div>
                    <span class="supplier-status-note" v-if="order.expected_date">Entrega esperada: {{ order.expected_date }}</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Fecha</span>
                    {{ order.order_date || '-' }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Estado</span>
                    <span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Total</span>
                    {{ order.currency_code }} {{ money(order.total) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Facturado</span>
                    {{ order.currency_code }} {{ money(order.invoiced_total) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Pendiente</span>
                    {{ order.currency_code }} {{ money(order.balance_total) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Acciones</span>
                    <div class="supplier-row-actions">
                        <button class="btn btn-xs btn-primary" @click="newInvoiceForOrder(order)">Facturar esta OC</button>
                        <button class="btn btn-xs btn-outline-primary" @click="openEvidence('purchase_order', order.id, order.folio)">Adjuntar evidencia</button>
                        <button class="btn btn-xs btn-outline-secondary" @click="openOrder(order)">Ver partidas</button>
                    </div>
                </td>
            </tr>
            <tr v-if="orders.length === 0">
                <td colspan="7">
                    <div class="supplier-empty-state">No hay órdenes de compra visibles por ahora. Cuando compras te asigne una OC aparecerá en esta sección.</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
