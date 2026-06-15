    <div class="modal fade" id="modal-quote" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" v-if="selected">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ selected.folio }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-quote')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Cliente</h6>
                            <p class="mb-1"><strong>{{ selected.party_name || '-' }}</strong></p>
                            <p class="mb-1 text-muted">{{ selected.party_email || '' }}</p>
                            <p class="mb-1 text-muted">{{ selected.party_phone || '' }}</p>
                            <p class="mb-3 text-muted">{{ selected.party_rfc || '' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Resumen</h6>
                            <p class="mb-1"><strong>Estado:</strong> <span class="badge" :class="statusClass(selected.status)">{{ statusLabel(selected.status) }}</span></p>
                            <p class="mb-1"><strong>Fecha:</strong> {{ selected.created_label }}</p>
                            <p class="mb-1"><strong>Vence:</strong> {{ selected.expires_label || '-' }}</p>
                            <p class="mb-3"><strong>Total:</strong> {{ selected.currency_code }} {{ money(selected.total) }}</p>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-success" @click="setStatus(selected, 'approved')" :disabled="selected.status === 'prequote' || selected.status === 'approved' || (selected.orders && selected.orders.length)">
                                    Aprobar y mandar a pedido
                                </button>
                                <button class="btn btn-outline-success" v-if="selected.orders && selected.orders.length" @click="openFulfillment(selected.orders[0])" :disabled="selected.orders[0].status === 'delivered' || selected.orders[0].status === 'closed' || selected.orders[0].status === 'billed'">
                                    Surtir pedido
                                </button>
                            </div>
                        </div>
                    </div>

                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selected.items" :key="item.sku + item.name">
                                <td><img class="quote-thumb" :src="item.image_url || noImage" :alt="item.name"></td>
                                <td>{{ item.sku }}</td>
                                <td>{{ item.name }}<div class="text-muted small">Exist. {{ money(item.available_stock) }}</div></td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ selected.currency_code }} {{ money(item.unit_price) }}</td>
                                <td>{{ selected.currency_code }} {{ money(item.line_total) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="form-group">
                        <label>Notas del cliente</label>
                        <div class="border rounded p-2 bg-light">{{ selected.customer_notes || 'Sin notas.' }}</div>
                    </div>
                    <div class="form-group">
                        <label>Notas internas</label>
                        <textarea class="form-control" rows="3" v-model="selected.internal_notes"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" v-model="selected.status">
                            <option value="prequote">Precotización</option>
                            <option value="requested">Solicitada</option>
                            <option value="approved">Aprobada</option>
                            <option value="rejected">Rechazada</option>
                            <option value="converted">Convertida</option>
                        </select>
                    </div>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <h6 class="mb-0">Flujo comercial</h6>
                            <button class="btn btn-sm btn-outline-success ml-auto" @click="setStatus(selected, 'approved')" :disabled="selected.status === 'prequote' || selected.status === 'approved' || (selected.orders && selected.orders.length)">
                                Aprobar y mandar a pedido
                            </button>
                        </div>
                        <div v-if="!selected.orders || selected.orders.length === 0" class="text-muted small">Sin pedido relacionado.</div>
                        <div v-for="order in selected.orders" :key="order.id" class="border rounded p-2 mb-2 bg-white">
                            <div class="d-flex align-items-center">
                                <strong>{{ order.folio }}</strong>
                                <span class="badge badge-info ml-2">{{ order.status }}</span>
                                <button class="btn btn-xs btn-outline-success ml-auto" @click="openFulfillment(order)" :disabled="order.status === 'delivered' || order.status === 'closed' || order.status === 'billed'">
                                    Surtir
                                </button>
                            </div>
                            <div v-if="!order.deliveries || order.deliveries.length === 0" class="text-muted small mt-1">Sin entrega.</div>
                            <div v-for="delivery in order.deliveries" :key="delivery.id" class="small mt-1">
                                Entrega <strong>{{ delivery.folio }}</strong>
                                <span class="badge badge-secondary">{{ delivery.status }}</span>
                                <button class="btn btn-xs btn-outline-primary ml-2" @click="invoiceDelivery(delivery)" :disabled="delivery.billing_invoice_id > 0">
                                    Facturar entrega
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="selected.status === 'prequote'" class="border rounded p-3 bg-light">
                        <h6>Cerrar con precios</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-control" v-model="closeForm.party_id">
                                    <option value="">Selecciona cliente</option>
                                    <option v-for="customer in options.customers" :value="customer.value">{{ customer.label }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-block" @click="closePrequote">Cerrar cotización</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-quote')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveSelected">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

