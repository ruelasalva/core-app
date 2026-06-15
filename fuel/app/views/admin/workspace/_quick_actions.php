<div class="card card-primary card-outline">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">Acciones rápidas</h3>
        <span class="badge badge-light ml-auto">Framework base</span>
    </div>
    <div class="card-body">
        <div v-if="loading" class="text-muted">Cargando Workspace...</div>
        <div v-else-if="quickActions.length === 0" class="text-muted">No hay acciones rápidas disponibles para tu rol.</div>
        <div v-else class="d-flex flex-wrap">
            <a v-for="action in quickActions" :key="action.code" :href="baseUrl + action.route" class="btn btn-sm mr-2 mb-2" :class="'btn-outline-' + (action.color || 'primary')">
                <i :class="action.icon || 'bi bi-lightning'"></i> {{ action.title }}
            </a>
        </div>
    </div>
</div>

