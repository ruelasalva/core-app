<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
    $status_class = [
        'open' => 'badge-success',
        'locked' => 'badge-warning',
        'closed' => 'badge-secondary',
        'sin periodo' => 'badge-light',
    ];
    $period_status = isset($dashboard['period_status']) ? (string) $dashboard['period_status'] : 'sin periodo';
    $period_badge = isset($status_class[$period_status]) ? $status_class[$period_status] : 'badge-light';
?>

<style>
    [v-cloak] { display: none; }
</style>

<div id="app-fiscal-dashboard" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">RFC {{ dashboard.rfc || 'No configurado' }} &middot; Periodo {{ dashboard.period }} &middot; Fuente {{ dashboard.rfc_source_label || 'No configurado' }}</div>
        </div>
        <div class="col-md-4">
            <form method="get" action="<?php echo Uri::create('admin/fiscal'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" placeholder="RFC" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="dashboard.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in dashboard.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><span class="badge" :class="periodBadge">{{ dashboard.period_status_label || 'Sin periodo' }}</span></h3>
                    <p>Estado del periodo</p>
                </div>
                <div class="icon"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ lastBuildLabel }}</h3>
                    <p>Ultima construccion</p>
                </div>
                <div class="icon"><i class="bi bi-database-check"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number(dashboard.ledger_lines_count) }}</h3>
                    <p>Lineas libro fiscal</p>
                </div>
                <div class="icon"><i class="bi bi-list-check"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ money(dashboard.preliminary_vat_payable) }}</h3>
                    <p>IVA preliminar por pagar</p>
                </div>
                <div class="icon"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title mb-0">IVA trasladado emitido</h3></div>
                <div class="card-body"><h4>{{ money(dashboard.issued_vat_transferred) }}</h4></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title mb-0">IVA acreditable recibido</h3></div>
                <div class="card-body"><h4>{{ money(dashboard.received_vat_transferred) }}</h4></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title mb-0">IVA retenido</h3></div>
                <div class="card-body"><h4>{{ money(dashboard.vat_retained) }}</h4></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-outline card-danger">
                <div class="card-header"><h3 class="card-title mb-0">ISR retenido</h3></div>
                <div class="card-body"><h4>{{ money(dashboard.isr_retained) }}</h4></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header"><h3 class="card-title mb-0">Detalle de construccion</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Estatus</th>
                        <th class="text-right">CFDI</th>
                        <th class="text-right">Conceptos</th>
                        <th class="text-right">Lineas</th>
                        <th class="text-right">Errores</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="dashboard.last_build">
                        <td>{{ dashboard.last_build.status }}</td>
                        <td class="text-right">{{ number(dashboard.last_build.cfdi_count) }}</td>
                        <td class="text-right">{{ number(dashboard.last_build.detail_count) }}</td>
                        <td class="text-right">{{ number(dashboard.last_build.line_count) }}</td>
                        <td class="text-right">{{ number(dashboard.last_build.error_count) }}</td>
                        <td>{{ datetime(dashboard.last_build.started_at) }}</td>
                        <td>{{ datetime(dashboard.last_build.finished_at) }}</td>
                    </tr>
                    <tr v-if="!dashboard.last_build">
                        <td colspan="7" class="text-center text-muted">Sin construcciones del libro fiscal para este periodo.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title mb-0">Validacion del libro fiscal</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th>CFDI procesados</th><td class="text-right">{{ number(dashboard.validation.cfdi_count) }}</td><th>Conceptos procesados</th><td class="text-right">{{ number(dashboard.validation.detail_count) }}</td></tr>
                            <tr><th>Lineas fiscales</th><td class="text-right">{{ number(dashboard.validation.ledger_lines) }}</td><th>IVA trasladado</th><td class="text-right">{{ money(dashboard.validation.issued_vat_transferred) }}</td></tr>
                            <tr><th>IVA acreditable</th><td class="text-right">{{ money(dashboard.validation.received_vat_transferred) }}</td><th>IVA retenido</th><td class="text-right">{{ money(dashboard.validation.vat_retained) }}</td></tr>
                            <tr><th>ISR retenido</th><td class="text-right">{{ money(dashboard.validation.isr_retained) }}</td><th>Advertencias / Errores</th><td class="text-right">{{ number(dashboard.validation.warning_count) }} / {{ number(dashboard.validation.error_count) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-body" v-if="dashboard.validation.warnings.length || dashboard.validation.errors.length">
                    <div v-if="dashboard.validation.warnings.length" class="alert alert-warning">
                        <strong>Advertencias</strong>
                        <ul class="mb-0"><li v-for="warning in dashboard.validation.warnings" :key="'vw-'+warning">{{ warning }}</li></ul>
                    </div>
                    <div v-if="dashboard.validation.errors.length" class="alert alert-danger mb-0">
                        <strong>Errores</strong>
                        <ul class="mb-0"><li v-for="error in dashboard.validation.errors" :key="'ve-'+error">{{ error }}</li></ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title mb-0">Comandos operativos</h3></div>
                <div class="card-body">
                    <p class="text-muted">Estos botones no ejecutan acciones desde el navegador. Copia el comando y ejecutalo en terminal.</p>
                    <div class="form-group">
                        <label>Construir libro fiscal</label>
                        <input class="form-control form-control-sm" readonly :value="dashboard.commands.build || '-'">
                    </div>
                    <div class="form-group">
                        <label>Validar libro fiscal</label>
                        <input class="form-control form-control-sm" readonly :value="dashboard.commands.validate || '-'">
                    </div>
                    <div class="form-group mb-0">
                        <label>Cerrar periodo</label>
                        <input class="form-control form-control-sm" readonly :value="dashboard.commands.close || '-'">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fiscal dashboard script loaded');

    var root = document.getElementById('app-fiscal-dashboard');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var fiscalDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialDashboard = <?php echo json_encode((array) $dashboard, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-dashboard',
        data: {
            loading: false,
            errorMessage: '',
            dashboard: initialDashboard
        },
        computed: {
            lastBuildLabel: function() {
                if (!this.dashboard.last_build) {
                    return 'Sin datos';
                }
                return this.dashboard.last_build.status || 'registrada';
            },
            periodBadge: function() {
                var status = this.dashboard.period_status || 'sin periodo';
                var classes = {
                    open: 'badge-success',
                    locked: 'badge-warning',
                    closed: 'badge-secondary',
                    'sin periodo': 'badge-light'
                };
                return classes[status] || 'badge-light';
            }
        },
        mounted: function() {
            this.loadDashboard();
        },
        methods: {
            loadDashboard: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var query = '?rfc=' + encodeURIComponent(initialRfc || '') + '&period=' + encodeURIComponent(initialPeriod || '');

                fetch(fiscalDataUrl + query, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar el dashboard fiscal.';
                            return;
                        }

                        self.dashboard = data.dashboard || self.dashboard;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint fiscal.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            money: function(value) {
                return Number(value || 0).toLocaleString('es-MX', {
                    style: 'currency',
                    currency: 'MXN',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            number: function(value) {
                return Number(value || 0).toLocaleString('es-MX');
            },
            datetime: function(value) {
                value = Number(value || 0);
                if (value <= 0) {
                    return '-';
                }
                return new Date(value * 1000).toLocaleString('es-MX');
            }
        }
    });
});
</script>
