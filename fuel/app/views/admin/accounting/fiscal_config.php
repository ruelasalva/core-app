<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
</style>

<div id="app-accounting-fiscal-config" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1">Configuracion Fiscal Contable</h4>
            <div class="text-muted">Relaciona impuestos fiscales del libro SAT con cuentas contables. Esta pantalla no genera polizas.</div>
        </div>
        <div class="col-md-4 text-md-right">
            <button class="btn btn-primary btn-sm" @click="saveMappings" :disabled="saving">
                <i class="bi bi-save"></i> Guardar configuracion
            </button>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="message" class="alert alert-success">{{ message }}</div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">Cuentas detectadas</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3" v-for="group in detectedAccounts" :key="group.label">
                    <strong>{{ group.label }}</strong>
                    <ul class="pl-3 mb-0 mt-2" v-if="group.matches.length">
                        <li v-for="account in group.matches" :key="group.label + '-' + account.id">{{ account.label }}</li>
                    </ul>
                    <div v-if="!group.matches.length" class="text-muted mt-2">Sin cuenta detectada.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Mapeo fiscal contable</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Impuesto</th>
                            <th>Tipo</th>
                            <th>Sentido</th>
                            <th>Cuenta contable</th>
                            <th>Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="mapping in mappings" :key="mapping.tax_code + '-' + mapping.tax_type + '-' + mapping.direction">
                            <td>
                                <strong>{{ mapping.label }}</strong>
                                <div class="text-muted small">{{ mapping.description }}</div>
                            </td>
                            <td>{{ mapping.tax_code }}</td>
                            <td>{{ mapping.tax_type_label }}</td>
                            <td>{{ mapping.direction_label }}</td>
                            <td style="min-width: 320px;">
                                <select class="form-control form-control-sm" v-model="mapping.account_id">
                                    <option value="0">Selecciona cuenta</option>
                                    <option v-for="account in accountOptions" :key="account.value" :value="account.value">{{ account.label }}</option>
                                </select>
                                <div class="text-muted small mt-1" v-if="mapping.account_label">Actual: {{ mapping.account_label }}</div>
                            </td>
                            <td>
                                <label class="mb-0">
                                    <input type="checkbox" v-model="mapping.active"> Activo
                                </label>
                            </td>
                        </tr>
                        <tr v-if="mappings.length === 0">
                            <td colspan="6" class="text-center text-muted">Sin mapeos fiscales.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Este mapeo se usara despues para conciliacion fiscal-contable y borradores de polizas. No contabiliza movimientos por si mismo.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-accounting-fiscal-config');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var dataUrl = <?php echo json_encode(Uri::create('admin/accounting/fiscal_config_data'), $json_flags); ?>;
    var saveUrl = <?php echo json_encode(Uri::create('admin/accounting/save_fiscal_mappings'), $json_flags); ?>;

    new Vue({
        el: '#app-accounting-fiscal-config',
        data: {
            loading: false,
            saving: false,
            error: '',
            message: '',
            accountOptions: [],
            detectedAccounts: [],
            mappings: []
        },
        mounted: function() {
            this.load();
        },
        methods: {
            load: function() {
                var self = this;
                self.loading = true;
                self.error = '';

                fetch(dataUrl, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse || function(response) { return response.json(); })
                    .then(function(data) {
                        if (!data || data.error) {
                            self.error = data && data.error ? data.error : 'No se pudo cargar la configuracion fiscal contable.';
                            return;
                        }

                        self.accountOptions = data.options && data.options.accounts ? data.options.accounts : [];
                        self.detectedAccounts = data.detected_accounts || [];
                        self.mappings = (data.mappings || []).map(function(mapping) {
                            mapping.account_id = String(mapping.account_id || 0);
                            mapping.active = mapping.active === true || mapping.active === 1 || mapping.active === '1';
                            return mapping;
                        });
                    })
                    .catch(function() {
                        self.error = 'No se pudo conectar con el endpoint de configuracion fiscal contable.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            saveMappings: function() {
                var self = this;
                self.saving = true;
                self.error = '';
                self.message = '';

                var payload = {
                    mappings: self.mappings.map(function(mapping) {
                        return {
                            tax_code: mapping.tax_code,
                            tax_type: mapping.tax_type,
                            direction: mapping.direction,
                            account_id: Number(mapping.account_id || 0),
                            active: mapping.active ? 1 : 0
                        };
                    })
                };

                fetch(saveUrl, window.coreAppFetchOptions(payload))
                    .then(window.coreAppParseJsonResponse || function(response) { return response.json(); })
                    .then(function(data) {
                        if (!data || data.error) {
                            self.error = data && data.error ? data.error : 'No se pudo guardar la configuracion fiscal contable.';
                            return;
                        }

                        self.message = data.message || 'Configuracion fiscal contable guardada.';
                        self.detectedAccounts = data.detected_accounts || self.detectedAccounts;
                        self.mappings = (data.mappings || self.mappings).map(function(mapping) {
                            mapping.account_id = String(mapping.account_id || 0);
                            mapping.active = mapping.active === true || mapping.active === 1 || mapping.active === '1';
                            return mapping;
                        });
                    })
                    .catch(function() {
                        self.error = 'No se pudo conectar con el endpoint de guardado.';
                    })
                    .then(function() {
                        self.saving = false;
                    });
            }
        }
    });
});
</script>
