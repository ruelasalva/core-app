<div id="app-calendar">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.events || 0 }}</h3><p>Eventos</p></div><div class="icon"><i class="bi bi-calendar3"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.resources || 0 }}</h3><p>Recursos</p></div><div class="icon"><i class="bi bi-door-open"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.next_7_days || 0 }}</h3><p>Proximos 7 dias</p></div><div class="icon"><i class="bi bi-clock-history"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ stats.pending || 0 }}</h3><p>Pendientes</p></div><div class="icon"><i class="bi bi-list-check"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Calendario</h3>
            <button class="btn btn-outline-secondary btn-sm ml-auto mr-2" @click="openResource({})"><i class="bi bi-plus-lg"></i> Recurso</button>
            <button class="btn btn-primary btn-sm" @click="openEvent({})"><i class="bi bi-plus-lg"></i> Evento</button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><div class="form-group"><label>Desde</label><input type="date" class="form-control" v-model="filters.date_from" @change="loadData"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Hasta</label><input type="date" class="form-control" v-model="filters.date_to" @change="loadData"></div></div>
                <div class="col-md-2"><div class="form-group"><label>Tipo</label><select class="form-control" v-model="filters.event_type" @change="loadData"><option value="">Todos</option><option v-for="option in options.types" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-2"><div class="form-group"><label>Recurso</label><select class="form-control" v-model="filters.resource_id" @change="loadData"><option value="0">Todos</option><option v-for="option in options.resources" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-2"><div class="form-group"><label>Usuario</label><select class="form-control" v-model="filters.assigned_user_id" @change="loadData"><option value="0">Todos</option><option v-for="option in options.users" :value="option.value">{{ option.label }}</option></select></div></div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Recursos reservables</th><th class="text-center">Acciones</th></tr></thead>
                            <tbody>
                                <tr v-for="resource in resources" :key="resource.id">
                                    <td>
                                        <span class="badge mr-1" :style="{ backgroundColor: resource.color, color: '#fff' }">&nbsp;</span>
                                        <strong>{{ resource.name }}</strong>
                                        <div class="text-muted small">{{ resource.location || 'Sin ubicacion' }} - {{ resource.capacity || 0 }} personas</div>
                                        <span class="badge badge-light">{{ resource.resource_type }}</span>
                                        <span v-if="resource.active != 1" class="badge badge-secondary">Inactivo</span>
                                    </td>
                                    <td class="text-center"><button class="btn btn-xs btn-outline-primary" @click="openResource(resource)"><i class="bi bi-pencil"></i></button></td>
                                </tr>
                                <tr v-if="resources.length === 0"><td colspan="2" class="text-center text-muted">Sin recursos</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div id="calendar-full" class="core-fullcalendar"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-secondary card-outline">
        <div class="card-header"><h3 class="card-title mb-0">Eventos</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Evento</th><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Recurso</th><th>Asignado</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="event in events" :key="event.id">
                        <td><strong>{{ event.title }}</strong><div class="text-muted small">{{ event.description }}</div></td>
                        <td>{{ typeLabel(event.event_type) }}</td>
                        <td>{{ event.start_label }}</td>
                        <td>{{ event.end_label }}</td>
                        <td>{{ resourceLabel(event.resource_id) || '-' }}</td>
                        <td>{{ userLabel(event.assigned_user_id) || '-' }}</td>
                        <td><span class="badge badge-light">{{ statusLabel(event.status) }}</span></td>
                        <td class="text-center"><button class="btn btn-xs btn-outline-primary" @click="openEvent(event)"><i class="bi bi-pencil"></i></button></td>
                    </tr>
                    <tr v-if="events.length === 0"><td colspan="8" class="text-center text-muted">Sin eventos</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-event" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Evento</h5><button class="close text-white" @click="hideModal('modal-event')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-8"><div class="form-group"><label>Titulo</label><input class="form-control" v-model="eventForm.title"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Tipo</label><select class="form-control" v-model="eventForm.event_type"><option v-for="option in options.types" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Inicio</label><input type="datetime-local" class="form-control" v-model="eventForm.start_at"></div></div>
                <div class="col-md-6"><div class="form-group"><label>Fin</label><input type="datetime-local" class="form-control" v-model="eventForm.end_at"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Recurso</label><select class="form-control" v-model="eventForm.resource_id"><option value="0">Sin recurso</option><option v-for="option in options.resources" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Asignado</label><select class="form-control" v-model="eventForm.assigned_user_id"><option value="0">Sin asignar</option><option v-for="option in options.users" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Estado</label><select class="form-control" v-model="eventForm.status"><option v-for="option in options.statuses" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Visibilidad</label><select class="form-control" v-model="eventForm.visibility"><option value="internal">Interna</option><option value="portal">Portal</option><option value="private">Privada</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Color</label><input type="color" class="form-control" v-model="eventForm.color"></div></div>
                <div class="col-md-4 d-flex align-items-center"><div class="custom-control custom-switch mt-3"><input type="checkbox" class="custom-control-input" id="event-active" v-model="eventForm.active"><label class="custom-control-label" for="event-active">Activo</label></div></div>
                <div class="col-md-12"><div class="form-group"><label>Descripcion</label><textarea class="form-control" rows="3" v-model="eventForm.description"></textarea></div></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-event')">Cerrar</button><button class="btn btn-primary" @click="saveEvent">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-resource" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-secondary text-white"><h5 class="modal-title">Recurso</h5><button class="close text-white" @click="hideModal('modal-resource')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group"><label>Nombre</label><input class="form-control" v-model="resourceForm.name"></div>
                <div class="form-group"><label>Codigo</label><input class="form-control" v-model="resourceForm.code"></div>
                <div class="form-group"><label>Tipo</label><select class="form-control" v-model="resourceForm.resource_type"><option value="meeting_room">Sala de juntas</option><option value="equipment">Equipo</option><option value="vehicle">Vehiculo</option><option value="space">Espacio</option></select></div>
                <div class="form-group"><label>Ubicacion</label><input class="form-control" v-model="resourceForm.location"></div>
                <div class="form-group"><label>Capacidad</label><input type="number" class="form-control" v-model="resourceForm.capacity"></div>
                <div class="form-group"><label>Color</label><input type="color" class="form-control" v-model="resourceForm.color"></div>
                <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="resource-active" v-model="resourceForm.active"><label class="custom-control-label" for="resource-active">Activo</label></div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-resource')">Cerrar</button><button class="btn btn-primary" @click="saveResource">Guardar</button></div>
        </div></div>
    </div>
</div>

<style>
    .core-fullcalendar { min-height: 620px; }
    .fc .fc-toolbar-title { font-size: 1.2rem; }
    .fc .fc-button-primary { background-color: #007bff; border-color: #007bff; }
    .fc .fc-daygrid-event { cursor: pointer; }
    .fc .fc-event-title { font-weight: 600; }
    @media (max-width: 767.98px) {
        .core-fullcalendar { min-height: 520px; }
        .fc .fc-toolbar { align-items: flex-start; flex-direction: column; gap: .5rem; }
    }
</style>

<script>
window.onload = function() {
    new Vue({
        el: '#app-calendar',
        data: {
            error: '',
            events: [],
            resources: [],
            options: { users: [], resources: [], types: [], statuses: [] },
            stats: {},
            filters: {
                date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
                date_to: new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate() + 14).toISOString().slice(0, 10),
                event_type: '',
                resource_id: 0,
                assigned_user_id: 0
            },
            eventForm: {},
            resourceForm: {},
            calendar: null
        },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                const params = new URLSearchParams(this.filters).toString();
                fetch('<?php echo Uri::create('admin/calendar/data'); ?>?' + params).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.error = '';
                    this.events = data.events || [];
                    this.resources = data.resources || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                    this.$nextTick(this.renderCalendar);
                });
            },
            renderCalendar() {
                const element = document.getElementById('calendar-full');
                if (!element || typeof FullCalendar === 'undefined') { return; }
                const source = this.events.map(event => ({
                    id: event.id,
                    title: event.title,
                    start: event.start_iso,
                    end: event.end_iso,
                    color: event.color,
                    allDay: event.all_day == 1,
                    extendedProps: { coreEvent: event }
                }));
                if (!this.calendar) {
                    this.calendar = new FullCalendar.Calendar(element, {
                        locale: 'es',
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        selectable: true,
                        nowIndicator: true,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                        },
                        buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Dia', list: 'Lista' },
                        dateClick: (info) => {
                            const start = info.allDay ? info.dateStr + 'T09:00' : info.dateStr.slice(0, 16);
                            const endDate = new Date(start);
                            endDate.setHours(endDate.getHours() + 1);
                            this.openEvent({ start_input: start, end_input: this.inputDateTime(endDate) });
                        },
                        eventClick: (info) => this.openEvent(info.event.extendedProps.coreEvent)
                    });
                    this.calendar.render();
                }
                this.calendar.removeAllEvents();
                this.calendar.addEventSource(source);
            },
            openEvent(event) {
                const now = new Date();
                now.setMinutes(0, 0, 0);
                const end = new Date(now.getTime());
                end.setHours(now.getHours() + 1);
                this.eventForm = Object.assign({
                    id: 0,
                    title: '',
                    description: '',
                    event_type: 'meeting',
                    resource_id: 0,
                    assigned_user_id: 0,
                    organizer_user_id: 0,
                    related_entity_type: '',
                    related_entity_id: 0,
                    start_at: this.inputDateTime(now),
                    end_at: this.inputDateTime(end),
                    all_day: false,
                    status: 'scheduled',
                    visibility: 'internal',
                    color: '#007bff',
                    active: true
                }, event);
                if (event.start_input) { this.eventForm.start_at = event.start_input; }
                if (event.end_input) { this.eventForm.end_at = event.end_input; }
                this.showModal('modal-event');
            },
            openResource(resource) {
                this.resourceForm = Object.assign({ id: 0, code: '', name: '', resource_type: 'meeting_room', location: '', capacity: 0, color: '#007bff', active: true }, resource);
                this.showModal('modal-resource');
            },
            saveEvent() {
                fetch('<?php echo Uri::create('admin/calendar/save_event'); ?>', window.coreAppFetchOptions(this.eventForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.events = data.events || [];
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-event');
                    this.$nextTick(this.renderCalendar);
                });
            },
            saveResource() {
                fetch('<?php echo Uri::create('admin/calendar/save_resource'); ?>', window.coreAppFetchOptions(this.resourceForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.resources = data.resources || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-resource');
                });
            },
            label(options, value) {
                const found = (options || []).find(option => String(option.value) === String(value));
                return found ? found.label : '';
            },
            inputDateTime(date) {
                const pad = value => String(value).padStart(2, '0');
                return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
            },
            typeLabel(value) { return this.label(this.options.types, value) || value; },
            statusLabel(value) { return this.label(this.options.statuses, value) || value; },
            userLabel(value) { return this.label(this.options.users, value); },
            resourceLabel(value) { return this.label(this.options.resources, value); },
            showModal(id) { $('#' + id).modal('show'); },
            hideModal(id) { $('#' + id).modal('hide'); }
        }
    });
};
</script>
