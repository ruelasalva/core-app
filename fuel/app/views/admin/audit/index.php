<div id="app-audit">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.total || 0 }}</h3><p>Eventos auditados</p></div>
                <div class="icon"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.today || 0 }}</h3><p>Hoy</p></div>
                <div class="icon"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.warnings || 0 }}</h3><p>Advertencias</p></div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ stats.danger || 0 }}</h3><p>Criticos</p></div>
                <div class="icon"><i class="bi bi-x-octagon"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Auditoria funcional</h3>
                <button class="btn btn-outline-primary btn-sm" @click="loadData">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Modulo</label>
                    <select class="form-control" v-model="filters.module">
                        <option value="">Todos</option>
                        <option v-for="option in filterOptions.modules" :value="option">{{ option }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Tabla</label>
                    <select class="form-control" v-model="filters.table_name">
                        <option value="">Todas</option>
                        <option v-for="option in filterOptions.tables" :value="option">{{ option }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Registro</label>
                    <input class="form-control" v-model="filters.record_pk" placeholder="ID">
                </div>
                <div class="col-md-2">
                    <label>Operacion</label>
                    <select class="form-control" v-model="filters.operation">
                        <option value="">Todas</option>
                        <option v-for="option in filterOptions.operations" :value="option">{{ option }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Severidad</label>
                    <select class="form-control" v-model="filters.severity">
                        <option value="">Todas</option>
                        <option v-for="option in filterOptions.severities" :value="option">{{ option }}</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Desde</label>
                    <input class="form-control" type="date" v-model="filters.date_from">
                </div>
                <div class="col-md-3 mt-2">
                    <label>Hasta</label>
                    <input class="form-control" type="date" v-model="filters.date_to">
                </div>
                <div class="col-md-3 mt-2">
                    <label>Portal</label>
                    <select class="form-control" v-model="filters.portal_code">
                        <option value="">Todos</option>
                        <option v-for="option in filterOptions.portals" :value="option">{{ option }}</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2 d-flex align-items-end">
                    <button class="btn btn-primary mr-2" @click="loadData">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <button class="btn btn-outline-secondary" @click="clearFilters">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Modulo</th>
                            <th>Operacion</th>
                            <th>Tabla / Registro</th>
                            <th>Cambios</th>
                            <th>Resumen</th>
                            <th>IP</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td>{{ item.created_at }}</td>
                            <td>{{ item.user_id }}</td>
                            <td>{{ item.module }}</td>
                            <td>
                                <span class="badge" :class="severityClass(item.severity)">
                                    {{ item.operation || item.action }}
                                </span>
                            </td>
                            <td>
                                <div>{{ item.table_name || item.entity_type }}</div>
                                <small class="text-muted">#{{ item.record_pk || item.entity_id }}</small>
                            </td>
                            <td>
                                <span v-if="item.changed_fields.length === 0" class="text-muted">-</span>
                                <span v-for="field in item.changed_fields.slice(0, 3)" :key="field" class="badge badge-light mr-1">{{ field }}</span>
                                <span v-if="item.changed_fields.length > 3" class="badge badge-secondary">+{{ item.changed_fields.length - 3 }}</span>
                            </td>
                            <td>{{ item.summary }}</td>
                            <td>{{ item.ip }}</td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-primary" @click="showDetail(item)">
                                    <i class="bi bi-search"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td colspan="9" class="text-center text-muted">Sin eventos de auditoria</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="audit-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Detalle de auditoria</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" v-if="selected">
                    <div class="row">
                        <div class="col-md-4"><strong>Evento:</strong> {{ selected.business_event || selected.event_code }}</div>
                        <div class="col-md-4"><strong>Ruta:</strong> {{ selected.route }}</div>
                        <div class="col-md-4"><strong>Metodo:</strong> {{ selected.http_method }}</div>
                        <div class="col-md-4"><strong>Backend:</strong> {{ selected.backend }}</div>
                        <div class="col-md-4"><strong>Portal:</strong> {{ selected.portal_code || '-' }}</div>
                        <div class="col-md-4"><strong>User agent:</strong> {{ selected.user_agent }}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Valores anteriores</h6>
                            <pre class="bg-light p-2 audit-json">{{ pretty(selected.old_values) }}</pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Valores nuevos</h6>
                            <pre class="bg-light p-2 audit-json">{{ pretty(selected.new_values) }}</pre>
                        </div>
                        <div class="col-md-12">
                            <h6>Metadata</h6>
                            <pre class="bg-light p-2 audit-json">{{ pretty(selected.metadata) }}</pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .audit-json { max-height: 320px; overflow: auto; font-size: 12px; }
</style>

<script>
window.onload = function() {
    new Vue({
        el: '#app-audit',
        data: {
            error: '',
            items: [],
            stats: {},
            filterOptions: { modules: [], tables: [], operations: [], severities: [], portals: [] },
            filters: { module: '', entity_type: '', table_name: '', record_pk: '', operation: '', severity: '', portal_code: '', date_from: '', date_to: '' },
            selected: null
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                const params = new URLSearchParams(this.filters);
                fetch('<?php echo Uri::create('admin/audit/data'); ?>?' + params.toString())
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.items = data.items || [];
                        this.stats = data.stats || {};
                        this.filterOptions = data.filters || this.filterOptions;
                    });
            },
            clearFilters() {
                this.filters = { module: '', entity_type: '', table_name: '', record_pk: '', operation: '', severity: '', portal_code: '', date_from: '', date_to: '' };
                this.loadData();
            },
            showDetail(item) {
                this.selected = item;
                $('#audit-detail-modal').modal('show');
            },
            severityClass(severity) {
                if (severity === 'danger') return 'badge-danger';
                if (severity === 'warning') return 'badge-warning';
                return 'badge-info';
            },
            pretty(value) {
                return JSON.stringify(value || {}, null, 2);
            }
        }
    });
};
</script>
