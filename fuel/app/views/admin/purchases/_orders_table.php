            <div v-show="!loading && tab === 'orders'">
                <button class="btn btn-primary btn-sm mb-3" @click="newOrder"><i class="bi bi-plus-lg"></i> Nueva orden</button>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Proveedor</th><th>Depto/Solicita</th><th>Estado</th><th>Total</th><th>Facturado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.id">
                            <td><strong>{{ order.folio }}</strong></td>
                            <td>{{ order.party_name || '-' }}<div class="text-muted small">{{ order.party_rfc || '' }}</div></td>
                            <td>{{ order.department_name || '-' }}<div class="text-muted small">{{ order.requested_by_name || '-' }}</div></td>
                            <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span><div class="text-muted small">{{ approvalLabel(order) }}</div></td>
                            <td>{{ order.currency_code }} {{ money(order.total) }}</td>
                            <td>{{ order.currency_code }} {{ money(order.invoiced_total) }}</td>
                            <td>{{ order.order_date }}</td>
                            <td>
                                <button class="btn btn-xs btn-outline-primary" @click="openOrder(order)">Detalle</button>
                                <button v-if="['draft','rejected'].indexOf(order.status) >= 0" class="btn btn-xs btn-outline-info" @click="orderAction(order, 'submit_order')">Solicitar</button>
                                <button v-if="order.can_authorize == 1 && ['pending_authorization','draft','rejected'].indexOf(order.status) >= 0" class="btn btn-xs btn-success" @click="orderAction(order, 'authorize_order')">Autorizar</button>
                                <button v-if="order.can_authorize == 1 && order.status === 'pending_authorization'" class="btn btn-xs btn-danger" @click="orderAction(order, 'reject_order')">Rechazar</button>
                                <button v-if="['authorized','partial'].indexOf(order.status) >= 0" class="btn btn-xs btn-secondary" @click="orderAction(order, 'close_order')">Cerrar</button>
                            </td>
                        </tr>
                        <tr v-if="orders.length === 0"><td colspan="8" class="text-center text-muted">Sin ordenes.</td></tr>
                    </tbody>
                </table>
            </div>
