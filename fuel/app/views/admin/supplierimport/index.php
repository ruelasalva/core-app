<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .supplier-import-table td { vertical-align: middle; }
    .supplier-import-help code { white-space: normal; }
</style>

<div id="app-supplier-import" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                Staging de cat&aacute;logos de proveedor. Solo lectura: no crea productos, precios ni inventario.
            </div>
        </div>
        <div class="col-md-4 text-md-right mt-2 mt-md-0">
            <a href="<?php echo Uri::create('admin/supplierimport/review'); ?>" class="btn btn-sm btn-outline-success mr-2">
                <i class="bi bi-check2-square"></i> Revisar staging
            </a>
            <button type="button" class="btn btn-sm btn-primary mr-2" @click="openUploadModal">
                <i class="bi bi-upload"></i> Importar CSV
            </button>
            <a href="<?php echo Uri::create('admin/supplierimport/csv_template'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download"></i> Descargar plantilla CSV
            </a>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in warnings" :key="warning">{{ warning }}</div>
    </div>

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

    <div class="row">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number(summary.total_rows) }}</h3>
                    <p>Total filas</p>
                </div>
                <div class="icon"><i class="bi bi-list-check"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number(summary.valid_rows) }}</h3>
                    <p>Filas v&aacute;lidas</p>
                </div>
                <div class="icon"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number(summary.invalid_rows) }}</h3>
                    <p>Filas inv&aacute;lidas</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-octagon"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number(summary.duplicates) }}</h3>
                    <p>Duplicados</p>
                </div>
                <div class="icon"><i class="bi bi-files"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number(summary.warnings) }}</h3>
                    <p>Advertencias</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number(summary.dry_run_runs) }}</h3>
                    <p>Dry-run guardados</p>
                </div>
                <div class="icon"><i class="bi bi-eye"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Importaciones</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0 supplier-import-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Tipo</th>
                            <th>Archivo</th>
                            <th>Estado</th>
                            <th class="text-right">Filas</th>
                            <th class="text-right">Insertadas</th>
                            <th class="text-right">Omitidas</th>
                            <th class="text-right">Errores</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="run in runs" :key="run.id">
                            <td>{{ run.id }}</td>
                            <td>{{ run.provider_code || '-' }}</td>
                            <td>{{ run.import_type }}</td>
                            <td>{{ run.source_name || run.file_path || '-' }}</td>
                            <td><span class="badge" :class="statusBadge(run.status)">{{ run.status_label }}</span></td>
                            <td class="text-right">{{ number(run.rows_count) }}</td>
                            <td class="text-right">{{ number(run.created_count) }}</td>
                            <td class="text-right">{{ number(run.skipped_count) }}</td>
                            <td class="text-right">{{ number(run.error_count) }}</td>
                            <td>{{ run.created_at_label }}</td>
                        </tr>
                        <tr v-if="runs.length === 0">
                            <td colspan="10" class="text-center text-muted">Sin importaciones registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Las ejecuciones dry-run por consola validan el archivo sin guardar filas. Las ejecuciones con dry-run=0 guardan solo staging.
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">Filas de staging</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0 supplier-import-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Run</th>
                            <th>Proveedor</th>
                            <th>SKU</th>
                            <th>Modelo</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Categor&iacute;a</th>
                            <th class="text-right">Precio proveedor</th>
                            <th class="text-right">Precio sugerido</th>
                            <th class="text-right">Stock proveedor</th>
                            <th>Estado</th>
                            <th>Errores / advertencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>{{ row.id }}</td>
                            <td>{{ row.import_run_id }}</td>
                            <td>{{ row.provider_code || '-' }}</td>
                            <td>{{ row.supplier_sku || '-' }}</td>
                            <td>{{ row.supplier_model || '-' }}</td>
                            <td>{{ row.supplier_name || '-' }}</td>
                            <td>{{ row.supplier_brand || '-' }}</td>
                            <td>{{ row.supplier_category || '-' }}</td>
                            <td class="text-right">{{ money(row.supplier_price, row.supplier_currency) }}</td>
                            <td class="text-right">{{ money(row.selling_price, row.supplier_currency) }}</td>
                            <td class="text-right">{{ number(row.supplier_stock) }}</td>
                            <td><span class="badge badge-light">{{ row.row_status_label }}</span></td>
                            <td>{{ row.error_message || '-' }}</td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="13" class="text-center text-muted">Sin filas de staging.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Se muestran hasta 500 filas recientes. Esta pantalla no permite crear productos ni actualizar datos reales.
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-secondary supplier-import-help">
                <div class="card-header">
                    <h3 class="card-title mb-0">Comandos</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Validar CSV sin guardar:</p>
                    <code>php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=1</code>
                    <hr>
                    <p class="mb-2">Guardar en staging:</p>
                    <code>php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=0</code>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-outline card-secondary supplier-import-help">
                <div class="card-header">
                    <h3 class="card-title mb-0">Ayuda: Importaci&oacute;n de proveedores</h3>
                </div>
                <div class="card-body">
                    <p>La importaci&oacute;n de proveedores permite cargar cat&aacute;logos externos a una tabla temporal de revisi&oacute;n antes de crear productos reales.</p>
                    <ul>
                        <li>El modo dry-run solo valida y muestra totales.</li>
                        <li>El modo staging guarda filas en espera de revisi&oacute;n.</li>
                        <li>No se modifican productos, precios, inventario ni im&aacute;genes.</li>
                        <li>Las columnas principales son SKU, modelo, nombre, marca, categor&iacute;a, precio, moneda y URL de origen.</li>
                    </ul>
                    <p class="mb-0">Cuando se aprueben fases posteriores, estas filas podr&aacute;n mapearse contra productos internos mediante equivalencias de proveedor.</p>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showUploadModal" class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,.45);">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar CSV de proveedor</h5>
                    <button type="button" class="close" aria-label="Cerrar" @click="closeUploadModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="uploadError" class="alert alert-danger">{{ uploadError }}</div>
                    <div class="alert alert-info">
                        Primero se cargan los productos a staging. No se crean productos reales hasta que se aprueben.
                    </div>
                    <div class="form-group">
                        <label>Proveedor comercial</label>
                        <select class="form-control" v-model="uploadForm.party_id" @change="selectSupplier">
                            <option value="0">Seleccionar proveedor comercial</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplierLabel(supplier) }}</option>
                        </select>
                        <small class="form-text text-muted">Selecciona un proveedor registrado en terceros comerciales. Si no existe, usa el c&oacute;digo avanzado temporalmente.</small>
                    </div>
                    <div class="form-group">
                        <label>Fuente de importaci&oacute;n</label>
                        <select class="form-control" v-model="uploadForm.source_code">
                            <option v-for="source in sources" :key="source.code" :value="source.code" :disabled="source.pending">{{ sourceLabel(source) }}</option>
                        </select>
                        <small class="form-text text-muted">Por ahora solo CSV / Excel manual est&aacute; disponible. Las APIs y scrapers quedan preparados para fases posteriores.</small>
                    </div>
                    <div class="form-group">
                        <label>C&oacute;digo avanzado de proveedor</label>
                        <input type="text" class="form-control" v-model="uploadForm.provider" placeholder="cva, ct, syscom, tonersparaimpresoras">
                        <small class="form-text text-muted">Usar solo si no seleccionas proveedor comercial. Ejemplo: cva, ct, syscom, tonersparaimpresoras, proveedor_local</small>
                        <small class="form-text text-muted">El c&oacute;digo se normaliza a min&uacute;sculas y guiones bajos. No crea proveedor nuevo.</small>
                    </div>
                    <div class="form-group">
                        <label>Archivo CSV</label>
                        <input ref="csvFile" type="file" class="form-control-file" accept=".csv,.txt,text/csv" @change="handleFileChange">
                        <small class="form-text text-muted">Tama&ntilde;o m&aacute;ximo 5 MB. No se ejecuta contenido del archivo.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Modo</label>
                        <div class="custom-control custom-radio">
                            <input id="supplier-import-mode-dry" type="radio" class="custom-control-input" value="dry_run" v-model="uploadForm.mode">
                            <label class="custom-control-label" for="supplier-import-mode-dry">Validar solamente</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input id="supplier-import-mode-staging" type="radio" class="custom-control-input" value="staging" v-model="uploadForm.mode">
                            <label class="custom-control-label" for="supplier-import-mode-staging">Importar a staging</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" :disabled="uploading" @click="closeUploadModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" :disabled="uploading" @click="submitUpload">
                        <span v-if="uploading" class="spinner-border spinner-border-sm mr-1"></span>
                        Procesar CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-supplier-import');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var dataUrl = <?php echo json_encode(Uri::create('admin/supplierimport/data'), $json_flags); ?>;
    var uploadCsvUrl = <?php echo json_encode(Uri::create('admin/supplierimport/upload_csv'), $json_flags); ?>;
    var initialData = <?php echo json_encode((array) $initial_data, $json_flags); ?>;

    new Vue({
        el: '#app-supplier-import',
        data: {
            loading: false,
            errorMessage: '',
            runs: initialData.runs || [],
            rows: initialData.rows || [],
            summary: initialData.validation || {},
            providers: initialData.providers || [],
            sources: initialData.sources || initialData.providers || [],
            suppliers: initialData.suppliers || [],
            warnings: initialData.warnings || [],
            showUploadModal: false,
            uploading: false,
            uploadError: '',
            uploadResponse: null,
            uploadSummary: {},
            uploadResult: null,
            uploadForm: {
                party_id: 0,
                source_code: 'csv_manual',
                provider: '',
                mode: 'dry_run',
                file: null
            }
        },
        mounted: function() {
            this.loadData();
        },
        methods: {
            loadData: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                fetch(dataUrl, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(response) {
                        if (!response || response.success === false) {
                            self.errorMessage = response && response.errors && response.errors.length ? response.errors[0] : 'No se pudo cargar la importaci&oacute;n de proveedores.';
                            return;
                        }
                        var payload = response.data || {};
                        self.runs = payload.runs || [];
                        self.rows = payload.rows || [];
                        self.summary = payload.validation || {};
                        self.providers = payload.providers || [];
                        self.sources = payload.sources || payload.providers || [];
                        self.suppliers = payload.suppliers || [];
                        self.warnings = payload.warnings || [];
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de importaci&oacute;n de proveedores.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            number: function(value) {
                return Number(value || 0).toLocaleString('es-MX');
            },
            money: function(value, currency) {
                return Number(value || 0).toLocaleString('es-MX', {
                    style: 'currency',
                    currency: currency || 'MXN'
                });
            },
            statusBadge: function(status) {
                if (status === 'completed') return 'badge-success';
                if (status === 'warning') return 'badge-warning';
                if (status === 'error') return 'badge-danger';
                if (status === 'running') return 'badge-info';
                return 'badge-secondary';
            },
            firstError: function(response) {
                if (response && response.errors && response.errors.length) {
                    return response.errors[0];
                }
                return 'No se pudo procesar el archivo CSV.';
            },
            supplierLabel: function(supplier) {
                var label = supplier && supplier.name ? supplier.name : 'Proveedor';
                if (supplier && supplier.rfc) {
                    label += ' - ' + supplier.rfc;
                }
                return label;
            },
            sourceLabel: function(source) {
                var label = source && source.name ? source.name : 'Fuente';
                if (source && source.pending) {
                    label += ' (pendiente)';
                }
                return label;
            },
            selectedSource: function() {
                var code = this.uploadForm.source_code;
                for (var i = 0; i < this.sources.length; i++) {
                    if (this.sources[i].code === code) {
                        return this.sources[i];
                    }
                }
                return null;
            },
            openUploadModal: function() {
                this.uploadError = '';
                this.uploadResponse = null;
                this.uploadSummary = {};
                this.uploadResult = null;
                this.showUploadModal = true;
            },
            closeUploadModal: function() {
                if (this.uploading) {
                    return;
                }
                this.showUploadModal = false;
            },
            handleFileChange: function(event) {
                var files = event.target.files || [];
                this.uploadForm.file = files.length ? files[0] : null;
            },
            selectProvider: function(providerCode) {
                if (providerCode) {
                    this.uploadForm.provider = this.normalizeProviderCode(providerCode);
                }
            },
            selectSupplier: function() {
                var partyId = Number(this.uploadForm.party_id || 0);
                if (partyId > 0) {
                    this.uploadForm.provider = '';
                }
            },
            normalizeProviderCode: function(value) {
                return String(value || '')
                    .toLowerCase()
                    .trim()
                    .replace(/\s+/g, '_')
                    .replace(/[^a-z0-9_]+/g, '')
                    .replace(/_+/g, '_')
                    .replace(/^_+|_+$/g, '');
            },
            submitUpload: function() {
                var self = this;
                self.uploadError = '';
                self.uploadResponse = null;
                self.uploadSummary = {};
                var providerCode = self.normalizeProviderCode(self.uploadForm.provider);
                var partyId = Number(self.uploadForm.party_id || 0);
                var sourceCode = self.normalizeProviderCode(self.uploadForm.source_code || 'csv_manual');
                var source = self.selectedSource();

                if (partyId < 1 && !providerCode) {
                    self.uploadError = 'Selecciona un proveedor comercial o captura un código avanzado de proveedor.';
                    self.setLocalUploadResponse('Selecciona un proveedor comercial o captura un código avanzado de proveedor.');
                    return;
                }
                if (!sourceCode) {
                    self.uploadError = 'Selecciona una fuente de importación.';
                    self.setLocalUploadResponse('Selecciona una fuente de importación.');
                    return;
                }
                if (source && source.pending) {
                    self.uploadError = 'La fuente seleccionada está pendiente. Usa CSV / Excel manual por ahora.';
                    self.setLocalUploadResponse('La fuente seleccionada está pendiente. Usa CSV / Excel manual por ahora.');
                    return;
                }
                if (!self.uploadForm.file) {
                    self.uploadError = 'Selecciona un archivo CSV.';
                    self.setLocalUploadResponse('Selecciona un archivo CSV.');
                    return;
                }

                self.uploadForm.provider = providerCode;
                var formData = new FormData();
                formData.append('party_id', partyId);
                formData.append('source_code', sourceCode);
                formData.append('provider', providerCode);
                formData.append('mode', self.uploadForm.mode);
                formData.append('csv_file', self.uploadForm.file);
                formData.append('file', self.uploadForm.file);
                if (window.coreAppCsrfKey && window.fuel_csrf_token) {
                    formData.append(window.coreAppCsrfKey, window.fuel_csrf_token());
                }

                self.uploading = true;
                fetch(uploadCsvUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-Token': window.fuel_csrf_token ? window.fuel_csrf_token() : ''
                    },
                    body: formData
                })
                    .then(function(httpResponse) {
                        var status = httpResponse.status;
                        return window.coreAppParseJsonResponse(httpResponse).then(function(response) {
                            response = response || {};
                            response.http_status = status;
                            return response;
                        });
                    })
                    .then(function(response) {
                        console.log('supplier import response', response);
                        self.uploadResponse = self.normalizeUploadResponse(response || {});
                        self.uploadSummary = self.responseSummary(response);

                        if (!response || response.success === false) {
                            self.uploadError = self.firstError(self.uploadResponse);
                            return;
                        }

                        var payload = response.data || {};
                        self.uploadResult = payload.result || null;
                        if (payload.dashboard) {
                            self.runs = payload.dashboard.runs || [];
                            self.rows = payload.dashboard.rows || [];
                            self.summary = payload.dashboard.validation || {};
                            self.providers = payload.dashboard.providers || [];
                            self.sources = payload.dashboard.sources || payload.dashboard.providers || [];
                            self.suppliers = payload.dashboard.suppliers || [];
                            self.warnings = payload.dashboard.warnings || [];
                        }
                        self.showUploadModal = false;
                        self.uploadForm.file = null;
                        if (self.$refs.csvFile) {
                            self.$refs.csvFile.value = '';
                        }
                        setTimeout(function() {
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }, 50);
                    })
                    .catch(function(error) {
                        console.log('supplier import response', error);
                        self.uploadError = 'No se pudo conectar con el endpoint de carga CSV.';
                        self.uploadResponse = {
                            http_status: 0,
                            success: false,
                            message: 'No se pudo conectar con el endpoint de carga CSV.',
                            errors: [error && error.message ? error.message : 'Error de conexión.'],
                            warnings: []
                        };
                        self.uploadSummary = self.responseSummary(self.uploadResponse);
                    })
                    .then(function() {
                        self.uploading = false;
                    });
            },
            responseSummary: function(response) {
                var summary = response && response.summary ? response.summary : {};
                var data = response && response.data ? response.data : {};
                var result = data.result || {};
                return {
                    total_rows: Number(summary.total_rows || result.total_rows || 0),
                    valid_rows: Number(summary.valid_rows || result.valid_rows || result.normalized || 0),
                    invalid_rows: Number(summary.invalid_rows || result.invalid_rows || 0),
                    duplicates: Number(summary.duplicates || result.duplicates || 0),
                    warnings: Number(summary.warnings || result.warnings || 0),
                    errors: Number(summary.errors || result.errors || 0)
                };
            },
            normalizeUploadResponse: function(response) {
                response = response || {};
                if (!response.message) {
                    response.message = response.error || (response.errors && response.errors.length ? response.errors[0] : 'No se pudo procesar el archivo CSV.');
                }
                if (!response.errors) {
                    response.errors = response.error ? [response.error] : [];
                }
                if (!response.warnings) {
                    response.warnings = [];
                }
                return response;
            },
            setLocalUploadResponse: function(message) {
                this.uploadResponse = this.normalizeUploadResponse({
                    http_status: 'local',
                    success: false,
                    message: message,
                    errors: [message],
                    warnings: []
                });
                this.uploadSummary = this.responseSummary(this.uploadResponse);
            }
        }
    });
});
</script>
