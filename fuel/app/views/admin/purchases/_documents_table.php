            <div v-show="!loading && tab === 'documents'">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Documento</th><th>Registro</th><th>Visibilidad</th><th>Evidencia</th><th>Fecha</th></tr></thead>
                    <tbody>
                        <tr v-for="document in documents" :key="document.id">
                            <td><a :href="document.download_url" target="_blank" rel="noopener">{{ document.title || document.filename || document.original_name }}</a><div class="text-muted small">{{ document.filename || document.original_name }}</div></td>
                            <td>{{ entityLabel(document.entity_type) }} #{{ document.entity_id }}</td>
                            <td>{{ document.visibility }}</td>
                            <td>{{ document.is_evidence == 1 ? 'Si' : 'No' }}</td>
                            <td>{{ document.created_label }}</td>
                        </tr>
                        <tr v-if="documents.length === 0"><td colspan="5" class="text-center text-muted">Sin evidencias.</td></tr>
                    </tbody>
                </table>
            </div>
