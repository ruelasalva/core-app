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
            stats: { active: 0, expiring_90: 0, expiring_60: 0, expiring_30: 0, expired: 0, no_end_date: 0 },
            filters: { status: 'all', contract_type: 'all', expiration: 'all' },
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
                expiration_filters: [{ value: 'all', label: 'Todos' }],
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
            },
            filteredContracts: function () {
                return this.contracts.filter(function (contract) {
                    if (this.filters.status !== 'all' && contract.status !== this.filters.status) {
                        return false;
                    }
                    if (this.filters.contract_type !== 'all' && contract.contract_type !== this.filters.contract_type) {
                        return false;
                    }
                    if (this.filters.expiration !== 'all' && contract.expiration_status !== this.filters.expiration) {
                        return false;
                    }
                    return true;
                }, this);
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
            },
            expirationClass: function (status) {
                return {
                    no_end_date: 'badge-secondary',
                    active: 'badge-success',
                    expiring_90: 'badge-info',
                    expiring_60: 'badge-warning',
                    expiring_30: 'badge-orange',
                    expired: 'badge-danger',
                    inactive: 'badge-dark'
                }[status] || 'badge-secondary';
            }
        }
    });
});
</script>
