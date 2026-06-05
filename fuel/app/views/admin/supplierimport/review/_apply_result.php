    <div v-if="applyResult" class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Resultado de creaci&oacute;n de productos</h3>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(applyResult.approved_found) }}</strong>
                    <div class="text-muted small">Aprobadas encontradas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(applyResult.products_created) }}</strong>
                    <div class="text-muted small">Productos creados</div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>{{ number(applyResult.existing_products_mapped) }}</strong>
                    <div class="text-muted small">Productos existentes mapeados</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(applyResult.skipped) }}</strong>
                    <div class="text-muted small">Omitidas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(applyResult.errors) }}</strong>
                    <div class="text-muted small">Errores</div>
                </div>
            </div>
            <div v-if="applyResult.messages && applyResult.messages.length" class="alert alert-warning mb-0">
                <div v-for="message in applyResult.messages" :key="message">{{ message }}</div>
            </div>
        </div>
    </div>
