<div v-show="!loading && tab === 'documents'">
    <table class="table table-bordered table-hover portal-table">
        <thead>
            <tr>
                <th>Documento</th>
                <th>Tipo</th>
                <th>Registro</th>
                <th>Tamano</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="document in documents" :key="document.document_id">
                <td>
                    <a :href="document.download_url" target="_blank" rel="noopener">{{ document.title || document.filename || document.original_name }}</a>
                    <div class="text-muted small">{{ document.original_name }}</div>
                    <div class="small">{{ document.description || '' }}</div>
                </td>
                <td>
                    <span class="badge badge-secondary">{{ documentTypeLabel(document.document_type) }}</span>
                    <div class="text-muted small">{{ document.file_extension }}</div>
                </td>
                <td>{{ entityLabel(document.entity_type) }} #{{ document.entity_id }}</td>
                <td>{{ formatSize(document.file_size) }}</td>
                <td>{{ dateLabel(document.created_at) }}</td>
            </tr>
            <tr v-if="documents.length === 0">
                <td colspan="5"><div class="portal-empty">Sin documentos o evidencias cargadas.</div></td>
            </tr>
        </tbody>
    </table>
</div>
