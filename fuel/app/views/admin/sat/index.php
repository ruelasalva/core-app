<div id="app-sat">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.cfdi }}</h3>
                    <p>CFDI</p>
                </div>
                <div class="icon"><i class="bi bi-file-earmark-xml"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ stats.requests }}</h3>
                    <p>Solicitudes</p>
                </div>
                <div class="icon"><i class="bi bi-cloud-arrow-down"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.missing_xml }}</h3>
                    <p>Sin XML</p>
                </div>
                <div class="icon"><i class="bi bi-file-earmark-x"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.cancelled }}</h3>
                    <p>Cancelados</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
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
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-sat-alerts" role="tab">
                        <i class="bi bi-shield-exclamation mr-1"></i> Alertas CFDI
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
                        <div class="col-md-12 mb-3">
                            <div class="alert" :class="integrations.sat_download && integrations.sat_download.enabled ? 'alert-success' : 'alert-warning'">
                                <strong>Descarga SAT:</strong>
                                {{ integrations.sat_download && integrations.sat_download.enabled ? 'habilitada desde Integraciones' : 'sin conexion habilitada en Integraciones' }}.
                                La descarga directa usa FIEL: .cer, .key y password; no requiere secret key.
                                <a href="<?php echo Uri::create('admin/integrations'); ?>" class="alert-link">Configurar integracion</a>
                            </div>
                            <div class="alert" :class="integrations.pac_billing && integrations.pac_billing.enabled ? 'alert-success' : 'alert-info'">
                                <strong>PAC facturacion:</strong>
                                {{ integrations.pac_billing && integrations.pac_billing.enabled ? 'Factura.com habilitado' : 'pendiente de conexion PAC en Integraciones' }}.
                                <a href="<?php echo Uri::create('admin/integrations'); ?>" class="alert-link">Configurar PAC</a>
                            </div>
                        </div>
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
                                <th>Dias</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="credential in credentials" :key="credential.id">
                                <td>{{ credential.credential_type }}</td>
                                <td>{{ credential.rfc }}</td>
                                <td>{{ credential.cer_original_name || credential.cer_path || '-' }}</td>
                                <td>{{ credential.key_original_name || credential.key_path || '-' }}</td>
                                <td>{{ credential.valid_from || '-' }} / {{ credential.valid_until || '-' }}</td>
                                <td>
                                    <span class="badge" :class="validityBadge(credential.validity_status)">
                                        {{ credential.days_remaining === null ? 'Sin fecha' : credential.days_remaining + ' dias' }}
                                    </span>
                                </td>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Ultimas solicitudes</h5>
                        <button class="btn btn-primary btn-sm" @click="newRequest">
                            <i class="bi bi-plus-circle"></i> Nueva solicitud
                        </button>
                    </div>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Descarga</th>
                                <th>Direccion</th>
                                <th>Rango</th>
                                <th>Estado</th>
                                <th>Paquetes</th>
                                <th>Procesados</th>
                                <th>Faltantes</th>
                                <th>Cancelados</th>
                                <th>Creada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="request in requests" :key="request.id">
                                <td>{{ request.id }}</td>
                                <td>{{ request.request_type }}</td>
                                <td>{{ request.download_type }}</td>
                                <td>{{ request.direction }}</td>
                                <td>{{ request.date_from }} / {{ request.date_to }}</td>
                                <td>{{ request.status }}</td>
                                <td>{{ request.package_count }}</td>
                                <td>{{ request.processed_count }}</td>
                                <td>{{ request.missing_count }}</td>
                                <td>{{ request.cancelled_count }}</td>
                                <td>{{ request.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-sat-alerts" role="tabpanel">
                    <h5>CFDI para revision</h5>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>UUID</th>
                                <th>Direccion</th>
                                <th>Emisor</th>
                                <th>Receptor</th>
                                <th>Total</th>
                                <th>Estado SAT</th>
                                <th>Origen</th>
                                <th>Validacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cfdi in cfdiAlerts" :key="cfdi.id">
                                <td><code>{{ cfdi.uuid }}</code></td>
                                <td>{{ cfdi.direction }}</td>
                                <td>{{ cfdi.emitter_rfc }}</td>
                                <td>{{ cfdi.receiver_rfc }}</td>
                                <td>{{ cfdi.total }}</td>
                                <td>
                                    <span class="badge" :class="cfdi.sat_status == 'cancelado' ? 'badge-danger' : 'badge-secondary'">
                                        {{ cfdi.sat_status || 'pendiente' }}
                                    </span>
                                </td>
                                <td>{{ cfdi.origin }}</td>
                                <td>{{ cfdi.last_validated_at }}</td>
                            </tr>
                            <tr v-if="cfdiAlerts.length === 0">
                                <td colspan="8" class="text-muted">Sin alertas CFDI por ahora.</td>
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
                                <input class="form-control" type="date" v-model="credentialForm.valid_from" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Vigente hasta</label>
                                <input class="form-control" type="date" v-model="credentialForm.valid_until" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Archivo .CER</label>
                                <div class="input-group">
                                    <input class="form-control" :value="credentialForm.cer_original_name || credentialForm.cer_path" readonly>
                                    <div class="input-group-append">
                                        <label class="btn btn-outline-primary mb-0">
                                            <i class="bi bi-upload"></i>
                                            <input type="file" accept=".cer" class="d-none" @change="uploadCredentialFile($event, 'cer')">
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted">La vigencia se toma del certificado.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Archivo .KEY</label>
                                <div class="input-group">
                                    <input class="form-control" :value="credentialForm.key_original_name || credentialForm.key_path" readonly>
                                    <div class="input-group-append">
                                        <label class="btn btn-outline-primary mb-0">
                                            <i class="bi bi-upload"></i>
                                            <input type="file" accept=".key" class="d-none" @change="uploadCredentialFile($event, 'key')">
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted">Guarda la credencial antes de cargar archivos.</small>
                            </div>
                        </div>
                        <div class="col-md-12" v-if="credentialForm.certificate_serial">
                            <div class="alert alert-light border">
                                <strong>Serie:</strong> {{ credentialForm.certificate_serial }}
                                <span class="ml-3"><strong>Vence:</strong> {{ credentialForm.days_remaining }} dias</span>
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

    <div class="modal fade" id="modal-request" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Nueva solicitud SAT</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-request')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo descarga</label>
                                <select class="form-control" v-model="requestForm.download_type">
                                    <option value="xml">XML</option>
                                    <option value="metadata">Metadata</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Direccion</label>
                                <select class="form-control" v-model="requestForm.direction">
                                    <option value="received">Recibidos</option>
                                    <option value="issued">Emitidos</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Desde</label>
                                <input class="form-control" type="date" v-model="requestForm.date_from">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hasta</label>
                                <input class="form-control" type="date" v-model="requestForm.date_to">
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        Esta accion registra la solicitud local. La llamada real al SAT se conectara despues con el servicio de descarga masiva y cron.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-request')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveRequest">Guardar solicitud</button>
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
            cfdiAlerts: [],
            stats: { cfdi: 0, requests: 0, packages: 0, credentials: 0, missing_xml: 0, cancelled: 0, unvalidated: 0 },
            integrations: {},
            credentialForm: {},
            requestForm: {}
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
                        this.integrations = data.integrations || {};
                        this.credentials = data.credentials || [];
                        this.requests = data.requests || [];
                        this.cfdiAlerts = data.cfdi_alerts || [];
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
                    const updated = this.credentials.find(item => String(item.id) === String(this.credentialForm.id));
                    if (updated) this.credentialForm = Object.assign(this.emptyCredential(), updated, { active: updated.active == 1, password: '' });
                    this.hideModal('modal-credential');
                });
            },
            uploadCredentialFile(event, fileType) {
                const file = event.target.files[0];
                event.target.value = '';
                if (!file) return;
                if (!this.credentialForm.id) {
                    alert('Guarda primero la credencial y despues carga el archivo.');
                    return;
                }
                const form = new FormData();
                form.append('credential_id', this.credentialForm.id);
                form.append('file_type', fileType);
                form.append('file', file);
                form.append(window.coreAppCsrfKey, fuel_csrf_token());
                fetch('<?php echo Uri::create('admin/sat/upload_credential_file'); ?>', { method: 'POST', body: form })
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.credentials = data.credentials || [];
                        const updated = this.credentials.find(item => String(item.id) === String(this.credentialForm.id));
                        if (updated) this.credentialForm = Object.assign(this.emptyCredential(), updated, { active: updated.active == 1, password: '' });
                    });
            },
            validityBadge(status) {
                if (status === 'expired') return 'badge-danger';
                if (status === 'warning') return 'badge-warning';
                if (status === 'valid') return 'badge-success';
                return 'badge-secondary';
            },
            newRequest() {
                const today = new Date().toISOString().slice(0, 10);
                this.requestForm = {
                    download_type: 'metadata',
                    direction: 'received',
                    date_from: today,
                    date_to: today
                };
                this.showModal('modal-request');
            },
            saveRequest() {
                fetch('<?php echo Uri::create('admin/sat/save_request'); ?>', {
                    ...window.coreAppFetchOptions(this.requestForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.requests = data.requests || [];
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-request');
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
