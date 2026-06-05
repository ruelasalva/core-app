            <table v-show="!loading && viewMode === 'orders'" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Cotizacion</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Pendiente</th>
                        <th>Backorder</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders" :key="order.id">
                        <td><strong>{{ order.folio }}</strong></td>
                        <td>{{ order.party_name || '-' }}</td>
                        <td>{{ order.quote_folio || '-' }}</td>
                        <td>{{ order.order_date || '-' }}</td>
                        <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                        <td>{{ order.currency_code }} {{ money(order.total) }}</td>
                        <td>{{ money(order.pending_quantity) }}</td>
                        <td><span class="badge" :class="order.backorder == 1 ? 'badge-warning' : 'badge-light'">{{ order.backorder == 1 ? 'Si' : 'No' }}</span></td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-success" @click="openFulfillment(order)" :disabled="order.status === 'delivered' || order.status === 'closed' || order.status === 'billed' || Number(order.pending_quantity || 0) <= 0">
                                Surtir
                            </button>
                        </td>
                    </tr>
                    <tr v-if="orders.length === 0">
                        <td colspan="9" class="text-center text-muted">Todavia no hay pedidos.</td>
                    </tr>
                </tbody>
            </table>
