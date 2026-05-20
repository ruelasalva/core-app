<div v-show="!loading && tab === 'documents'">
    <table class="table table-bordered table-hover">
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
            <tr v-for="document in documents" :key="document.id">
                <td>
                    <a :href="baseUrl + document.file_path" target="_blank">{{ document.title || document.original_name }}</a>
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
                <td colspan="5" class="text-center text-muted">Sin documentos.</td>
            </tr>
        </tbody>
    </table>
</div>
