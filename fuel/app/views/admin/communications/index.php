<div id="app-communications">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.events }}</h3>
                    <p>Eventos</p>
                </div>
                <div class="icon"><i class="bi bi-lightning"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ stats.notifications }}</h3>
                    <p>Notificaciones</p>
                </div>
                <div class="icon"><i class="bi bi-bell"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.emails_pending }}</h3>
                    <p>Correos pendientes</p>
                </div>
                <div class="icon"><i class="bi bi-envelope"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.emails_failed }}</h3>
                    <p>Correos fallidos</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-events" role="tab">
                        <i class="bi bi-lightning mr-1"></i> Eventos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-notify" role="tab">
                        <i class="bi bi-megaphone mr-1"></i> Enviar notificacion
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando comunicaciones...</p>
            </div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-events" role="tabpanel">
                    <h5 class="mb-3">Eventos configurados</h5>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Interna</th>
                                <th>Email</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="event in events" :key="event.id">
                                <td><code>{{ event.code }}</code></td>
                                <td>{{ event.name }}</td>
                                <td>{{ event.notify_internal == 1 ? 'Si' : 'No' }}</td>
                                <td>{{ event.notify_email == 1 ? 'Si' : 'No' }}</td>
                                <td>
                                    <span class="badge" :class="event.active == 1 ? 'badge-success' : 'badge-secondary'">
                                        {{ event.active == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-notify" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-7">
                            <h5>Notificacion interna manual</h5>
                            <p class="text-muted">Selecciona evento, usuarios o departamentos. La notificacion aparecera en la campana del admin.</p>
                            <div class="form-group">
                                <label>Evento</label>
                                <select class="form-control" v-model="notificationForm.event_code" @change="applyEventDefaults">
                                    <option value="manual.admin.notification">Notificacion interna manual</option>
                                    <option v-for="event in events" :key="event.code" :value="event.code">{{ event.name }} ({{ event.code }})</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Titulo</label>
                                <input class="form-control" v-model="notificationForm.title">
                            </div>
                            <div class="form-group">
                                <label>Mensaje</label>
                                <textarea class="form-control" rows="4" v-model="notificationForm.message"></textarea>
                            </div>
                            <div class="form-group">
                                <label>URL destino</label>
                                <input class="form-control" v-model="notificationForm.url" placeholder="admin">
                            </div>
                            <div class="form-group">
                                <label>Prioridad</label>
                                <select class="form-control" v-model="notificationForm.priority">
                                    <option value="1">Normal</option>
                                    <option value="2">Alta</option>
                                    <option value="3">Critica</option>
                                </select>
                            </div>
                            <button class="btn btn-primary" @click="sendNotification">
                                <i class="bi bi-send mr-1"></i> Enviar
                            </button>
                        </div>
                        <div class="col-lg-5">
                            <h5>Departamentos</h5>
                            <div class="border rounded p-2 mb-3" style="max-height: 180px; overflow:auto;">
                                <label v-for="department in departments" :key="department.id" class="d-flex align-items-start mb-2">
                                    <input type="checkbox" class="mt-1 mr-2" :value="department.id" v-model="notificationForm.department_ids">
                                    <span>{{ department.name }}</span>
                                </label>
                                <div v-if="departments.length === 0" class="text-muted">Sin departamentos activos.</div>
                            </div>
                            <h5>Destinatarios</h5>
                            <div class="border rounded p-2" style="max-height: 360px; overflow:auto;">
                                <label v-for="user in users" :key="user.id" class="d-flex align-items-start mb-2">
                                    <input type="checkbox" class="mt-1 mr-2" :value="user.id" v-model="notificationForm.user_ids">
                                    <span>
                                        <strong>{{ user.label }}</strong><br>
                                        <small class="text-muted">{{ user.email }} - Grupo {{ user.group_id }}</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-communications',
        data: {
            loading: true,
            events: [],
            users: [],
            departments: [],
            notificationForm: {
                event_code: 'manual.admin.notification',
                title: '',
                message: '',
                url: 'admin',
                priority: 1,
                user_ids: [],
                department_ids: []
            },
            stats: { events: 0, notifications: 0, unread: 0, emails_pending: 0, emails_failed: 0 }
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/communications/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.events = data.events || [];
                        this.users = data.users || [];
                        this.departments = data.departments || [];
                        this.stats = data.stats || this.stats;
                    });
            },
            applyEventDefaults() {
                const found = this.events.find(event => event.code === this.notificationForm.event_code);
                if (!found) return;
                if (!this.notificationForm.title) this.notificationForm.title = found.name;
                if (!this.notificationForm.url) this.notificationForm.url = 'admin';
            },
            sendNotification() {
                fetch('<?php echo Uri::create('admin/communications/send_notification'); ?>', {
                    ...window.coreAppFetchOptions(this.notificationForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.stats = data.stats || this.stats;
                    this.notificationForm = { event_code: 'manual.admin.notification', title: '', message: '', url: 'admin', priority: 1, user_ids: [], department_ids: [] };
                    alert('Notificacion enviada.');
                });
            }
        }
    });
};
</script>
