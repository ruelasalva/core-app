<div v-show="!loading && tab === 'documents'">
    <div class="supplier-section-help mb-3">
        Aquí se concentran XML, PDF, remisiones, comprobantes y evidencias cargadas para órdenes, facturas y contrarecibos.
    </div>
    <table class="table table-bordered table-hover portal-table supplier-responsive-table">
        <thead>
            <tr>
                <th>Documento</th>
                <th>Tipo</th>
                <th>Relacionado con</th>
                <th>Relación</th>
                <th>Tamaño</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="document in documents" :key="document.document_id">
                <td>
                    <span class="supplier-mobile-card-label">Documento</span>
                    <a :href="document.download_url" target="_blank" rel="noopener">{{ document.title || document.filename || document.original_name || 'Documento' }}</a>
                    <div class="text-muted small">{{ document.original_name || document.filename }}</div>
                    <div class="small">{{ document.description || '' }}</div>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Tipo</span>
                    <span class="badge badge-secondary">{{ documentTypeLabel(document.document_type) }}</span>
                    <div class="text-muted small">{{ document.file_extension }}</div>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Relacionado con</span>
                    <span class="badge badge-light">{{ entityLabel(document.entity_type) }}</span>
                    <div class="text-muted small">#{{ document.entity_id }}</div>
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Relación</span>
                    {{ relationLabel(document.relation_type) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Tamaño</span>
                    {{ formatSize(document.file_size) }}
                </td>
                <td>
                    <span class="supplier-mobile-card-label">Fecha</span>
                    {{ dateLabel(document.created_at) }}
                </td>
            </tr>
            <tr v-if="documents.length === 0">
                <td colspan="6">
                    <div class="supplier-empty-state">Aún no hay evidencias cargadas. Adjunta XML, PDF, remisiones o comprobantes desde una OC, factura o contrarecibo.</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
