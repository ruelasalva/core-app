    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-light mb-3">
        <div class="card-body py-2">
            <div class="row align-items-end">
                <div class="col-md-3"><label>Desde</label><input type="date" class="form-control" v-model="periodFilters.start_date"></div>
                <div class="col-md-3"><label>Hasta</label><input type="date" class="form-control" v-model="periodFilters.end_date"></div>
                <div class="col-md-3"><button class="btn btn-primary" @click="load"><i class="bi bi-funnel"></i> Consultar</button></div>
            </div>
        </div>
    </div>
