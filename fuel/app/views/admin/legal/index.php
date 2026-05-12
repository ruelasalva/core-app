<div id="app-legal">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.documents }}</h3>
                    <p>Documentos</p>
                </div>
                <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ stats.consents }}</h3>
                    <p>Consentimientos</p>
                </div>
                <div class="icon"><i class="bi bi-check2-square"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.cookie_preferences }}</h3>
                    <p>Preferencias cookies</p>
                </div>
                <div class="icon"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.marketing }}</h3>
                    <p>Marketing aceptado</p>
                </div>
                <div class="icon"><i class="bi bi-bullseye"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-documents" role="tab">
                        <i class="bi bi-file-earmark-text mr-1"></i> Documentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-cookies" role="tab">
                        <i class="bi bi-shield-check mr-1"></i> Cookies
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando legal...</p>
            </div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-documents" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Documentos legales versionados</h5>
                        <button class="btn btn-primary btn-sm" @click="newDocument">
                            <i class="bi bi-plus-lg"></i> Nuevo
                        </button>
                    </div>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Shortcode</th>
                                <th>Titulo</th>
                                <th>Categoria</th>
                                <th>Tipo</th>
                                <th>Version</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in documents" :key="document.id">
                                <td><code>{{ document.shortcode }}</code></td>
                                <td>{{ document.title }}</td>
                                <td>{{ document.category }}</td>
                                <td>{{ document.document_type }}</td>
                                <td>{{ document.version }}</td>
                                <td>
                                    <span class="badge" :class="document.active == 1 ? 'badge-success' : 'badge-secondary'">
                                        {{ document.active == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-warning" @click="editDocument(document)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-cookies" role="tabpanel">
                    <h5>Ultimas preferencias de cookies</h5>
                    <p class="text-muted">Convencion Core-App: 1 = aceptado, 0 = rechazado.</p>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Token</th>
                                <th>Analytics</th>
                                <th>Marketing</th>
                                <th>Personalizacion</th>
                                <th>IP</th>
                                <th>Actualizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pref in cookie_preferences" :key="pref.id">
                                <td>{{ pref.id }}</td>
                                <td>{{ pref.user_id || 'Anonimo' }}</td>
                                <td><code>{{ pref.token || '-' }}</code></td>
                                <td>{{ pref.analytics == 1 ? 'Acepta' : 'Rechaza' }}</td>
                                <td>{{ pref.marketing == 1 ? 'Acepta' : 'Rechaza' }}</td>
                                <td>{{ pref.personalization == 1 ? 'Acepta' : 'Rechaza' }}</td>
                                <td>{{ pref.ip_address || '-' }}</td>
                                <td>{{ pref.updated_at || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-document" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ documentForm.id ? 'Editar' : 'Nuevo' }} Documento</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-document')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Shortcode</label>
                                <input class="form-control" v-model="documentForm.shortcode">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Titulo</label>
                                <input class="form-control" v-model="documentForm.title">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Categoria</label>
                                <select class="form-control" v-model="documentForm.category">
                                    <option value="general">General</option>
                                    <option value="visitante">Visitante</option>
                                    <option value="cliente">Cliente</option>
                                    <option value="proveedor">Proveedor</option>
                                    <option value="socio">Socio</option>
                                    <option value="empleado">Empleado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo</label>
                                <select class="form-control" v-model="documentForm.document_type">
                                    <option value="aviso_privacidad">Aviso privacidad</option>
                                    <option value="terminos">Terminos</option>
                                    <option value="cookies">Cookies</option>
                                    <option value="politicas">Politicas</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Version</label>
                                <input class="form-control" v-model="documentForm.version">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Contenido</label>
                                <textarea class="form-control" rows="7" v-model="documentForm.content"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="document-active" v-model="documentForm.active">
                                <label class="custom-control-label" for="document-active">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="document-required" v-model="documentForm.required">
                                <label class="custom-control-label" for="document-required">Obligatorio</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="document-download" v-model="documentForm.allow_download">
                                <label class="custom-control-label" for="document-download">Permitir descarga</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-document')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveDocument">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-legal',
        data: {
            loading: true,
            documents: [],
            cookie_preferences: [],
            stats: { documents: 0, consents: 0, cookie_preferences: 0, analytics: 0, marketing: 0 },
            documentForm: {}
        },
        mounted() {
            this.loadData();
        },
        methods: {
            emptyDocument() {
                return {
                    id: null,
                    category: 'general',
                    document_type: 'otros',
                    shortcode: '',
                    title: '',
                    content: '',
                    version: '1.0',
                    required: false,
                    active: true,
                    allow_download: false
                };
            },
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/legal/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.documents = data.documents || [];
                        this.cookie_preferences = data.cookie_preferences || [];
                        this.stats = data.stats || this.stats;
                    });
            },
            newDocument() {
                this.documentForm = this.emptyDocument();
                this.showModal('modal-document');
            },
            editDocument(document) {
                this.documentForm = Object.assign(this.emptyDocument(), document, {
                    required: document.required == 1,
                    active: document.active == 1,
                    allow_download: document.allow_download == 1
                });
                this.showModal('modal-document');
            },
            saveDocument() {
                fetch('<?php echo Uri::create('admin/legal/save_document'); ?>', {
                    ...window.coreAppFetchOptions(this.documentForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.documents = data.documents || [];
                    this.hideModal('modal-document');
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
