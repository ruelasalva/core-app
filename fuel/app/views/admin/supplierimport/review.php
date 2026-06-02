<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .supplier-review-table td { vertical-align: middle; }
    .supplier-review-name { min-width: 220px; max-width: 360px; overflow-wrap: anywhere; }
    .supplier-review-url { max-width: 180px; overflow-wrap: anywhere; }
    .supplier-review-actions { white-space: nowrap; }
</style>

<div id="app-supplier-review" v-cloak>
    <div class="row mb-3">
        <div class="col-md-7">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                Revisi&oacute;n de filas cargadas a staging. Aprobar o rechazar no crea productos reales.
            </div>
        </div>
        <div class="col-md-5 text-md-right mt-2 mt-md-0">
            <a href="<?php echo Uri::create('admin/supplierimport'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a importaciones
            </a>
            <button type="button" class="btn btn-sm btn-success ml-1" :disabled="selectedIds.length === 0 || loadingAction" @click="approveSelected">
                <i class="bi bi-check2-circle"></i> Aprobar seleccionados
            </button>
            <button type="button" class="btn btn-sm btn-danger ml-1" :disabled="selectedIds.length === 0 || loadingAction" @click="rejectSelected">
                <i class="bi bi-x-circle"></i> Rechazar seleccionados
            </button>
            <button type="button" class="btn btn-sm btn-primary ml-1" :disabled="loadingAction" @click="applyApproved">
                <i class="bi bi-box-seam"></i> Crear productos aprobados
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary ml-1" :disabled="loadingAction" @click="downloadImages">
                <i class="bi bi-image"></i> Descargar im&aacute;genes
            </button>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>
    <div v-if="successMessage" class="alert alert-success">
        {{ successMessage }}
    </div>
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

    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title mb-0">Filtros</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 mb-2">
                    <label>Proveedor</label>
                    <select class="form-control form-control-sm" v-model="filters.provider">
                        <option value="">Todos</option>
                        <option v-for="provider in filterOptions.providers" :key="provider" :value="provider">{{ provider }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Marca</label>
                    <select class="form-control form-control-sm" v-model="filters.brand">
                        <option value="">Todas</option>
                        <option v-for="brand in filterOptions.brands" :key="brand" :value="brand">{{ brand }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Categor&iacute;a</label>
                    <select class="form-control form-control-sm" v-model="filters.category">
                        <option value="">Todas</option>
                        <option v-for="category in filterOptions.categories" :key="category" :value="category">{{ category }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Estado</label>
                    <select class="form-control form-control-sm" v-model="filters.row_status">
                        <option value="">Todos</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>Run</label>
                    <select class="form-control form-control-sm" v-model="filters.import_run_id">
                        <option value="0">Todos</option>
                        <option v-for="run in filterOptions.runs" :key="run.id" :value="run.id">#{{ run.id }} {{ run.provider_code }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-primary btn-block" @click="loadData">
                        <i class="bi bi-funnel"></i> Aplicar filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Filas de staging</h3>
            <span class="badge badge-light ml-2">{{ rows.length }} filas</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0 supplier-review-table">
                    <thead>
                        <tr>
                            <th style="width: 36px;">
                                <input type="checkbox" :checked="allVisibleSelected" @change="toggleAllVisible">
                            </th>
                            <th>Proveedor</th>
                            <th>SKU</th>
                            <th>Modelo</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Categor&iacute;a</th>
                            <th class="text-right">Costo proveedor</th>
                            <th class="text-right">Precio sugerido</th>
                            <th>Estado</th>
                            <th>Advertencia</th>
                            <th>Producto</th>
                            <th class="supplier-review-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td><input type="checkbox" :value="row.id" v-model="selectedIds"></td>
                            <td>{{ row.provider_code || '-' }}</td>
                            <td>{{ row.supplier_sku || '-' }}</td>
                            <td>{{ row.supplier_model || '-' }}</td>
                            <td class="supplier-review-name">{{ row.supplier_name || '-' }}</td>
                            <td>{{ row.supplier_brand || '-' }}</td>
                            <td>{{ row.supplier_category || '-' }}</td>
                            <td class="text-right">{{ money(row.supplier_cost || row.supplier_price, row.supplier_currency) }}</td>
                            <td class="text-right">{{ money(row.selling_price, row.supplier_currency) }}</td>
                            <td><span class="badge" :class="statusBadge(row.row_status)">{{ row.row_status_label }}</span></td>
                            <td>{{ row.warning_message || '-' }}</td>
                            <td>
                                <span class="badge" :class="matchBadge(row.match)">{{ row.match.match_label }}</span>
                                <div v-if="row.match.product_id" class="small text-muted">
                                    #{{ row.match.product_id }} {{ row.match.product_sku || '' }} {{ row.match.product_name || '' }}
                                </div>
                            </td>
                            <td class="supplier-review-actions">
                                <button type="button" class="btn btn-xs btn-outline-info" @click="openDetail(row)">
                                    Ver detalle
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-success" :disabled="loadingAction || row.row_status === 'approved' || row.row_status === 'error'" @click="approveRows([row.id])">
                                    Aprobar
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-danger" :disabled="loadingAction || row.row_status === 'rejected' || row.row_status === 'error'" @click="rejectRows([row.id])">
                                    Rechazar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="13" class="text-center text-muted">No hay filas de staging con los filtros seleccionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Se muestran hasta 500 filas. La aprobaci&oacute;n solo cambia el estado de staging; no crea productos ni modifica precios o inventario.
        </div>
    </div>

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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-supplier-review');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var reviewDataUrl = <?php echo json_encode(Uri::create('admin/supplierimport/review_data'), $json_flags); ?>;
    var approveRowsUrl = <?php echo json_encode(Uri::create('admin/supplierimport/approve_rows'), $json_flags); ?>;
    var rejectRowsUrl = <?php echo json_encode(Uri::create('admin/supplierimport/reject_rows'), $json_flags); ?>;
    var applyApprovedUrl = <?php echo json_encode(Uri::create('admin/supplierimport/apply_approved'), $json_flags); ?>;
    var downloadImagesUrl = <?php echo json_encode(Uri::create('admin/supplierimport/download_images'), $json_flags); ?>;
    var initialData = <?php echo json_encode((array) $initial_data, $json_flags); ?>;

    new Vue({
        el: '#app-supplier-review',
        data: {
            loading: false,
            loadingAction: false,
            errorMessage: '',
            successMessage: '',
            warnings: initialData.warnings || [],
            rows: initialData.rows || [],
            filterOptions: initialData.filters || { providers: [], brands: [], categories: [], runs: [] },
            statusOptions: initialData.status_options || [],
            filters: {
                provider: '',
                brand: '',
                category: '',
                row_status: '',
                import_run_id: 0
            },
            selectedIds: [],
            detailRow: null,
            applyResult: null,
            imageResult: null
        },
        computed: {
            allVisibleSelected: function() {
                if (!this.rows.length) {
                    return false;
                }
                for (var i = 0; i < this.rows.length; i++) {
                    if (this.selectedIds.indexOf(this.rows[i].id) === -1) {
                        return false;
                    }
                }
                return true;
            }
        },
        methods: {
            loadData: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';
                self.successMessage = '';
                self.applyResult = null;
                self.imageResult = null;

                fetch(reviewDataUrl + '?' + self.queryString(), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(response) {
                        if (!response || response.success === false) {
                            self.errorMessage = response && response.errors && response.errors.length ? response.errors[0] : 'No se pudo cargar la revision de staging.';
                            return;
                        }
                        self.applyPayload(response.data || {});
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de revision.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            applyPayload: function(payload) {
                this.rows = payload.rows || [];
                this.filterOptions = payload.filters || this.filterOptions;
                this.statusOptions = payload.status_options || this.statusOptions;
                this.warnings = payload.warnings || [];
                this.selectedIds = [];
            },
            queryString: function() {
                var params = new URLSearchParams();
                params.set('provider', this.filters.provider || '');
                params.set('brand', this.filters.brand || '');
                params.set('category', this.filters.category || '');
                params.set('row_status', this.filters.row_status || '');
                params.set('import_run_id', this.filters.import_run_id || 0);
                return params.toString();
            },
            toggleAllVisible: function() {
                if (this.allVisibleSelected) {
                    this.selectedIds = [];
                    return;
                }
                this.selectedIds = this.rows.map(function(row) { return row.id; });
            },
            approveSelected: function() {
                this.approveRows(this.selectedIds);
            },
            rejectSelected: function() {
                this.rejectRows(this.selectedIds);
            },
            applyApproved: function() {
                if (!confirm('Se crearan productos no publicados solo desde filas aprobadas. No se actualizara inventario ni imagenes. Deseas continuar?')) {
                    return;
                }
                this.changeRows(applyApprovedUrl, [], 'apply');
            },
            downloadImages: function() {
                if (!confirm('Se descargaran imagenes solo para productos creados o mapeados. No se sobrescribiran imagenes existentes. Deseas continuar?')) {
                    return;
                }
                this.changeRows(downloadImagesUrl, [], 'images');
            },
            approveRows: function(ids) {
                this.changeRows(approveRowsUrl, ids);
            },
            rejectRows: function(ids) {
                this.changeRows(rejectRowsUrl, ids);
            },
            changeRows: function(url, ids, specialAction) {
                var self = this;
                if (!specialAction && (!ids || !ids.length)) {
                    self.errorMessage = 'Selecciona al menos una fila.';
                    return;
                }

                var formData = new FormData();
                if (!specialAction) {
                    formData.append('ids', JSON.stringify(ids));
                }
                if (window.coreAppCsrfKey && window.fuel_csrf_token) {
                    formData.append(window.coreAppCsrfKey, window.fuel_csrf_token());
                }

                self.loadingAction = true;
                self.errorMessage = '';
                self.successMessage = '';

                fetch(url + '?' + self.queryString(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-Token': window.fuel_csrf_token ? window.fuel_csrf_token() : ''
                    },
                    body: formData
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(response) {
                        if (!response || response.success === false) {
                            self.errorMessage = response && response.errors && response.errors.length ? response.errors[0] : 'No se pudo actualizar el estado.';
                            return;
                        }
                        self.successMessage = response.message || 'Estado actualizado.';
                        if (specialAction === 'apply' && response.data && response.data.result) {
                            self.applyResult = response.data.result;
                        }
                        if (specialAction === 'images' && response.data && response.data.result) {
                            self.imageResult = response.data.result;
                        }
                        var payload = response.data && response.data.review ? response.data.review : null;
                        if (payload) {
                            self.applyPayload(payload);
                        } else {
                            self.loadData();
                        }
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de actualizacion.';
                    })
                    .then(function() {
                        self.loadingAction = false;
                    });
            },
            openDetail: function(row) {
                this.detailRow = row;
            },
            money: function(value, currency) {
                return Number(value || 0).toLocaleString('es-MX', {
                    style: 'currency',
                    currency: currency || 'MXN'
                });
            },
            number: function(value) {
                return Number(value || 0).toLocaleString('es-MX');
            },
            statusBadge: function(status) {
                if (status === 'approved') return 'badge-success';
                if (status === 'rejected') return 'badge-danger';
                if (status === 'mapped') return 'badge-info';
                if (status === 'error') return 'badge-danger';
                if (status === 'skipped') return 'badge-secondary';
                return 'badge-light';
            },
            matchBadge: function(match) {
                match = match || {};
                if (match.match_status === 'existing') return 'badge-info';
                if (match.match_status === 'possible') return 'badge-warning';
                return 'badge-secondary';
            }
        }
    });
});
</script>
