<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .fiscal-ledger-row { cursor: pointer; }
    .fiscal-ledger-row.table-active td { background-color: #e9f3ff; }
    .fiscal-ledger-detail-table th { width: 34%; }
    .fiscal-ledger-help ol { margin-bottom: 0; }
</style>

<div id="app-fiscal-ledger" v-cloak>
    <div class="row mb-3">
        <div class="col-md-7">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ ledger.rfc || 'No configurado' }} &middot;
                Periodo {{ ledger.period }} &middot;
                Fuente {{ ledger.rfc_source_label || 'No configurado' }}
            </div>
        </div>
        <div class="col-md-5">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/ledger'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Filtros del libro fiscal</h3>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/ledger'); ?>">
                <input type="hidden" name="period" value="<?php echo e($period); ?>">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>UUID</label>
                        <input type="text" name="uuid" class="form-control form-control-sm" value="<?php echo e(\Arr::get($filters, 'uuid', '')); ?>" placeholder="UUID parcial o completo">
                    </div>
                    <div class="form-group col-md-3">
                        <label>RFC</label>
                        <input type="text" name="rfc_filter" class="form-control form-control-sm" value="<?php echo e(\Arr::get($filters, 'rfc', '')); ?>" placeholder="Emisor, receptor o contraparte">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Direcci&oacute;n</label>
                        <select name="direction" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="issued" <?php echo \Arr::get($filters, 'direction') === 'issued' ? 'selected' : ''; ?>>Emitidos</option>
                            <option value="received" <?php echo \Arr::get($filters, 'direction') === 'received' ? 'selected' : ''; ?>>Recibidos</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Impuesto</label>
                        <select name="tax_code" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="001" <?php echo \Arr::get($filters, 'tax_code') === '001' ? 'selected' : ''; ?>>ISR</option>
                            <option value="002" <?php echo \Arr::get($filters, 'tax_code') === '002' ? 'selected' : ''; ?>>IVA</option>
                            <option value="003" <?php echo \Arr::get($filters, 'tax_code') === '003' ? 'selected' : ''; ?>>IEPS</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Tipo CFDI</label>
                        <select name="cfdi_type" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="I" <?php echo \Arr::get($filters, 'cfdi_type') === 'I' ? 'selected' : ''; ?>>Ingreso</option>
                            <option value="E" <?php echo \Arr::get($filters, 'cfdi_type') === 'E' ? 'selected' : ''; ?>>Egreso</option>
                            <option value="P" <?php echo \Arr::get($filters, 'cfdi_type') === 'P' ? 'selected' : ''; ?>>Pago</option>
                            <option value="N" <?php echo \Arr::get($filters, 'cfdi_type') === 'N' ? 'selected' : ''; ?>>N&oacute;mina</option>
                            <option value="T" <?php echo \Arr::get($filters, 'cfdi_type') === 'T' ? 'selected' : ''; ?>>Traslado</option>
                        </select>
                    </div>
                </div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Estatus SAT</label>
                        <input type="text" name="sat_status" class="form-control form-control-sm" value="<?php echo e(\Arr::get($filters, 'sat_status', '')); ?>" placeholder="vigente, cancelado...">
                    </div>
                    <div class="form-group col-md-9 text-md-right">
                        <a href="<?php echo Uri::create('admin/fiscal/ledger', [], ['period' => $period]); ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar filtros
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-funnel"></i> Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="ledger.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in ledger.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number(ledger.total) }}</h3>
                    <p>Movimientos encontrados</p>
                </div>
                <div class="icon"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number(ledger.shown) }}</h3>
                    <p>Movimientos mostrados</p>
                </div>
                <div class="icon"><i class="bi bi-list-ul"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box" :class="ledger.has_more ? 'bg-warning' : 'bg-success'">
                <div class="inner">
                    <h3>{{ number(ledger.limit) }}</h3>
                    <p>L&iacute;mite de consulta</p>
                </div>
                <div class="icon"><i class="bi bi-speedometer"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Movimientos fiscales</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>UUID</th>
                                    <th>Fecha</th>
                                    <th>Emisor</th>
                                    <th>Receptor</th>
                                    <th>Contraparte</th>
                                    <th>Tipo</th>
                                    <th>Direcci&oacute;n</th>
                                    <th>Impuesto</th>
                                    <th>Tipo impuesto</th>
                                    <th class="text-right">Tasa</th>
                                    <th class="text-right">Base</th>
                                    <th class="text-right">Impuesto</th>
                                    <th>Estatus SAT</th>
                                    <th>Periodo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in ledger.items" :key="item.id" class="fiscal-ledger-row" :class="{ 'table-active': selected && selected.id === item.id }" @click="selectRow(item)">
                                    <td><span :title="item.uuid">{{ shortUuid(item.uuid) }}</span></td>
                                    <td>{{ shortDate(item.issue_date) }}</td>
                                    <td>{{ item.emitter_rfc }}</td>
                                    <td>{{ item.receiver_rfc }}</td>
                                    <td>{{ item.counterparty_rfc }}</td>
                                    <td>{{ item.cfdi_type }}</td>
                                    <td>{{ item.direction_label }}</td>
                                    <td>{{ item.tax_code_label }}</td>
                                    <td>{{ item.tax_type_label }}</td>
                                    <td class="text-right">{{ rate(item.tax_rate) }}</td>
                                    <td class="text-right">{{ money(item.base_amount_mxn) }}</td>
                                    <td class="text-right">{{ money(item.tax_amount_mxn) }}</td>
                                    <td>{{ item.sat_status || 'Sin estatus' }}</td>
                                    <td>{{ item.fiscal_period }}</td>
                                </tr>
                                <tr v-if="ledger.items.length === 0">
                                    <td colspan="14" class="text-center text-muted">Sin movimientos fiscales para los filtros seleccionados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    Esta pantalla es de solo lectura. Selecciona un movimiento para ver su detalle completo.
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detalle del movimiento</h3>
                </div>
                <div class="card-body p-0">
                    <table v-if="selected" class="table table-sm fiscal-ledger-detail-table mb-0">
                        <tbody>
                            <tr><th>ID fiscal</th><td>{{ selected.id }}</td></tr>
                            <tr><th>UUID</th><td class="text-break">{{ selected.uuid }}</td></tr>
                            <tr><th>Fecha emisi&oacute;n</th><td>{{ selected.issue_date }}</td></tr>
                            <tr><th>Timbrado</th><td>{{ selected.stamped_at || 'No disponible' }}</td></tr>
                            <tr><th>RFC contribuyente</th><td>{{ selected.taxpayer_rfc }}</td></tr>
                            <tr><th>RFC emisor</th><td>{{ selected.emitter_rfc }}</td></tr>
                            <tr><th>RFC receptor</th><td>{{ selected.receiver_rfc }}</td></tr>
                            <tr><th>RFC contraparte</th><td>{{ selected.counterparty_rfc }}</td></tr>
                            <tr><th>Direcci&oacute;n</th><td>{{ selected.direction_label }}</td></tr>
                            <tr><th>Tipo CFDI</th><td>{{ selected.cfdi_type_label }}</td></tr>
                            <tr><th>M&eacute;todo pago</th><td>{{ selected.payment_method || 'No disponible' }}</td></tr>
                            <tr><th>Forma pago</th><td>{{ selected.payment_form || 'No disponible' }}</td></tr>
                            <tr><th>L&iacute;nea</th><td>{{ selected.line_number }} &middot; {{ selected.line_type }}</td></tr>
                            <tr><th>Clave SAT</th><td>{{ selected.product_service_code || 'No disponible' }}</td></tr>
                            <tr><th>Identificaci&oacute;n</th><td>{{ selected.identification_number || 'No disponible' }}</td></tr>
                            <tr><th>Descripci&oacute;n</th><td>{{ selected.description || 'Sin descripci&oacute;n' }}</td></tr>
                            <tr><th>Objeto impuesto</th><td>{{ selected.tax_object || 'No disponible' }}</td></tr>
                            <tr><th>Impuesto</th><td>{{ selected.tax_code_label }}</td></tr>
                            <tr><th>Tipo impuesto</th><td>{{ selected.tax_type_label }}</td></tr>
                            <tr><th>Factor</th><td>{{ selected.tax_factor_type || 'No disponible' }}</td></tr>
                            <tr><th>Tasa</th><td>{{ rate(selected.tax_rate) }}</td></tr>
                            <tr><th>Base</th><td>{{ money(selected.base_amount) }} {{ selected.currency }}</td></tr>
                            <tr><th>Impuesto</th><td>{{ money(selected.tax_amount) }} {{ selected.currency }}</td></tr>
                            <tr><th>Base MXN</th><td>{{ money(selected.base_amount_mxn) }}</td></tr>
                            <tr><th>Impuesto MXN</th><td>{{ money(selected.tax_amount_mxn) }}</td></tr>
                            <tr><th>Tipo cambio</th><td>{{ selected.exchange_rate }}</td></tr>
                            <tr><th>Estatus SAT</th><td>{{ selected.sat_status || 'Sin estatus' }}</td></tr>
                            <tr><th>XML</th><td>{{ selected.xml_available ? 'Disponible' : 'No disponible' }}</td></tr>
                            <tr><th>Origen</th><td>{{ selected.source_origin }}</td></tr>
                            <tr><th>Hash fuente</th><td class="text-break">{{ selected.source_hash }}</td></tr>
                            <tr><th>CFDI ID</th><td>{{ selected.cfdi_id }}</td></tr>
                            <tr><th>Detalle CFDI ID</th><td>{{ selected.cfdi_detail_id }}</td></tr>
                        </tbody>
                    </table>
                    <div v-if="!selected" class="p-3 text-muted">
                        Selecciona una fila del libro fiscal para revisar el movimiento completo.
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary fiscal-ledger-help">
                <div class="card-header">
                    <h3 class="card-title mb-0">&iquest;Qu&eacute; es el Libro Fiscal?</h3>
                </div>
                <div class="card-body">
                    <p>
                        El Libro Fiscal es la fuente de trazabilidad fiscal generada desde los CFDI SAT.
                        Sus movimientos alimentan IVA mensual, preparaci&oacute;n DIOT, conciliaci&oacute;n fiscal-contable y borradores de p&oacute;lizas fiscales.
                    </p>
                    <ol>
                        <li>Cada fila representa un movimiento fiscal de impuesto.</li>
                        <li>La informaci&oacute;n es de solo lectura.</li>
                        <li>Los c&aacute;lculos fiscales no se modifican desde esta pantalla.</li>
                        <li>Si faltan movimientos, reconstruye y valida el libro fiscal desde las tareas Oil autorizadas.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-fiscal-ledger');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var fiscalLedgerDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/ledger_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialFilters = <?php echo json_encode((array) $filters, $json_flags); ?>;
    var initialLedger = <?php echo json_encode((array) $ledger, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-ledger',
        data: {
            loading: false,
            errorMessage: '',
            ledger: initialLedger,
            selected: initialLedger.items && initialLedger.items.length ? initialLedger.items[0] : null
        },
        mounted: function() {
            this.loadLedger();
        },
        methods: {
            loadLedger: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var params = new URLSearchParams();
                params.set('rfc', initialRfc || '');
                params.set('period', initialPeriod || '');
                params.set('uuid', initialFilters.uuid || '');
                params.set('rfc_filter', initialFilters.rfc || '');
                params.set('direction', initialFilters.direction || '');
                params.set('tax_code', initialFilters.tax_code || '');
                params.set('cfdi_type', initialFilters.cfdi_type || '');
                params.set('sat_status', initialFilters.sat_status || '');

                fetch(fiscalLedgerDataUrl + '?' + params.toString(), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar el libro fiscal.';
                            return;
                        }

                        self.ledger = data.ledger || self.ledger;
                        self.selected = self.ledger.items && self.ledger.items.length ? self.ledger.items[0] : null;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint del libro fiscal.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            selectRow: function(item) {
                this.selected = item;
            },
            shortUuid: function(uuid) {
                uuid = String(uuid || '');
                return uuid.length > 12 ? uuid.substring(0, 8) + '...' : uuid;
            },
            shortDate: function(value) {
                value = String(value || '');
                return value.length >= 10 ? value.substring(0, 10) : value;
            },
            money: function(value) {
                return Number(value || 0).toLocaleString('es-MX', {
                    style: 'currency',
                    currency: 'MXN',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            number: function(value) {
                return Number(value || 0).toLocaleString('es-MX');
            },
            rate: function(value) {
                return (Number(value || 0) * 100).toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 4
                }) + '%';
            }
        }
    });
});
</script>
