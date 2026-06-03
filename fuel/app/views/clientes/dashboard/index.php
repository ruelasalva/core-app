<style>
    .customer-dashboard-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .customer-dashboard-card .icon { opacity: .2; }
    .customer-dashboard-table td,
    .customer-dashboard-table th { vertical-align: middle; }
    .customer-dashboard-empty { min-height: 84px; display: flex; align-items: center; justify-content: center; }
</style>

<div id="app-customer-dashboard" v-cloak>
    <div class="card card-primary card-outline">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 mb-1">Portal de clientes</h1>
                <p class="text-muted mb-0">Consulta tu estado comercial, CFDI visibles y solicitudes recientes.</p>
            </div>
            <div class="customer-dashboard-actions mt-3 mt-md-0">
                <a class="btn btn-primary btn-sm" href="<?php echo Uri::create('clientes/quotes'); ?>">
                    <i class="bi bi-plus-circle"></i> Solicitar cotización
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo Uri::create('clientes/quotes'); ?>">
                    <i class="bi bi-receipt"></i> Ver facturas
                </a>
                <a class="btn btn-outline-info btn-sm" href="<?php echo Uri::create('clientes/helpdesk'); ?>">
                    <i class="bi bi-life-preserver"></i> Abrir ticket
                </a>
                <a class="btn btn-outline-success btn-sm" href="<?php echo Uri::create('clientes/perfil'); ?>">
                    <i class="bi bi-person-lines-fill"></i> Actualizar perfil
                </a>
            </div>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-if="loading" class="text-center p-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Cargando portal...</p>
    </div>

    <div v-show="!loading">
        <div class="row">
            <div class="col-sm-6 col-lg-3">
                <div class="small-box bg-info customer-dashboard-card">
                    <div class="inner">
                        <h3>{{ money(stats.open_balance) }}</h3>
                        <p>Saldo pendiente</p>
                    </div>
                    <div class="icon"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small-box bg-warning customer-dashboard-card">
                    <div class="inner">
                        <h3>{{ money(stats.overdue_balance) }}</h3>
                        <p>Saldo vencido</p>
                    </div>
                    <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small-box bg-success customer-dashboard-card">
                    <div class="inner">
                        <h3>{{ account.invoices.length || 0 }}</h3>
                        <p>Facturas visibles</p>
                    </div>
                    <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small-box bg-primary customer-dashboard-card">
                    <div class="inner">
                        <h3>{{ stats.cfdi || 0 }}</h3>
                        <p>CFDI visibles</p>
                    </div>
                    <div class="icon"><i class="bi bi-filetype-xml"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small-box bg-secondary customer-dashboard-card">
                    <div class="inner">
                        <h3>{{ stats.quotes || 0 }}</h3>
                        <p>Cotizaciones recientes</p>
                    </div>
                    <div class="icon"><i class="bi bi-card-checklist"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small-box bg-teal customer-dashboard-card">
                    <div class="inner">
                        <h3>{{ stats.orders || 0 }}</h3>
                        <p>Pedidos recientes</p>
                    </div>
                    <div class="icon"><i class="bi bi-box-seam"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small-box bg-danger customer-dashboard-card">
                    <div class="inner">
                        <h3>{{ helpdeskStats.open || 0 }}</h3>
                        <p>Tickets abiertos</p>
                    </div>
                    <div class="icon"><i class="bi bi-life-preserver"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-header">
                        <h2 class="card-title h6 mb-0">Cotizaciones recientes</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover customer-dashboard-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Estado</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="quote in recentQuotes" :key="quote.id">
                                        <td>
                                            <strong>{{ quote.folio }}</strong>
                                            <div class="text-muted small">{{ quote.created_label }}</div>
                                        </td>
                                        <td><span class="badge" :class="statusClass(quote.status)">{{ statusLabel(quote.status) }}</span></td>
                                        <td class="text-right">{{ money(quote.total, quote.currency_code) }}</td>
                                    </tr>
                                    <tr v-if="recentQuotes.length === 0">
                                        <td colspan="3" class="text-muted text-center customer-dashboard-empty">Sin cotizaciones recientes.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="<?php echo Uri::create('clientes/quotes'); ?>" class="btn btn-xs btn-outline-info">Ver cotizaciones</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-success card-outline h-100">
                    <div class="card-header">
                        <h2 class="card-title h6 mb-0">Pedidos recientes</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover customer-dashboard-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Estado</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="order in recentOrders" :key="order.id">
                                        <td>
                                            <strong>{{ order.folio }}</strong>
                                            <div class="text-muted small">{{ order.created_label }}</div>
                                        </td>
                                        <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                                        <td class="text-right">{{ money(order.total, order.currency_code) }}</td>
                                    </tr>
                                    <tr v-if="recentOrders.length === 0">
                                        <td colspan="3" class="text-muted text-center customer-dashboard-empty">Sin pedidos recientes.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="<?php echo Uri::create('clientes/quotes'); ?>" class="btn btn-xs btn-outline-success">Ver pedidos</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-warning card-outline h-100">
                    <div class="card-header">
                        <h2 class="card-title h6 mb-0">Facturas recientes</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover customer-dashboard-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Vence</th>
                                        <th class="text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="invoice in recentInvoices" :key="invoice.id">
                                        <td>
                                            <strong>{{ invoice.folio }}</strong>
                                            <div class="text-muted small">{{ invoice.issue_label }}</div>
                                        </td>
                                        <td><span :class="invoice.is_overdue == 1 ? 'text-danger font-weight-bold' : ''">{{ invoice.due_label || '-' }}</span></td>
                                        <td class="text-right">{{ money(invoice.balance_due, invoice.currency_code) }}</td>
                                    </tr>
                                    <tr v-if="recentInvoices.length === 0">
                                        <td colspan="3" class="text-muted text-center customer-dashboard-empty">Sin facturas visibles.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="<?php echo Uri::create('clientes/quotes'); ?>" class="btn btn-xs btn-outline-warning">Ver facturas</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#app-customer-dashboard',
        data: {
            loading: true,
            error: '',
            stats: {},
            helpdeskStats: {},
            account: { invoices: [], payments: [], balance_due: 0, overdue_balance: 0 },
            cfdi: [],
            quotes: [],
            orders: []
        },
        computed: {
            recentQuotes: function() {
                return (this.quotes || []).slice(0, 5);
            },
            recentOrders: function() {
                return (this.orders || []).slice(0, 5);
            },
            recentInvoices: function() {
                return (this.account.invoices || []).slice(0, 5);
            }
        },
        mounted: function() {
            this.load();
        },
        methods: {
            load: function() {
                var self = this;
                self.loading = true;
                self.error = '';
                fetch('<?php echo Uri::create('clientes/data'); ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function(response) {
                        if (window.coreAppJson) {
                            return window.coreAppJson(response);
                        }
                        return response.json().then(function(json) {
                            if (!response.ok) {
                                throw json;
                            }
                            return json;
                        });
                    })
                    .then(function(data) {
                        self.loading = false;
                        if (data.error) {
                            self.error = data.error;
                            return;
                        }
                        self.stats = data.stats || {};
                        self.helpdeskStats = data.helpdesk_stats || {};
                        self.account = data.account || self.account;
                        self.cfdi = data.cfdi || [];
                        self.quotes = data.quotes || [];
                        self.orders = data.orders || [];
                    })
                    .catch(function(error) {
                        self.loading = false;
                        self.error = error && error.error ? error.error : 'No se pudo cargar el dashboard.';
                    });
            },
            statusLabel: function(status) {
                var labels = {
                    requested: 'Solicitada',
                    reviewed: 'Revisada',
                    approved: 'Aprobada',
                    rejected: 'Rechazada',
                    converted: 'Convertida',
                    draft: 'Borrador',
                    issued: 'Emitida',
                    paid: 'Pagada',
                    pending: 'Pendiente',
                    cancelled: 'Cancelada',
                    delivered: 'Entregado',
                    shipped: 'Enviado'
                };
                return labels[status] || status || '-';
            },
            statusClass: function(status) {
                if (['approved', 'paid', 'issued', 'delivered'].indexOf(status) >= 0) {
                    return 'badge-success';
                }
                if (['rejected', 'cancelled'].indexOf(status) >= 0) {
                    return 'badge-danger';
                }
                if (['requested', 'pending', 'draft'].indexOf(status) >= 0) {
                    return 'badge-warning';
                }
                return 'badge-info';
            },
            money: function(value, currency) {
                return (currency || 'MXN') + ' ' + Number(value || 0).toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }
    });
});
</script>
