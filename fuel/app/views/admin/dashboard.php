<div class="row" id="app-dashboard">
    <div class="col-lg-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Bienvenido</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo Auth::get_screen_name(); ?></h5>
                <p class="card-text">Estas logueado como administrador del sistema.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Estado del sistema</div>
            <div class="card-body">
                <p>Modulos base cargados. Utiliza el menu lateral para navegar.</p>
                <?php \Log::info("Dashboard renderizado visualmente para el usuario."); ?>
            </div>
        </div>
    </div>

    <?php if (!empty($modules['calendar'])): ?>
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">Mi calendario</h3>
                <a class="btn btn-sm btn-outline-primary ml-auto" href="<?php echo Uri::create('admin/calendar'); ?>">
                    <i class="bi bi-calendar3"></i> Abrir
                </a>
            </div>
            <div class="card-body">
                <div id="dashboard-mini-calendar" class="dashboard-mini-calendar"></div>
                <div v-if="calendarError" class="alert alert-warning mt-3 mb-0">{{ calendarError }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title mb-0">Pendientes proximos</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li v-for="item in upcoming" :key="item.id" class="list-group-item">
                        <div class="d-flex align-items-start">
                            <span class="badge mr-2 mt-1" :style="{ backgroundColor: item.color, color: '#fff' }">&nbsp;</span>
                            <div>
                                <strong>{{ item.title }}</strong>
                                <div class="text-muted small">{{ item.start_label }}</div>
                            </div>
                        </div>
                    </li>
                    <li v-if="upcoming.length === 0" class="list-group-item text-muted">Sin pendientes proximos</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .dashboard-mini-calendar { min-height: 420px; }
    .dashboard-mini-calendar .fc-toolbar-title { font-size: 1rem; }
    .dashboard-mini-calendar .fc-header-toolbar { margin-bottom: .75rem; }
    .dashboard-mini-calendar .fc-button { padding: .2rem .45rem; font-size: .8rem; }
    .dashboard-mini-calendar .fc-daygrid-event { cursor: pointer; }
</style>

<?php if (!empty($modules['calendar'])): ?>
<script>
window.onload = function() {
    new Vue({
        el: '#app-dashboard',
        data: {
            calendar: null,
            calendarError: '',
            events: []
        },
        computed: {
            upcoming() {
                return this.events
                    .slice()
                    .sort((a, b) => new Date(a.start) - new Date(b.start))
                    .slice(0, 8)
                    .map(event => Object.assign({}, event, {
                        start_label: new Date(event.start).toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
                    }));
            }
        },
        mounted() {
            this.loadCalendar();
        },
        methods: {
            loadCalendar() {
                fetch('<?php echo Uri::create('admin/dashboard/calendar_data'); ?>').then(res => res.json()).then(data => {
                    if (data.error) { this.calendarError = data.error; return; }
                    this.events = data.events || [];
                    this.$nextTick(this.renderCalendar);
                });
            },
            renderCalendar() {
                const element = document.getElementById('dashboard-mini-calendar');
                if (!element || typeof FullCalendar === 'undefined') { return; }
                if (!this.calendar) {
                    this.calendar = new FullCalendar.Calendar(element, {
                        locale: 'es',
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'listWeek,dayGridMonth'
                        },
                        buttonText: { today: 'Hoy', month: 'Mes', list: 'Lista' },
                        eventClick: function(info) {
                            if (info.event.url) {
                                window.location.href = info.event.url;
                                info.jsEvent.preventDefault();
                            }
                        }
                    });
                    this.calendar.render();
                }
                this.calendar.removeAllEvents();
                this.calendar.addEventSource(this.events);
            }
        }
    });
};
</script>
<?php endif; ?>
