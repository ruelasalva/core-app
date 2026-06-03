<div id="app-clientes-cfdi">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0"><?php echo e($portal_title); ?></h3>
            <span class="badge badge-light">{{ items.length }} CFDI</span>
        </div>
        <div class="card-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="message" class="alert alert-info">{{ message }}</div>

            <form class="mb-3" @submit.prevent="load">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label>Fecha desde</label>
                        <input type="date" class="form-control form-control-sm" v-model="filters.date_from">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Fecha hasta</label>
                        <input type="date" class="form-control form-control-sm" v-model="filters.date_to">
                    </div>
                    <div class="form-group col-md-3">
                        <label>UUID</label>
                        <input type="text" class="form-control form-control-sm" v-model.trim="filters.uuid" placeholder="UUID">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Serie/Folio</label>
                        <input type="text" class="form-control form-control-sm" v-model.trim="filters.serie_folio" placeholder="Serie o folio">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Estatus SAT</label>
                        <select class="form-control form-control-sm" v-model="filters.sat_status">
                            <option value="">Todos</option>
                            <option value="vigente">Vigente</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="form-group col-md-1">
                        <label>Tipo CFDI</label>
                        <select class="form-control form-control-sm" v-model="filters.voucher_type">
                            <option value="">Todos</option>
                            <option value="I">Ingreso</option>
                            <option value="E">Egreso</option>
                            <option value="P">Pago</option>
                            <option value="T">Traslado</option>
                            <option value="N">Nómina</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <button type="submit" class="btn btn-primary btn-sm mr-2" :disabled="loading">
                        <i class="bi bi-search mr-1"></i> Filtrar
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearFilters" :disabled="loading">
                        Limpiar
                    </button>
                    <span v-if="loading" class="text-muted ml-3">Cargando CFDI...</span>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>UUID</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Serie/Folio</th>
                            <th class="text-right">Total</th>
                            <th>Estatus SAT</th>
                            <th>Estado de pago</th>
                            <th>XML</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td><code>{{ item.uuid }}</code></td>
                            <td>{{ item.issued_label }}</td>
                            <td><span class="badge badge-secondary">{{ voucherLabel(item.voucher_type) }}</span></td>
                            <td>{{ item.serie_folio || '-' }}</td>
                            <td class="text-right">{{ money(item.total, item.currency) }}</td>
                            <td>
                                <span class="badge" :class="satStatusClass(item.sat_status)">
                                    {{ item.sat_status || 'Sin estatus' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="paymentStatusClass(item.payment_status)">
                                    {{ item.payment_status }}
                                </span>
                            </td>
                            <td>
                                <a v-if="item.has_xml == 1" class="btn btn-outline-primary btn-xs" :href="item.xml_download_url">
                                    XML
                                </a>
                                <span v-else class="text-muted">No disponible</span>
                            </td>
                            <td>
                                <a v-if="item.has_pdf == 1" class="btn btn-outline-danger btn-xs" :href="item.pdf_download_url">
                                    PDF
                                </a>
                                <span v-else class="text-muted">No disponible</span>
                            </td>
                        </tr>
                        <tr v-if="items.length === 0 && !loading">
                            <td colspan="9" class="text-center text-muted py-4">Sin CFDI disponibles para este portal.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">
                Las descargas se entregan mediante rutas seguras del portal y no exponen rutas fisicas de archivos.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#app-clientes-cfdi',
        data: function() {
            return {
                items: [],
                error: '',
                message: '',
                loading: false,
                filters: {
                    date_from: '',
                    date_to: '',
                    uuid: '',
                    serie_folio: '',
                    sat_status: '',
                    voucher_type: ''
                }
            };
        },
        mounted: function() {
            this.load();
        },
        methods: {
            load: function() {
                var self = this;
                self.loading = true;
                self.error = '';
                self.message = '';
                var params = new URLSearchParams();
                Object.keys(self.filters).forEach(function(key) {
                    if (self.filters[key]) {
                        params.append(key, self.filters[key]);
                    }
                });
                var url = '<?php echo Uri::create('clientes/cfdi_data'); ?>';
                if (params.toString()) {
                    url += '?' + params.toString();
                }

                fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function(response) {
                        if (window.coreAppJson) {
                            return window.coreAppJson(response);
                        }
                        return response.json().then(function(json) {
                            if (!response.ok) {
                                throw json;
                            }
                            return json;
                        });
                    })
                    .then(function(data) {
                        self.items = data.items || [];
                        self.message = data.message || '';
                    })
                    .catch(function(err) {
                        self.error = err && err.error ? err.error : 'No se pudo cargar el Centro CFDI.';
                    })
                    .finally(function() {
                        self.loading = false;
                    });
            },
            clearFilters: function() {
                this.filters = {
                    date_from: '',
                    date_to: '',
                    uuid: '',
                    serie_folio: '',
                    sat_status: '',
                    voucher_type: ''
                };
                this.load();
            },
            voucherLabel: function(type) {
                var labels = { I: 'Ingreso', E: 'Egreso', T: 'Traslado', P: 'Pago', N: 'Nomina' };
                return labels[type] || type || '-';
            },
            satStatusClass: function(status) {
                status = (status || '').toLowerCase();
                if (status === 'cancelado') {
                    return 'badge-danger';
                }
                if (status === 'vigente') {
                    return 'badge-success';
                }
                return 'badge-secondary';
            },
            paymentStatusClass: function(status) {
                if (status === 'Pagada') {
                    return 'badge-success';
                }
                if (status === 'Vencida') {
                    return 'badge-danger';
                }
                if (status === 'Pendiente') {
                    return 'badge-warning';
                }
                return 'badge-secondary';
            },
            money: function(value, currency) {
                value = parseFloat(value || 0);
                return (currency || 'MXN') + ' ' + value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    });
});
</script>
