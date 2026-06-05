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
