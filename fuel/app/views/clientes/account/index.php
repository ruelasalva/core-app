<style>
    .customer-account-card .icon { opacity: .2; }
    .customer-account-table td,
    .customer-account-table th { vertical-align: middle; }
    .customer-account-filters .form-group { margin-bottom: .75rem; }
    .customer-account-meta { color: #64748b; font-size: .86rem; }
    .customer-account-filter-note {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e3a8a;
        border-radius: 8px;
        padding: .75rem 1rem;
        margin-bottom: 1rem;
    }
    .customer-account-empty-balance {
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 10px;
        padding: 1rem;
        color: #475569;
        margin-bottom: 1rem;
    }
    .customer-account-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
    }
</style>

<div id="app-customer-account" v-cloak>
    <div class="portal-page-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 mb-1">Estado de cuenta</h1>
                <p class="text-muted mb-1">Consulta informativa basada en las facturas, pagos y aplicaciones visibles en tu portal.</p>
                <div class="customer-account-meta">Consulta generada el {{ generatedLabel }}</div>
            </div>
            <div class="portal-page-actions mt-3 mt-md-0">
                <a class="btn btn-primary btn-sm" href="<?php echo Uri::create('clientes/cfdi'); ?>">
                    <i class="bi bi-file-earmark-text mr-1"></i> Ver CFDI
                </a>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo Uri::create('clientes/helpdesk'); ?>">
                    <i class="bi bi-life-preserver mr-1"></i> Abrir ticket
                </a>
            </div>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="portal-panel customer-account-filters">
        <div class="portal-panel-header">
            <h2 class="h6 mb-0">Filtros</h2>
        </div>
        <div class="portal-panel-body">
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
        <div v-if="hasFiltersActive" class="customer-account-filter-note">
            <span class="badge badge-primary mr-2">Resumen filtrado</span>
            Los importes mostrados corresponden al periodo o filtros seleccionados.
        </div>

        <div v-if="Number(account.balance_due || 0) <= 0 && account.invoices.length === 0" class="customer-account-empty-balance">
            <strong>Sin saldo pendiente visible.</strong>
            <div class="small">Cuando existan facturas visibles en el portal apareceran aqui con sus pagos y aplicaciones.</div>
        </div>

        <div class="portal-kpi-grid">
            <div class="portal-kpi">
                <div>
                    <div class="portal-kpi-label">Saldo pendiente</div>
                    <div class="portal-kpi-value">{{ money(account.balance_due) }}</div>
                </div>
                <i class="bi bi-wallet2 portal-kpi-icon"></i>
            </div>
            <div class="portal-kpi">
                <div>
                    <div class="portal-kpi-label">Saldo vencido</div>
                    <div class="portal-kpi-value">{{ money(account.overdue_balance) }}</div>
                </div>
                <i class="bi bi-exclamation-triangle portal-kpi-icon"></i>
            </div>
            <div class="portal-kpi">
                <div>
                    <div class="portal-kpi-label">Facturas abiertas</div>
                    <div class="portal-kpi-value">{{ summary.open_invoices || 0 }}</div>
                </div>
                <i class="bi bi-file-earmark-text portal-kpi-icon"></i>
            </div>
            <div class="portal-kpi">
                <div>
                    <div class="portal-kpi-label">Facturas pagadas</div>
                    <div class="portal-kpi-value">{{ summary.paid_invoices || 0 }}</div>
                </div>
                <i class="bi bi-check2-circle portal-kpi-icon"></i>
            </div>
            <div class="portal-kpi">
                <div>
                    <div class="portal-kpi-label">Pagos recibidos</div>
                    <div class="portal-kpi-value">{{ summary.payments_received || 0 }}</div>
                </div>
                <i class="bi bi-cash-coin portal-kpi-icon"></i>
            </div>
            <div class="portal-kpi">
                <div>
                    <div class="portal-kpi-label">Facturas vencidas</div>
                    <div class="portal-kpi-value">{{ overdueInvoiceCount }}</div>
                </div>
                <i class="bi bi-calendar-x portal-kpi-icon"></i>
            </div>
        </div>

        <div class="portal-panel">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Antigüedad de saldo</h2>
            </div>
            <div class="portal-panel-body">
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

        <div class="portal-panel">
            <div class="portal-panel-header">
                <div>
                    <h2 class="h6 mb-0">Facturas</h2>
                    <div class="text-muted small">Saldos informativos segun facturas visibles para tu cuenta.</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover customer-account-table portal-table mb-0">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Emision</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Saldo</th>
                                <th class="text-right">Dias vencidos</th>
                                <th>Moneda</th>
                                <th>Acciones</th>
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
                                <td>{{ invoice.currency_code || 'MXN' }}</td>
                                <td>
                                    <div class="customer-account-actions">
                                        <a class="btn btn-outline-primary btn-xs" href="<?php echo Uri::create('clientes/cfdi'); ?>">Ver CFDI</a>
                                        <a class="btn btn-outline-secondary btn-xs" href="<?php echo Uri::create('clientes/helpdesk'); ?>">Abrir ticket</a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="account.invoices.length === 0">
                                <td colspan="9">
                                    <div class="portal-empty m-3">
                                        Sin facturas para los filtros seleccionados.
                                        <div class="mt-2">
                                            <a class="btn btn-outline-primary btn-xs" href="<?php echo Uri::create('clientes/cfdi'); ?>">Ver CFDI</a>
                                            <a class="btn btn-outline-secondary btn-xs" href="<?php echo Uri::create('clientes/helpdesk'); ?>">Solicitar aclaracion</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="portal-panel">
            <div class="portal-panel-header">
                <div>
                    <h2 class="h6 mb-0">Pagos recibidos</h2>
                    <div class="text-muted small">Pagos registrados y visibles para esta cuenta.</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover customer-account-table portal-table mb-0">
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
                                <td colspan="5">
                                    <div class="portal-empty m-3">
                                        Sin pagos recibidos para los filtros seleccionados.
                                        <div class="small mt-1">Si ya realizaste un pago, abre un ticket con tu comprobante para solicitar aclaracion.</div>
                                        <a class="btn btn-outline-secondary btn-xs mt-2" href="<?php echo Uri::create('clientes/helpdesk'); ?>">Abrir ticket</a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="portal-panel">
            <div class="portal-panel-header">
                <div>
                    <h2 class="h6 mb-0">Aplicaciones de pago</h2>
                    <div class="text-muted small">Relacion entre pagos recibidos y facturas aplicadas.</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover customer-account-table portal-table mb-0">
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
                                <td colspan="5">
                                    <div class="portal-empty m-3">
                                        Sin aplicaciones de pago para los filtros seleccionados.
                                        <div class="small mt-1">Los pagos pueden aparecer después de validación administrativa.</div>
                                    </div>
                                </td>
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
                },
                generatedAt: new Date()
            };
        },
        computed: {
            aging: function() {
                return this.account.aging || {};
            },
            summary: function() {
                return this.account.summary || {};
            },
            generatedLabel: function() {
                return this.generatedAt.toLocaleString('es-MX', {
                    dateStyle: 'medium',
                    timeStyle: 'short'
                });
            },
            hasFiltersActive: function() {
                return !!(this.filters.date_from || this.filters.date_to || this.filters.folio || this.filters.currency || this.filters.status !== 'all');
            },
            overdueInvoiceCount: function() {
                return (this.account.invoices || []).filter(function(invoice) {
                    return Number(invoice.is_overdue || 0) === 1;
                }).length;
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
