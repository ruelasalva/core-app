    <div v-if="uploadResult" class="card card-outline" :class="uploadResult.errors > 0 ? 'card-danger' : (uploadResult.warnings > 0 || uploadResult.duplicates > 0 ? 'card-warning' : 'card-success')">
        <div class="card-header">
            <h3 class="card-title mb-0">Resultado de importaci&oacute;n CSV</h3>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadResult.total_rows) }}</strong>
                    <div class="text-muted small">Total filas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadResult.valid_rows || uploadResult.normalized) }}</strong>
                    <div class="text-muted small">V&aacute;lidas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadResult.invalid_rows) }}</strong>
                    <div class="text-muted small">Inv&aacute;lidas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadResult.duplicates) }}</strong>
                    <div class="text-muted small">Duplicadas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadResult.warnings) }}</strong>
                    <div class="text-muted small">Advertencias</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadResult.errors) }}</strong>
                    <div class="text-muted small">Errores</div>
                </div>
            </div>
            <div v-if="uploadResult.messages && uploadResult.messages.length" class="mt-2">
                <div class="small text-muted mb-1">Mensajes</div>
                <ul class="small mb-0">
                    <li v-for="message in uploadResult.messages.slice(0, 10)" :key="message">{{ message }}</li>
                </ul>
            </div>
            <div v-if="uploadResult.total_rows == 0" class="alert alert-warning mt-3 mb-0">
                El archivo no contiene filas para validar.
            </div>
            <div v-else-if="(uploadResult.valid_rows || uploadResult.normalized || 0) == 0" class="alert alert-warning mt-3 mb-0">
                La validaci&oacute;n no encontr&oacute; filas v&aacute;lidas. Revisa los mensajes y columnas del CSV.
            </div>
        </div>
    </div>
