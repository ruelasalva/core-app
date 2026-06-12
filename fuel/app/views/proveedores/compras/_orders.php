<div v-show="!loading && tab === 'orders'">
    <div class="mb-3 text-muted">
        Revisa tus órdenes autorizadas. Desde aquí puedes registrar una factura contra la OC o adjuntar evidencia de entrega/remisión.
    </div>
    <table class="table table-bordered table-hover portal-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Facturado</th>
                <th>Pendiente</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="order in orders" :key="order.id">
                <td>
                    <strong>{{ order.folio }}</strong>
                    <span class="supplier-status-note" v-if="order.expected_date">Entrega esperada: {{ order.expected_date }}</span>
                </td>
                <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                <td>{{ order.currency_code }} {{ money(order.total) }}</td>
                <td>{{ order.currency_code }} {{ money(order.invoiced_total) }}</td>
                <td>{{ order.currency_code }} {{ money(order.balance_total) }}</td>
                <td>{{ order.order_date }}</td>
                <td>
                    <div class="supplier-row-actions">
                        <button class="btn btn-xs btn-outline-secondary" @click="openOrder(order)">Detalle</button>
                        <button class="btn btn-xs btn-primary" @click="newInvoiceForOrder(order)">Facturar esta OC</button>
                        <button class="btn btn-xs btn-outline-primary" @click="openEvidence('purchase_order', order.id, order.folio)">Adjuntar evidencia</button>
                    </div>
                </td>
            </tr>
            <tr v-if="orders.length === 0">
                <td colspan="7"><div class="portal-empty">Sin órdenes asignadas para este proveedor.</div></td>
            </tr>
        </tbody>
    </table>
</div>
