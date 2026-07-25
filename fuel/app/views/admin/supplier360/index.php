<?php
$can_purchases = !empty($can_purchases);
$can_communications = !empty($can_communications);
$can_documents = !empty($can_documents);
?>
<script src="<?php echo Uri::base(false); ?>assets/js/core-api-client.js"></script>

<style>
.supplier360-page .summary-card {
    min-height: 104px;
}
.supplier360-page .summary-value {
    font-size: 1.35rem;
    font-weight: 700;
}
.supplier360-page .section-empty {
    border: 1px dashed #cfd6de;
    border-radius: 6px;
    color: #6c757d;
    padding: 18px;
    background: #f8f9fa;
}
.supplier360-page .timeline-entry {
    border-left: 3px solid #198754;
    padding: 0 0 14px 12px;
    margin-left: 4px;
}
.supplier360-page .timeline-entry:last-child {
    padding-bottom: 0;
}
.supplier360-page .text-truncate-safe {
    min-width: 0;
    overflow-wrap: anywhere;
}
.supplier360-page .supplier-header {
    border-top: 3px solid #198754;
}
@media (max-width: 767px) {
    .supplier360-page .btn-group-responsive {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }
}
</style>

<div id="supplier360-app" class="supplier360-page" v-cloak>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-7">
                    <h1 class="m-0">Vista 360 de Proveedor</h1>
                    <p class="text-muted mb-0">Consulta integral del proveedor: compras, facturas, pagos, documentos y comunicaciones.</p>
                </div>
                <div class="col-sm-5">
                    <form class="form-inline justify-content-sm-end mt-2 mt-sm-0" @submit.prevent="load">
                        <label class="sr-only" for="supplier360-party-id">ID de proveedor</label>
                        <input id="supplier360-party-id" type="number" min="1" class="form-control mr-2 mb-2 mb-sm-0" v-model="partyId" placeholder="ID de proveedor">
                        <button type="submit" class="btn btn-success" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span>
                            Consultar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div v-if="error" class="alert alert-warning">
                {{ error }}
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-lg-7">
                            <label for="supplier360-search">Buscar proveedor permitido</label>
                            <input id="supplier360-search" type="text" class="form-control" v-model="supplierQuery" @keyup.enter="searchSuppliers" placeholder="Nombre, c&oacute;digo o correo">
                            <small class="form-text text-muted">La b&uacute;squeda respeta permisos de compras, proveedores o terceros.</small>
                        </div>
                        <div class="col-lg-5 mt-3 mt-lg-0 btn-group-responsive">
                            <button type="button" class="btn btn-outline-success" :disabled="searchLoading" @click="searchSuppliers">
                                <span v-if="searchLoading" class="spinner-border spinner-border-sm mr-1"></span>
                                Buscar proveedores
                            </button>
                            <a v-if="supplier.id && canPurchases" class="btn btn-outline-secondary" :href="purchasesUrl">Abrir Compras</a>
                        </div>
                    </div>
                    <div v-if="searchError" class="alert alert-warning mt-3 mb-0">{{ searchError }}</div>
                    <div v-if="supplierResults.length" class="list-group mt-3">
                        <button type="button" class="list-group-item list-group-item-action" v-for="item in supplierResults" :key="'supplier-result-'+item.id" @click="selectSupplier(item)">
                            <div class="d-flex justify-content-between">
                                <strong>{{ item.label || item.name }}</strong>
                                <span class="text-muted">#{{ item.id }}</span>
                            </div>
                            <div class="small text-muted">{{ item.email || 'Sin correo' }} - {{ item.phone || 'Sin tel&eacute;fono' }}</div>
                        </button>
                    </div>
                    <div v-if="searchCompleted && !searchLoading && supplierResults.length === 0" class="section-empty mt-3">
                        No se encontraron proveedores permitidos con ese criterio.
                    </div>
                </div>
            </div>

            <div v-if="!supplier.id && !loading && !error" class="card">
                <div class="card-body section-empty">
                    Busca un proveedor o captura su ID para consultar la Vista 360 de Proveedor.
                </div>
            </div>

            <div v-if="supplier.id">
                <div class="card supplier-header">
                    <div class="card-body">
                        <div class="row align-items-start">
                            <div class="col-lg-7">
                                <h2 class="h4 mb-1">{{ supplier.name || 'Proveedor' }}</h2>
                                <div class="text-muted text-truncate-safe">
                                    <span v-if="supplier.code">C&oacute;digo: {{ supplier.code }}</span>
                                    <span v-if="supplier.legal_name"> &middot; {{ supplier.legal_name }}</span>
                                </div>
                                <div class="mt-2">
                                    <span class="badge" :class="supplier.active ? 'badge-success' : 'badge-secondary'">
                                        {{ supplier.active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    <span v-for="label in supplier.labels" :key="'label-'+label" class="badge badge-info ml-1">{{ label }}</span>
                                </div>
                            </div>
                            <div class="col-lg-5 mt-3 mt-lg-0">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Responsable</dt>
                                    <dd class="col-sm-8">{{ ownerLabel }}</dd>
                                    <dt class="col-sm-4">Contacto</dt>
                                    <dd class="col-sm-8">{{ contactLabel }}</dd>
                                    <dt class="col-sm-4">Estado</dt>
                                    <dd class="col-sm-8">{{ supplier.status || '-' }}</dd>
                                </dl>
                                <div class="btn-group btn-group-sm btn-group-responsive mt-2">
                                    <a v-if="canPurchases" class="btn btn-outline-secondary" :href="purchasesUrl">Abrir Compras</a>
                                    <a v-if="canCommunications" class="btn btn-outline-secondary" :href="communications.url">Abrir Comunicaciones</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="warnings.length" class="alert alert-info">
                    <strong>Advertencias:</strong>
                    <ul class="mb-0">
                        <li v-for="warning in warnings" :key="'warning-'+warning">{{ warning }}</li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-md-3" v-for="card in kpiCards" :key="'kpi-'+card.label">
                        <div class="card summary-card">
                            <div class="card-body">
                                <div class="text-muted small">{{ card.label }}</div>
                                <div class="summary-value">{{ card.value }}</div>
                                <div class="small text-muted">{{ card.help }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#supplier360-resumen">Resumen</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplier360-timeline">Timeline</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplier360-comunicaciones">Comunicaciones</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplier360-documentos">Documentos</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplier360-compras">Compras</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplier360-tickets">Tickets</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplier360-contratos">Contratos</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div id="supplier360-resumen" class="tab-pane fade show active">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <h5>Informaci&oacute;n general</h5>
                                        <dl class="row">
                                            <dt class="col-sm-4">RFC</dt>
                                            <dd class="col-sm-8">{{ general.rfc_visible ? (general.rfc || '-') : 'Restringido' }}</dd>
                                            <dt class="col-sm-4">Correo</dt>
                                            <dd class="col-sm-8">{{ general.email || '-' }}</dd>
                                            <dt class="col-sm-4">Tel&eacute;fono</dt>
                                            <dd class="col-sm-8">{{ general.phone || '-' }}</dd>
                                            <dt class="col-sm-4">Tipo</dt>
                                            <dd class="col-sm-8">{{ general.supplier_type || '-' }}</dd>
                                            <dt class="col-sm-4">Direcci&oacute;n</dt>
                                            <dd class="col-sm-8">{{ addressLabel }}</dd>
                                        </dl>
                                    </div>
                                    <div class="col-lg-6">
                                        <h5>Resumen de compras</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <tbody>
                                                    <tr><th>Órdenes de compra</th><td>{{ purchasing.purchase_orders.count || 0 }}</td><td class="text-right">{{ money(purchasing.purchase_orders.amount) }}</td></tr>
                                                    <tr><th>Facturas proveedor</th><td>{{ purchasing.supplier_invoices.count || 0 }}</td><td class="text-right">{{ money(purchasing.supplier_invoices.amount) }}</td></tr>
                                                    <tr><th>Contrarecibos</th><td>{{ purchasing.counter_receipts.count || 0 }}</td><td class="text-right">{{ money(purchasing.counter_receipts.amount) }}</td></tr>
                                                    <tr><th>Pagos salientes</th><td>{{ purchasing.payments.count || 0 }}</td><td class="text-right">{{ money(purchasing.payments.amount) }}</td></tr>
                                                    <tr><th>Saldo pendiente</th><td colspan="2" class="text-right">{{ money(purchasing.pending_balance) }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="text-muted small mb-0">Valores le&iacute;dos de saldos almacenados. Esta vista no recalcula compras, facturas ni pagos.</p>
                                    </div>
                                </div>
                            </div>

                            <div id="supplier360-timeline" class="tab-pane fade">
                                <div v-if="timeline.length === 0" class="section-empty">No hay eventos visibles para este proveedor.</div>
                                <div v-for="entry in timeline" :key="'timeline-'+entry.source_module+'-'+entry.source_entity_type+'-'+entry.source_entity_id+'-'+entry.event_date" class="timeline-entry">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ entry.title || entry.event_label || 'Evento' }}</strong>
                                        <small class="text-muted">{{ dateTime(entry.event_date) }}</small>
                                    </div>
                                    <div class="text-muted small">{{ entry.source_module || '-' }} &middot; {{ entry.event_type || '-' }}</div>
                                    <div class="text-truncate-safe">{{ entry.description || 'Sin descripci&oacute;n.' }}</div>
                                </div>
                                <div v-if="timelineHidden > 0" class="text-muted small mt-3">
                                    {{ timelineHidden }} eventos ocultos por permisos.
                                </div>
                            </div>

                            <div id="supplier360-comunicaciones" class="tab-pane fade">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="mb-1">Comunicaciones recientes</h5>
                                        <p class="text-muted small mb-0">Solo se muestran conversaciones de cuentas asignadas al usuario.</p>
                                    </div>
                                    <a v-if="canCommunications" class="btn btn-sm btn-outline-success" :href="communications.url">Abrir Comunicaciones</a>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="text-muted small">Conversaciones visibles</div>
                                                <div class="summary-value">{{ communications.total || 0 }}</div>
                                                <div class="small text-muted">Sin leer: {{ communications.unread_count || 0 }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div v-if="communications.recent.length === 0" class="section-empty">No hay conversaciones visibles.</div>
                                        <div v-for="item in communications.recent" :key="'comm-'+item.id" class="border-bottom py-2">
                                            <strong>{{ item.subject || '(Sin asunto)' }}</strong>
                                            <div class="small text-muted">{{ item.channel_code || '-' }} &middot; {{ dateTime(item.last_message_at) }}</div>
                                            <span v-if="item.unread_count > 0" class="badge badge-danger">{{ item.unread_count }} sin leer</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="supplier360-documentos" class="tab-pane fade">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="mb-1">Documentos</h5>
                                        <p class="text-muted small mb-0">Descargas mediante endpoints controlados.</p>
                                    </div>
                                    <a v-if="canDocuments" class="btn btn-sm btn-outline-success" href="<?php echo Uri::create('admin/documents'); ?>">Abrir Documentos</a>
                                </div>
                                <div v-if="documents.length === 0" class="section-empty">No hay documentos visibles.</div>
                                <div class="table-responsive" v-else>
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Documento</th>
                                                <th>Tipo</th>
                                                <th>Fecha</th>
                                                <th class="text-right">Acci&oacute;n</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="doc in documents" :key="'doc-'+doc.id">
                                                <td>{{ doc.title || 'Documento' }}</td>
                                                <td>{{ doc.document_type || '-' }}</td>
                                                <td>{{ dateTime(doc.created_at) }}</td>
                                                <td class="text-right">
                                                    <a class="btn btn-xs btn-outline-success" :href="doc.download_url" target="_blank" rel="noopener">Descargar</a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="supplier360-compras" class="tab-pane fade">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <h5>Últimas órdenes</h5>
                                        <div v-if="purchasing.recent_orders.length === 0" class="section-empty">Sin órdenes recientes.</div>
                                        <div v-for="order in purchasing.recent_orders" :key="'order-'+order.id" class="border-bottom py-2">
                                            <strong>{{ order.folio || ('OC #' + order.id) }}</strong>
                                            <div class="small text-muted">{{ order.status || '-' }} &middot; {{ order.order_date || '-' }}</div>
                                            <div>{{ order.currency_code || 'MXN' }} {{ money(order.total) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <h5>Últimas facturas</h5>
                                        <div v-if="purchasing.recent_invoices.length === 0" class="section-empty">Sin facturas recientes.</div>
                                        <div v-for="invoice in purchasing.recent_invoices" :key="'invoice-'+invoice.id" class="border-bottom py-2">
                                            <strong>{{ invoice.folio || invoice.uuid || ('Factura #' + invoice.id) }}</strong>
                                            <div class="small text-muted">{{ invoice.validation_status || invoice.status || '-' }} &middot; {{ invoice.invoice_date || '-' }}</div>
                                            <div>{{ invoice.currency_code || 'MXN' }} {{ money(invoice.total) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <h5>Pagos programados</h5>
                                        <div v-if="purchasing.scheduled_payments.length === 0" class="section-empty">Sin pagos programados visibles.</div>
                                        <div v-for="receipt in purchasing.scheduled_payments" :key="'scheduled-'+receipt.id" class="border-bottom py-2">
                                            <strong>{{ receipt.folio || ('Contrarecibo #' + receipt.id) }}</strong>
                                            <div class="small text-muted">{{ receipt.status || '-' }} &middot; {{ receipt.scheduled_payment_date || 'Pendiente' }}</div>
                                            <div>{{ receipt.currency_code || 'MXN' }} {{ money(receipt.total) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="supplier360-tickets" class="tab-pane fade">
                                <div class="mb-2"><strong>Tickets abiertos:</strong> {{ helpdesk.open || 0 }}</div>
                                <div v-if="helpdesk.recent.length === 0" class="section-empty">No hay tickets recientes.</div>
                                <div v-for="ticket in helpdesk.recent" :key="'ticket-'+ticket.id" class="border-bottom py-2">
                                    <strong>{{ ticket.folio || ('Ticket #' + ticket.id) }}</strong>
                                    <span class="badge ml-1" :class="ticket.closed ? 'badge-secondary' : 'badge-success'">{{ ticket.closed ? 'Cerrado' : 'Abierto' }}</span>
                                    <div>{{ ticket.subject || '-' }}</div>
                                    <div class="small text-muted">Prioridad: {{ ticket.priority || '-' }} &middot; {{ dateTime(ticket.last_message_at || ticket.created_at) }}</div>
                                </div>
                            </div>

                            <div id="supplier360-contratos" class="tab-pane fade">
                                <div class="row mb-3">
                                    <div class="col-md-4"><strong>Activos:</strong> {{ contracts.active || 0 }}</div>
                                    <div class="col-md-4"><strong>Por vencer:</strong> {{ contracts.expiring || 0 }}</div>
                                </div>
                                <div v-if="contracts.recent.length === 0" class="section-empty">No hay contratos visibles.</div>
                                <div v-for="contract in contracts.recent" :key="'contract-'+contract.id" class="border-bottom py-2">
                                    <strong>{{ contract.contract_number || ('Contrato #' + contract.id) }}</strong>
                                    <div>{{ contract.title || '-' }}</div>
                                    <div class="small text-muted">{{ contract.contract_type || '-' }} &middot; {{ contract.status || '-' }} &middot; Vence: {{ contract.end_date || '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="overlay-wrapper">
                <div class="text-center text-muted py-4">
                    <span class="spinner-border spinner-border-sm mr-1"></span>
                    Cargando Vista 360 de Proveedor...
                </div>
            </div>
        </div>
    </section>
</div>

<script>
(function (window) {
    'use strict';

    if (!window.Vue) {
        return;
    }

    new Vue({
        el: '#supplier360-app',
        data: function () {
            return {
                endpoint: '<?php echo Uri::create('admin/supplier360/data'); ?>',
                searchEndpoint: '<?php echo Uri::create('admin/supplier360/search'); ?>',
                canPurchases: <?php echo $can_purchases ? 'true' : 'false'; ?>,
                canCommunications: <?php echo $can_communications ? 'true' : 'false'; ?>,
                canDocuments: <?php echo $can_documents ? 'true' : 'false'; ?>,
                partyId: new URLSearchParams(window.location.search).get('party_id') || '',
                supplierQuery: '',
                supplierResults: [],
                searchLoading: false,
                searchError: '',
                searchCompleted: false,
                loading: false,
                error: '',
                supplier: {},
                general: {},
                purchasing: {
                    purchase_orders: {},
                    supplier_invoices: {},
                    counter_receipts: {},
                    payments: {},
                    recent_orders: [],
                    recent_invoices: [],
                    recent_receipts: [],
                    scheduled_payments: []
                },
                communications: { recent: [] },
                timeline: [],
                timelineHidden: 0,
                documents: [],
                helpdesk: { recent: [] },
                contracts: { recent: [] },
                kpis: {},
                warnings: []
            };
        },
        computed: {
            ownerLabel: function () {
                return this.supplier.owner && (this.supplier.owner.name || this.supplier.owner.label) ? (this.supplier.owner.name || this.supplier.owner.label) : '-';
            },
            contactLabel: function () {
                var contact = this.supplier.primary_contact || {};
                if (!contact.name && !contact.email && !contact.phone) {
                    return '-';
                }
                return [contact.name, contact.email, contact.phone].filter(Boolean).join(' - ');
            },
            addressLabel: function () {
                return this.general.address && this.general.address.label ? this.general.address.label : '-';
            },
            purchasesUrl: function () {
                return '<?php echo Uri::create('admin/purchases'); ?>' + (this.supplier.id ? '?party_id=' + encodeURIComponent(this.supplier.id) : '');
            },
            kpiCards: function () {
                return [
                    { label: 'Total comprado', value: this.money(this.kpis.total_purchased), help: 'Órdenes de compra' },
                    { label: 'Pendiente por pagar', value: this.money(this.kpis.pending_payable), help: 'Saldo almacenado' },
                    { label: 'Facturas proveedor', value: this.kpis.recent_invoices || 0, help: 'Registradas' },
                    { label: 'Tickets abiertos', value: this.kpis.open_tickets || 0, help: 'Helpdesk' },
                    { label: 'Contratos activos', value: this.kpis.active_contracts || 0, help: 'Contratos' },
                    { label: 'Comunicaciones', value: this.kpis.communications || 0, help: 'Cuentas visibles' },
                    { label: 'Última actividad', value: this.dateTime(this.kpis.last_activity_date), help: 'Eventos recientes' },
                    { label: 'Contrarecibos', value: this.purchasing.counter_receipts.count || 0, help: 'Emitidos' }
                ];
            }
        },
        mounted: function () {
            if (this.partyId) {
                this.load();
            }
        },
        methods: {
            searchSuppliers: function () {
                this.searchLoading = true;
                this.searchError = '';
                this.searchCompleted = false;

                var params = new URLSearchParams();
                params.append('q', this.supplierQuery || '');
                params.append('limit', 15);

                window.CoreApiClient.get(this.searchEndpoint + '?' + params.toString())
                    .then((result) => {
                        var payload = result.payload || {};
                        if (!result.ok || payload.success === false) {
                            this.supplierResults = [];
                            this.searchError = payload.message || result.message || 'No fue posible buscar proveedores.';
                            return;
                        }

                        var data = payload.data || {};
                        this.supplierResults = data.suppliers || [];
                    })
                    .catch((error) => {
                        this.supplierResults = [];
                        this.searchError = error && error.message ? error.message : 'No fue posible buscar proveedores.';
                    })
                    .finally(() => {
                        this.searchCompleted = true;
                        this.searchLoading = false;
                    });
            },
            selectSupplier: function (item) {
                this.partyId = item && item.id ? item.id : '';
                this.supplierResults = [];
                this.load();
            },
            load: function () {
                var partyId = parseInt(this.partyId || 0, 10);
                if (!partyId) {
                    this.error = 'Captura el ID de un proveedor antes de consultar.';
                    return;
                }

                this.loading = true;
                this.error = '';

                var url = this.endpoint + '?party_id=' + encodeURIComponent(partyId);
                window.CoreApiClient.get(url)
                    .then((result) => {
                        var payload = result.payload || {};
                        if (!result.ok || payload.success === false) {
                            this.resetData();
                            this.error = payload.message || result.message || 'No fue posible cargar Vista 360 de Proveedor.';
                            return;
                        }

                        this.applyData(payload.data || {});
                    })
                    .catch((error) => {
                        this.resetData();
                        this.error = error && error.message ? error.message : 'No fue posible cargar Vista 360 de Proveedor.';
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            applyData: function (data) {
                this.supplier = data.supplier || {};
                this.general = data.general || {};
                this.purchasing = Object.assign({
                    purchase_orders: {},
                    supplier_invoices: {},
                    counter_receipts: {},
                    payments: {},
                    recent_orders: [],
                    recent_invoices: [],
                    recent_receipts: [],
                    scheduled_payments: []
                }, data.purchasing_summary || {});
                this.communications = Object.assign({ recent: [] }, data.communications || {});
                this.timeline = data.timeline || [];
                this.timelineHidden = data.timeline_hidden_count || 0;
                this.documents = data.documents || [];
                this.helpdesk = Object.assign({ recent: [] }, data.helpdesk || {});
                this.contracts = Object.assign({ recent: [] }, data.contracts || {});
                this.kpis = data.kpis || {};
                this.warnings = data.warnings || [];
            },
            resetData: function () {
                this.supplier = {};
                this.general = {};
                this.purchasing = {
                    purchase_orders: {},
                    supplier_invoices: {},
                    counter_receipts: {},
                    payments: {},
                    recent_orders: [],
                    recent_invoices: [],
                    recent_receipts: [],
                    scheduled_payments: []
                };
                this.communications = { recent: [] };
                this.timeline = [];
                this.timelineHidden = 0;
                this.documents = [];
                this.helpdesk = { recent: [] };
                this.contracts = { recent: [] };
                this.kpis = {};
                this.warnings = [];
            },
            money: function (value) {
                var amount = parseFloat(value || 0);
                return amount.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
            },
            dateTime: function (value) {
                var timestamp = parseInt(value || 0, 10);
                if (!timestamp) {
                    return '-';
                }
                return new Date(timestamp * 1000).toLocaleString('es-MX');
            }
        }
    });
})(window);
</script>
