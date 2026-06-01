<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
</style>

<div id="app-fiscal-reconciliation" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ reconciliation.rfc || 'No configurado' }} &middot;
                Periodo {{ reconciliation.period }} &middot;
                Fuente {{ reconciliation.rfc_source_label || 'No configurado' }}
            </div>
        </div>
        <div class="col-md-4">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/reconciliation'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="reconciliation.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in reconciliation.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ money(reconciliation.totals.fiscal_amount) }}</h3>
                    <p>Importe fiscal</p>
                </div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ money(reconciliation.totals.accounting_amount) }}</h3>
                    <p>Importe contable</p>
                </div>
                <div class="icon"><i class="bi bi-journal-check"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box" :class="Math.abs(Number(reconciliation.totals.difference || 0)) <= 0.01 ? 'bg-success' : 'bg-warning'">
                <div class="inner">
                    <h3>{{ money(reconciliation.totals.difference) }}</h3>
                    <p>Diferencia</p>
                </div>
                <div class="icon"><i class="bi bi-columns-gap"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Conciliación fiscal-contable</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Cuenta configurada</th>
                            <th class="text-right">Importe fiscal</th>
                            <th class="text-right">Importe contable</th>
                            <th class="text-right">Diferencia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in reconciliation.items" :key="item.key">
                            <td>
                                <strong>{{ item.concept }}</strong>
                                <div class="text-muted small">{{ ruleLabel(item) }}</div>
                            </td>
                            <td>
                                <span v-if="item.account_label">{{ item.account_label }}</span>
                                <span v-else class="text-muted">Sin cuenta configurada</span>
                            </td>
                            <td class="text-right">{{ money(item.fiscal_amount) }}</td>
                            <td class="text-right">{{ money(item.accounting_amount) }}</td>
                            <td class="text-right" :class="Math.abs(Number(item.difference || 0)) > 0.01 ? 'text-danger font-weight-bold' : ''">
                                {{ money(item.difference) }}
                            </td>
                            <td><span class="badge" :class="statusClass(item.status)">{{ item.status }}</span></td>
                        </tr>
                        <tr v-if="reconciliation.items.length === 0">
                            <td colspan="6" class="text-center text-muted">Sin datos de conciliación para el periodo.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="reconciliation.items.length > 0">
                        <tr>
                            <th colspan="2">Totales</th>
                            <th class="text-right">{{ money(reconciliation.totals.fiscal_amount) }}</th>
                            <th class="text-right">{{ money(reconciliation.totals.accounting_amount) }}</th>
                            <th class="text-right">{{ money(reconciliation.totals.difference) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Pantalla de solo lectura. Compara el libro fiscal contra pólizas contabilizadas usando las cuentas configuradas en Contabilidad.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-fiscal-reconciliation');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var reconciliationDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/reconciliation_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialReconciliation = <?php echo json_encode((array) $reconciliation, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-reconciliation',
        data: {
            loading: false,
            errorMessage: '',
            reconciliation: initialReconciliation
        },
        mounted: function() {
            this.loadReconciliation();
        },
        methods: {
            loadReconciliation: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var query = '?rfc=' + encodeURIComponent(initialRfc || '') + '&period=' + encodeURIComponent(initialPeriod || '');

                fetch(reconciliationDataUrl + query, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar la conciliación fiscal-contable.';
                            return;
                        }

                        self.reconciliation = data.reconciliation || self.reconciliation;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de conciliación fiscal-contable.';
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
            ruleLabel: function(item) {
                var direction = item.direction === 'issued' ? 'emitidos' : (item.direction === 'received' ? 'recibidos' : 'ambos sentidos');
                var type = item.tax_type === 'transferred' ? 'trasladado' : 'retenido';
                return 'Impuesto ' + item.tax_code + ' ' + type + ' · ' + direction;
            },
            statusClass: function(status) {
                if (status === 'OK') {
                    return 'badge-success';
                }
                if (status === 'Diferencia') {
                    return 'badge-danger';
                }
                if (status === 'Sin cuenta configurada') {
                    return 'badge-warning';
                }
                if (status === 'Sin movimientos contables') {
                    return 'badge-secondary';
                }
                return 'badge-light';
            }
        }
    });
});
</script>
