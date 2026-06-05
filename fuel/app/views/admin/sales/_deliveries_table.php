            <table v-show="!loading && viewMode === 'deliveries'" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Entrega</th>
                        <th>Cliente</th>
                        <th>Pedido</th>
                        <th>Almacen</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="delivery in deliveries" :key="delivery.id">
                        <td><strong>{{ delivery.folio }}</strong></td>
                        <td>{{ delivery.party_name || '-' }}</td>
                        <td>{{ delivery.order_folio || '-' }}</td>
                        <td>{{ delivery.warehouse_name || '-' }}</td>
                        <td>{{ delivery.delivery_date || '-' }}</td>
                        <td><span class="badge" :class="statusClass(delivery.status)">{{ statusLabel(delivery.status) }}</span></td>
                        <td>{{ delivery.currency_code }} {{ money(delivery.total) }}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-primary" @click="invoiceDelivery(delivery)" :disabled="delivery.billing_invoice_id > 0">
                                Facturar
                            </button>
                        </td>
                    </tr>
                    <tr v-if="deliveries.length === 0">
                        <td colspan="8" class="text-center text-muted">Todavia no hay entregas.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
