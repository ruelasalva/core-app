    <div v-if="detailRow" class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,.45);">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de fila staging #{{ detailRow.id }}</h5>
                    <button type="button" class="close" aria-label="Cerrar" @click="detailRow = null">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt>Proveedor</dt><dd>{{ detailRow.provider_code || '-' }}</dd>
                                <dt>SKU</dt><dd>{{ detailRow.supplier_sku || '-' }}</dd>
                                <dt>Modelo</dt><dd>{{ detailRow.supplier_model || '-' }}</dd>
                                <dt>Nombre</dt><dd>{{ detailRow.supplier_name || '-' }}</dd>
                                <dt>Marca</dt><dd>{{ detailRow.supplier_brand || '-' }}</dd>
                                <dt>Categor&iacute;a</dt><dd>{{ detailRow.supplier_category || '-' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl>
                                <dt>Costo proveedor</dt><dd>{{ money(detailRow.supplier_cost || detailRow.supplier_price, detailRow.supplier_currency) }}</dd>
                                <dt>Precio sugerido</dt><dd>{{ money(detailRow.selling_price, detailRow.supplier_currency) }}</dd>
                                <dt>Estado</dt><dd>{{ detailRow.row_status_label }}</dd>
                                <dt>Advertencia</dt><dd>{{ detailRow.warning_message || '-' }}</dd>
                                <dt>Imagen URL</dt><dd class="supplier-review-url">{{ detailRow.image_url || '-' }}</dd>
                                <dt>URL origen</dt><dd class="supplier-review-url">{{ detailRow.source_url || '-' }}</dd>
                            </dl>
                        </div>
                    </div>
                    <hr>
                    <h6>Coincidencia de producto</h6>
                    <div class="alert mb-0" :class="detailRow.match.match_status === 'new' ? 'alert-secondary' : (detailRow.match.match_status === 'possible' ? 'alert-warning' : 'alert-info')">
                        <strong>{{ detailRow.match.match_label }}</strong>
                        <div v-if="detailRow.match.product_id">
                            Producto #{{ detailRow.match.product_id }} - {{ detailRow.match.product_sku || '-' }} - {{ detailRow.match.product_name || '-' }}
                            <span v-if="detailRow.match.product_brand">({{ detailRow.match.product_brand }})</span>
                        </div>
                        <div v-else>
                            No hay producto interno relacionado todav&iacute;a.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="detailRow = null">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
