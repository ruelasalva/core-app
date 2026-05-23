<div id="app-web">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ integrations.length }}</h3>
                    <p>Integraciones</p>
                </div>
                <div class="icon"><i class="bi bi-code-slash"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ enabledCount }}</h3>
                    <p>Activas</p>
                </div>
                <div class="icon"><i class="bi bi-toggle-on"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.analytics }}</h3>
                    <p>Consentimientos analytics</p>
                </div>
                <div class="icon"><i class="bi bi-bar-chart"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.marketing }}</h3>
                    <p>Consentimientos marketing</p>
                </div>
                <div class="icon"><i class="bi bi-bullseye"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-integrations" role="tab">
                        <i class="bi bi-code-slash mr-1"></i> Integraciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-consent" role="tab">
                        <i class="bi bi-shield-check mr-1"></i> Cookies
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando web...</p>
            </div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-integrations" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Analytics, pixeles, captcha y scripts externos</h5>
                        <button class="btn btn-primary btn-sm" @click="newIntegration">
                            <i class="bi bi-plus-lg"></i> Nuevo
                        </button>
                    </div>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Proveedor</th>
                                <th>Tipo</th>
                                <th>Consentimiento</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="integration in integrations" :key="integration.id">
                                <td><code>{{ integration.code }}</code></td>
                                <td>{{ integration.name }}</td>
                                <td>{{ integration.provider || '-' }}</td>
                                <td>{{ integration.integration_type }}</td>
                                <td>
                                    <span class="badge badge-light">{{ integration.consent_category }}</span>
                                </td>
                                <td>
                                    <span class="badge" :class="integration.enabled == 1 ? 'badge-success' : 'badge-secondary'">
                                        {{ integration.enabled == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-warning" @click="editIntegration(integration)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-consent" role="tabpanel">
                    <h5>Preferencias de cookies</h5>
                    <p class="text-muted mb-3">Esta base guarda consentimiento como 1 = aceptado y 0 = rechazado. Necessary siempre queda activa.</p>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Total registros</th>
                                <td>{{ stats.total }}</td>
                            </tr>
                            <tr>
                                <th>Analytics aceptado</th>
                                <td>{{ stats.analytics }}</td>
                            </tr>
                            <tr>
                                <th>Marketing aceptado</th>
                                <td>{{ stats.marketing }}</td>
                            </tr>
                            <tr>
                                <th>Personalizacion aceptada</th>
                                <td>{{ stats.personalization }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-integration" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ integrationForm.id ? 'Editar' : 'Nueva' }} Integracion</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-integration')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Codigo</label>
                                <input class="form-control" v-model="integrationForm.code">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input class="form-control" v-model="integrationForm.name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Proveedor</label>
                                <input class="form-control" v-model="integrationForm.provider">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo</label>
                                <select class="form-control" v-model="integrationForm.integration_type">
                                    <option value="analytics">Analytics</option>
                                    <option value="tag_manager">Tag manager</option>
                                    <option value="pixel">Pixel</option>
                                    <option value="captcha">Captcha</option>
                                    <option value="map">Mapa</option>
                                    <option value="contact">Contacto flotante</option>
                                    <option value="messenger">Messenger</option>
                                    <option value="script">Script</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ambiente</label>
                                <select class="form-control" v-model="integrationForm.environment">
                                    <option value="production">Produccion</option>
                                    <option value="staging">Staging</option>
                                    <option value="development">Desarrollo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Llave publica / ID</label>
                                <input class="form-control" v-model="integrationForm.public_key">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Valor publico</label>
                                <input class="form-control" v-model="integrationForm.public_value">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Valor secreto</label>
                                <input class="form-control" v-model="integrationForm.secret_value" :placeholder="integrationForm.has_secret ? 'Se conserva el valor guardado' : ''">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Categoria consentimiento</label>
                                <select class="form-control" v-model="integrationForm.consent_category">
                                    <option value="necessary">Necesarias</option>
                                    <option value="analytics">Analytics</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="personalization">Personalizacion</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Configuracion JSON</label>
                                <textarea class="form-control" rows="3" v-model="integrationForm.settings_json"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="integration-enabled" v-model="integrationForm.enabled">
                                <label class="custom-control-label" for="integration-enabled">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="integration-frontend" v-model="integrationForm.load_in_frontend">
                                <label class="custom-control-label" for="integration-frontend">Frontend</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="integration-admin" v-model="integrationForm.load_in_admin">
                                <label class="custom-control-label" for="integration-admin">Admin</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="integration-consent" v-model="integrationForm.requires_consent">
                                <label class="custom-control-label" for="integration-consent">Requiere consentimiento</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-integration')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveIntegration">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-web',
        data: {
            loading: true,
            integrations: [],
            stats: { total: 0, analytics: 0, marketing: 0, personalization: 0 },
            integrationForm: {}
        },
        computed: {
            enabledCount() {
                return this.integrations.filter(item => item.enabled == 1).length;
            }
        },
        mounted() {
            this.loadData();
        },
        methods: {
            emptyIntegration() {
                return {
                    id: null,
                    code: '',
                    name: '',
                    provider: '',
                    integration_type: 'script',
                    environment: 'production',
                    public_key: '',
                    public_value: '',
                    secret_value: '',
                    settings_json: '',
                    enabled: false,
                    has_secret: 0,
                    load_in_frontend: true,
                    load_in_admin: false,
                    requires_consent: true,
                    consent_category: 'analytics',
                    sort_order: 0
                };
            },
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/web/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.integrations = data.integrations || [];
                        this.stats = data.stats || this.stats;
                    });
            },
            newIntegration() {
                this.integrationForm = this.emptyIntegration();
                this.showModal('modal-integration');
            },
            editIntegration(integration) {
                this.integrationForm = Object.assign(this.emptyIntegration(), integration, {
                    enabled: this.asBool(integration.enabled),
                    load_in_frontend: this.asBool(integration.load_in_frontend),
                    load_in_admin: this.asBool(integration.load_in_admin),
                    requires_consent: this.asBool(integration.requires_consent)
                });
                this.showModal('modal-integration');
            },
            asBool(value) {
                return value === true || value === 1 || value === '1' || value === 'true';
            },
            saveIntegration() {
                fetch('<?php echo Uri::create('admin/web/save_integration'); ?>', {
                    ...window.coreAppFetchOptions(this.integrationForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.integrations = data.integrations || [];
                    this.hideModal('modal-integration');
                });
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(element).show();
                    return;
                }
                if (window.jQuery && $.fn.modal) $('#' + id).modal('show');
            },
            hideModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) {
                    const instance = bootstrap.Modal.getInstance(element);
                    if (instance) instance.hide();
                } else if (window.jQuery && $.fn.modal) {
                    $('#' + id).modal('hide');
                }
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            }
        }
    });
};
</script>
