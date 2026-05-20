<?php
$no_image_svg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="360" height="260" viewBox="0 0 360 260"><rect width="360" height="260" fill="#eef3f7"/><path d="M72 178h216l-64-82-48 60-34-44-70 66z" fill="#cbd5e1"/><circle cx="130" cy="86" r="24" fill="#cbd5e1"/><text x="180" y="226" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" fill="#64748b">Sin imagen</text></svg>');
?>
<style>
    .portal-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .quote-cta { display: flex; justify-content: space-between; gap: 16px; align-items: center; border-left: 4px solid var(--portal-primary); }
    .export-tools { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; margin-bottom: 10px; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; }
    .product-card { border: 1px solid #dde3ea; border-radius: 8px; overflow: hidden; background: #fff; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
    .product-card:hover, .product-card.active { border-color: var(--portal-primary); box-shadow: 0 6px 18px rgba(15, 23, 42, .08); }
    .product-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; background: #eef3f7; }
    .product-card-body { padding: 10px; min-height: 104px; }
    .product-card-title { font-size: .9rem; font-weight: 700; line-height: 1.25; margin-bottom: 6px; }
    .product-thumb { width: 52px; height: 42px; object-fit: cover; border: 1px solid #dde3ea; border-radius: 6px; background: #eef3f7; }
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100%; padding: 24px; }
    }
</style>
<div id="app-clientes">
    <div class="card quote-cta">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h5 mb-1">Portal de clientes</h1>
                <div class="text-muted">Consulta tu estado de cuenta y solicita cotizaciones desde el catalogo publicado.</div>
            </div>
            <button class="btn btn-primary mt-2 mt-md-0" @click="tab = 'new_quote'">
                Nueva cotizacion
            </button>
        </div>
    </div>

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
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <h2 class="h6 mb-2">Facturas</h2>
                    <div class="export-tools">
                        <button class="btn btn-xs btn-outline-secondary" @click="exportAccountInvoices('csv')">CSV</button>
                        <button class="btn btn-xs btn-outline-success" @click="exportAccountInvoices('xls')">Excel</button>
                        <button class="btn btn-xs btn-outline-danger" @click="exportAccountInvoices('pdf')">PDF</button>
                    </div>
                </div>
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

                <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 mb-2">
                    <h2 class="h6 mb-2">Pagos registrados</h2>
                    <div class="export-tools">
                        <button class="btn btn-xs btn-outline-secondary" @click="exportPayments('csv')">CSV</button>
                        <button class="btn btn-xs btn-outline-success" @click="exportPayments('xls')">Excel</button>
                        <button class="btn btn-xs btn-outline-danger" @click="exportPayments('pdf')">PDF</button>
                    </div>
                </div>
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
                <div class="export-tools">
                    <button class="btn btn-xs btn-outline-secondary" @click="exportCfdi('csv')">CSV</button>
                    <button class="btn btn-xs btn-outline-success" @click="exportCfdi('xls')">Excel</button>
                    <button class="btn btn-xs btn-outline-danger" @click="exportCfdi('pdf')">PDF</button>
                </div>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <button class="btn btn-primary btn-sm" @click="tab = 'new_quote'">Nueva cotizacion</button>
                    <div class="export-tools mb-0">
                        <button class="btn btn-xs btn-outline-secondary" @click="exportQuotes('csv')">CSV</button>
                        <button class="btn btn-xs btn-outline-success" @click="exportQuotes('xls')">Excel</button>
                        <button class="btn btn-xs btn-outline-danger" @click="exportQuotes('pdf')">PDF</button>
                    </div>
                </div>
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
                                <td>
                                    <div v-for="item in quote.items" class="d-flex align-items-center mb-1">
                                        <img class="product-thumb mr-2" :src="productImage(item)" :alt="item.name">
                                        <div class="small">{{ item.quantity }} x {{ item.name }}</div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="quotes.length === 0"><td colspan="6" class="text-center text-muted">Sin cotizaciones.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="!loading && tab === 'orders'">
                <div class="export-tools">
                    <button class="btn btn-xs btn-outline-secondary" @click="exportOrders('csv')">CSV</button>
                    <button class="btn btn-xs btn-outline-success" @click="exportOrders('xls')">Excel</button>
                    <button class="btn btn-xs btn-outline-danger" @click="exportOrders('pdf')">PDF</button>
                </div>
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
                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap">
                    <div><strong>Nueva cotizacion</strong><div class="text-muted small">Selecciona productos del catalogo, ajusta cantidades y envia tu solicitud a ventas.</div></div>
                    <button class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" @click="tab = 'quotes'">Ver seguimiento</button>
                </div>
                <div class="border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-7"><label>Producto</label><select class="form-control" v-model="line.product_id"><option value="">Selecciona</option><option v-for="product in options.products" :value="product.value">{{ product.label }} - {{ money(product.price, product.currency_code) }}</option></select></div>
                        <div class="col-md-3"><label>Cantidad</label><input type="number" min="1" step="1" class="form-control" v-model.number="line.quantity"></div>
                        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary btn-block" @click="addLine">Agregar</button></div>
                    </div>
                </div>
                <div class="product-grid mb-3" v-if="options.products.length">
                    <div class="product-card" v-for="product in options.products" :key="product.value" :class="{active: Number(line.product_id) === Number(product.value)}" @click="selectProduct(product)">
                        <img :src="product.image_url || noImage" :alt="product.label">
                        <div class="product-card-body">
                            <div class="product-card-title">{{ product.label }}</div>
                            <div class="text-muted small">{{ money(product.price, product.currency_code) }}</div>
                            <button class="btn btn-xs btn-outline-primary mt-2" @click.stop="quickAdd(product)">Agregar</button>
                        </div>
                    </div>
                </div>
                <table class="table table-sm table-bordered" v-if="form.items.length">
                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(item, index) in form.items" :key="index">
                            <td><img class="product-thumb mr-2" :src="productImage(productById(item.product_id))" :alt="productLabel(item.product_id)">{{ productLabel(item.product_id) }}</td>
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
            line: { product_id: '', quantity: 1 },
            noImage: <?php echo json_encode($no_image_svg); ?>
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
            selectProduct: function(product) {
                this.line.product_id = product.value;
            },
            quickAdd: function(product) {
                this.form.items.push({ product_id: product.value, quantity: this.line.quantity || 1 });
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
            productImage: function(item) { return (item && (item.image_url || item.image_path)) ? (item.image_url || item.image_path) : this.noImage; },
            exportAccountInvoices: function(type) {
                this.exportRows('facturas', ['Folio', 'Fecha', 'Vence', 'Estado', 'Total', 'Saldo'], (this.account.invoices || []).map(row => [row.folio, row.issue_label, row.due_label || '', this.statusLabel(row.status), row.total, row.balance_due]), type);
            },
            exportPayments: function(type) {
                this.exportRows('pagos', ['Folio', 'Fecha', 'Referencia', 'Estado', 'Monto'], (this.account.payments || []).map(row => [row.folio, row.payment_label, row.reference || '', this.statusLabel(row.status), row.amount]), type);
            },
            exportCfdi: function(type) {
                this.exportRows('cfdi', ['Fecha', 'Tipo', 'Serie/Folio', 'UUID', 'Total', 'Estado'], (this.cfdi || []).map(row => [row.issued_label, this.voucherLabel(row.voucher_type), [row.serie, row.folio].filter(Boolean).join('-'), row.uuid, row.total, row.sat_status]), type);
            },
            exportQuotes: function(type) {
                this.exportRows('cotizaciones', ['Folio', 'Estado', 'Fecha', 'Vence', 'Total', 'Productos'], (this.quotes || []).map(row => [row.folio, this.statusLabel(row.status), row.created_label, row.expires_label, row.total, (row.items || []).map(item => item.quantity + ' x ' + item.name).join('; ')]), type);
            },
            exportOrders: function(type) {
                this.exportRows('pedidos', ['Folio', 'Estado', 'Fecha', 'Total'], (this.orders || []).map(row => [row.folio, this.statusLabel(row.status), row.created_label, row.total]), type);
            },
            exportRows: function(name, headers, rows, type) {
                if (type === 'pdf') {
                    this.printRows(name, headers, rows);
                    return;
                }
                var separator = type === 'xls' ? '\t' : ',';
                var extension = type === 'xls' ? 'xls' : 'csv';
                var mime = type === 'xls' ? 'application/vnd.ms-excel;charset=utf-8' : 'text/csv;charset=utf-8';
                var lines = [headers].concat(rows).map(function(row) {
                    return row.map(function(value) {
                        value = String(value === null || value === undefined ? '' : value);
                        if (separator === ',') {
                            return '"' + value.replace(/"/g, '""') + '"';
                        }
                        return value.replace(/\t/g, ' ').replace(/\r?\n/g, ' ');
                    }).join(separator);
                }).join('\n');
                this.downloadFile(name + '-' + this.today() + '.' + extension, lines, mime);
            },
            printRows: function(name, headers, rows) {
                var html = '<div id="print-area"><h1>' + this.escapeHtml(name) + '</h1><table border="1" cellspacing="0" cellpadding="6" style="width:100%;border-collapse:collapse;font-family:Arial;font-size:12px"><thead><tr>' + headers.map(h => '<th>' + this.escapeHtml(h) + '</th>').join('') + '</tr></thead><tbody>' + rows.map(row => '<tr>' + row.map(value => '<td>' + this.escapeHtml(value) + '</td>').join('') + '</tr>').join('') + '</tbody></table></div>';
                var holder = document.createElement('div');
                holder.innerHTML = html;
                document.body.appendChild(holder);
                window.print();
                document.body.removeChild(holder);
            },
            downloadFile: function(filename, content, mime) {
                var blob = new Blob([content], { type: mime });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            },
            today: function() { return new Date().toISOString().slice(0, 10); },
            escapeHtml: function(value) {
                return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function(char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
                });
            },
            voucherLabel: function(type) { return ({ I: 'Ingreso', E: 'Egreso', T: 'Traslado', P: 'Pago', N: 'Nomina' })[type] || type; },
            statusLabel: function(status) { return ({ requested: 'Solicitada', reviewed: 'Revisada', approved: 'Aprobada', rejected: 'Rechazada', converted: 'Convertida', draft: 'Borrador', issued: 'Emitida', paid: 'Pagada', pending: 'Pendiente', cancelled: 'Cancelada', delivered: 'Entregado', shipped: 'Enviado' })[status] || status; },
            statusClass: function(status) { if (['approved', 'paid', 'issued'].indexOf(status) >= 0) return 'badge-success'; if (['rejected', 'cancelled'].indexOf(status) >= 0) return 'badge-danger'; if (['requested', 'pending', 'draft'].indexOf(status) >= 0) return 'badge-warning'; return 'badge-info'; },
            money: function(value, currency) { return (currency || 'MXN') + ' ' + Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        }
    });
});
</script>
