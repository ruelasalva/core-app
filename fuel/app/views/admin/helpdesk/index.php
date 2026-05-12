<div id="app-helpdesk">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.tickets || 0 }}</h3><p>Tickets</p></div>
                <div class="icon"><i class="bi bi-life-preserver"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.open || 0 }}</h3><p>Abiertos</p></div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.assigned_to_me || 0 }}</h3><p>Asignados a mi</p></div>
                <div class="icon"><i class="bi bi-person-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ stats.messages || 0 }}</h3><p>Mensajes</p></div>
                <div class="icon"><i class="bi bi-chat-left-text"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Tickets de soporte</h3>
                    <p class="text-muted small mb-0">Atencion interna y externa con seguimiento, responsables y evidencias documentales.</p>
                </div>
                <button class="btn btn-primary btn-sm" @click="openCreate">
                    <i class="bi bi-plus-lg"></i> Nuevo ticket
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando tickets...</p>
            </div>

            <div v-show="!loading" class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Asunto</th>
                            <th>Tercero</th>
                            <th>Categoria</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Asignado</th>
                            <th>Vencimiento</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ticket in tickets" :key="ticket.id">
                            <td><strong>{{ ticket.folio }}</strong></td>
                            <td>
                                {{ ticket.subject }}
                                <div class="text-muted small">{{ ticket.created_at }}</div>
                            </td>
                            <td>{{ label(options.parties, ticket.party_id) || '-' }}</td>
                            <td>{{ label(options.categories, ticket.category_id) || '-' }}</td>
                            <td><span :class="'badge badge-' + statusColor(ticket.status_id)">{{ label(options.statuses, ticket.status_id) || '-' }}</span></td>
                            <td><span :class="'badge badge-' + priorityColor(ticket.priority)">{{ priorityLabel(ticket.priority) }}</span></td>
                            <td>{{ label(options.users, ticket.assigned_user_id) || 'Sin asignar' }}</td>
                            <td>
                                <span v-if="ticket.due_at_label">{{ ticket.due_at_label }}</span>
                                <span v-else class="text-muted">Sin fecha</span>
                                <div v-if="ticket.scheduled_start_at_label" class="text-muted small">{{ ticket.scheduled_start_at_label }} - {{ ticket.scheduled_end_at_label }}</div>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-outline-primary" @click="openThread(ticket)" title="Ver seguimiento">
                                    <i class="bi bi-chat-dots"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-secondary" @click="openEdit(ticket)" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="tickets.length === 0">
                            <td colspan="9" class="text-center text-muted">Sin tickets registrados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-helpdesk-ticket" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ ticketForm.id ? 'Editar ticket' : 'Nuevo ticket' }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-helpdesk-ticket')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tercero</label>
                                <select class="form-control" v-model="ticketForm.party_id">
                                    <option value="0">Sin tercero</option>
                                    <option v-for="option in options.parties" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Categoria</label>
                                <select class="form-control" v-model="ticketForm.category_id">
                                    <option value="0">Selecciona</option>
                                    <option v-for="option in options.categories" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Prioridad</label>
                                <select class="form-control" v-model="ticketForm.priority">
                                    <option v-for="option in options.priorities" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Responsable</label>
                                <select class="form-control" v-model="ticketForm.assigned_user_id">
                                    <option value="0">Sin asignar</option>
                                    <option v-for="option in options.users" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Departamento</label>
                                <select class="form-control" v-model="ticketForm.department_id">
                                    <option value="0">Sin departamento</option>
                                    <option v-for="option in options.departments" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3" v-if="ticketForm.id">
                            <div class="form-group">
                                <label>Estado</label>
                                <select class="form-control" v-model="ticketForm.status_id">
                                    <option v-for="option in options.statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Asunto</label>
                                <input class="form-control" v-model="ticketForm.subject" :disabled="ticketForm.id">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Vencimiento</label>
                                <input type="datetime-local" class="form-control" v-model="ticketForm.due_at_input">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Inicio programado</label>
                                <input type="datetime-local" class="form-control" v-model="ticketForm.scheduled_start_at_input">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fin programado</label>
                                <input type="datetime-local" class="form-control" v-model="ticketForm.scheduled_end_at_input">
                            </div>
                        </div>
                        <div class="col-md-12" v-if="!ticketForm.id">
                            <div class="form-group">
                                <label>Descripcion</label>
                                <textarea class="form-control" rows="4" v-model="ticketForm.description"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-helpdesk-ticket')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveTicket" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-helpdesk-thread" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ selectedTicket ? selectedTicket.folio + ' - ' + selectedTicket.subject : 'Seguimiento' }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-helpdesk-thread')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div v-if="selectedTicket" class="mb-3">
                        <span :class="'badge badge-' + statusColor(selectedTicket.status_id)">{{ label(options.statuses, selectedTicket.status_id) || '-' }}</span>
                        <span :class="'badge badge-' + priorityColor(selectedTicket.priority)">{{ priorityLabel(selectedTicket.priority) }}</span>
                        <span class="badge badge-light">{{ label(options.categories, selectedTicket.category_id) || 'Sin categoria' }}</span>
                    </div>

                    <div class="border rounded p-3 mb-3" style="max-height: 340px; overflow-y: auto;">
                        <div v-for="message in ticketMessages" :key="message.id" class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ message.author_type }}</strong>
                                <span class="text-muted small">{{ message.created_at }}</span>
                            </div>
                            <div class="mt-1">{{ message.message }}</div>
                            <span v-if="message.is_internal == 1" class="badge badge-warning mt-1">Nota interna</span>
                        </div>
                        <div v-if="ticketMessages.length === 0" class="text-center text-muted">Sin mensajes</div>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Adjuntos y evidencias</strong>
                            <span class="badge badge-light">{{ ticketDocuments.length }}</span>
                        </div>
                        <div v-if="ticketDocuments.length === 0" class="text-muted small mb-2">Sin adjuntos</div>
                        <div v-for="document in ticketDocuments" :key="document.id" class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <a :href="assetUrl(document.file_path)" target="_blank">{{ document.original_name || document.title }}</a>
                                <div class="text-muted small">{{ document.created_at }} · {{ document.file_extension }} · {{ formatSize(document.file_size) }}</div>
                            </div>
                            <span v-if="document.is_evidence == 1" class="badge badge-info">Evidencia</span>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-5">
                                <input type="text" class="form-control form-control-sm" placeholder="Titulo del adjunto" v-model="uploadForm.title">
                            </div>
                            <div class="col-md-4">
                                <input type="file" class="form-control form-control-sm" @change="setUploadFile">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-sm btn-outline-primary btn-block" @click="uploadDocument" :disabled="saving">
                                    <i class="bi bi-paperclip"></i> Adjuntar
                                </button>
                            </div>
                            <div class="col-md-12">
                                <small class="text-muted">PDF, XML, imagenes, Office, CSV o TXT. Maximo 15 MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Respuesta</label>
                        <textarea class="form-control" rows="3" v-model="replyForm.message"></textarea>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="reply-internal-note" v-model="replyForm.is_internal">
                        <label class="custom-control-label" for="reply-internal-note">Nota interna</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-helpdesk-thread')">Cerrar</button>
                    <button class="btn btn-primary" @click="sendReply" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                        Responder
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-helpdesk',
        data: {
            loading: true,
            saving: false,
            error: '',
            tickets: [],
            messages: [],
            documents: [],
            options: { parties: [], departments: [], categories: [], statuses: [], users: [], priorities: [] },
            stats: {},
            selectedTicket: null,
            ticketForm: {},
            replyForm: {},
            uploadForm: {},
            uploadFile: null
        },
        computed: {
            ticketMessages() {
                if (!this.selectedTicket) return [];
                return this.messages.filter(message => String(message.ticket_id) === String(this.selectedTicket.id));
            },
            ticketDocuments() {
                if (!this.selectedTicket) return [];
                return this.documents.filter(document => String(document.ticket_id) === String(this.selectedTicket.id));
            }
        },
        mounted() {
            this.resetTicket();
            this.loadData();
        },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/helpdesk/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) { this.error = data.error; return; }
                        this.tickets = data.tickets || [];
                        this.messages = data.messages || [];
                        this.documents = data.documents || [];
                        this.options = data.options || this.options;
                        this.stats = data.stats || {};
                    })
                    .catch(() => {
                        this.loading = false;
                        this.error = 'No se pudo cargar helpdesk.';
                    });
            },
            resetTicket() {
                this.ticketForm = {
                    id: 0,
                    party_id: 0,
                    assigned_user_id: 0,
                    department_id: 0,
                    category_id: 0,
                    status_id: 0,
                    priority: 'normal',
                    subject: '',
                    description: '',
                    due_at_input: '',
                    scheduled_start_at_input: '',
                    scheduled_end_at_input: ''
                };
                this.error = '';
            },
            openCreate() {
                this.resetTicket();
                this.showModal('modal-helpdesk-ticket');
            },
            openEdit(ticket) {
                this.ticketForm = Object.assign({}, ticket);
                this.ticketForm.due_at_input = ticket.due_at_input || '';
                this.ticketForm.scheduled_start_at_input = ticket.scheduled_start_at_input || '';
                this.ticketForm.scheduled_end_at_input = ticket.scheduled_end_at_input || '';
                this.error = '';
                this.showModal('modal-helpdesk-ticket');
            },
            openThread(ticket) {
                this.selectedTicket = ticket;
                this.replyForm = { ticket_id: ticket.id, message: '', is_internal: false };
                this.uploadForm = { ticket_id: ticket.id, title: '', description: '', is_evidence: true };
                this.uploadFile = null;
                this.error = '';
                this.showModal('modal-helpdesk-thread');
            },
            saveTicket() {
                this.saving = true;
                this.error = '';
                const url = this.ticketForm.id ? '<?php echo Uri::create('admin/helpdesk/update_ticket'); ?>' : '<?php echo Uri::create('admin/helpdesk/create_ticket'); ?>';
                fetch(url, { ...window.coreAppFetchOptions(this.ticketForm) })
                    .then(res => res.json())
                    .then(data => {
                        this.saving = false;
                        if (data.error) { this.error = data.error; return; }
                        this.tickets = data.tickets || this.tickets;
                        this.messages = data.messages || this.messages;
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-helpdesk-ticket');
                    })
                    .catch(() => {
                        this.saving = false;
                        this.error = 'No se pudo guardar el ticket.';
                    });
            },
            sendReply() {
                if (!this.replyForm.message) {
                    this.error = 'Captura la respuesta.';
                    return;
                }
                this.saving = true;
                this.error = '';
                fetch('<?php echo Uri::create('admin/helpdesk/reply'); ?>', { ...window.coreAppFetchOptions(this.replyForm) })
                    .then(res => res.json())
                    .then(data => {
                        this.saving = false;
                        if (data.error) { this.error = data.error; return; }
                        this.tickets = data.tickets || this.tickets;
                        this.messages = data.messages || this.messages;
                        this.documents = data.documents || this.documents;
                        this.stats = data.stats || this.stats;
                        this.replyForm.message = '';
                    })
                    .catch(() => {
                        this.saving = false;
                        this.error = 'No se pudo guardar la respuesta.';
                    });
            },
            setUploadFile(event) {
                this.uploadFile = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            },
            uploadDocument() {
                if (!this.selectedTicket || !this.uploadFile) {
                    this.error = 'Selecciona un archivo.';
                    return;
                }

                this.saving = true;
                this.error = '';
                const data = new FormData();
                Object.keys(this.uploadForm).forEach(key => data.append(key, this.uploadForm[key]));
                data.append('file', this.uploadFile);
                data.append(window.coreAppCsrfKey, fuel_csrf_token());

                fetch('<?php echo Uri::create('admin/helpdesk/upload_document'); ?>', { method: 'POST', body: data })
                    .then(res => res.json())
                    .then(data => {
                        this.saving = false;
                        if (data.error) { this.error = data.error; return; }
                        this.tickets = data.tickets || this.tickets;
                        this.messages = data.messages || this.messages;
                        this.documents = data.documents || this.documents;
                        this.stats = data.stats || this.stats;
                        this.uploadForm = { ticket_id: this.selectedTicket.id, title: '', description: '', is_evidence: true };
                        this.uploadFile = null;
                    })
                    .catch(() => {
                        this.saving = false;
                        this.error = 'No se pudo adjuntar el archivo.';
                    });
            },
            label(options, value) {
                const found = (options || []).find(option => String(option.value) === String(value));
                return found ? found.label : '';
            },
            statusColor(statusId) {
                const found = (this.options.statuses || []).find(option => String(option.value) === String(statusId));
                return found && found.color ? found.color : 'secondary';
            },
            priorityLabel(priority) {
                return this.label(this.options.priorities, priority) || priority || 'Normal';
            },
            priorityColor(priority) {
                if (priority === 'urgente') return 'danger';
                if (priority === 'alta') return 'warning';
                if (priority === 'baja') return 'secondary';
                return 'info';
            },
            assetUrl(path) {
                if (!path) return '';
                if (/^https?:\/\//.test(path)) return path;
                return '<?php echo Uri::base(false); ?>' + path.replace(/^\/+/, '');
            },
            formatSize(size) {
                size = parseInt(size || 0, 10);
                if (size >= 1048576) return (size / 1048576).toFixed(1) + ' MB';
                if (size >= 1024) return (size / 1024).toFixed(1) + ' KB';
                return size + ' B';
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
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
