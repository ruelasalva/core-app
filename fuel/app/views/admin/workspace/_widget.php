<div class="card h-100" :class="widgetCardClass(instance.widget_code)">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">{{ widgetTitle(instance.widget_code) }}</h3>
        <button type="button" class="btn btn-xs btn-outline-secondary ml-auto" @click="loadWidget(instance.widget_code)">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
    <div class="card-body">
        <div v-if="widgetLoading[instance.widget_code]" class="text-muted">Cargando...</div>
        <div v-else-if="widgetErrors[instance.widget_code]" class="text-warning">{{ widgetErrors[instance.widget_code] }}</div>
        <div v-else v-html="widgetHtml(instance.widget_code)"></div>
    </div>
</div>
