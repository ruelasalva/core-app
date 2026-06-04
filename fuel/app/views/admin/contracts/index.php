<div id="app-contracts">
    <div class="row">
        <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.active || 0 }}</h3><p>Activos</p></div>
                <div class="icon"><i class="bi bi-file-earmark-check"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.expiring || 0 }}</h3><p>Por vencer</p></div>
                <div class="icon"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ stats.expired || 0 }}</h3><p>Vencidos</p></div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title mb-0">Contratos</h3>
                    <div class="ml-auto">
                        <button v-if="permissions.create" class="btn btn-primary btn-sm" @click="openForm()"><i class="bi bi-plus-circle"></i> Nuevo contrato</button>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="message" class="alert alert-success">{{ message }}</div>
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div v-if="options.contract_type_catalog_empty" class="alert alert-warning">
                        No hay tipos de contrato configurados. Ejecuta <code>php oil refine contractsseed</code>.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Numero</th>
                                    <th>Tipo</th>
                                    <th>Tercero</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="contract in contracts" :key="contract.id" :class="{ 'table-primary': selected && selected.id === contract.id }">
                                    <td><strong>{{ contract.contract_number }}</strong><div class="text-muted small">{{ contract.title }}</div></td>
                                    <td>{{ contract.contract_type_label || contract.contract_type }}</td>
                                    <td>{{ contract.party_name || '-' }}</td>
                                    <td>{{ contract.end_date || '-' }}<div class="text-muted small">{{ contract.expiration_label }}</div></td>
                                    <td><span class="badge" :class="statusClass(contract.status)">{{ contract.status_label }}</span></td>
                                    <td>
                                        <button class="btn btn-outline-secondary btn-xs" @click="selectContract(contract)">Detalle</button>
                                        <button v-if="permissions.edit" class="btn btn-outline-primary btn-xs" @click="openForm(contract)">Editar</button>
                                        <select v-if="permissions.status" class="form-control form-control-sm mt-1" v-model="contract.next_status" @change="changeStatus(contract)">
                                            <option value="">Cambiar estado</option>
                                            <option v-for="status in options.statuses" :value="status.value">{{ status.label }}</option>
                                        </select>
                                        <span v-if="!permissions.edit && !permissions.status" class="text-muted small">Solo lectura</span>
                                    </td>
                                </tr>
                                <tr v-if="contracts.length === 0">
                                    <td colspan="6" class="text-center text-muted">Sin contratos registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detalle del contrato</h3>
                </div>
                <div class="card-body">
                    <div v-if="!selected" class="text-muted">Selecciona un contrato para ver documentos, relaciones y eventos.</div>
                    <div v-if="selected">
                        <h5 class="mb-1">{{ selected.contract_number }} - {{ selected.title }}</h5>
                        <div class="text-muted mb-3">{{ selected.party_name || 'Sin tercero' }} / {{ selected.contract_type_label }}</div>

                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'general'}" @click.prevent="tab = 'general'">General</a></li>
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'documents'}" @click.prevent="tab = 'documents'">Documentos</a></li>
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'relations'}" @click.prevent="tab = 'relations'">Relaciones</a></li>
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'events'}" @click.prevent="tab = 'events'">Eventos</a></li>
                        </ul>

                        <div v-show="tab === 'general'">
                            <table class="table table-sm table-bordered">
                                <tr><th>Inicio</th><td>{{ selected.start_date || '-' }}</td></tr>
                                <tr><th>Fin</th><td>{{ selected.end_date || '-' }} / {{ selected.expiration_label }}</td></tr>
                                <tr><th>Valor</th><td>{{ money(selected.contract_value) }} {{ selected.currency_code }}</td></tr>
                                <tr><th>Renovacion</th><td>{{ selected.renewal_type }}</td></tr>
                                <tr><th>Facturacion</th><td>{{ selected.billing_type }}</td></tr>
                                <tr><th>SLA</th><td>Respuesta {{ selected.response_hours || 0 }} h / Resolucion {{ selected.resolution_hours || 0 }} h</td></tr>
                            </table>
                            <p class="mb-1"><strong>Descripcion</strong></p>
                            <div v-if="selected.description" class="border rounded bg-light p-2 contract-rich-preview" v-html="selected.description"></div>
                            <p v-else class="text-muted">Sin descripcion.</p>
                            <p class="mb-1"><strong>Notas</strong></p>
                            <div v-if="selected.notes" class="border rounded bg-light p-2 contract-rich-preview" v-html="selected.notes"></div>
                            <p v-else class="text-muted">Sin notas.</p>
                        </div>

                        <div v-show="tab === 'documents'">
                            <div class="alert alert-info py-2">
                                Adjunta documentos de este contrato desde esta pestaña. El archivo queda en Documentos y aqui se guarda el vinculo al contrato.
                            </div>
                            <div v-if="permissions.upload_document" class="mb-3">
                                <button class="btn btn-primary btn-sm mr-2 mb-1" @click="openUpload('main_contract')">
                                    <i class="bi bi-file-earmark-pdf"></i> Subir contrato PDF
                                </button>
                                <button class="btn btn-outline-primary btn-sm mr-2 mb-1" @click="openUpload('annex')">
                                    <i class="bi bi-paperclip"></i> Subir anexo
                                </button>
                                <button class="btn btn-outline-success btn-sm mr-2 mb-1" @click="openUpload('signed_document')">
                                    <i class="bi bi-file-earmark-check"></i> Subir documento firmado
                                </button>
                                <button class="btn btn-outline-secondary btn-sm mb-1" @click="showLinkDocument = !showLinkDocument; showUpload = false">
                                    <i class="bi bi-link-45deg"></i> Vincular documento existente
                                </button>
                                <div class="text-muted small mt-2">
                                    Tipos permitidos: PDF, JPG, PNG, DOC y DOCX. Usa el tipo de relacion correcto: Contrato principal, Anexo, Evidencia o Documento firmado.
                                </div>
                            </div>

                            <div v-if="permissions.upload_document && showUpload" class="border rounded p-2 mb-3">
                                <h6>Subir documento</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Archivo</label>
                                        <input type="file" class="form-control-file" @change="onFileChange">
                                        <small class="form-text text-muted">Acepta PDF, JPG, PNG, DOC y DOCX.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Tipo</label>
                                        <select class="form-control form-control-sm" v-model="uploadForm.relation_type">
                                            <option v-for="option in documentStructure.relation_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label>Titulo</label>
                                        <input class="form-control form-control-sm" v-model="uploadForm.title">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label>Visibilidad</label>
                                        <select class="form-control form-control-sm" v-model="uploadForm.visibility">
                                            <option v-for="option in options.visibilities" :value="option.value">{{ option.label }}</option>
                                        </select>
                                        <small class="form-text text-muted">{{ visibilityHelp(uploadForm.visibility) }}</small>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <label>Notas</label>
                                        <input class="form-control form-control-sm" v-model="uploadForm.notes">
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm mt-2" @click="uploadDocument">Subir documento</button>
                            </div>

                            <div v-if="permissions.upload_document && showLinkDocument" class="border rounded p-2 mb-3">
                                <h6>Vincular documento existente</h6>
                                <div class="row">
                                    <div class="col-md-7">
                                        <select class="form-control form-control-sm" v-model="linkDocumentForm.document_id">
                                            <option value="0">Selecciona documento</option>
                                            <option v-for="document in availableDocuments" :value="document.value">{{ document.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control form-control-sm" v-model="linkDocumentForm.relation_type">
                                            <option v-for="option in documentStructure.relation_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-outline-primary btn-sm btn-block" @click="linkDocument">Vincular</button>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Tipo</th><th>Documento</th><th>Archivo</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <tr v-for="document in selectedDocuments" :key="document.link_id">
                                        <td>{{ document.relation_label }}</td>
                                        <td>{{ document.title }}<div class="text-muted small">{{ document.created_at }}</div></td>
                                        <td>{{ document.original_name || '-' }}</td>
                                        <td>
                                            <a class="btn btn-outline-secondary btn-xs" :href="document.download_url">Descargar</a>
                                            <button v-if="permissions.upload_document" class="btn btn-outline-danger btn-xs" @click="removeDocumentLink(document)">Quitar</button>
                                        </td>
                                    </tr>
                                    <tr v-if="selectedDocuments.length === 0"><td colspan="4" class="text-muted text-center">Sin documentos.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-show="tab === 'relations'">
                            <div v-if="permissions.link" class="border rounded p-2 mb-3">
                                <h6>Relacionar entidad</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label>Entidad</label>
                                        <select class="form-control form-control-sm" v-model="relationForm.related_entity_type">
                                            <option v-for="option in relationOptions.entity_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>ID</label>
                                        <input type="number" min="1" class="form-control form-control-sm" v-model.number="relationForm.related_entity_id">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Relacion</label>
                                        <select class="form-control form-control-sm" v-model="relationForm.relation_type">
                                            <option v-for="option in relationOptions.relation_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button class="btn btn-primary btn-sm btn-block" @click="saveRelation">Agregar</button>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <input class="form-control form-control-sm" placeholder="Notas" v-model="relationForm.notes">
                                    </div>
                                </div>
                            </div>

                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Modulo</th><th>Entidad</th><th>Relacion</th><th>Notas</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <tr v-for="relation in selectedRelations" :key="relation.id">
                                        <td>{{ relation.related_module }}</td>
                                        <td>{{ relation.related_entity_label }} #{{ relation.related_entity_id }}</td>
                                        <td>{{ relation.relation_type }}</td>
                                        <td>{{ relation.notes || '-' }}</td>
                                        <td><button v-if="permissions.link" class="btn btn-outline-danger btn-xs" @click="removeRelation(relation)">Quitar</button></td>
                                    </tr>
                                    <tr v-if="selectedRelations.length === 0"><td colspan="5" class="text-muted text-center">Sin relaciones.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-show="tab === 'events'">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Fecha</th><th>Evento</th><th>Estado</th><th>Mensaje</th></tr></thead>
                                <tbody>
                                    <tr v-for="event in selectedEvents" :key="event.id">
                                        <td>{{ event.created_at }}</td>
                                        <td>{{ event.event_type }}</td>
                                        <td>{{ event.old_status || '-' }} &rarr; {{ event.new_status || '-' }}</td>
                                        <td>{{ event.message }}</td>
                                    </tr>
                                    <tr v-if="selectedEvents.length === 0"><td colspan="4" class="text-muted text-center">Sin eventos.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-contract" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ form.id ? 'Editar contrato' : 'Nuevo contrato' }}</h5>
                    <button type="button" class="close text-white" @click="hideModal()"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Tipo</label>
                            <select class="form-control" v-model="form.contract_type">
                                <option v-for="option in options.contract_types" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Tercero</label>
                            <select class="form-control" v-model="form.party_id">
                                <option value="0">Sin tercero</option>
                                <option v-for="option in options.parties" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Portal</label>
                            <select class="form-control" v-model="form.portal_code">
                                <option v-for="option in options.portal_codes" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-8 mt-3">
                            <label>Titulo</label>
                            <input class="form-control" v-model="form.title" maxlength="180">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Responsable</label>
                            <select class="form-control" v-model="form.responsible_user_id">
                                <option value="0">Sin responsable</option>
                                <option v-for="option in options.users" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label>Descripcion</label>
                            <textarea id="contract-description-editor" class="form-control" rows="5" v-model="form.description"></textarea>
                            <small class="form-text text-muted">Puedes usar parrafos, listas y texto con formato. No se permiten scripts, iframes ni eventos.</small>
                            <small v-if="richEditorFallbackMessage" class="form-text text-warning">{{ richEditorFallbackMessage }}</small>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label>Notas internas</label>
                            <textarea id="contract-notes-editor" class="form-control" rows="5" v-model="form.notes"></textarea>
                            <small class="form-text text-muted">Notas visibles solo para administracion autorizada.</small>
                            <small v-if="richEditorFallbackMessage" class="form-text text-warning">{{ richEditorFallbackMessage }}</small>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Inicio</label>
                            <input type="date" class="form-control" v-model="form.start_date">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Fin</label>
                            <input type="date" class="form-control" v-model="form.end_date">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Renovacion</label>
                            <select class="form-control" v-model="form.renewal_type">
                                <option v-for="option in options.renewal_types" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Visibilidad</label>
                            <select class="form-control" v-model="form.visibility">
                                <option v-for="option in options.visibilities" :value="option.value">{{ option.label }}</option>
                            </select>
                            <small class="form-text text-muted">{{ visibilityHelp(form.visibility) }}</small>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Valor</label>
                            <input type="number" min="0" step="0.01" class="form-control" v-model.number="form.contract_value">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Moneda</label>
                            <select class="form-control" v-model="form.currency_code">
                                <option v-for="option in options.currencies" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Valor renovacion</label>
                            <input type="number" min="0" step="0.01" class="form-control" v-model.number="form.renewal_value">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Moneda renovacion</label>
                            <select class="form-control" v-model="form.renewal_currency_code">
                                <option v-for="option in options.currencies" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Tipo de facturacion</label>
                            <select class="form-control" v-model="form.billing_type">
                                <option v-for="option in options.billing_types" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Horas respuesta</label>
                            <input type="number" min="0" step="0.25" class="form-control" v-model.number="form.response_hours">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Horas resolucion</label>
                            <input type="number" min="0" step="0.25" class="form-control" v-model.number="form.resolution_hours">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal()">Cancelar</button>
                    <button class="btn btn-primary" @click="save()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    new Vue({
        el: '#app-contracts',
        data: {
            contracts: [],
            documents: [],
            relations: [],
            events: [],
            availableDocuments: [],
            selected: null,
            tab: 'general',
            stats: { active: 0, expiring: 0, expired: 0 },
            options: {
                contract_types: [],
                parties: [],
                users: [],
                currencies: [{ value: 'MXN', label: 'MXN' }],
                portal_codes: [],
                renewal_types: [],
                billing_types: [],
                statuses: [],
                visibilities: [],
                contract_type_catalog_empty: false
            },
            permissions: { create: false, edit: false, status: false, upload_document: false, link: false },
            documentStructure: { relation_types: [] },
            relationOptions: { entity_types: [], relation_types: [] },
            form: {},
            uploadForm: {},
            linkDocumentForm: {},
            relationForm: {},
            selectedFile: null,
            showUpload: false,
            showLinkDocument: false,
            richEditors: { description: null, notes: null },
            richEditorFallbackMessage: '',
            message: '',
            error: ''
        },
        computed: {
            selectedDocuments: function () {
                if (!this.selected) return [];
                return this.documents.filter(function (document) { return Number(document.contract_id) === Number(this.selected.id); }, this);
            },
            selectedRelations: function () {
                if (!this.selected) return [];
                return this.relations.filter(function (relation) { return Number(relation.contract_id) === Number(this.selected.id); }, this);
            },
            selectedEvents: function () {
                if (!this.selected) return [];
                return this.events.filter(function (event) { return Number(event.contract_id) === Number(this.selected.id); }, this);
            }
        },
        mounted: function () {
            this.resetForm();
            this.resetDocumentForms();
            this.resetRelationForm();
            this.load();
        },
        methods: {
            load: function () {
                var self = this;
                fetch('<?php echo \Uri::create('admin/contracts/data'); ?>')
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo cargar contratos.');
                            return;
                        }
                        self.applyData(json.data || {});
                    })
                    .catch(function (error) { self.error = error.message || 'No se pudo cargar contratos.'; });
            },
            applyData: function (data) {
                var selectedId = this.selected ? Number(this.selected.id) : 0;
                this.contracts = data.contracts || this.contracts;
                this.documents = data.documents || this.documents;
                this.relations = data.relations || this.relations;
                this.events = data.events || this.events;
                this.availableDocuments = data.available_documents || this.availableDocuments;
                this.stats = data.stats || this.stats;
                this.options = Object.assign(this.options, data.options || {});
                this.permissions = Object.assign(this.permissions, data.permissions || {});
                this.documentStructure = Object.assign(this.documentStructure, data.document_structure || {});
                this.relationOptions = Object.assign(this.relationOptions, data.relation_options || {});
                if (data.selected && data.selected.contract_id) {
                    selectedId = Number(data.selected.contract_id);
                }
                this.selected = this.contracts.find(function (contract) { return Number(contract.id) === selectedId; }) || this.selected;
                if (!this.selected && this.contracts.length) {
                    this.selected = this.contracts[0];
                }
            },
            resetForm: function () {
                this.form = {
                    id: 0,
                    contract_type: 'service_agreement',
                    party_id: 0,
                    portal_code: 'admin',
                    title: '',
                    description: '',
                    start_date: '',
                    end_date: '',
                    renewal_type: 'none',
                    responsible_user_id: 0,
                    contract_value: 0,
                    currency_code: 'MXN',
                    renewal_value: 0,
                    renewal_currency_code: 'MXN',
                    billing_type: 'none',
                    response_hours: 0,
                    resolution_hours: 0,
                    visibility: 'internal',
                    notes: '',
                    active: 1
                };
            },
            resetDocumentForms: function () {
                this.uploadForm = { relation_type: 'annex', title: '', visibility: 'internal', notes: '' };
                this.linkDocumentForm = { document_id: 0, relation_type: 'annex', notes: '' };
                this.selectedFile = null;
                this.showUpload = false;
                this.showLinkDocument = false;
            },
            resetRelationForm: function () {
                this.relationForm = { related_entity_type: 'helpdesk_ticket', related_entity_id: '', relation_type: 'reference', notes: '' };
            },
            selectContract: function (contract) {
                this.selected = contract;
                this.tab = 'general';
                this.resetDocumentForms();
                this.resetRelationForm();
            },
            openForm: function (contract) {
                this.error = '';
                this.message = '';
                this.resetForm();
                if (contract) {
                    this.form = Object.assign(this.form, JSON.parse(JSON.stringify(contract)));
                }
                $('#modal-contract').modal('show');
                this.$nextTick(function () { this.initRichEditors(); });
            },
            hideModal: function () {
                this.destroyRichEditors();
                $('#modal-contract').modal('hide');
            },
            save: function () {
                var self = this;
                this.syncRichEditors();
                fetch('<?php echo \Uri::create('admin/contracts/save'); ?>', window.coreAppFetchOptions(this.form))
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo guardar.');
                            return;
                        }
                        self.message = json.message || 'Contrato guardado.';
                        self.error = '';
                        self.applyData(json.data || {});
                        self.hideModal();
                    })
                    .catch(function (error) { self.error = error.message || 'No se pudo guardar el contrato.'; });
            },
            changeStatus: function (contract) {
                var self = this;
                if (!contract.next_status) return;
                fetch('<?php echo \Uri::create('admin/contracts/change_status'); ?>', window.coreAppFetchOptions({ id: contract.id, status: contract.next_status }))
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        contract.next_status = '';
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo cambiar estado.');
                            return;
                        }
                        self.message = json.message || 'Estado actualizado.';
                        self.error = '';
                        self.applyData(json.data || {});
                    })
                    .catch(function (error) {
                        contract.next_status = '';
                        self.error = error.message || 'No se pudo cambiar el estado.';
                    });
            },
            onFileChange: function (event) {
                this.selectedFile = event.target.files && event.target.files.length ? event.target.files[0] : null;
            },
            openUpload: function (relationType) {
                this.showUpload = true;
                this.showLinkDocument = false;
                this.uploadForm.relation_type = relationType || 'annex';
                if (relationType === 'main_contract') {
                    this.uploadForm.title = 'Contrato principal';
                } else if (relationType === 'annex') {
                    this.uploadForm.title = 'Anexo';
                } else if (relationType === 'signed_document') {
                    this.uploadForm.title = 'Documento firmado';
                }
            },
            uploadDocument: function () {
                if (!this.selected || !this.selectedFile) {
                    this.error = 'Selecciona contrato y archivo.';
                    return;
                }
                var self = this;
                var data = new FormData();
                data.append('contract_id', this.selected.id);
                data.append('file', this.selectedFile);
                data.append('relation_type', this.uploadForm.relation_type);
                data.append('title', this.uploadForm.title);
                data.append('visibility', this.uploadForm.visibility);
                data.append('notes', this.uploadForm.notes);
                data.append(window.coreAppCsrfKey, fuel_csrf_token());
                fetch('<?php echo \Uri::create('admin/contracts/upload_document'); ?>', { method: 'POST', body: data })
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo subir.');
                            return;
                        }
                        self.message = json.message || 'Documento cargado.';
                        self.error = '';
                        self.applyData(json.data || {});
                        self.resetDocumentForms();
                    })
                    .catch(function (error) { self.error = error.message || 'No se pudo subir el documento.'; });
            },
            linkDocument: function () {
                if (!this.selected) return;
                var self = this;
                var payload = Object.assign({}, this.linkDocumentForm, { contract_id: this.selected.id });
                fetch('<?php echo \Uri::create('admin/contracts/link_document'); ?>', window.coreAppFetchOptions(payload))
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo vincular.');
                            return;
                        }
                        self.message = json.message || 'Documento vinculado.';
                        self.error = '';
                        self.applyData(json.data || {});
                        self.resetDocumentForms();
                    })
                    .catch(function (error) { self.error = error.message || 'No se pudo vincular el documento.'; });
            },
            removeDocumentLink: function (document) {
                var self = this;
                fetch('<?php echo \Uri::create('admin/contracts/remove_document_link'); ?>', window.coreAppFetchOptions({ link_id: document.link_id }))
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo quitar.');
                            return;
                        }
                        self.message = json.message || 'Vinculo removido.';
                        self.error = '';
                        self.applyData(json.data || {});
                    })
                    .catch(function (error) { self.error = error.message || 'No se pudo quitar el vinculo.'; });
            },
            saveRelation: function () {
                if (!this.selected) return;
                var self = this;
                var payload = Object.assign({}, this.relationForm, { contract_id: this.selected.id });
                fetch('<?php echo \Uri::create('admin/contracts/save_relation'); ?>', window.coreAppFetchOptions(payload))
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo relacionar.');
                            return;
                        }
                        self.message = json.message || 'Relacion creada.';
                        self.error = '';
                        self.applyData(json.data || {});
                        self.resetRelationForm();
                    })
                    .catch(function (error) { self.error = error.message || 'No se pudo crear la relacion.'; });
            },
            removeRelation: function (relation) {
                var self = this;
                fetch('<?php echo \Uri::create('admin/contracts/remove_relation'); ?>', window.coreAppFetchOptions({ relation_id: relation.id }))
                    .then(function (response) { return self.parseResponse(response); })
                    .then(function (json) {
                        if (!json.success) {
                            self.showBackendError(json, 'No se pudo quitar.');
                            return;
                        }
                        self.message = json.message || 'Relacion removida.';
                        self.error = '';
                        self.applyData(json.data || {});
                    })
                    .catch(function (error) { self.error = error.message || 'No se pudo quitar la relacion.'; });
            },
            parseResponse: function (response) {
                return response.json().catch(function () {
                    return {
                        success: false,
                        message: 'El servidor no devolvio una respuesta JSON valida.',
                        errors: ['HTTP ' + response.status + ' ' + response.statusText]
                    };
                });
            },
            showBackendError: function (json, fallback) {
                var errors = [];
                if (json && Array.isArray(json.errors)) {
                    errors = json.errors;
                } else if (json && json.errors && typeof json.errors === 'object') {
                    Object.keys(json.errors).forEach(function (key) {
                        errors.push(json.errors[key]);
                    });
                }
                this.error = errors.join(' ') || (json && json.message) || fallback;
            },
            initRichEditors: function () {
                var self = this;
                this.destroyRichEditors();
                this.richEditorFallbackMessage = '';
                if (!window.ClassicEditor) {
                    this.richEditorFallbackMessage = 'Editor enriquecido no disponible; puedes usar texto simple.';
                    return;
                }
                var description = document.querySelector('#contract-description-editor');
                var notes = document.querySelector('#contract-notes-editor');
                if (description) {
                    window.ClassicEditor.create(description, { language: 'es' })
                        .then(function (editor) {
                            self.richEditors.description = editor;
                            editor.setData(self.form.description || '');
                        })
                        .catch(function () {
                            self.richEditorFallbackMessage = 'Editor enriquecido no disponible; puedes usar texto simple.';
                        });
                }
                if (notes) {
                    window.ClassicEditor.create(notes, { language: 'es' })
                        .then(function (editor) {
                            self.richEditors.notes = editor;
                            editor.setData(self.form.notes || '');
                        })
                        .catch(function () {
                            self.richEditorFallbackMessage = 'Editor enriquecido no disponible; puedes usar texto simple.';
                        });
                }
            },
            syncRichEditors: function () {
                if (this.richEditors.description) {
                    this.form.description = this.richEditors.description.getData();
                }
                if (this.richEditors.notes) {
                    this.form.notes = this.richEditors.notes.getData();
                }
            },
            destroyRichEditors: function () {
                if (this.richEditors.description) {
                    this.richEditors.description.destroy();
                    this.richEditors.description = null;
                }
                if (this.richEditors.notes) {
                    this.richEditors.notes.destroy();
                    this.richEditors.notes = null;
                }
            },
            visibilityHelp: function (value) {
                return {
                    internal: 'Interno: visible solo para administracion autorizada.',
                    portal: 'Visible en portal: podra mostrarse al cliente/proveedor cuando se habilite portal.',
                    private: 'Privado: solo usuarios con permiso para informacion sensible.'
                }[value] || 'Selecciona la visibilidad del contrato.';
            },
            money: function (value) {
                return Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            statusClass: function (status) {
                return {
                    draft: 'badge-secondary',
                    pending_signature: 'badge-warning',
                    active: 'badge-success',
                    renewal_pending: 'badge-info',
                    expired: 'badge-danger',
                    terminated: 'badge-dark',
                    cancelled: 'badge-danger',
                    archived: 'badge-light border'
                }[status] || 'badge-secondary';
            }
        }
    });
});
</script>
