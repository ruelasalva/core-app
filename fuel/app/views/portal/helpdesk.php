<div id="app-portal-helpdesk">
    <div class="row">
        <div class="col-lg-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.tickets || 0 }}</h3><p>Tickets registrados</p></div>
                <div class="icon"><i class="bi bi-life-preserver"></i></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.open || 0 }}</h3><p>Tickets abiertos</p></div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Soporte y seguimiento</h3>
                    <p class="text-muted small mb-0">Crea solicitudes y revisa respuestas del equipo de atencion.</p>
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
                            <th>Categoria</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Ultima actividad</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ticket in tickets" :key="ticket.id">
                            <td><strong>{{ ticket.folio }}</strong></td>
                            <td>{{ ticket.subject }}</td>
                            <td>{{ label(options.categories, ticket.category_id) || '-' }}</td>
                            <td><span :class="'badge badge-' + statusColor(ticket.status_id)">{{ label(options.statuses, ticket.status_id) || '-' }}</span></td>
                            <td><span :class="'badge badge-' + priorityColor(ticket.priority)">{{ priorityLabel(ticket.priority) }}</span></td>
                            <td>{{ ticket.last_message_at || ticket.created_at }}</td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-outline-primary" @click="openThread(ticket)">
                                    <i class="bi bi-chat-dots"></i> Ver
                                </button>
                            </td>
                        </tr>
                        <tr v-if="tickets.length === 0">
                            <td colspan="7" class="text-center text-muted">Sin tickets registrados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-portal-ticket" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Nuevo ticket</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-portal-ticket')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Asunto</label>
                                <input class="form-control" v-model="ticketForm.subject">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Categoria</label>
                                <select class="form-control" v-model="ticketForm.category_id">
                                    <option value="0">Selecciona</option>
                                    <option v-for="option in options.categories" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prioridad</label>
                                <select class="form-control" v-model="ticketForm.priority">
                                    <option v-for="option in options.priorities" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Descripcion</label>
                                <textarea class="form-control" rows="5" v-model="ticketForm.description"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-portal-ticket')">Cerrar</button>
                    <button class="btn btn-primary" @click="createTicket" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                        Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-portal-thread" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ selectedTicket ? selectedTicket.folio + ' - ' + selectedTicket.subject : 'Seguimiento' }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-portal-thread')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div class="border rounded p-3 mb-3" style="max-height: 360px; overflow-y: auto;">
                        <div v-for="message in ticketMessages" :key="message.id" class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ authorLabel(message.author_type) }}</strong>
                                <span class="text-muted small">{{ message.created_at }}</span>
                            </div>
                            <div class="mt-1">{{ message.message }}</div>
                        </div>
                        <div v-if="ticketMessages.length === 0" class="text-center text-muted">Sin mensajes</div>
                    </div>

                    <div class="form-group">
                        <label>Respuesta</label>
                        <textarea class="form-control" rows="3" v-model="replyForm.message"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-portal-thread')">Cerrar</button>
                    <button class="btn btn-primary" @click="sendReply" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm"></span>
                        Responder
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo Asset::js('vue.min.js'); ?>
<script>
new Vue({
    el: '#app-portal-helpdesk',
    data: {
        loading: true,
        saving: false,
        error: '',
        tickets: [],
        messages: [],
        options: { categories: [], statuses: [], priorities: [] },
        stats: {},
        selectedTicket: null,
        ticketForm: {},
        replyForm: {}
    },
    computed: {
        ticketMessages() {
            if (!this.selectedTicket) return [];
            return this.messages.filter(message => String(message.ticket_id) === String(this.selectedTicket.id));
        }
    },
    mounted() {
        this.resetTicket();
        this.loadData();
    },
    methods: {
        loadData() {
            this.loading = true;
            fetch('<?php echo Uri::create($portal_code.'/helpdesk_data'); ?>')
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    if (data.error) { this.error = data.error; return; }
                    this.tickets = data.tickets || [];
                    this.messages = data.messages || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                })
                .catch(() => {
                    this.loading = false;
                    this.error = 'No se pudo cargar helpdesk.';
                });
        },
        resetTicket() {
            this.ticketForm = { subject: '', category_id: 0, priority: 'normal', description: '' };
            this.error = '';
        },
        openCreate() {
            this.resetTicket();
            this.showModal('modal-portal-ticket');
        },
        openThread(ticket) {
            this.selectedTicket = ticket;
            this.replyForm = { ticket_id: ticket.id, message: '' };
            this.error = '';
            this.showModal('modal-portal-thread');
        },
        createTicket() {
            this.saving = true;
            this.error = '';
            fetch('<?php echo Uri::create($portal_code.'/helpdesk_create'); ?>', { ...window.coreAppFetchOptions(this.ticketForm) })
                .then(res => res.json())
                .then(data => {
                    this.saving = false;
                    if (data.error) { this.error = data.error; return; }
                    this.tickets = data.tickets || [];
                    this.messages = data.messages || [];
                    this.stats = data.stats || {};
                    this.resetTicket();
                    this.hideModal('modal-portal-ticket');
                })
                .catch(() => {
                    this.saving = false;
                    this.error = 'No se pudo crear el ticket.';
                });
        },
        sendReply() {
            if (!this.replyForm.message) {
                this.error = 'Captura la respuesta.';
                return;
            }
            this.saving = true;
            this.error = '';
            fetch('<?php echo Uri::create($portal_code.'/helpdesk_reply'); ?>', { ...window.coreAppFetchOptions(this.replyForm) })
                .then(res => res.json())
                .then(data => {
                    this.saving = false;
                    if (data.error) { this.error = data.error; return; }
                    this.tickets = data.tickets || [];
                    this.messages = data.messages || [];
                    this.stats = data.stats || {};
                    this.replyForm.message = '';
                })
                .catch(() => {
                    this.saving = false;
                    this.error = 'No se pudo guardar la respuesta.';
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
        authorLabel(author) {
            return author === 'admin' ? 'Equipo de soporte' : 'Usuario portal';
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
</script>
