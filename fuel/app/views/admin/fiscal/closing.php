<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .fiscal-step-card { min-height: 100%; }
    .fiscal-step-number {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }
</style>

<div id="app-fiscal-closing" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ closing.rfc || 'No configurado' }} &middot;
                Periodo {{ closing.period }} &middot;
                Fuente {{ closing.rfc_source_label || 'No configurado' }} &middot;
                Estado {{ closing.period_status_label || 'Sin periodo' }}
            </div>
        </div>
        <div class="col-md-4">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/closing'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="closing.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in closing.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-3" v-for="step in closing.steps" :key="step.number">
            <div class="card fiscal-step-card" :class="cardClass(step.status)">
                <div class="card-header d-flex align-items-center">
                    <span class="fiscal-step-number mr-2" :class="numberClass(step.status)">{{ step.number }}</span>
                    <h3 class="card-title mb-0">{{ step.title }}</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <span class="badge" :class="badgeClass(step.status)">{{ step.status_label }}</span>
                    </p>
                    <dl class="row mb-0 small">
                        <dt class="col-5">Periodo</dt>
                        <dd class="col-7">{{ step.period || '-' }}</dd>
                        <dt class="col-5">RFC</dt>
                        <dd class="col-7">{{ step.rfc || 'No configurado' }}</dd>
                        <dt class="col-5">Ultima ejecucion</dt>
                        <dd class="col-7">{{ step.last_run_label || 'Sin ejecucion' }}</dd>
                        <dt class="col-5">Usuario</dt>
                        <dd class="col-7">{{ step.user || 'No disponible' }}</dd>
                    </dl>
                </div>
                <div class="card-footer small">
                    <strong>Observaciones:</strong> {{ step.notes || '-' }}
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">&iquest;C&oacute;mo cerrar un periodo fiscal?</h3>
        </div>
        <div class="card-body">
            <ol class="mb-0">
                <li>Descargar CFDI SAT.</li>
                <li>Construir libro fiscal.</li>
                <li>Validar libro fiscal.</li>
                <li>Revisar IVA mensual.</li>
                <li>Revisar conciliaci&oacute;n fiscal-contable.</li>
                <li>Generar borrador de p&oacute;liza.</li>
                <li>Revisar p&oacute;liza.</li>
                <li>Contabilizar p&oacute;liza.</li>
                <li>Generar DIOT.</li>
                <li>Cerrar periodo fiscal.</li>
            </ol>
        </div>
        <div class="card-footer text-muted">
            Esta pantalla es de solo lectura. Las acciones fiscales se ejecutan desde sus pantallas o tareas correspondientes.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-fiscal-closing');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var closingDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/closing_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialClosing = <?php echo json_encode((array) $closing, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-closing',
        data: {
            loading: false,
            errorMessage: '',
            closing: initialClosing
        },
        mounted: function() {
            this.loadClosing();
        },
        methods: {
            loadClosing: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var query = '?rfc=' + encodeURIComponent(initialRfc || '') + '&period=' + encodeURIComponent(initialPeriod || '');

                fetch(closingDataUrl + query, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar el centro de cierre fiscal.';
                            return;
                        }

                        self.closing = data.closing || self.closing;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de cierre fiscal.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            cardClass: function(status) {
                if (status === 'completado') return 'card-outline card-success';
                if (status === 'advertencia') return 'card-outline card-warning';
                if (status === 'error') return 'card-outline card-danger';
                return 'card-outline card-secondary';
            },
            badgeClass: function(status) {
                if (status === 'completado') return 'badge-success';
                if (status === 'advertencia') return 'badge-warning';
                if (status === 'error') return 'badge-danger';
                return 'badge-secondary';
            },
            numberClass: function(status) {
                if (status === 'completado') return 'bg-success text-white';
                if (status === 'advertencia') return 'bg-warning text-dark';
                if (status === 'error') return 'bg-danger text-white';
                return 'bg-secondary text-white';
            }
        }
    });
});
</script>
