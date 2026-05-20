<div v-show="!loading && tab === 'orders'">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Facturado</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="order in orders" :key="order.id">
                <td><strong>{{ order.folio }}</strong></td>
                <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                <td>{{ order.currency_code }} {{ money(order.total) }}</td>
                <td>{{ order.currency_code }} {{ money(order.invoiced_total) }}</td>
                <td>{{ order.order_date }}</td>
                <td>
                    <button class="btn btn-xs btn-outline-primary" @click="openOrder(order)">Detalle</button>
                </td>
            </tr>
            <tr v-if="orders.length === 0">
                <td colspan="6" class="text-center text-muted">Sin ordenes asignadas.</td>
            </tr>
        </tbody>
    </table>
</div>
