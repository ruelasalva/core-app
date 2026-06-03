<style>
    .customer-account-card .icon { opacity: .2; }
    .customer-account-table td,
    .customer-account-table th { vertical-align: middle; }
    .customer-account-empty { min-height: 84px; display: flex; align-items: center; justify-content: center; }
    .customer-account-filters .form-group { margin-bottom: .75rem; }
</style>

<div id="app-customer-account" v-cloak>
    <div class="card card-primary card-outline">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 mb-1">Estado de cuenta</h1>
                <p class="text-muted mb-0">Consulta facturas, pagos recibidos y saldos sin realizar acciones de pago.</p>
            </div>
            <a class="btn btn-outline-secondary btn-sm mt-3 mt-md-0" href="<?php echo Uri::create('clientes'); ?>">
                <i class="bi bi-arrow-left"></i> Volver al portal
            </a>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card customer-account-filters">
        <div class="card-header">
            <h2 class="card-title h6 mb-0">Filtros</h2>
        </div>
        <div class="card-body">
            <form @submit.prevent="load">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label>Fecha desde</label>
                        <input type="date" class="form-control form-control-sm" v-model="filters.date_from">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Fecha hasta</label>
                        <input type="date" class="form-control form-control-sm" v-model="filters.date_to">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Estado</label>
                        <select class="form-control form-control-sm" v-model="filters.status">
                            <option value="all">Todos</option>
                            <option value="open">Abiertas</option>
                            <option value="paid">Pagadas</option>
                            <option value="overdue">Vencidas</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Folio</label>
                        <input type="text" class="form-control form-control-sm" v-model.trim="filters.folio" placeholder="Factura, pago o referencia">
                    </div>
                    <div class="form-group col-md-1">
                        <label>Moneda</label>
                        <input type="text" class="form-control form-control-sm" v-model.trim="filters.currency" maxlength="3" placeholder="MXN">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm mr-2" :disabled="loading">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearFilters" :disabled="loading">
                            Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div v-if="loading" class="text-center p-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Cargando estado de cuenta...</p>
    </div>

    <div v-show="!loading">
        <div class="row">
            <div class="col-sm-6 col-lg">
                <div class="small-box bg-info customer-account-card">
                    <div class="inner">
                        <h3>{{ money(account.balance_due) }}</h3>
                        <p>Saldo pendiente</p>
                    </div>
                    <div class="icon"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="small-box bg-warning customer-account-card">
                    <div class="inner">
                        <h3>{{ money(account.overdue_balance) }}</h3>
                        <p>Saldo vencido</p>
                    </div>
                    <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="small-box bg-primary customer-account-card">
                    <div class="inner">
                        <h3>{{ summary.open_invoices || 0 }}</h3>
                        <p>Facturas abiertas</p>
                    </div>
                    <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="small-box bg-success customer-account-card">
                    <div class="inner">
                        <h3>{{ summary.paid_invoices || 0 }}</h3>
                        <p>Facturas pagadas</p>
                    </div>
                    <div class="icon"><i class="bi bi-check2-circle"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="small-box bg-secondary customer-account-card">
                    <div class="inner">
                        <h3>{{ summary.payments_received || 0 }}</h3>
                        <p>Pagos recibidos</p>
                    </div>
                    <div class="icon"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </div>

        <div class="card card-warning card-outline">
            <div class="card-header">
                <h2 class="card-title h6 mb-0">Antigüedad de saldo</h2>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 col-md">
                        <strong>{{ money(aging.current) }}</strong>
                        <div class="text-muted small">Vigente</div>
                    </div>
                    <div class="col-6 col-md">
                        <strong>{{ money(aging.days_1_30) }}</strong>
                        <div class="text-muted small">1-30 días</div>
                    </div>
                    <div class="col-6 col-md">
                        <strong>{{ money(aging.days_31_60) }}</strong>
                        <div class="text-muted small">31-60 días</div>
                    </div>
                    <div class="col-6 col-md">
                        <strong>{{ money(aging.days_61_90) }}</strong>
                        <div class="text-muted small">61-90 días</div>
                    </div>
                    <div class="col-6 col-md">
                        <strong>{{ money(aging.days_over_90) }}</strong>
                        <div class="text-muted small">Más de 90 días</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-info card-outline">
            <div class="card-header">
                <h2 class="card-title h6 mb-0">Facturas</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover customer-account-table mb-0">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Saldo</th>
                                <th class="text-right">Días vencidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in account.invoices" :key="invoice.id">
                                <td><strong>{{ invoice.folio }}</strong></td>
                                <td>{{ invoice.issue_label || '-' }}</td>
                                <td>{{ invoice.due_label || '-' }}</td>
                                <td>
                                    <span class="badge" :class="paymentStatusClass(invoice.payment_status)">
                                        {{ invoice.payment_status }}
                                    </span>
                                </td>
                                <td class="text-right">{{ money(invoice.total, invoice.currency_code) }}</td>
                                <td class="text-right">{{ money(invoice.balance_due, invoice.currency_code) }}</td>
                                <td class="text-right">{{ invoice.days_overdue || 0 }}</td>
                            </tr>
                            <tr v-if="account.invoices.length === 0">
                                <td colspan="7" class="text-muted text-center customer-account-empty">Sin facturas para los filtros seleccionados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card card-success card-outline">
            <div class="card-header">
                <h2 class="card-title h6 mb-0">Pagos recibidos</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover customer-account-table mb-0">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Referencia</th>
                                <th>Estado</th>
                                <th class="text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in account.payments" :key="payment.id">
                                <td><strong>{{ payment.folio }}</strong></td>
                                <td>{{ payment.payment_label || '-' }}</td>
                                <td>{{ payment.reference || '-' }}</td>
                                <td><span class="badge badge-secondary">{{ statusLabel(payment.status) }}</span></td>
                                <td class="text-right">{{ money(payment.amount, payment.currency_code) }}</td>
                            </tr>
                            <tr v-if="account.payments.length === 0">
                                <td colspan="5" class="text-muted text-center customer-account-empty">Sin pagos recibidos para los filtros seleccionados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h2 class="card-title h6 mb-0">Aplicaciones</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover customer-account-table mb-0">
                        <thead>
                            <tr>
                                <th>Pago</th>
                                <th>Fecha</th>
                                <th>Factura</th>
                                <th>Referencia</th>
                                <th class="text-right">Importe aplicado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="allocation in account.allocations" :key="allocation.id">
                                <td><strong>{{ allocation.payment_folio }}</strong></td>
                                <td>{{ allocation.payment_label || '-' }}</td>
                                <td>{{ allocation.invoice_folio }}</td>
                                <td>{{ allocation.reference || allocation.notes || '-' }}</td>
                                <td class="text-right">{{ money(allocation.amount, allocation.currency_code) }}</td>
                            </tr>
                            <tr v-if="account.allocations.length === 0">
                                <td colspan="5" class="text-muted text-center customer-account-empty">Sin aplicaciones para los filtros seleccionados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted small">
                Esta pantalla es solo de consulta. No registra pagos ni modifica saldos.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#app-customer-account',
        data: function() {
            return {
                loading: true,
                error: '',
                filters: {
                    date_from: '',
                    date_to: '',
                    status: 'all',
                    folio: '',
                    currency: ''
                },
                account: {
                    invoices: [],
                    payments: [],
                    allocations: [],
                    aging: {},
                    summary: {},
                    balance_due: 0,
                    overdue_balance: 0
                }
            };
        },
        computed: {
            aging: function() {
                return this.account.aging || {};
            },
            summary: function() {
                return this.account.summary || {};
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

                var params = new URLSearchParams();
                Object.keys(self.filters).forEach(function(key) {
                    if (self.filters[key]) {
                        params.append(key, self.filters[key]);
                    }
                });

                var url = '<?php echo Uri::create('clientes/estado-cuenta_data'); ?>';
                if (params.toString()) {
                    url += '?' + params.toString();
                }

                fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
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
                        self.account = data.account || self.account;
                    })
                    .catch(function(error) {
                        self.error = error && error.error ? error.error : 'No se pudo cargar el estado de cuenta.';
                    })
                    .finally(function() {
                        self.loading = false;
                    });
            },
            clearFilters: function() {
                this.filters = {
                    date_from: '',
                    date_to: '',
                    status: 'all',
                    folio: '',
                    currency: ''
                };
                this.load();
            },
            paymentStatusClass: function(status) {
                if (status === 'Pagada') {
                    return 'badge-success';
                }
                if (status === 'Vencida') {
                    return 'badge-danger';
                }
                if (status === 'Pendiente') {
                    return 'badge-warning';
                }
                return 'badge-secondary';
            },
            statusLabel: function(status) {
                var labels = {
                    pending: 'Pendiente',
                    confirmed: 'Confirmado',
                    applied: 'Aplicado',
                    cancelled: 'Cancelado',
                    paid: 'Pagado',
                    partial: 'Parcial'
                };
                return labels[status] || status || '-';
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
