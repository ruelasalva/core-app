<div id="app-documents">
    <div class="row">
        <div class="col-lg-4">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.documents || 0 }}</h3><p>Documentos</p></div>
                <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.evidence || 0 }}</h3><p>Evidencias</p></div>
                <div class="icon"><i class="bi bi-paperclip"></i></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.links || 0 }}</h3><p>Vinculos</p></div>
                <div class="icon"><i class="bi bi-link-45deg"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Repositorio documental</h3>
                    <p class="text-muted small mb-0">Documentos y evidencias reutilizables para terceros, tickets, facturas, ordenes y cotizaciones.</p>
                </div>
                <button class="btn btn-primary btn-sm" @click="showModal('modal-document-upload')">
                    <i class="bi bi-upload"></i> Subir documento
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando documentos...</p>
            </div>

            <table v-show="!loading" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Tipo</th>
                        <th>Archivo</th>
                        <th>Visibilidad</th>
                        <th>Evidencia</th>
                        <th>Fecha</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="document in documents" :key="document.id">
                        <td>{{ document.title }}</td>
                        <td><span class="badge badge-light">{{ document.document_type }}</span></td>
                        <td>
                            <a :href="assetUrl(document.file_path)" target="_blank">{{ document.original_name || document.file_path }}</a>
                        </td>
                        <td>{{ document.visibility }}</td>
                        <td>{{ document.is_evidence == 1 ? 'Si' : 'No' }}</td>
                        <td>{{ document.created_at }}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-primary" @click="openLink(document)">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="documents.length === 0">
                        <td colspan="7" class="text-center text-muted">Sin documentos</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-document-upload" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Subir documento</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-document-upload')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Titulo</label>
                                <input class="form-control" v-model="uploadForm.title">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo documento</label>
                                <select class="form-control" v-model="uploadForm.document_type">
                                    <option value="general">General</option>
                                    <option value="fiscal">Fiscal</option>
                                    <option value="evidence">Evidencia</option>
                                    <option value="contract">Contrato</option>
                                    <option value="support">Soporte</option>
                                    <option value="ticket">Ticket</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Descripcion</label>
                                <textarea class="form-control" rows="2" v-model="uploadForm.description"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Visibilidad</label>
                                <select class="form-control" v-model="uploadForm.visibility">
                                    <option value="internal">Interna</option>
                                    <option value="portal">Portal externo</option>
                                    <option value="public">Publica</option>
                                    <option value="private">Privada</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-control custom-switch mt-4">
                                <input type="checkbox" class="custom-control-input" id="is-evidence" v-model="uploadForm.is_evidence">
                                <label class="custom-control-label" for="is-evidence">Marcar como evidencia</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Archivo</label>
                                <input type="file" class="form-control" @change="setFile">
                                <small class="text-muted">PDF, XML, imagenes, Office, CSV o TXT. Maximo 15 MB.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Vincular a</label>
                                <select class="form-control" v-model="uploadForm.entity_type">
                                    <option value="">Sin vinculo</option>
                                    <option v-for="option in options.entity_types" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5" v-if="uploadForm.entity_type === 'party'">
                            <div class="form-group">
                                <label>Tercero</label>
                                <select class="form-control" v-model="uploadForm.entity_id">
                                    <option value="0">Selecciona</option>
                                    <option v-for="option in options.parties" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5" v-if="uploadForm.entity_type && uploadForm.entity_type !== 'party'">
                            <div class="form-group">
                                <label>ID entidad</label>
                                <input type="number" class="form-control" v-model="uploadForm.entity_id">
                            </div>
                        </div>
                        <div class="col-md-3" v-if="uploadForm.entity_type">
                            <div class="form-group">
                                <label>Relacion</label>
                                <input class="form-control" v-model="uploadForm.relation_type">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-document-upload')">Cerrar</button>
                    <button class="btn btn-primary" @click="uploadDocument" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                        Subir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-document-link" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Vincular documento</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-document-link')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div class="form-group">
                        <label>Entidad</label>
                        <select class="form-control" v-model="linkForm.entity_type">
                            <option v-for="option in options.entity_types" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="form-group" v-if="linkForm.entity_type === 'party'">
                        <label>Tercero</label>
                        <select class="form-control" v-model="linkForm.entity_id">
                            <option value="0">Selecciona</option>
                            <option v-for="option in options.parties" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="form-group" v-else>
                        <label>ID entidad</label>
                        <input type="number" class="form-control" v-model="linkForm.entity_id">
                    </div>
                    <div class="form-group">
                        <label>Tipo relacion</label>
                        <input class="form-control" v-model="linkForm.relation_type">
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" rows="2" v-model="linkForm.notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-document-link')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveLink">Vincular</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-documents',
        data: {
            loading: true,
            saving: false,
            error: '',
            documents: [],
            links: [],
            options: { parties: [], entity_types: [] },
            stats: {},
            selectedFile: null,
            uploadForm: {},
            linkForm: {}
        },
        mounted() {
            this.resetUpload();
            this.loadData();
        },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/documents/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) { this.error = data.error; return; }
                        this.documents = data.documents || [];
                        this.links = data.links || [];
                        this.options = data.options || { parties: [], entity_types: [] };
                        this.stats = data.stats || {};
                    });
            },
            resetUpload() {
                this.uploadForm = {
                    title: '',
                    description: '',
                    document_type: 'general',
                    visibility: 'internal',
                    is_evidence: false,
                    entity_type: '',
                    entity_id: 0,
                    relation_type: 'attachment',
                    link_notes: ''
                };
                this.selectedFile = null;
                this.error = '';
            },
            setFile(event) {
                this.selectedFile = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            },
            uploadDocument() {
                if (!this.selectedFile) {
                    this.error = 'Selecciona un archivo.';
                    return;
                }
                this.saving = true;
                this.error = '';

                const data = new FormData();
                Object.keys(this.uploadForm).forEach(key => data.append(key, this.uploadForm[key]));
                data.append('file', this.selectedFile);
                data.append(window.coreAppCsrfKey, fuel_csrf_token());

                fetch('<?php echo Uri::create('admin/documents/upload'); ?>', { method: 'POST', body: data })
                    .then(res => res.json())
                    .then(data => {
                        this.saving = false;
                        if (data.error) { this.error = data.error; return; }
                        this.documents = data.documents || [];
                        this.links = data.links || [];
                        this.stats = data.stats || {};
                        this.resetUpload();
                        this.hideModal('modal-document-upload');
                    })
                    .catch(() => {
                        this.saving = false;
                        this.error = 'No se pudo subir el documento.';
                    });
            },
            openLink(document) {
                this.linkForm = {
                    document_id: document.id,
                    entity_type: 'party',
                    entity_id: 0,
                    relation_type: 'attachment',
                    notes: ''
                };
                this.error = '';
                this.showModal('modal-document-link');
            },
            saveLink() {
                fetch('<?php echo Uri::create('admin/documents/link'); ?>', {
                    ...window.coreAppFetchOptions(this.linkForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.links = data.links || [];
                    this.stats = data.stats || {};
                    this.hideModal('modal-document-link');
                });
            },
            assetUrl(path) {
                if (!path) return '';
                if (/^https?:\/\//.test(path)) return path;
                return '<?php echo Uri::base(false); ?>' + path.replace(/^\/+/, '');
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (id === 'modal-document-upload') this.resetUpload();
                if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(element).show(); return; }
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
