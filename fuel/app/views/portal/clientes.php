<div id="app-clientes">
    <div class="row">
        <div class="col-md-3"><div class="card card-primary card-outline"><div class="card-body"><div class="text-muted small">CFDI visibles</div><div class="h3 mb-0">{{ stats.cfdi || 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-info card-outline"><div class="card-body"><div class="text-muted small">Cotizaciones</div><div class="h3 mb-0">{{ stats.quotes || 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-success card-outline"><div class="card-body"><div class="text-muted small">Pedidos</div><div class="h3 mb-0">{{ stats.orders || 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-warning card-outline"><div class="card-body"><div class="text-muted small">Saldo pendiente</div><div class="h5 mb-0">{{ money(stats.open_balance) }}</div></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'account'}" href="#" @click.prevent="tab = 'account'">Estado de cuenta</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'cfdi'}" href="#" @click.prevent="tab = 'cfdi'">CFDI</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'quotes'}" href="#" @click.prevent="tab = 'quotes'">Cotizaciones</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'orders'}" href="#" @click.prevent="tab = 'orders'">Pedidos</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'new_quote'}" href="#" @click.prevent="tab = 'new_quote'">Nueva cotizacion</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-4"><div class="spinner-border text-primary"></div></div>

            <div v-show="!loading && tab === 'account'">
                <h2 class="h6">Facturas</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Folio</th><th>Fecha</th><th>Vence</th><th>Estado</th><th class="text-right">Total</th><th class="text-right">Saldo</th></tr></thead>
                        <tbody>
                            <tr v-for="invoice in account.invoices" :key="invoice.id">
                                <td><strong>{{ invoice.folio }}</strong></td>
                                <td>{{ invoice.issue_label }}</td>
                                <td><span :class="invoice.is_overdue == 1 ? 'text-danger font-weight-bold' : ''">{{ invoice.due_label || '-' }}</span></td>
                                <td><span class="badge" :class="statusClass(invoice.status)">{{ statusLabel(invoice.status) }}</span></td>
                                <td class="text-right">{{ money(invoice.total, invoice.currency_code) }}</td>
                                <td class="text-right">{{ money(invoice.balance_due, invoice.currency_code) }}</td>
                            </tr>
                            <tr v-if="account.invoices.length === 0"><td colspan="6" class="text-center text-muted">Sin facturas disponibles.</td></tr>
                        </tbody>
                    </table>
                </div>

                <h2 class="h6 mt-4">Pagos registrados</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Folio</th><th>Fecha</th><th>Referencia</th><th>Estado</th><th class="text-right">Monto</th></tr></thead>
                        <tbody>
                            <tr v-for="payment in account.payments" :key="payment.id">
                                <td><strong>{{ payment.folio }}</strong></td>
                                <td>{{ payment.payment_label }}</td>
                                <td>{{ payment.reference || '-' }}</td>
                                <td><span class="badge" :class="statusClass(payment.status)">{{ statusLabel(payment.status) }}</span></td>
                                <td class="text-right">{{ money(payment.amount, payment.currency_code) }}</td>
                            </tr>
                            <tr v-if="account.payments.length === 0"><td colspan="5" class="text-center text-muted">Sin pagos registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="!loading && tab === 'cfdi'">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Fecha</th><th>Tipo</th><th>Serie/Folio</th><th>UUID</th><th class="text-right">Total</th><th>Estado</th></tr></thead>
                        <tbody>
                            <tr v-for="item in cfdi" :key="item.id">
                                <td>{{ item.issued_label }}</td>
                                <td><span class="badge badge-secondary">{{ voucherLabel(item.voucher_type) }}</span></td>
                                <td>{{ [item.serie, item.folio].filter(Boolean).join('-') }}</td>
                                <td><code>{{ item.uuid }}</code></td>
                                <td class="text-right">{{ money(item.total, item.currency) }}</td>
                                <td><span class="badge" :class="item.sat_status === 'cancelado' ? 'badge-danger' : 'badge-success'">{{ item.sat_status }}</span></td>
                            </tr>
                            <tr v-if="cfdi.length === 0"><td colspan="6" class="text-center text-muted">Sin CFDI visibles.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="!loading && tab === 'quotes'">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Folio</th><th>Estado</th><th>Fecha</th><th>Vence</th><th class="text-right">Total</th><th>Productos</th></tr></thead>
                        <tbody>
                            <tr v-for="quote in quotes" :key="quote.id">
                                <td><strong>{{ quote.folio }}</strong><div class="text-muted small">{{ quote.source }}</div></td>
                                <td><span class="badge" :class="statusClass(quote.status)">{{ statusLabel(quote.status) }}</span></td>
                                <td>{{ quote.created_label }}</td>
                                <td>{{ quote.expires_label }}</td>
                                <td class="text-right">{{ money(quote.total, quote.currency_code) }}</td>
                                <td><div v-for="item in quote.items" class="small">{{ item.quantity }} x {{ item.name }}</div></td>
                            </tr>
                            <tr v-if="quotes.length === 0"><td colspan="6" class="text-center text-muted">Sin cotizaciones.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="!loading && tab === 'orders'">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Folio</th><th>Estado</th><th>Fecha</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            <tr v-for="order in orders" :key="order.id">
                                <td><strong>{{ order.folio }}</strong></td>
                                <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                                <td>{{ order.created_label }}</td>
                                <td class="text-right">{{ money(order.total, order.currency_code) }}</td>
                            </tr>
                            <tr v-if="orders.length === 0"><td colspan="4" class="text-center text-muted">Sin pedidos disponibles.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="!loading && tab === 'new_quote'">
                <div class="border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-7"><label>Producto</label><select class="form-control" v-model="line.product_id"><option value="">Selecciona</option><option v-for="product in options.products" :value="product.value">{{ product.label }} - {{ money(product.price, product.currency_code) }}</option></select></div>
                        <div class="col-md-3"><label>Cantidad</label><input type="number" min="1" step="1" class="form-control" v-model.number="line.quantity"></div>
                        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary btn-block" @click="addLine">Agregar</button></div>
                    </div>
                </div>
                <table class="table table-sm table-bordered" v-if="form.items.length">
                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(item, index) in form.items" :key="index">
                            <td>{{ productLabel(item.product_id) }}</td>
                            <td>{{ item.quantity }}</td>
                            <td>{{ money(productPrice(item.product_id), productCurrency(item.product_id)) }}</td>
                            <td>{{ money(productPrice(item.product_id) * item.quantity, productCurrency(item.product_id)) }}</td>
                            <td class="text-center"><button class="btn btn-xs btn-danger" @click="form.items.splice(index, 1)">Quitar</button></td>
                        </tr>
                    </tbody>
                </table>
                <label>Comentarios</label>
                <textarea class="form-control" rows="3" v-model="form.customer_notes"></textarea>
                <button class="btn btn-primary mt-3" @click="sendQuote">Enviar cotizacion</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#app-clientes',
        data: {
            loading: true,
            error: '',
            tab: <?php echo json_encode(isset($initial_tab) ? $initial_tab : 'account'); ?>,
            stats: {},
            account: { invoices: [], payments: [], balance_due: 0, overdue_balance: 0 },
            cfdi: [],
            quotes: [],
            orders: [],
            options: { products: [] },
            form: { items: [], customer_notes: '' },
            line: { product_id: '', quantity: 1 }
        },
        mounted: function() { this.load(); },
        methods: {
            load: function() {
                this.loading = true;
                fetch('<?php echo Uri::create('clientes/data'); ?>')
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        this.loading = false;
                        if (data.error) { this.error = data.error; return; }
                        this.stats = data.stats || {};
                        this.account = data.account || this.account;
                        this.cfdi = data.cfdi || [];
                        this.quotes = data.quotes || [];
                        this.orders = data.orders || [];
                        this.options = data.options || this.options;
                    })
                    .catch(() => { this.loading = false; this.error = 'No se pudo cargar el portal.'; });
            },
            addLine: function() {
                if (!this.line.product_id) return;
                this.form.items.push({ product_id: this.line.product_id, quantity: this.line.quantity || 1 });
                this.line = { product_id: '', quantity: 1 };
            },
            sendQuote: function() {
                fetch('<?php echo Uri::create('clientes/quote_request'); ?>', window.coreAppFetchOptions(this.form))
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.form = { items: [], customer_notes: '' };
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                        this.tab = 'quotes';
                    });
            },
            productById: function(id) { return (this.options.products || []).find(function(p) { return Number(p.value) === Number(id); }) || {}; },
            productLabel: function(id) { return this.productById(id).label || '-'; },
            productPrice: function(id) { return Number(this.productById(id).price || 0); },
            productCurrency: function(id) { return this.productById(id).currency_code || 'MXN'; },
            voucherLabel: function(type) { return ({ I: 'Ingreso', E: 'Egreso', T: 'Traslado', P: 'Pago', N: 'Nomina' })[type] || type; },
            statusLabel: function(status) { return ({ requested: 'Solicitada', reviewed: 'Revisada', approved: 'Aprobada', rejected: 'Rechazada', converted: 'Convertida', draft: 'Borrador', issued: 'Emitida', paid: 'Pagada', pending: 'Pendiente', cancelled: 'Cancelada', delivered: 'Entregado', shipped: 'Enviado' })[status] || status; },
            statusClass: function(status) { if (['approved', 'paid', 'issued'].indexOf(status) >= 0) return 'badge-success'; if (['rejected', 'cancelled'].indexOf(status) >= 0) return 'badge-danger'; if (['requested', 'pending', 'draft'].indexOf(status) >= 0) return 'badge-warning'; return 'badge-info'; },
            money: function(value, currency) { return (currency || 'MXN') + ' ' + Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        }
    });
});
</script>
