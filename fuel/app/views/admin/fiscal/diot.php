<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .diot-warning-list { margin: 0; padding-left: 1rem; }
</style>

<div id="app-fiscal-diot" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ preview.rfc || 'No configurado' }} &middot;
                Periodo {{ preview.period }} &middot;
                Fuente {{ preview.rfc_source_label || 'No configurado' }}
            </div>
        </div>
        <div class="col-md-4">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/diot'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="preview.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in preview.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ money(preview.totals.taxed_base) }}</h3>
                    <p>Base gravada</p>
                </div>
                <div class="icon"><i class="bi bi-table"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ money(preview.totals.creditable_vat) }}</h3>
                    <p>IVA acreditable</p>
                </div>
                <div class="icon"><i class="bi bi-percent"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ money(preview.totals.vat_retained) }}</h3>
                    <p>IVA retenido</p>
                </div>
                <div class="icon"><i class="bi bi-dash-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number(preview.totals.cfdi_count) }}</h3>
                    <p>CFDI relacionados</p>
                </div>
                <div class="icon"><i class="bi bi-files"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Proveedores DIOT</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>RFC</th>
                            <th class="text-right">Base gravada</th>
                            <th class="text-right">IVA acreditable</th>
                            <th class="text-right">IVA retenido</th>
                            <th class="text-right">ISR retenido</th>
                            <th class="text-right">CFDI</th>
                            <th>Advertencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in preview.items" :key="item.counterparty_rfc || 'sin-rfc'">
                            <td>{{ item.counterparty_name || 'Sin nombre' }}</td>
                            <td>{{ item.counterparty_rfc || 'Sin RFC' }}</td>
                            <td class="text-right">{{ money(item.taxed_base) }}</td>
                            <td class="text-right">{{ money(item.creditable_vat) }}</td>
                            <td class="text-right">{{ money(item.vat_retained) }}</td>
                            <td class="text-right">{{ money(item.isr_retained) }}</td>
                            <td class="text-right">{{ number(item.cfdi_count) }}</td>
                            <td>
                                <span v-if="!item.warnings.length" class="badge badge-success">Sin advertencias</span>
                                <ul v-if="item.warnings.length" class="diot-warning-list">
                                    <li v-for="warning in item.warnings" :key="item.counterparty_rfc + '-' + warning">{{ warning }}</li>
                                </ul>
                            </td>
                        </tr>
                        <tr v-if="preview.items.length === 0">
                            <td colspan="8" class="text-center text-muted">Sin CFDI recibidos para preparar DIOT en este periodo.</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Totales</th>
                            <th class="text-right">{{ money(preview.totals.taxed_base) }}</th>
                            <th class="text-right">{{ money(preview.totals.creditable_vat) }}</th>
                            <th class="text-right">{{ money(preview.totals.vat_retained) }}</th>
                            <th class="text-right">{{ money(preview.totals.isr_retained) }}</th>
                            <th class="text-right">{{ number(preview.totals.cfdi_count) }}</th>
                            <th>{{ number(preview.totals.warning_count) }} advertencias</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Esta pantalla prepara datos para revision interna. No genera el archivo oficial DIOT.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-fiscal-diot');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var fiscalDiotDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/diot_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialPreview = <?php echo json_encode((array) $preview, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-diot',
        data: {
            loading: false,
            errorMessage: '',
            preview: initialPreview
        },
        mounted: function() {
            this.loadPreview();
        },
        methods: {
            loadPreview: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var query = '?rfc=' + encodeURIComponent(initialRfc || '') + '&period=' + encodeURIComponent(initialPeriod || '');

                fetch(fiscalDiotDataUrl + query, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar la preparacion DIOT.';
                            return;
                        }

                        self.preview = data.preview || self.preview;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de preparacion DIOT.';
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
            }
        }
    });
});
</script>
