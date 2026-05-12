<div id="app-audit">
    <div class="row">
        <div class="col-lg-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.total || 0 }}</h3><p>Eventos auditados</p></div>
                <div class="icon"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.today || 0 }}</h3><p>Hoy</p></div>
                <div class="icon"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Auditoria funcional</h3>
                <button class="btn btn-outline-primary btn-sm" @click="loadData"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div class="row mb-3">
                <div class="col-md-4"><input class="form-control" placeholder="Modulo" v-model="filters.module"></div>
                <div class="col-md-4"><input class="form-control" placeholder="Entidad" v-model="filters.entity_type"></div>
                <div class="col-md-4"><button class="btn btn-primary" @click="loadData">Filtrar</button></div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Modulo</th>
                            <th>Accion</th>
                            <th>Entidad</th>
                            <th>Resumen</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td>{{ item.created_at }}</td>
                            <td>{{ item.user_id }}</td>
                            <td>{{ item.module }}</td>
                            <td><span class="badge badge-light">{{ item.action }}</span></td>
                            <td>{{ item.entity_type }} #{{ item.entity_id }}</td>
                            <td>{{ item.summary }}</td>
                            <td>{{ item.ip }}</td>
                        </tr>
                        <tr v-if="items.length === 0"><td colspan="7" class="text-center text-muted">Sin eventos de auditoria</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-audit',
        data: {
            error: '',
            items: [],
            stats: {},
            filters: { module: '', entity_type: '' }
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
                    });
            }
        }
    });
};
</script>
