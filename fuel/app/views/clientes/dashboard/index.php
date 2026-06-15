<style>
    .customer-dashboard-shell { --dash-border: #e5e9f0; --dash-text: #223047; --dash-muted: #6b778c; }
    .customer-dashboard-hero {
        border: 1px solid var(--dash-border);
        border-radius: 10px;
        background: linear-gradient(135deg, #ffffff 0%, #f7fbff 100%);
        box-shadow: 0 10px 28px rgba(15, 23, 42, .07);
        padding: 22px;
        margin-bottom: 18px;
    }
    .customer-dashboard-hero h1 { color: var(--dash-text); font-weight: 800; }
    .customer-dashboard-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .customer-dashboard-actions .btn { border-radius: 999px; font-weight: 700; padding: .45rem .8rem; }
    .customer-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }
    .customer-kpi {
        min-height: 120px;
        border: 1px solid var(--dash-border);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .055);
        padding: 16px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }
    .customer-kpi-label { color: var(--dash-muted); font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    .customer-kpi-value { color: var(--dash-text); font-size: 1.55rem; font-weight: 800; line-height: 1.1; margin-top: 8px; word-break: break-word; }
    .customer-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex: 0 0 auto;
    }
    .customer-kpi-info { background: #0d6efd; }
    .customer-kpi-warning { background: #f59f00; }
    .customer-kpi-success { background: #198754; }
    .customer-kpi-primary { background: #2563eb; }
    .customer-kpi-secondary { background: #64748b; }
    .customer-kpi-teal { background: #0f766e; }
    .customer-kpi-danger { background: #dc3545; }
    .customer-panel {
        border: 1px solid var(--dash-border);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .055);
        height: 100%;
    }
    .customer-panel .card-header { background: #fff; border-bottom: 1px solid var(--dash-border); border-radius: 10px 10px 0 0; }
    .customer-panel-title { font-size: .95rem; font-weight: 800; color: var(--dash-text); margin: 0; }
    .customer-dashboard-table td,
    .customer-dashboard-table th { vertical-align: middle; }
    .customer-dashboard-empty {
        min-height: 104px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: var(--dash-muted);
    }
    .customer-chart-box { min-height: 250px; display: flex; align-items: center; justify-content: center; }
    .customer-chart-box canvas { max-height: 220px; }
    @media (max-width: 1199.98px) {
        .customer-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .customer-dashboard-hero { padding: 18px; }
        .customer-dashboard-actions .btn { width: 100%; }
        .customer-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 480px) {
        .customer-kpi-grid { grid-template-columns: 1fr; }
    }
</style>

<div id="app-customer-dashboard" class="customer-dashboard-shell" v-cloak>
    <section class="customer-dashboard-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="mb-3 mb-lg-0">
                <h1 class="h3 mb-1">Portal de clientes</h1>
                <p class="text-muted mb-0">Consulta saldos, facturas, contratos y solicitudes desde un solo lugar.</p>
            </div>
            <div class="customer-dashboard-actions">
                <a class="btn btn-primary btn-sm" href="<?php echo Uri::create('clientes/estado-cuenta'); ?>">
                    <i class="bi bi-wallet2 mr-1"></i> Estado de cuenta
                </a>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo Uri::create('clientes/cfdi'); ?>">
                    <i class="bi bi-filetype-xml mr-1"></i> Facturas / CFDI
                </a>
                <a class="btn btn-outline-dark btn-sm" href="<?php echo Uri::create('clientes/contracts'); ?>">
                    <i class="bi bi-file-earmark-lock mr-1"></i> Contratos
                </a>
                <a class="btn btn-outline-info btn-sm" href="<?php echo Uri::create('clientes/helpdesk'); ?>">
                    <i class="bi bi-life-preserver mr-1"></i> Abrir ticket
                </a>
            </div>
        </div>
    </section>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-if="loading" class="text-center p-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Cargando portal...</p>
    </div>

    <div v-show="!loading">
        <div class="customer-kpi-grid">
            <div class="customer-kpi">
                <div>
                    <div class="customer-kpi-label">Saldo pendiente</div>
                    <div class="customer-kpi-value">{{ money(stats.open_balance) }}</div>
                </div>
                <div class="customer-kpi-icon customer-kpi-info"><i class="bi bi-wallet2"></i></div>
            </div>
            <div class="customer-kpi">
                <div>
                    <div class="customer-kpi-label">Saldo vencido</div>
                    <div class="customer-kpi-value">{{ money(stats.overdue_balance) }}</div>
                </div>
                <div class="customer-kpi-icon customer-kpi-warning"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
            <div class="customer-kpi">
                <div>
                    <div class="customer-kpi-label">Facturas visibles</div>
                    <div class="customer-kpi-value">{{ account.invoices.length || 0 }}</div>
                </div>
                <div class="customer-kpi-icon customer-kpi-success"><i class="bi bi-file-earmark-text"></i></div>
            </div>
            <div class="customer-kpi">
                <div>
                    <div class="customer-kpi-label">CFDI visibles</div>
                    <div class="customer-kpi-value">{{ stats.cfdi || 0 }}</div>
                </div>
                <div class="customer-kpi-icon customer-kpi-primary"><i class="bi bi-filetype-xml"></i></div>
            </div>
            <div class="customer-kpi">
                <div>
                    <div class="customer-kpi-label">Cotizaciones</div>
                    <div class="customer-kpi-value">{{ stats.quotes || 0 }}</div>
                </div>
                <div class="customer-kpi-icon customer-kpi-secondary"><i class="bi bi-card-checklist"></i></div>
            </div>
            <div class="customer-kpi">
                <div>
                    <div class="customer-kpi-label">Pedidos</div>
                    <div class="customer-kpi-value">{{ stats.orders || 0 }}</div>
                </div>
                <div class="customer-kpi-icon customer-kpi-teal"><i class="bi bi-box-seam"></i></div>
            </div>
            <div class="customer-kpi">
                <div>
                    <div class="customer-kpi-label">Tickets abiertos</div>
                    <div class="customer-kpi-value">{{ helpdeskStats.open || 0 }}</div>
                </div>
                <div class="customer-kpi-icon customer-kpi-danger"><i class="bi bi-life-preserver"></i></div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-lg-5 mb-3 mb-lg-0">
                <div class="customer-panel">
                    <div class="card-header">
                        <h2 class="customer-panel-title">Saldo pendiente vs vencido</h2>
                    </div>
                    <div class="card-body customer-chart-box">
                        <canvas v-show="hasBalanceData" id="customer-balance-chart"></canvas>
                        <div v-show="!hasBalanceData" class="customer-dashboard-empty">
                            <i class="bi bi-bar-chart"></i>
                            <span>Sin saldos para graficar.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="customer-panel">
                    <div class="card-header">
                        <h2 class="customer-panel-title">Actividad del portal</h2>
                    </div>
                    <div class="card-body customer-chart-box">
                        <canvas v-show="hasActivityData" id="customer-activity-chart"></canvas>
                        <div v-show="!hasActivityData" class="customer-dashboard-empty">
                            <i class="bi bi-grid"></i>
                            <span>Sin actividad reciente para graficar.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="customer-panel">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="customer-panel-title">Cotizaciones recientes</h2>
                        <a href="<?php echo Uri::create('clientes/quotes'); ?>" class="btn btn-xs btn-outline-info">Ver</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover customer-dashboard-table mb-0">
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
                                        <td colspan="3">
                                            <div class="customer-dashboard-empty">
                                                <span>Sin cotizaciones recientes.</span>
                                                <a href="<?php echo Uri::create('clientes/quotes'); ?>" class="btn btn-xs btn-outline-info">Solicitar cotización</a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="customer-panel">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="customer-panel-title">Pedidos recientes</h2>
                        <a href="<?php echo Uri::create('clientes/quotes'); ?>" class="btn btn-xs btn-outline-success">Ver</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover customer-dashboard-table mb-0">
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
                                        <td colspan="3">
                                            <div class="customer-dashboard-empty">
                                                <span>Sin pedidos recientes.</span>
                                                <a href="<?php echo Uri::create('clientes/quotes'); ?>" class="btn btn-xs btn-outline-success">Ver cotizaciones</a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="customer-panel">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="customer-panel-title">Facturas recientes</h2>
                        <a href="<?php echo Uri::create('clientes/estado-cuenta'); ?>" class="btn btn-xs btn-outline-warning">Ver</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover customer-dashboard-table mb-0">
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
                                        <td colspan="3">
                                            <div class="customer-dashboard-empty">
                                                <span>Sin facturas visibles.</span>
                                                <a href="<?php echo Uri::create('clientes/cfdi'); ?>" class="btn btn-xs btn-outline-primary">Ver CFDI</a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="customer-panel">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="customer-panel-title">Tickets recientes</h2>
                        <a href="<?php echo Uri::create('clientes/helpdesk'); ?>" class="btn btn-xs btn-outline-info">Ver</a>
                    </div>
                    <div class="card-body">
                        <div class="customer-dashboard-empty">
                            <strong>{{ helpdeskStats.open || 0 }} abiertos</strong>
                            <span>Consulta seguimiento o abre una nueva solicitud.</span>
                            <a href="<?php echo Uri::create('clientes/helpdesk'); ?>" class="btn btn-xs btn-outline-info">Abrir ticket</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo Asset::js('chart.umd.js'); ?>
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
            orders: [],
            balanceChart: null,
            activityChart: null
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
            },
            hasBalanceData: function() {
                return Number(this.stats.open_balance || 0) > 0 || Number(this.stats.overdue_balance || 0) > 0;
            },
            hasActivityData: function() {
                return Number((this.account.invoices || []).length || 0) > 0
                    || Number(this.stats.quotes || 0) > 0
                    || Number(this.stats.orders || 0) > 0
                    || Number(this.helpdeskStats.open || 0) > 0;
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
                        self.$nextTick(function() {
                            self.renderCharts();
                        });
                    })
                    .catch(function(error) {
                        self.loading = false;
                        self.error = error && error.error ? error.error : 'No se pudo cargar el dashboard.';
                    });
            },
            renderCharts: function() {
                if (typeof Chart === 'undefined') {
                    return;
                }

                this.renderBalanceChart();
                this.renderActivityChart();
            },
            renderBalanceChart: function() {
                var element = document.getElementById('customer-balance-chart');
                if (!element || !this.hasBalanceData) {
                    return;
                }

                if (this.balanceChart) {
                    this.balanceChart.destroy();
                }

                this.balanceChart = new Chart(element, {
                    type: 'doughnut',
                    data: {
                        labels: ['Pendiente', 'Vencido'],
                        datasets: [{
                            data: [Number(this.stats.open_balance || 0), Number(this.stats.overdue_balance || 0)],
                            backgroundColor: ['#0d6efd', '#f59f00'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            },
            renderActivityChart: function() {
                var element = document.getElementById('customer-activity-chart');
                if (!element || !this.hasActivityData) {
                    return;
                }

                if (this.activityChart) {
                    this.activityChart.destroy();
                }

                this.activityChart = new Chart(element, {
                    type: 'bar',
                    data: {
                        labels: ['Facturas', 'Cotizaciones', 'Pedidos', 'Tickets'],
                        datasets: [{
                            label: 'Registros',
                            data: [
                                Number((this.account.invoices || []).length || 0),
                                Number(this.stats.quotes || 0),
                                Number(this.stats.orders || 0),
                                Number(this.helpdeskStats.open || 0)
                            ],
                            backgroundColor: ['#198754', '#64748b', '#0f766e', '#dc3545'],
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                            x: { grid: { display: false } }
                        }
                    }
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
