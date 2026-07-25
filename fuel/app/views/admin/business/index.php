<div id="app-business-suite">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="card-title mb-0">Administración Comercial</h3>
                            <small class="text-muted">Indicadores comerciales, cobranza, margen y operación del negocio.</small>
                        </div>
                        <div class="form-inline mt-2 mt-md-0">
                            <label class="mr-2">Desde</label>
                            <input type="date" class="form-control form-control-sm mr-2" v-model="filters.start_date">
                            <label class="mr-2">Hasta</label>
                            <input type="date" class="form-control form-control-sm mr-2" v-model="filters.end_date">
                            <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                                Actualizar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div v-if="warnings.length" class="alert alert-warning">
                        <strong>Alertas de datos incompletos</strong>
                        <ul class="mb-0">
                            <li v-for="warning in warnings" :key="warning">{{ warning }}</li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-lg-3 col-md-6" v-for="card in kpiCards" :key="card.label">
                            <div class="small-box" :class="card.className">
                                <div class="inner">
                                    <h3>{{ card.value }}</h3>
                                    <p>{{ card.label }}</p>
                                </div>
                                <div class="icon"><i :class="card.icon"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card shadow-none border">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Vista 360 de Cliente base</h3>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-7">Clientes visibles</dt>
                                        <dd class="col-5 text-right">{{ customer360.clientes_visibles.count || 0 }}</dd>
                                        <dt class="col-7">Facturas visibles</dt>
                                        <dd class="col-5 text-right">{{ customer360.actividad_comercial.invoices || 0 }}</dd>
                                        <dt class="col-7">Saldo pendiente</dt>
                                        <dd class="col-5 text-right">{{ money(customer360.actividad_comercial.balance_due) }}</dd>
                                        <dt class="col-7">Saldo vencido</dt>
                                        <dd class="col-5 text-right">{{ money(customer360.actividad_comercial.overdue) }}</dd>
                                        <dt class="col-7">Tickets abiertos</dt>
                                        <dd class="col-5 text-right">{{ customer360.tickets.open || 0 }}</dd>
                                        <dt class="col-7">Conversaciones</dt>
                                        <dd class="col-5 text-right">{{ customer360.comunicaciones.conversations || 0 }}</dd>
                                        <dt class="col-7">Oportunidades abiertas</dt>
                                        <dd class="col-5 text-right">{{ customer360.oportunidades.open || 0 }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card shadow-none border">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Top clientes</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th class="text-right">Venta</th>
                                                <th class="text-right">Saldo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in summary.top_clientes" :key="item.party_id">
                                                <td>{{ item.name || 'Sin cliente' }}</td>
                                                <td class="text-right">{{ money(item.total) }}</td>
                                                <td class="text-right">{{ money(item.balance_due) }}</td>
                                            </tr>
                                            <tr v-if="!summary.top_clientes.length">
                                                <td colspan="3" class="text-muted text-center p-3">No hay datos en el periodo.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card shadow-none border">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Top vendedores</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Vendedor</th>
                                                <th class="text-right">Base</th>
                                                <th class="text-right">Comisión</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in summary.top_vendedores" :key="item.seller_id + '-' + item.name">
                                                <td>{{ item.name || 'Sin vendedor' }}</td>
                                                <td class="text-right">{{ money(item.total) }}</td>
                                                <td class="text-right">{{ money(item.commission_total) }}</td>
                                            </tr>
                                            <tr v-if="!summary.top_vendedores.length">
                                                <td colspan="3" class="text-muted text-center p-3">No hay datos de vendedores.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card shadow-none border">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Facturas recientes</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Folio</th>
                                                <th>Cliente</th>
                                                <th>Estado</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-right">Saldo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="invoice in summary.facturas_recientes" :key="invoice.id">
                                                <td>{{ invoice.issue_date }}</td>
                                                <td>{{ invoice.folio }}</td>
                                                <td>{{ invoice.party_name || 'Sin cliente' }}</td>
                                                <td><span class="badge badge-light">{{ invoice.status }}</span></td>
                                                <td class="text-right">{{ money(invoice.total) }}</td>
                                                <td class="text-right">{{ money(invoice.balance_due) }}</td>
                                            </tr>
                                            <tr v-if="!summary.facturas_recientes.length">
                                                <td colspan="6" class="text-muted text-center p-3">No hay facturas recientes para mostrar.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card shadow-none border">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Alertas operativas</h3>
                                </div>
                                <div class="card-body">
                                    <div v-if="!summary.alertas_datos.length" class="text-muted">No hay alertas de datos incompletos.</div>
                                    <div v-for="alert in summary.alertas_datos" :key="alert.message" class="alert py-2" :class="alert.level === 'warning' ? 'alert-warning' : 'alert-info'">
                                        <strong>{{ alert.count > 0 ? alert.count : '' }}</strong>
                                        {{ alert.message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0">
                        <strong>Modo read-only:</strong>
                        esta pantalla no recalcula facturas, no aplica pagos, no genera comisiones, no modifica SAT/Fiscal y no crea movimientos contables.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
new Vue({
    el: '#app-business-suite',
    data: {
        loading: false,
        error: '',
        filters: {
            start_date: '<?php echo date('Y-m-01'); ?>',
            end_date: '<?php echo date('Y-m-t'); ?>'
        },
        kpis: {},
        summary: {
            top_clientes: [],
            top_vendedores: [],
            facturas_recientes: [],
            alertas_datos: []
        },
        customer360: {
            clientes_visibles: { count: 0, sample: [] },
            actividad_comercial: {},
            tickets: {},
            comunicaciones: {},
            oportunidades: {}
        },
        warnings: []
    },
    computed: {
        kpiCards: function() {
            return [
                { label: 'Ventas facturadas', value: this.money(this.get('ventas_facturadas.total')), icon: 'bi bi-receipt', className: 'bg-info' },
                { label: 'Cobranza', value: this.money(this.get('cobranza.total')), icon: 'bi bi-cash-stack', className: 'bg-success' },
                { label: 'Cartera pendiente', value: this.money(this.get('cartera_pendiente.total')), icon: 'bi bi-wallet2', className: 'bg-warning' },
                { label: 'Saldo vencido', value: this.money(this.get('cartera_pendiente.overdue')), icon: 'bi bi-exclamation-triangle', className: 'bg-danger' },
                { label: 'Margen estimado', value: this.money(this.get('margen_estimado.estimated_margin')), icon: 'bi bi-graph-up', className: 'bg-primary' },
                { label: 'Comisiones actuales', value: this.money(this.get('comisiones_actuales.total')), icon: 'bi bi-cash-coin', className: 'bg-secondary' },
                { label: 'Rentas activas', value: String(this.get('rentas.active_profiles') || 0), icon: 'bi bi-arrow-repeat', className: 'bg-info' },
                { label: 'Flujo neto', value: this.money(this.get('flujo_efectivo.net_flow')), icon: 'bi bi-activity', className: 'bg-success' }
            ];
        }
    },
    mounted: function() {
        this.load();
    },
    methods: {
        load: function() {
            this.loading = true;
            this.error = '';
            var params = new URLSearchParams(this.filters).toString();
            fetch('<?php echo \Uri::create('admin/business/data'); ?>?' + params)
                .then(function(response) {
                    if (window.coreAppParseJsonResponse) {
                        return window.coreAppParseJsonResponse(response);
                    }
                    return response.json();
                })
                .then(function(response) {
                    this.loading = false;
                    if (response.success === false) {
                        this.error = response.message || 'No se pudo cargar Administración Comercial.';
                        return;
                    }
                    var data = response.data || response;
                    this.filters = data.filters || this.filters;
                    this.kpis = data.kpis || {};
                    this.summary = data.commercial_summary || this.summary;
                    this.customer360 = data.customer_360 || this.customer360;
                    this.warnings = data.warnings || [];
                }.bind(this))
                .catch(function() {
                    this.loading = false;
                    this.error = 'No se pudo cargar Administración Comercial.';
                }.bind(this));
        },
        get: function(path) {
            var parts = path.split('.');
            var value = this.kpis;
            for (var i = 0; i < parts.length; i++) {
                if (!value || typeof value[parts[i]] === 'undefined') {
                    return 0;
                }
                value = value[parts[i]];
            }
            return value;
        },
        money: function(value) {
            var number = parseFloat(value || 0);
            return number.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        }
    }
});
});
</script>
