<div class="card h-100" :class="widgetCardClass(instance.widget_code)">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">{{ widgetTitle(instance.widget_code) }}</h3>
        <button v-if="showWidgetInspector" type="button" class="btn btn-xs btn-link ml-auto workspace-inspector-toggle" @click="toggleWidgetInspector(instance.widget_code)">
            Inspector
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary" :class="{ 'ml-auto': !showWidgetInspector }" @click="loadWidget(instance.widget_code)">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
    <div v-if="editMode" class="workspace-widget-controls">
        <button type="button" class="btn btn-xs btn-outline-secondary" @click="moveWidget(instance.widget_code, -1)">
            <i class="bi bi-arrow-up"></i> Subir
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary" @click="moveWidget(instance.widget_code, 1)">
            <i class="bi bi-arrow-down"></i> Bajar
        </button>
        <div class="btn-group btn-group-xs ml-1" role="group" aria-label="Tamaño">
            <button type="button" class="btn btn-outline-secondary" @click="setWidgetSize(instance.widget_code, 'small')">S</button>
            <button type="button" class="btn btn-outline-secondary" @click="setWidgetSize(instance.widget_code, 'medium')">M</button>
            <button type="button" class="btn btn-outline-secondary" @click="setWidgetSize(instance.widget_code, 'large')">L</button>
        </div>
        <button type="button" class="btn btn-xs btn-outline-danger ml-1" @click="hideWidget(instance.widget_code)">
            <i class="bi bi-eye-slash"></i> Ocultar
        </button>
    </div>
    <div class="card-body">
        <div v-if="widgetState(instance.widget_code) === 'loading'" class="workspace-widget-skeleton">
            <div class="placeholder-line w-75"></div>
            <div class="placeholder-line w-50"></div>
            <div class="placeholder-line w-25"></div>
        </div>
        <div v-else-if="widgetState(instance.widget_code) === 'error'" class="alert alert-warning mb-0">
            {{ widgetMessage(instance.widget_code, 'No se pudo cargar el widget.') }}
        </div>
        <div v-else-if="widgetState(instance.widget_code) === 'forbidden'" class="alert alert-secondary mb-0">
            {{ widgetMessage(instance.widget_code, 'No tienes permiso para ver este widget.') }}
        </div>
        <div v-else-if="widgetState(instance.widget_code) === 'disabled'" class="alert alert-light border mb-0">
            {{ widgetMessage(instance.widget_code, 'Widget deshabilitado.') }}
        </div>
        <div v-else-if="widgetRenderType(instance.widget_code) === 'compact_table'" class="workspace-compact-table">
            <div v-if="widgetRows(instance.widget_code).length > 0" class="workspace-table-list">
                <div class="workspace-table-row workspace-table-head">
                    <span v-for="column in widgetColumns(instance.widget_code)" :key="column.key">{{ column.label }}</span>
                </div>
                <div class="workspace-table-row" v-for="(row, rowIndex) in widgetRows(instance.widget_code)" :key="rowIndex">
                    <span v-for="column in widgetColumns(instance.widget_code)" :key="column.key">{{ formatCell(row[column.key]) }}</span>
                </div>
            </div>
            <div v-else class="workspace-widget-empty">
                <div class="workspace-empty-state">
                    <strong><span class="workspace-empty-icon"><i :class="widgetPayload(instance.widget_code).empty_icon || 'bi bi-info-circle'"></i></span>{{ widgetPayload(instance.widget_code).empty_title || 'Sin datos disponibles.' }}</strong>
                    <p>{{ widgetPayload(instance.widget_code).empty_message || 'No hay información para mostrar.' }}</p>
                </div>
            </div>
            <a v-if="widgetAction(instance.widget_code)" class="btn btn-xs btn-outline-primary mt-2" :href="widgetAction(instance.widget_code).url">
                <i :class="widgetAction(instance.widget_code).icon || 'bi bi-arrow-right'"></i>
                {{ widgetAction(instance.widget_code).label || 'Abrir módulo' }}
            </a>
        </div>
        <div v-else-if="widgetState(instance.widget_code) === 'empty'" class="workspace-widget-empty" v-html="widgetHtml(instance.widget_code)"></div>
        <div v-else v-html="widgetHtml(instance.widget_code)"></div>

        <div v-if="showWidgetInspector && isWidgetInspectorOpen(instance.widget_code)" class="workspace-widget-inspector mt-2">
            <span>code: {{ instance.widget_code }}</span>
            <span>state: {{ widgetState(instance.widget_code) }}</span>
            <span>generated_at: {{ widgetHealth(instance.widget_code).generated_at || '-' }}</span>
            <span>execution_ms: {{ widgetHealth(instance.widget_code).execution_ms || 0 }}</span>
            <span>cache_hit: {{ widgetHealth(instance.widget_code).cache_hit ? 'sí' : 'no' }}</span>
        </div>
    </div>
</div>
