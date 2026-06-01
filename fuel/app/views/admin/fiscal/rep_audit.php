<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .rep-audit-help ul { margin-bottom: 0; }
    .rep-audit-table th,
    .rep-audit-table td { white-space: nowrap; }
</style>

<div id="app-rep-audit" v-cloak>
    <div class="row mb-3">
        <div class="col-md-7">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ audit.rfc || 'No configurado' }} &middot;
                Periodo {{ audit.period }} &middot;
                Fuente {{ audit.rfc_source_label || 'No configurado' }}
            </div>
        </div>
        <div class="col-md-5">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/rep_audit'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Filtros de auditor&iacute;a</h3>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/rep_audit'); ?>">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Periodo</label>
                        <input type="month" name="period" class="form-control form-control-sm" value="<?php echo e($period); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Tipo</label>
                        <select name="type" class="form-control form-control-sm">
                            <option value="all" <?php echo \Arr::get($filters, 'type') === 'all' ? 'selected' : ''; ?>>Todos</option>
                            <option value="issued" <?php echo \Arr::get($filters, 'type') === 'issued' ? 'selected' : ''; ?>>Emitidos</option>
                            <option value="received" <?php echo \Arr::get($filters, 'type') === 'received' ? 'selected' : ''; ?>>Recibidos</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 text-md-right">
                        <a href="<?php echo Uri::create('admin/fiscal/rep_audit', [], ['period' => $period]); ?>" class="btn btn-sm btn-secondary">
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

    <div v-if="audit.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in audit.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner"><h3>{{ number(audit.summary.ppd_issued) }}</h3><p>CFDI PPD emitidos</p></div>
                <div class="icon"><i class="bi bi-box-arrow-up-right"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ number(audit.summary.ppd_received) }}</h3><p>CFDI PPD recibidos</p></div>
                <div class="icon"><i class="bi bi-box-arrow-in-down-left"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.ppd_without_rep > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.ppd_without_rep) }}</h3><p>CFDI PPD sin REP</p></div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ number(audit.summary.related_rep) }}</h3><p>REP relacionados</p></div>
                <div class="icon"><i class="bi bi-link-45deg"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.cancelled_rep > 0 ? 'bg-danger' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.cancelled_rep) }}</h3><p>REP cancelados</p></div>
                <div class="icon"><i class="bi bi-slash-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.rep_without_xml > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.rep_without_xml) }}</h3><p>REP sin XML</p></div>
                <div class="icon"><i class="bi bi-file-earmark-x"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.duplicate_rep > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.duplicate_rep) }}</h3><p>REP duplicados</p></div>
                <div class="icon"><i class="bi bi-files"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.rep_without_internal_payment > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.rep_without_internal_payment) }}</h3><p>REP sin pago interno</p></div>
                <div class="icon"><i class="bi bi-cash"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.internal_payments_without_rep > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.internal_payments_without_rep) }}</h3><p>Pagos internos sin REP</p></div>
                <div class="icon"><i class="bi bi-bank"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-dark">
                <div class="inner"><h3>{{ money(audit.summary.pending_balance) }}</h3><p>Saldos pendientes</p></div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.issued_without_rep > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.issued_without_rep) }}</h3><p>Emitidos sin REP</p></div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.received_without_rep > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.received_without_rep) }}</h3><p>Recibidos sin REP</p></div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ number(audit.summary.issued_paid) }}</h3><p>Emitidos pagados</p></div>
                <div class="icon"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ number(audit.summary.received_paid) }}</h3><p>Recibidos pagados</p></div>
                <div class="icon"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ number(audit.summary.rep_with_saved_taxes) }}</h3><p>REP con impuestos guardados</p></div>
                <div class="icon"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.rep_without_saved_taxes > 0 ? 'bg-warning' : 'bg-success'">
                <div class="inner"><h3>{{ number(audit.summary.rep_without_saved_taxes) }}</h3><p>REP sin impuestos guardados</p></div>
                <div class="icon"><i class="bi bi-shield-exclamation"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ number(audit.summary.rep_tax_movements) }}</h3><p>Movimientos de impuestos REP</p></div>
                <div class="icon"><i class="bi bi-list-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ money(audit.summary.rep_dr_base) }}</h3><p>Base REP DR</p></div>
                <div class="icon"><i class="bi bi-calculator"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner"><h3>{{ money(audit.summary.rep_dr_vat) }}</h3><p>IVA REP DR</p></div>
                <div class="icon"><i class="bi bi-percent"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ money(audit.summary.rep_p_base) }}</h3><p>Base REP P</p></div>
                <div class="icon"><i class="bi bi-calculator"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner"><h3>{{ money(audit.summary.rep_p_vat) }}</h3><p>IVA REP P</p></div>
                <div class="icon"><i class="bi bi-percent"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box" :class="audit.summary.rep_retentions > 0 ? 'bg-warning' : 'bg-secondary'">
                <div class="inner"><h3>{{ money(audit.summary.rep_retentions) }}</h3><p>Retenciones REP</p></div>
                <div class="icon"><i class="bi bi-dash-circle"></i></div>
            </div>
        </div>
    </div>

    <div v-if="showIssued()" class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">CFDI PPD emitidos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped rep-audit-table mb-0">
                    <thead>
                        <tr>
                            <th>UUID factura</th>
                            <th>Fecha</th>
                            <th>Contraparte</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Pagado por REP</th>
                            <th class="text-right">Saldo pendiente</th>
                            <th>Estado PPD</th>
                            <th>REP</th>
                            <th>Estatus SAT</th>
                            <th>XML</th>
                            <th>Impuestos REP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in audit.issued_items" :key="item.id">
                            <td><span :title="item.uuid">{{ shortUuid(item.uuid) }}</span></td>
                            <td>{{ item.issued_label }}</td>
                            <td>{{ item.counterparty_rfc }}<br><small class="text-muted">{{ item.counterparty_name || 'Sin nombre' }}</small></td>
                            <td class="text-right">{{ money(item.total) }}</td>
                            <td class="text-right">{{ money(item.paid_amount) }}</td>
                            <td class="text-right">{{ money(item.pending_balance) }}</td>
                            <td><span class="badge" :class="ppdBadge(item.status)">{{ item.status_label }}</span></td>
                            <td>{{ number(item.rep_count) }}</td>
                            <td>{{ item.sat_status || 'Sin estatus' }}</td>
                            <td>{{ item.xml_available ? 'Disponible' : 'No disponible' }}</td>
                            <td>
                                <span class="badge" :class="invoiceRepTaxBadge(item)">
                                    {{ invoiceRepTaxLabel(item) }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="audit.issued_items.length === 0">
                            <td colspan="11" class="text-center text-muted">Sin CFDI PPD emitidos para los filtros seleccionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-if="showReceived()" class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">CFDI PPD recibidos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped rep-audit-table mb-0">
                    <thead>
                        <tr>
                            <th>UUID factura</th>
                            <th>Fecha</th>
                            <th>Contraparte</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Pagado por REP</th>
                            <th class="text-right">Saldo pendiente</th>
                            <th>Estado PPD</th>
                            <th>REP</th>
                            <th>Estatus SAT</th>
                            <th>XML</th>
                            <th>Impuestos REP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in audit.received_items" :key="item.id">
                            <td><span :title="item.uuid">{{ shortUuid(item.uuid) }}</span></td>
                            <td>{{ item.issued_label }}</td>
                            <td>{{ item.counterparty_rfc }}<br><small class="text-muted">{{ item.counterparty_name || 'Sin nombre' }}</small></td>
                            <td class="text-right">{{ money(item.total) }}</td>
                            <td class="text-right">{{ money(item.paid_amount) }}</td>
                            <td class="text-right">{{ money(item.pending_balance) }}</td>
                            <td><span class="badge" :class="ppdBadge(item.status)">{{ item.status_label }}</span></td>
                            <td>{{ number(item.rep_count) }}</td>
                            <td>{{ item.sat_status || 'Sin estatus' }}</td>
                            <td>{{ item.xml_available ? 'Disponible' : 'No disponible' }}</td>
                            <td>
                                <span class="badge" :class="invoiceRepTaxBadge(item)">
                                    {{ invoiceRepTaxLabel(item) }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="audit.received_items.length === 0">
                            <td colspan="11" class="text-center text-muted">Sin CFDI PPD recibidos para los filtros seleccionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title mb-0">REP relacionados</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped rep-audit-table mb-0">
                            <thead>
                                <tr>
                                    <th>REP</th>
                                    <th>Factura relacionada</th>
                                    <th>Fecha REP</th>
                                    <th class="text-right">Parcialidad</th>
                                    <th class="text-right">Pagado</th>
                                    <th class="text-right">Saldo insoluto</th>
                                    <th>Impuestos REP</th>
                                    <th class="text-right">DR</th>
                                    <th class="text-right">P</th>
                                    <th class="text-right">Importe impuesto</th>
                                    <th>Alertas fiscales</th>
                                    <th>Estado</th>
                                    <th>Pago interno</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="rep in audit.rep_items" :key="rep.payment_detail_id">
                                    <td><span :title="rep.payment_uuid">{{ shortUuid(rep.payment_uuid) }}</span></td>
                                    <td><span :title="rep.invoice_uuid">{{ shortUuid(rep.invoice_uuid) }}</span></td>
                                    <td>{{ rep.payment_issued_label }}</td>
                                    <td class="text-right">{{ rep.partiality_number }}</td>
                                    <td class="text-right">{{ money(rep.paid_amount) }}</td>
                                    <td class="text-right">{{ money(rep.remaining_balance) }}</td>
                                    <td>
                                        <span class="badge" :class="rep.has_rep_taxes ? 'badge-success' : 'badge-warning'">
                                            {{ rep.has_rep_taxes ? 'Guardados' : 'Sin guardar' }}
                                        </span>
                                    </td>
                                    <td class="text-right">{{ number(rep.dr_tax_count) }}</td>
                                    <td class="text-right">{{ number(rep.p_tax_count) }}</td>
                                    <td class="text-right">{{ money(rep.rep_tax_amount) }}</td>
                                    <td>
                                        <span class="badge" :class="rep.rep_tax_warnings && rep.rep_tax_warnings.length ? 'badge-warning' : 'badge-success'">
                                            {{ rep.rep_tax_warning_status || 'No disponible' }}
                                        </span>
                                    </td>
                                    <td>{{ rep.status_label }}</td>
                                    <td>{{ rep.has_internal_payment ? ('#' + rep.internal_payment_id) : 'Sin pago interno' }}</td>
                                </tr>
                                <tr v-if="audit.rep_items.length === 0">
                                    <td colspan="13" class="text-center text-muted">Sin REP relacionados para el periodo seleccionado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title mb-0">Pagos internos sin REP</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped rep-audit-table mb-0">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th class="text-right">Importe</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in audit.internal_payment_items" :key="payment.id">
                                    <td>{{ payment.folio }}</td>
                                    <td>{{ payment.payment_date }}</td>
                                    <td>{{ payment.payment_type_label }}</td>
                                    <td class="text-right">{{ money(payment.amount) }}</td>
                                    <td>{{ payment.reference || '-' }}</td>
                                </tr>
                                <tr v-if="audit.internal_payment_items.length === 0">
                                    <td colspan="5" class="text-center text-muted">Sin pagos internos sin REP detectados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    Esta se&ntilde;al es preventiva: un pago interno puede no requerir REP si corresponde a PUE u otra operaci&oacute;n.
                </div>
            </div>

            <div class="card card-outline card-secondary rep-audit-help">
                <div class="card-header">
                    <h3 class="card-title mb-0">Ayuda REP/PPD</h3>
                </div>
                <div class="card-body">
                    <h6>&iquest;Qu&eacute; es un REP?</h6>
                    <p>El REP es el complemento de recepci&oacute;n de pagos. Relaciona un pago con una o varias facturas PPD.</p>
                    <h6>&iquest;Qu&eacute; es una factura PPD?</h6>
                    <p>Es una factura con pago en parcialidades o diferido. Fiscalmente requiere control por los pagos que la van liquidando.</p>
                    <h6>&iquest;Por qu&eacute; afecta IVA y DIOT?</h6>
                    <ul>
                        <li>En PPD, el IVA se controla por pago y no solo por emisi&oacute;n de factura.</li>
                        <li>La DIOT debe considerar operaciones recibidas con soporte de pago cuando aplica.</li>
                        <li>Esta pantalla no cambia c&aacute;lculos; solo muestra riesgos y faltantes.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-rep-audit');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var repAuditDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/rep_audit_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialFilters = <?php echo json_encode((array) $filters, $json_flags); ?>;
    var initialAudit = <?php echo json_encode((array) $audit, $json_flags); ?>;

    new Vue({
        el: '#app-rep-audit',
        data: {
            loading: false,
            errorMessage: '',
            audit: initialAudit
        },
        mounted: function() {
            this.loadAudit();
        },
        methods: {
            loadAudit: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var params = new URLSearchParams();
                params.set('rfc', initialRfc || '');
                params.set('period', initialPeriod || '');
                params.set('type', initialFilters.type || 'all');

                fetch(repAuditDataUrl + '?' + params.toString(), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar la auditoria REP/PPD.';
                            return;
                        }

                        self.audit = data.audit || self.audit;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de auditoria REP/PPD.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            shortUuid: function(uuid) {
                uuid = String(uuid || '');
                return uuid.length > 12 ? uuid.substring(0, 8) + '...' : uuid;
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
            ppdBadge: function(status) {
                if (status === 'pagado') return 'badge-success';
                if (status === 'parcial') return 'badge-info';
                if (status === 'sin_rep') return 'badge-warning';
                if (status === 'rep_cancelado') return 'badge-danger';
                if (status === 'sin_xml') return 'badge-secondary';
                return 'badge-light';
            },
            showIssued: function() {
                return !this.audit.filters || this.audit.filters.type === 'all' || this.audit.filters.type === 'issued';
            },
            showReceived: function() {
                return !this.audit.filters || this.audit.filters.type === 'all' || this.audit.filters.type === 'received';
            },
            invoiceRepTaxLabel: function(item) {
                var uuids = item.rep_uuids || [];
                if (!uuids.length) {
                    return 'Sin REP';
                }

                var reps = this.audit.rep_items || [];
                var related = reps.filter(function(rep) {
                    return uuids.indexOf(rep.payment_uuid) !== -1 && rep.invoice_uuid === item.uuid;
                });

                var withTaxes = related.filter(function(rep) {
                    return !!rep.has_rep_taxes;
                }).length;

                if (withTaxes > 0) {
                    return withTaxes + ' REP con impuestos';
                }
                return 'REP sin impuestos';
            },
            invoiceRepTaxBadge: function(item) {
                var label = this.invoiceRepTaxLabel(item);
                if (label === 'Sin REP') return 'badge-secondary';
                if (label === 'REP sin impuestos') return 'badge-warning';
                return 'badge-success';
            }
        }
    });
});
</script>
