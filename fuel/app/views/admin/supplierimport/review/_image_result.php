    <div v-if="imageResult" class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">Resultado de descarga de im&aacute;genes</h3>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-2">
                    <strong>{{ number(imageResult.products_processed) }}</strong>
                    <div class="text-muted small">Productos procesados</div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>{{ number(imageResult.images_downloaded) }}</strong>
                    <div class="text-muted small">Im&aacute;genes descargadas</div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>{{ number(imageResult.images_skipped) }}</strong>
                    <div class="text-muted small">Im&aacute;genes omitidas</div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>{{ number(imageResult.errors) }}</strong>
                    <div class="text-muted small">Errores</div>
                </div>
            </div>
            <div v-if="imageResult.messages && imageResult.messages.length" class="alert alert-warning mb-0">
                <div v-for="message in imageResult.messages" :key="message">{{ message }}</div>
            </div>
        </div>
    </div>
    <div v-if="warnings.length" class="alert alert-warning">
        <div v-for="warning in warnings" :key="warning">{{ warning }}</div>
    </div>
