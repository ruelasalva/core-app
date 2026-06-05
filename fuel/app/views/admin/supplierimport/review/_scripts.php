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
