<div id="app-sales">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.quotes || 0 }}</h3>
                    <p>Cotizaciones</p>
                </div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.requested || 0 }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ stats.approved || 0 }}</h3>
                    <p>Aprobadas</p>
                </div>
                <div class="icon"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.rejected || 0 }}</h3>
                    <p>Rechazadas</p>
                </div>
                <div class="icon"><i class="bi bi-x-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Solicitudes de cotizacion</h3>
            <div class="card-tools">
                <button class="btn btn-primary btn-sm" @click="newQuote">
                    <i class="bi bi-plus-lg"></i> Nueva cotizacion
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando ventas...</p>
            </div>

            <table v-show="!loading" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="quote in quotes" :key="quote.id">
                        <td><strong>{{ quote.folio }}</strong><div class="text-muted small">{{ quote.source }}</div></td>
                        <td>{{ quote.party_name || '-' }}<div class="text-muted small">{{ quote.party_email || '' }}</div></td>
                        <td><span class="badge" :class="statusClass(quote.status)">{{ statusLabel(quote.status) }}</span></td>
                        <td>{{ quote.currency_code }} {{ money(quote.total) }}</td>
                        <td>{{ quote.created_label }}</td>
                        <td>
                            <div v-for="item in quote.items" :key="item.sku + item.name" class="small">
                                {{ item.quantity }} x {{ item.name }}
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" @click="openDetail(quote)">Detalle</button>
                                <button class="btn btn-outline-secondary" @click="setStatus(quote, 'reviewed')">Revisada</button>
                                <button class="btn btn-outline-success" @click="setStatus(quote, 'approved')">Aprobar</button>
                                <button class="btn btn-outline-danger" @click="setStatus(quote, 'rejected')">Rechazar</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="quotes.length === 0">
                        <td colspan="7" class="text-center text-muted">Todavia no hay cotizaciones.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-new-quote" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Nueva cotizacion</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-new-quote')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Cliente</label>
                        <select class="form-control" v-model="quoteForm.party_id">
                            <option value="">Selecciona cliente</option>
                            <option v-for="customer in options.customers" :value="customer.value">{{ customer.label }}</option>
                        </select>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label>Producto</label>
                                    <select class="form-control" v-model="lineForm.product_id">
                                        <option value="">Selecciona producto</option>
                                        <option v-for="product in options.products" :value="product.value">{{ product.label }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" min="1" step="1" class="form-control" v-model.number="lineForm.quantity">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-outline-primary btn-block mb-3" @click="addLine">Agregar</button>
                            </div>
                        </div>
                    </div>

                    <table class="table table-sm table-bordered" v-if="quoteForm.items.length">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio base</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in quoteForm.items" :key="index">
                                <td>{{ productLabel(item.product_id) }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ productCurrency(item.product_id) }} {{ money(productPrice(item.product_id)) }}</td>
                                <td class="text-center"><button class="btn btn-xs btn-danger" @click="removeLine(index)">Quitar</button></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="form-group">
                        <label>Notas para el cliente</label>
                        <textarea class="form-control" rows="2" v-model="quoteForm.customer_notes"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notas internas</label>
                        <textarea class="form-control" rows="2" v-model="quoteForm.internal_notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-new-quote')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveQuote">Guardar cotizacion</button>
                </div>
            </div>
        </div>
    </div>

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
                        </div>
                    </div>

                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selected.items" :key="item.sku + item.name">
                                <td>{{ item.sku }}</td>
                                <td>{{ item.name }}</td>
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
                            <option value="requested">Solicitada</option>
                            <option value="reviewed">Revisada</option>
                            <option value="approved">Aprobada</option>
                            <option value="rejected">Rechazada</option>
                            <option value="converted">Convertida</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-quote')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveSelected">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-sales',
        data: {
            loading: true,
            quotes: [],
            selected: null,
            stats: { quotes: 0, requested: 0, reviewed: 0, approved: 0, rejected: 0 },
            options: { customers: [], products: [] },
            quoteForm: { party_id: '', items: [], customer_notes: '', internal_notes: '' },
            lineForm: { product_id: '', quantity: 1 }
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/sales/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                        this.options = data.options || this.options;
                    });
            },
            money(value) {
                return Number(value || 0).toFixed(2);
            },
            statusLabel(status) {
                const labels = {
                    requested: 'Solicitada',
                    reviewed: 'Revisada',
                    approved: 'Aprobada',
                    rejected: 'Rechazada',
                    converted: 'Convertida'
                };
                return labels[status] || status;
            },
            statusClass(status) {
                const classes = {
                    requested: 'badge-warning',
                    reviewed: 'badge-info',
                    approved: 'badge-success',
                    rejected: 'badge-danger',
                    converted: 'badge-primary'
                };
                return classes[status] || 'badge-secondary';
            },
            openDetail(quote) {
                this.selected = JSON.parse(JSON.stringify(quote));
                this.showModal('modal-quote');
            },
            saveSelected() {
                if (!this.selected) return;
                this.setStatus(this.selected, this.selected.status, this.selected.internal_notes, true);
            },
            setStatus(quote, status) {
                fetch('<?php echo Uri::create('admin/sales/update_status'); ?>', window.coreAppFetchOptions({
                    id: quote.id,
                    status: status,
                    internal_notes: quote.internal_notes || ''
                }))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-quote');
                    });
            },
            showModal(id) {
                if (window.jQuery) {
                    $('#' + id).modal('show');
                }
            },
            hideModal(id) {
                if (window.jQuery) {
                    $('#' + id).modal('hide');
                }
            },
            newQuote() {
                this.quoteForm = { party_id: '', items: [], customer_notes: '', internal_notes: '' };
                this.lineForm = { product_id: '', quantity: 1 };
                this.showModal('modal-new-quote');
            },
            addLine() {
                if (!this.lineForm.product_id) return;
                this.quoteForm.items.push({
                    product_id: this.lineForm.product_id,
                    quantity: this.lineForm.quantity || 1
                });
                this.lineForm = { product_id: '', quantity: 1 };
            },
            removeLine(index) {
                this.quoteForm.items.splice(index, 1);
            },
            productById(productId) {
                return (this.options.products || []).find(product => Number(product.value) === Number(productId)) || {};
            },
            productLabel(productId) {
                return this.productById(productId).label || '-';
            },
            productPrice(productId) {
                return this.productById(productId).price || 0;
            },
            productCurrency(productId) {
                return this.productById(productId).currency_code || 'MXN';
            },
            saveQuote() {
                fetch('<?php echo Uri::create('admin/sales/create_quote'); ?>', window.coreAppFetchOptions(this.quoteForm))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-new-quote');
                    });
            }
        }
    });
};
</script>
