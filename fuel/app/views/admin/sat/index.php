<div id="app-sat">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.cfdi }}</h3>
                    <p>CFDI</p>
                </div>
                <div class="icon"><i class="bi bi-file-earmark-xml"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ stats.requests }}</h3>
                    <p>Solicitudes</p>
                </div>
                <div class="icon"><i class="bi bi-cloud-arrow-down"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.packages }}</h3>
                    <p>Paquetes</p>
                </div>
                <div class="icon"><i class="bi bi-box-seam"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.credentials }}</h3>
                    <p>Credenciales</p>
                </div>
                <div class="icon"><i class="bi bi-key"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Configuracion y operacion SAT</h3>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo Uri::create('admin/sat/catalogs'); ?>">
                    <i class="bi bi-list-check"></i> Catalogos SAT
                </a>
            </div>
        </div>
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-sat-config" role="tab">
                        <i class="bi bi-gear mr-1"></i> Configuracion
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-sat-credentials" role="tab">
                        <i class="bi bi-key mr-1"></i> Credenciales
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-sat-requests" role="tab">
                        <i class="bi bi-cloud-arrow-down mr-1"></i> Solicitudes
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando SAT...</p>
            </div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-sat-config" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Configuracion general SAT</h5>
                        <button class="btn btn-primary btn-sm" @click="saveConfig">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modo</label>
                                <select class="form-control" v-model="config.mode">
                                    <option value="test">Test</option>
                                    <option value="production">Produccion</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Ruta de almacenamiento</label>
                                <input class="form-control" v-model="config.storage_path">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sat-enabled" v-model="config.enabled">
                                <label class="custom-control-label" for="sat-enabled">SAT activo</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Ultima sincronizacion</label>
                                <input class="form-control" :value="config.last_sync_at" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-sat-credentials" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Credenciales FIEL / CSD</h5>
                        <button class="btn btn-primary btn-sm" @click="newCredential">
                            <i class="bi bi-plus-lg"></i> Nueva
                        </button>
                    </div>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>RFC</th>
                                <th>.CER</th>
                                <th>.KEY</th>
                                <th>Vigencia</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="credential in credentials" :key="credential.id">
                                <td>{{ credential.credential_type }}</td>
                                <td>{{ credential.rfc }}</td>
                                <td>{{ credential.cer_path || '-' }}</td>
                                <td>{{ credential.key_path || '-' }}</td>
                                <td>{{ credential.valid_from || '-' }} / {{ credential.valid_until || '-' }}</td>
                                <td>
                                    <span class="badge" :class="credential.active == 1 ? 'badge-success' : 'badge-secondary'">
                                        {{ credential.active == 1 ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-warning" @click="editCredential(credential)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-sat-requests" role="tabpanel">
                    <h5>Ultimas solicitudes</h5>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Rango</th>
                                <th>Estado</th>
                                <th>Procesados</th>
                                <th>Creada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="request in requests" :key="request.id">
                                <td>{{ request.id }}</td>
                                <td>{{ request.request_type }}</td>
                                <td>{{ request.date_from }} / {{ request.date_to }}</td>
                                <td>{{ request.status }}</td>
                                <td>{{ request.processed_count }}</td>
                                <td>{{ request.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-credential" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ credentialForm.id ? 'Editar' : 'Nueva' }} Credencial SAT</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-credential')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tipo</label>
                                <select class="form-control" v-model="credentialForm.credential_type">
                                    <option value="fiel">FIEL</option>
                                    <option value="csd">CSD</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>RFC</label>
                                <input class="form-control text-uppercase" v-model="credentialForm.rfc">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Vigente desde</label>
                                <input class="form-control" type="date" v-model="credentialForm.valid_from">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Vigente hasta</label>
                                <input class="form-control" type="date" v-model="credentialForm.valid_until">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ruta .CER</label>
                                <input class="form-control" v-model="credentialForm.cer_path">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ruta .KEY</label>
                                <input class="form-control" v-model="credentialForm.key_path">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password llave</label>
                                <input class="form-control" type="password" v-model="credentialForm.password" :placeholder="credentialForm.has_password ? 'Se conserva el valor cifrado' : ''">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-control custom-switch mt-4">
                                <input type="checkbox" class="custom-control-input" id="credential-active" v-model="credentialForm.active">
                                <label class="custom-control-label" for="credential-active">Activa</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notas</label>
                                <textarea class="form-control" rows="3" v-model="credentialForm.notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-credential')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveCredential">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-sat',
        data: {
            loading: true,
            config: { id: null, mode: 'test', enabled: false, storage_path: 'fuel/app/storage/sat', last_sync_at: 'Nunca' },
            credentials: [],
            requests: [],
            stats: { cfdi: 0, requests: 0, packages: 0, credentials: 0 },
            credentialForm: {}
        },
        mounted() {
            this.loadData();
        },
        methods: {
            emptyCredential() {
                return {
                    id: null,
                    credential_type: 'fiel',
                    rfc: '',
                    cer_path: '',
                    key_path: '',
                    password: '',
                    has_password: 0,
                    valid_from: '',
                    valid_until: '',
                    notes: '',
                    active: true
                };
            },
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/sat/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.config = data.config || this.config;
                        this.config.enabled = this.config.enabled == 1;
                        this.credentials = data.credentials || [];
                        this.requests = data.requests || [];
                        this.stats = data.stats || this.stats;
                    });
            },
            saveConfig() {
                fetch('<?php echo Uri::create('admin/sat/save_config'); ?>', {
                    ...window.coreAppFetchOptions(this.config)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.config = data.config || this.config;
                    this.config.enabled = this.config.enabled == 1;
                });
            },
            newCredential() {
                this.credentialForm = this.emptyCredential();
                this.showModal('modal-credential');
            },
            editCredential(credential) {
                this.credentialForm = Object.assign(this.emptyCredential(), credential, {
                    active: credential.active == 1,
                    password: ''
                });
                this.showModal('modal-credential');
            },
            saveCredential() {
                fetch('<?php echo Uri::create('admin/sat/save_credential'); ?>', {
                    ...window.coreAppFetchOptions(this.credentialForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.credentials = data.credentials || [];
                    this.hideModal('modal-credential');
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
