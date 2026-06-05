    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title mb-0">Filtros</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 mb-2">
                    <label>Proveedor</label>
                    <select class="form-control form-control-sm" v-model="filters.provider">
                        <option value="">Todos</option>
                        <option v-for="provider in filterOptions.providers" :key="provider" :value="provider">{{ provider }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Marca</label>
                    <select class="form-control form-control-sm" v-model="filters.brand">
                        <option value="">Todas</option>
                        <option v-for="brand in filterOptions.brands" :key="brand" :value="brand">{{ brand }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Categor&iacute;a</label>
                    <select class="form-control form-control-sm" v-model="filters.category">
                        <option value="">Todas</option>
                        <option v-for="category in filterOptions.categories" :key="category" :value="category">{{ category }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Estado</label>
                    <select class="form-control form-control-sm" v-model="filters.row_status">
                        <option value="">Todos</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Run</label>
                    <select class="form-control form-control-sm" v-model="filters.import_run_id">
                        <option value="0">Todos</option>
                        <option v-for="run in filterOptions.runs" :key="run.id" :value="run.id">#{{ run.id }} {{ run.provider_code }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-primary btn-block" @click="loadData">
                        <i class="bi bi-funnel"></i> Aplicar filtros
                    </button>
                </div>
            </div>
        </div>
    </div>
