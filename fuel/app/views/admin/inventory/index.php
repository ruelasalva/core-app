<div id="app-inventory" class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">Inventario y almacenes</h3>
        <button class="btn btn-primary btn-sm ml-auto" @click="openMovement">
            <i class="bi bi-plus-circle"></i> Movimiento
        </button>
    </div>
    <div class="card-body">
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="loading" class="text-muted">Cargando inventario...</div>

        <div v-if="!loading">
            <div class="row">
                <div class="col-md-3 col-6" v-for="box in statBoxes" :key="box.key">
                    <div class="small-box" :class="box.className">
                        <div class="inner">
                            <h3>{{ stats[box.key] || 0 }}</h3>
                            <p>{{ box.label }}</p>
                        </div>
                        <div class="icon"><i :class="box.icon"></i></div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><button class="nav-link" :class="{ active: tab === 'stock' }" @click="tab = 'stock'">Existencias</button></li>
                <li class="nav-item"><button class="nav-link" :class="{ active: tab === 'deliveries' }" @click="tab = 'deliveries'">Entregas</button></li>
                <li class="nav-item"><button class="nav-link" :class="{ active: tab === 'movements' }" @click="tab = 'movements'">Movimientos</button></li>
                <li class="nav-item"><button class="nav-link" :class="{ active: tab === 'audit' }" @click="tab = 'audit'">Auditoria stock</button></li>
            </ul>

            <div v-if="tab === 'stock'">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0">Existencias por producto</h5>
                    <input class="form-control form-control-sm ml-auto" style="max-width: 280px;" v-model="filters.stock" placeholder="Buscar SKU o producto">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Almacen</th>
                                <th>Producto</th>
                                <th class="text-right">Existencia</th>
                                <th class="text-right">Reservado</th>
                                <th class="text-right">Disponible</th>
                                <th>Ultima actualizacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in filteredStock" :key="row.product_id">
                                <td>{{ row.sku }}</td>
                                <td>{{ row.warehouse_name || '-' }}</td>
                                <td>{{ row.name }}</td>
                                <td class="text-right">{{ qty(row.stock_quantity) }}</td>
                                <td class="text-right">{{ qty(row.stock_reserved) }}</td>
                                <td class="text-right">{{ qty(Number(row.stock_quantity || 0) - Number(row.stock_reserved || 0)) }}</td>
                                <td>{{ date(row.stock_updated_at) }}</td>
                            </tr>
                            <tr v-if="filteredStock.length === 0"><td colspan="7" class="text-muted">Sin productos.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="tab === 'deliveries'">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0">Entregas de venta</h5>
                    <span class="ml-auto text-muted small">La entrega es el documento que mueve inventario.</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Entrega</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Almacen</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="text-right">Total</th>
                                <th>Factura</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in deliveries" :key="row.id">
                                <td>{{ row.folio }}</td>
                                <td>{{ row.order_folio || '-' }}</td>
                                <td>{{ row.party_name || '-' }}</td>
                                <td>{{ row.warehouse_name || '-' }}</td>
                                <td>{{ row.delivery_date }}</td>
                                <td><span class="badge badge-info">{{ row.status }}</span></td>
                                <td class="text-right">{{ money(row.total) }}</td>
                                <td>{{ row.billing_invoice_id > 0 ? ('Factura #' + row.billing_invoice_id) : 'Pendiente' }}</td>
                            </tr>
                            <tr v-if="deliveries.length === 0"><td colspan="8" class="text-muted">Sin entregas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="tab === 'movements'">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0">Movimientos de almacen</h5>
                    <input class="form-control form-control-sm ml-auto" style="max-width: 280px;" v-model="filters.movements" placeholder="Buscar movimiento">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Almacen</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th class="text-right">Cantidad</th>
                                <th>Referencia</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in filteredMovements" :key="row.id">
                                <td>{{ date(row.created_at) }}</td>
                                <td>{{ row.warehouse_name }}</td>
                                <td>{{ row.sku }} {{ row.product_name }}</td>
                                <td><span class="badge" :class="movementClass(row.movement_type)">{{ movementLabel(row.movement_type) }}</span></td>
                                <td class="text-right" :class="Number(row.quantity) < 0 ? 'text-danger' : 'text-success'">{{ qty(row.quantity) }}</td>
                                <td>{{ row.related_entity_type }} #{{ row.related_entity_id }}</td>
                                <td>{{ row.notes }}</td>
                            </tr>
                            <tr v-if="filteredMovements.length === 0"><td colspan="7" class="text-muted">Sin movimientos.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="tab === 'audit'">
                <div class="alert alert-info py-2">
                    Compara existencia actual contra la suma de movimientos registrados. Diferencias indican carga inicial, ajuste pendiente o movimientos anteriores a la bitacora.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th class="text-right">Existencia</th>
                                <th class="text-right">Movimientos</th>
                                <th class="text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in audit" :key="row.product_id" :class="{ 'table-warning': Number(row.difference || 0) !== 0 }">
                                <td>{{ row.sku }}</td>
                                <td>{{ row.name }}</td>
                                <td class="text-right">{{ qty(row.stock_quantity) }}</td>
                                <td class="text-right">{{ qty(row.movement_balance) }}</td>
                                <td class="text-right">{{ qty(row.difference) }}</td>
                            </tr>
                            <tr v-if="audit.length === 0"><td colspan="5" class="text-muted">Sin auditoria.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-inventory-movement" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Movimiento de inventario</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select class="form-control" v-model="movementForm.movement_type">
                            <option value="adjustment_in">Entrada por ajuste</option>
                            <option value="purchase_in">Entrada por compra</option>
                            <option value="adjustment_out">Salida por ajuste</option>
                            <option value="sale_out">Salida por venta</option>
                            <option value="damage_out">Merma / dano</option>
                            <option value="transfer">Traspaso entre almacenes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Almacen origen</label>
                        <select class="form-control" v-model="movementForm.warehouse_id">
                            <option value="0">Selecciona almacen</option>
                            <option v-for="warehouse in warehouses" :value="warehouse.id">{{ warehouse.code }} - {{ warehouse.name }}</option>
                        </select>
                    </div>
                    <div class="form-group" v-if="movementForm.movement_type === 'transfer'">
                        <label>Almacen destino</label>
                        <select class="form-control" v-model="movementForm.target_warehouse_id">
                            <option value="0">Selecciona almacen destino</option>
                            <option v-for="warehouse in warehouses" :value="warehouse.id">{{ warehouse.code }} - {{ warehouse.name }}</option>
                        </select>
                    </div>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <h6>Partidas del movimiento</h6>
                        <div class="row align-items-end">
                            <div class="form-group col-md-7">
                                <label>Producto</label>
                                <select class="form-control" v-model="movementLine.product_id">
                                    <option value="0">Selecciona producto</option>
                                    <option v-for="product in products" :value="product.id">{{ product.sku }} - {{ product.name }}</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Cantidad</label>
                                <input class="form-control" type="number" min="0.0001" step="0.0001" v-model.number="movementLine.quantity">
                            </div>
                            <div class="form-group col-md-2">
                                <button class="btn btn-outline-primary btn-block" type="button" @click="addMovementLine">Agregar</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-right">Cantidad</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in movementForm.items" :key="index">
                                        <td>{{ productLabel(item.product_id) }}</td>
                                        <td class="text-right">{{ qty(item.quantity) }}</td>
                                        <td class="text-right"><button class="btn btn-xs btn-outline-danger" type="button" @click="removeMovementLine(index)">Quitar</button></td>
                                    </tr>
                                    <tr v-if="!movementForm.items || movementForm.items.length === 0">
                                        <td colspan="3" class="text-muted">Agrega uno o varios productos al movimiento.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" rows="3" v-model="movementForm.notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveMovement">Guardar movimiento</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#app-inventory',
    data: {
        loading: true,
        error: '',
        tab: 'stock',
        warehouses: [],
        products: [],
        stock: [],
        movements: [],
        deliveries: [],
        audit: [],
        stats: {},
        filters: { stock: '', movements: '' },
        movementForm: {},
        movementLine: {},
        statBoxes: [
            { key: 'products', label: 'Productos', icon: 'bi bi-box-seam', className: 'bg-info' },
            { key: 'warehouses', label: 'Almacenes', icon: 'bi bi-building', className: 'bg-secondary' },
            { key: 'movements', label: 'Movimientos', icon: 'bi bi-arrow-left-right', className: 'bg-success' },
            { key: 'pending_deliveries', label: 'Entregas pendientes', icon: 'bi bi-truck', className: 'bg-warning' }
        ]
    },
    computed: {
        filteredStock: function() {
            var q = (this.filters.stock || '').toLowerCase();
            return this.stock.filter(function(row) {
                return [row.sku, row.name, row.warehouse_name].join(' ').toLowerCase().indexOf(q) !== -1;
            });
        },
        filteredMovements: function() {
            var q = (this.filters.movements || '').toLowerCase();
            return this.movements.filter(function(row) {
                return [row.sku, row.product_name, row.warehouse_name, row.movement_type, row.notes].join(' ').toLowerCase().indexOf(q) !== -1;
            });
        }
    },
    mounted: function() { this.load(); },
    methods: {
        load: function() {
            this.loading = true;
            fetch('<?php echo Uri::create('admin/inventory/data'); ?>')
                .then(function(res) { return res.json(); })
                .then(data => {
                    if (data.error) this.error = data.error;
                    this.warehouses = data.warehouses || [];
                    this.products = data.products || [];
                    this.stock = data.stock || [];
                    this.movements = data.movements || [];
                    this.deliveries = data.deliveries || [];
                    this.audit = data.audit || [];
                    this.stats = data.stats || {};
                })
                .catch(() => { this.error = 'No se pudo cargar inventario.'; })
                .finally(() => { this.loading = false; });
        },
        openMovement: function() {
            var warehouse = this.warehouses.find(function(item) { return Number(item.is_default || 0) === 1; }) || this.warehouses[0] || {};
            this.movementForm = { movement_type: 'adjustment_in', warehouse_id: warehouse.id || 0, target_warehouse_id: 0, items: [], notes: '' };
            this.movementLine = { product_id: 0, quantity: 1 };
            $('#modal-inventory-movement').modal('show');
        },
        addMovementLine: function() {
            if (!this.movementLine.product_id || Number(this.movementLine.quantity || 0) <= 0) {
                alert('Selecciona producto y cantidad.');
                return;
            }
            this.movementForm.items.push({
                product_id: Number(this.movementLine.product_id),
                quantity: Number(this.movementLine.quantity)
            });
            this.movementLine = { product_id: 0, quantity: 1 };
        },
        removeMovementLine: function(index) {
            this.movementForm.items.splice(index, 1);
        },
        productLabel: function(productId) {
            var product = this.products.find(function(item) { return Number(item.id) === Number(productId); }) || {};
            return product.id ? ((product.sku || 'Sin SKU') + ' - ' + product.name) : '-';
        },
        saveMovement: function() {
            this.error = '';
            if (!this.movementForm.items || this.movementForm.items.length === 0) {
                alert('Agrega al menos un producto.');
                return;
            }
            fetch('<?php echo Uri::create('admin/inventory/save_movement'); ?>', window.coreAppFetchOptions(this.movementForm))
                .then(function(res) { return res.json(); })
                .then(data => {
                    if (data.error) {
                        this.error = data.error;
                        return;
                    }
                    this.stock = data.stock || [];
                    this.movements = data.movements || [];
                    this.audit = data.audit || [];
                    this.stats = data.stats || this.stats;
                    $('#modal-inventory-movement').modal('hide');
                })
                .catch(() => { this.error = 'No se pudo guardar el movimiento.'; });
        },
        movementLabel: function(type) {
            var labels = {
                adjustment_in: 'Entrada ajuste',
                adjustment_out: 'Salida ajuste',
                purchase_in: 'Entrada compra',
                sale_out: 'Salida venta',
                damage_out: 'Merma',
                delivery_out: 'Entrega venta',
                transfer_in: 'Traspaso entrada',
                transfer_out: 'Traspaso salida'
            };
            return labels[type] || type;
        },
        movementClass: function(type) {
            return Number(String(type).indexOf('_out')) >= 0 || type === 'damage_out' ? 'badge-danger' : 'badge-success';
        },
        qty: function(value) { return Number(value || 0).toFixed(2); },
        money: function(value) { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0)); },
        date: function(value) {
            if (!value) return '';
            if (String(value).indexOf('-') > -1) return value;
            return new Date(Number(value) * 1000).toLocaleString('es-MX');
        }
    }
});
</script>
