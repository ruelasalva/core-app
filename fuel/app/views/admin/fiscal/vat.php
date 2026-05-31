<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
</style>

<div id="app-fiscal-vat" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ detail.rfc || 'No configurado' }} &middot;
                Periodo {{ detail.period }} &middot;
                Fuente {{ detail.rfc_source_label || 'No configurado' }}
            </div>
        </div>
        <div class="col-md-4">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/vat'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="detail.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in detail.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ money(detail.result.vat_caused) }}</h3>
                    <p>IVA causado</p>
                </div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ money(detail.result.vat_creditable) }}</h3>
                    <p>IVA acreditable</p>
                </div>
                <div class="icon"><i class="bi bi-cart-check"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ money(detail.result.preliminary_vat_payable) }}</h3>
                    <p>IVA preliminar por pagar</p>
                </div>
                <div class="icon"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Ventas</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th>Base gravada</th><td class="text-right">{{ money(detail.sales.taxed_base) }}</td></tr>
                            <tr><th>IVA trasladado</th><td class="text-right">{{ money(detail.sales.vat_transferred) }}</td></tr>
                            <tr><th>Base tasa 0</th><td class="text-right">{{ money(detail.sales.zero_base) }}</td></tr>
                            <tr><th>Base exenta</th><td class="text-right">{{ money(detail.sales.exempt_base) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title mb-0">Compras</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th>Base gravada</th><td class="text-right">{{ money(detail.purchases.taxed_base) }}</td></tr>
                            <tr><th>IVA acreditable</th><td class="text-right">{{ money(detail.purchases.vat_creditable) }}</td></tr>
                            <tr><th>Base tasa 0</th><td class="text-right">{{ money(detail.purchases.zero_base) }}</td></tr>
                            <tr><th>Base exenta</th><td class="text-right">{{ money(detail.purchases.exempt_base) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title mb-0">Retenciones</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th>IVA retenido</th><td class="text-right">{{ money(detail.withholdings.vat_retained) }}</td></tr>
                            <tr><th>ISR retenido</th><td class="text-right">{{ money(detail.withholdings.isr_retained) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title mb-0">Resultado</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th>IVA causado</th><td class="text-right">{{ money(detail.result.vat_caused) }}</td></tr>
                            <tr><th>IVA acreditable</th><td class="text-right">{{ money(detail.result.vat_creditable) }}</td></tr>
                            <tr><th>IVA preliminar por pagar</th><td class="text-right font-weight-bold">{{ money(detail.result.preliminary_vat_payable) }}</td></tr>
                            <tr><th>Lineas fiscales consideradas</th><td class="text-right">{{ number(detail.ledger_rows) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted">
                    La pantalla es de solo lectura y usa el libro fiscal construido desde CFDI SAT.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-fiscal-vat');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var fiscalVatDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/vat_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialDetail = <?php echo json_encode((array) $detail, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-vat',
        data: {
            loading: false,
            errorMessage: '',
            detail: initialDetail
        },
        mounted: function() {
            this.loadDetail();
        },
        methods: {
            loadDetail: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var query = '?rfc=' + encodeURIComponent(initialRfc || '') + '&period=' + encodeURIComponent(initialPeriod || '');

                fetch(fiscalVatDataUrl + query, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar el IVA mensual.';
                            return;
                        }

                        self.detail = data.detail || self.detail;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de IVA mensual.';
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
