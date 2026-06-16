<?php
$portal_title = ($portal_code === 'proveedores' && $portal_title === 'CFDI de proveedor') ? 'CFDI del proveedor' : $portal_title;
?>
<div id="app-portal-cfdi">
    <div class="portal-page-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h1 class="h4 mb-1"><?php echo e($portal_title); ?></h1>
                <p class="text-muted mb-0">
                    Consulta los CFDI visibles para tu portal. Cuando existan archivos disponibles, las descargas se muestran como botones XML o PDF.
                </p>
            </div>
            <div class="portal-page-actions mt-3 mt-md-0">
                <button class="btn btn-primary btn-sm" v-on:click="load" v-bind:disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span>
                    Actualizar
                </button>
            </div>
        </div>
    </div>

    <div class="portal-panel">
        <div class="portal-panel-header">
            <div>
                <h2 class="h6 mb-0">Comprobantes disponibles</h2>
                <p class="text-muted small mb-0">Las acciones usan rutas controladas del portal; no se muestran rutas físicas.</p>
            </div>
            <span class="badge badge-light ml-auto">{{ items.length }} CFDI</span>
        </div>
        <div class="portal-panel-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="message" class="alert alert-info">{{ message }}</div>

            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 mb-0">Cargando CFDI...</p>
            </div>

            <div v-show="!loading && items.length > 0" class="table-responsive">
                <table class="table table-sm table-hover portal-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Serie/Folio</th>
                            <th>UUID</th>
                            <th class="text-right">Subtotal</th>
                            <th class="text-right">IVA</th>
                            <th class="text-right">Retenciones</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" v-bind:key="item.id">
                            <td>{{ item.issued_label }}</td>
                            <td><span class="badge badge-secondary">{{ voucherLabel(item.voucher_type) }}</span></td>
                            <td>{{ [item.serie, item.folio].filter(Boolean).join('-') || '-' }}</td>
                            <td><code>{{ item.uuid }}</code></td>
                            <td class="text-right">{{ money(item.subtotal, item.currency) }}</td>
                            <td class="text-right">{{ money(item.tax_transferred_total, item.currency) }}</td>
                            <td class="text-right">{{ money(item.tax_withheld_total, item.currency) }}</td>
                            <td class="text-right">{{ money(item.total, item.currency) }}</td>
                            <td>
                                <span class="badge" v-bind:class="item.sat_status === 'cancelado' ? 'badge-danger' : 'badge-success'">{{ item.sat_status || '-' }}</span>
                                <span v-if="item.has_payment_complement == 1" class="badge badge-info">REP</span>
                                <span v-if="item.has_waybill == 1" class="badge badge-warning">Carta porte</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Acciones CFDI">
                                    <a v-if="item.xml_download_url" class="btn btn-outline-primary" v-bind:href="item.xml_download_url" target="_blank" rel="noopener">XML</a>
                                    <a v-if="item.pdf_download_url" class="btn btn-outline-danger" v-bind:href="item.pdf_download_url" target="_blank" rel="noopener">PDF</a>
                                    <a v-if="item.detail_url" class="btn btn-outline-secondary" v-bind:href="item.detail_url">Detalle</a>
                                    <span v-if="!item.xml_download_url && !item.pdf_download_url && !item.detail_url" class="text-muted small">Sin acciones</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!loading && items.length === 0" class="portal-empty">
                No hay CFDI disponibles para este portal.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#app-portal-cfdi',
        data: function() {
            return {
                items: [],
                error: '',
                message: '',
                loading: true
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

                fetch('<?php echo Uri::create($portal_code.'/cfdi_data'); ?>', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function(response) {
                        return response.json().then(function(json) {
                            if (!response.ok) {
                                throw json;
                            }
                            return json;
                        });
                    })
                    .then(function(data) {
                        if (data.error) {
                            self.error = data.error;
                            return;
                        }
                        self.message = data.message || '';
                        self.items = data.items || [];
                    })
                    .catch(function(error) {
                        self.error = error && error.error ? error.error : 'No se pudo cargar CFDI. Revisa sesión, permisos o conexión.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            voucherLabel: function(type) {
                var labels = { I: 'Ingreso', E: 'Egreso', T: 'Traslado', P: 'Pago', N: 'Nómina' };
                return labels[type] || type || '-';
            },
            money: function(value, currency) {
                value = parseFloat(value || 0);
                return (currency || 'MXN') + ' ' + value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    });
});
</script>
