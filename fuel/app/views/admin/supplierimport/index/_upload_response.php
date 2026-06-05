    <div v-if="uploadResponse" class="card card-outline" :class="uploadResponse.success ? 'card-info' : 'card-danger'">
        <div class="card-header">
            <h3 class="card-title mb-0">Respuesta de carga CSV</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-6 mb-2">
                    <strong>{{ uploadResponse.http_status || '-' }}</strong>
                    <div class="text-muted small">HTTP status</div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <span class="badge" :class="uploadResponse.success ? 'badge-success' : 'badge-danger'">{{ uploadResponse.success ? 'success true' : 'success false' }}</span>
                    <div class="text-muted small">Resultado</div>
                </div>
                <div class="col-md-6 col-12 mb-2">
                    <strong>{{ uploadResponse.message || uploadResponse.error || firstError(uploadResponse) }}</strong>
                    <div class="text-muted small">Mensaje</div>
                </div>
            </div>
            <div class="row text-center mt-2">
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadSummary.total_rows) }}</strong>
                    <div class="text-muted small">Total filas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadSummary.valid_rows) }}</strong>
                    <div class="text-muted small">V&aacute;lidas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadSummary.invalid_rows) }}</strong>
                    <div class="text-muted small">Inv&aacute;lidas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadSummary.duplicates) }}</strong>
                    <div class="text-muted small">Duplicadas</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadSummary.warnings) }}</strong>
                    <div class="text-muted small">Advertencias</div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <strong>{{ number(uploadSummary.errors) }}</strong>
                    <div class="text-muted small">Errores</div>
                </div>
            </div>
            <div v-if="uploadResponse.errors && uploadResponse.errors.length" class="alert alert-danger mt-2 mb-0">
                <div v-for="error in uploadResponse.errors" :key="error">{{ error }}</div>
            </div>
            <div v-if="uploadResponse.warnings && uploadResponse.warnings.length" class="alert alert-warning mt-2 mb-0">
                <div v-for="warning in uploadResponse.warnings" :key="warning">{{ warning }}</div>
            </div>
            <div v-if="uploadResponse.debug" class="mt-3">
                <div class="small text-muted mb-1">Debug recibido</div>
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                        <tr><th>has_file</th><td>{{ uploadResponse.debug.has_file ? 'si' : 'no' }}</td></tr>
                        <tr><th>filename</th><td>{{ uploadResponse.debug.filename || '-' }}</td></tr>
                        <tr><th>party_id</th><td>{{ uploadResponse.debug.party_id }}</td></tr>
                        <tr><th>source_code</th><td>{{ uploadResponse.debug.source_code || '-' }}</td></tr>
                        <tr><th>provider</th><td>{{ uploadResponse.debug.provider || '-' }}</td></tr>
                        <tr><th>mode</th><td>{{ uploadResponse.debug.mode || '-' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
