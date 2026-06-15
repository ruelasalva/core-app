    <div class="modal fade" id="modal-fulfillment" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" v-if="selectedOrder">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Surtir pedido {{ selectedOrder.folio }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-fulfillment')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Almacen de salida</label>
                        <select class="form-control" v-model="deliveryForm.warehouse_id">
                            <option v-for="warehouse in options.warehouses" :value="warehouse.value">{{ warehouse.label }}</option>
                        </select>
                    </div>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th></th><th>SKU</th><th>Producto</th><th>Pedido</th><th>Surtido</th><th>Pendiente</th><th>A surtir</th></tr></thead>
                        <tbody>
                            <tr v-for="item in deliveryForm.items" :key="item.order_item_id">
                                <td><img class="quote-thumb" :src="item.image_url || noImage" :alt="item.name"></td>
                                <td>{{ item.sku }}</td>
                                <td>{{ item.name }}<div class="text-muted small">Disponible catálogo: {{ money(item.available_stock) }}</div></td>
                                <td>{{ money(item.ordered_quantity) }}</td>
                                <td>{{ money(item.delivered_quantity) }}</td>
                                <td><strong>{{ money(item.pending_quantity) }}</strong></td>
                                <td><input class="form-control form-control-sm" type="number" min="0" :max="item.pending_quantity" step="1" v-model.number="item.quantity"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="alert alert-warning py-2 mb-0">
                        Si surtimos menos del pendiente, el pedido queda parcial y el resto queda en backorder esperando inventario.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-fulfillment')">Cerrar</button>
                    <button class="btn btn-success" @click="createDeliveryFromOrder()">Crear entrega</button>
                </div>
            </div>
        </div>
    </div>
