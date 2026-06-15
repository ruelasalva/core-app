    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Solicitudes de cotización</h3>
            <div class="card-tools">
                <span class="badge mr-2" :class="offline.online ? 'badge-success' : 'badge-warning'">
                    {{ offline.online ? 'En línea' : 'Sin conexión' }}
                </span>
                <button class="btn btn-outline-info btn-sm mr-2" @click="syncDrafts" :disabled="offline.syncing || offline.drafts.length === 0">
                    <i class="bi bi-arrow-repeat"></i> Sincronizar {{ offline.drafts.length || '' }}
                </button>
                <a class="btn btn-outline-secondary btn-sm mr-1" href="<?php echo Uri::create('admin/sales/create?mode=prequote'); ?>">
                    <i class="bi bi-bag-plus"></i> Vista cliente / catálogo
                </a>
                <a class="btn btn-primary btn-sm" href="<?php echo Uri::create('admin/sales/create'); ?>">
                    <i class="bi bi-plus-lg"></i> Nueva cotización
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="btn-group btn-group-sm mb-3" role="group" aria-label="Vista de ventas">
                <button class="btn" :class="viewMode === 'quotes' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'quotes'">
                    Cotizaciones
                </button>
                <button class="btn" :class="viewMode === 'orders' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'orders'">
                    Pedidos
                </button>
                <button class="btn" :class="viewMode === 'deliveries' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'deliveries'">
                    Entregas
                </button>
            </div>
            <div v-if="offline.drafts.length" class="alert alert-warning">
                <strong>Borradores en este equipo:</strong>
                <span v-for="draft in offline.drafts" :key="draft.key" class="badge badge-light border ml-2">
                    {{ draft.value.label || 'Cotizacion local' }}
                    <a href="#" @click.prevent="recoverDraft(draft)">abrir</a>
                    <a href="#" class="text-danger" @click.prevent="discardDraft(draft)">quitar</a>
                </span>
            </div>
            <div v-if="error" class="alert alert-danger">
                {{ error }}
            </div>
            <div class="card card-light mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-3"><label>Desde</label><input type="date" class="form-control" v-model="periodFilters.start_date"></div>
                        <div class="col-md-3"><label>Hasta</label><input type="date" class="form-control" v-model="periodFilters.end_date"></div>
                        <div class="col-md-3"><button class="btn btn-primary" @click="loadData"><i class="bi bi-funnel"></i> Consultar</button></div>
                    </div>
                </div>
            </div>
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando ventas...</p>
            </div>

