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
        <div class="card-header">
            <h3 class="card-title">Eventos configurados</h3>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando comunicaciones...</p>
            </div>

            <table v-show="!loading" class="table table-bordered table-hover">
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
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-communications',
        data: {
            loading: true,
            events: [],
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
                        this.stats = data.stats || this.stats;
                    });
            }
        }
    });
};
</script>
