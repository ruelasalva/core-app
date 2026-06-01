<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .fiscal-validation-row { cursor: pointer; }
    .fiscal-validation-row.table-active td { background-color: #e9f3ff; }
    .fiscal-validation-summary pre {
        white-space: pre-wrap;
        margin-bottom: 0;
        font-size: .82rem;
    }
</style>

<div id="app-fiscal-validations" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ validations.rfc || 'No configurado' }} &middot;
                Periodo {{ validations.period }} &middot;
                Fuente {{ validations.rfc_source_label || 'No configurado' }}
            </div>
        </div>
        <div class="col-md-4">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/validations'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="validations.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in validations.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number(validations.items.length) }}</h3>
                    <p>Validaciones del periodo</p>
                </div>
                <div class="icon"><i class="bi bi-check2-square"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box" :class="latestStatusClass">
                <div class="inner">
                    <h3>{{ validations.latest ? validations.latest.status_label : 'Sin validar' }}</h3>
                    <p>Ultima validaci&oacute;n</p>
                </div>
                <div class="icon"><i class="bi bi-clipboard-check"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ validations.latest ? validations.latest.executed_at_label : 'Sin fecha' }}</h3>
                    <p>Fecha de ejecuci&oacute;n</p>
                </div>
                <div class="icon"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Historial de validaciones</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>RFC</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th class="text-right">Advertencias</th>
                                    <th class="text-right">Errores</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in validations.items" :key="item.id" class="fiscal-validation-row" :class="{ 'table-active': selected && selected.id === item.id }" @click="selectRow(item)">
                                    <td>{{ item.fiscal_period }}</td>
                                    <td>{{ item.taxpayer_rfc }}</td>
                                    <td>{{ item.validation_type_label }}</td>
                                    <td><span class="badge" :class="badgeClass(item.status)">{{ item.status_label }}</span></td>
                                    <td class="text-right">{{ number(item.warnings_count) }}</td>
                                    <td class="text-right">{{ number(item.errors_count) }}</td>
                                    <td>{{ item.executed_at_label }}</td>
                                    <td>{{ item.executed_by_label }}</td>
                                </tr>
                                <tr v-if="validations.items.length === 0">
                                    <td colspan="8" class="text-center text-muted">Sin validaciones fiscales persistidas para este periodo.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    Las validaciones se guardan al ejecutar la tarea validatefiscalledger. Las validaciones anteriores a esta fase no aparecer&aacute;n hasta volver a ejecutarse.
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-outline card-info fiscal-validation-summary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detalle de validaci&oacute;n</h3>
                </div>
                <div class="card-body">
                    <div v-if="selected">
                        <dl class="row small">
                            <dt class="col-5">Estado</dt>
                            <dd class="col-7"><span class="badge" :class="badgeClass(selected.status)">{{ selected.status_label }}</span></dd>
                            <dt class="col-5">Periodo</dt>
                            <dd class="col-7">{{ selected.fiscal_period }}</dd>
                            <dt class="col-5">RFC</dt>
                            <dd class="col-7">{{ selected.taxpayer_rfc }}</dd>
                            <dt class="col-5">Fecha</dt>
                            <dd class="col-7">{{ selected.executed_at_label }}</dd>
                            <dt class="col-5">Usuario</dt>
                            <dd class="col-7">{{ selected.executed_by_label }}</dd>
                            <dt class="col-5">Advertencias</dt>
                            <dd class="col-7">{{ number(selected.warnings_count) }}</dd>
                            <dt class="col-5">Errores</dt>
                            <dd class="col-7">{{ number(selected.errors_count) }}</dd>
                        </dl>

                        <h6>Resumen</h6>
                        <pre class="bg-light border rounded p-2">{{ summaryText(selected.summary) }}</pre>
                    </div>
                    <div v-if="!selected" class="text-muted">
                        Selecciona una validaci&oacute;n para revisar su resumen.
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Comando de validaci&oacute;n</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Ejecuta la validaci&oacute;n desde terminal para persistir el resultado:</p>
                    <code>php oil refine validatefiscalledger --rfc={{ validations.rfc || 'RFC' }} --period={{ validations.period || 'YYYY-MM' }}</code>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-fiscal-validations');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var validationsDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/validations_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialValidations = <?php echo json_encode((array) $validations, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-validations',
        data: {
            loading: false,
            errorMessage: '',
            validations: initialValidations,
            selected: initialValidations.items && initialValidations.items.length ? initialValidations.items[0] : null
        },
        computed: {
            latestStatusClass: function() {
                if (!this.validations.latest) return 'bg-secondary';
                if (this.validations.latest.status === 'ok') return 'bg-success';
                if (this.validations.latest.status === 'warning') return 'bg-warning';
                if (this.validations.latest.status === 'error') return 'bg-danger';
                return 'bg-secondary';
            }
        },
        mounted: function() {
            this.loadValidations();
        },
        methods: {
            loadValidations: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var query = '?rfc=' + encodeURIComponent(initialRfc || '') + '&period=' + encodeURIComponent(initialPeriod || '');

                fetch(validationsDataUrl + query, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudieron cargar las validaciones fiscales.';
                            return;
                        }

                        self.validations = data.validations || self.validations;
                        self.selected = self.validations.items && self.validations.items.length ? self.validations.items[0] : null;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de validaciones fiscales.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            selectRow: function(item) {
                this.selected = item;
            },
            badgeClass: function(status) {
                if (status === 'ok') return 'badge-success';
                if (status === 'warning') return 'badge-warning';
                if (status === 'error') return 'badge-danger';
                return 'badge-secondary';
            },
            number: function(value) {
                return Number(value || 0).toLocaleString('es-MX');
            },
            summaryText: function(summary) {
                if (!summary) {
                    return 'Sin resumen disponible.';
                }
                return JSON.stringify(summary, null, 2);
            }
        }
    });
});
</script>
