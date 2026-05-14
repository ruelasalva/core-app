<div id="cfdi-app">
    <div class="row">
        <div class="col-md-2 col-6" v-for="card in statCards" :key="card.key">
            <div class="small-box bg-light">
                <div class="inner"><h3>{{ card.value }}</h3><p>{{ card.label }}</p></div>
                <div class="icon"><i :class="card.icon"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Auditoria SAT</h3>
            <div class="ml-auto d-flex align-items-center">
                <input type="month" class="form-control form-control-sm mr-2" v-model="filters.month" @change="load">
                <input type="search" class="form-control form-control-sm mr-2" placeholder="UUID, RFC o razon social" v-model="filters.q" @keyup.enter="load">
                <button type="button" class="btn btn-sm btn-outline-primary mr-2" @click="load"><i class="bi bi-search"></i></button>
                <label class="btn btn-sm btn-primary mb-0">
                    <i class="bi bi-upload"></i>
                    <input type="file" accept=".xml,text/xml" class="d-none" @change="importXml">
                </label>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <ul class="nav nav-tabs mr-3">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.key">
                        <a href="#" class="nav-link" :class="{ active: filters.tab === tab.key }" @click.prevent="selectTab(tab.key)">
                            <i :class="tab.icon"></i> {{ tab.label }}
                        </a>
                    </li>
                </ul>
                <div class="btn-group btn-group-sm mt-2 mt-md-0">
                    <button v-for="type in docTypes" :key="type.key" class="btn" :class="filters.doc_type === type.key ? 'btn-primary' : 'btn-outline-secondary'" @click="selectDocType(type.key)">
                        {{ type.label }}
                    </button>
                </div>
            </div>

            <div v-if="message" class="alert alert-info py-2">{{ message }}</div>
            <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Serie/Folio</th>
                            <th>UUID</th>
                            <th>{{ filters.tab === 'issued' ? 'Cliente' : 'Proveedor' }}</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="item in items">
                            <tr :key="'row-' + item.id" :class="{ 'table-active': selected && selected.id === item.id }" @click="openDetails(item)">
                                <td>{{ item.issued_label }}</td>
                                <td><span class="badge badge-secondary">{{ item.type_label }}</span></td>
                                <td>{{ item.serie || '-' }} {{ item.folio || '' }}</td>
                                <td><code>{{ item.uuid }}</code></td>
                                <td>
                                    <strong>{{ counterpartyRfc(item) }}</strong>
                                    <div class="text-muted small">{{ counterpartyName(item) }}</div>
                                </td>
                                <td class="text-right">{{ money(item.total, item.currency) }}</td>
                                <td>
                                    <span class="badge" :class="item.sat_status === 'cancelado' ? 'badge-danger' : 'badge-success'">{{ item.sat_status }}</span>
                                    <span v-if="item.purchase_status === 'linked'" class="badge badge-primary">Compra</span>
                                    <span v-if="item.sales_status === 'linked'" class="badge badge-primary">Venta</span>
                                    <span v-if="item.has_payment_complement == 1" class="badge badge-info">REP</span>
                                    <span v-if="item.has_waybill == 1" class="badge badge-warning">Carta porte</span>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-xs btn-outline-secondary" @click.stop="openDetails(item)">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                    <button v-if="item.convertible_purchase == 1" class="btn btn-xs btn-outline-primary" @click.stop="convertPurchase(item)">
                                        <i class="bi bi-cart-check"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="selected && selected.id === item.id" :key="'detail-' + item.id">
                                <td colspan="8" class="bg-light">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>Detalle {{ selected.uuid }}</strong>
                                                <span class="text-muted small">{{ selected.type_label }} | {{ selected.sat_status }}</span>
                                            </div>
                                            <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead><tr><th>Tipo</th><th>Clave</th><th>Descripcion</th><th class="text-right">Importe</th><th class="text-right">IVA</th><th class="text-right">Ret.</th></tr></thead>
                                                    <tbody>
                                                        <tr v-for="line in selectedContext.details" :key="line.id">
                                                            <td>{{ line.line_type }}</td>
                                                            <td>{{ line.product_service_code || line.related_uuid || line.payment_uuid }}</td>
                                                            <td>{{ line.description || line.relation_type || line.payment_folio }}</td>
                                                            <td class="text-right">{{ money(line.amount, selected.currency) }}</td>
                                                            <td class="text-right">{{ money(line.vat_amount, selected.currency) }}</td>
                                                            <td class="text-right">{{ money(line.retention_amount, selected.currency) }}</td>
                                                        </tr>
                                                        <tr v-if="selectedContext.details.length === 0"><td colspan="6" class="text-muted text-center">Sin detalle cargado.</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="border rounded p-2 mb-2">
                                                <div class="d-flex justify-content-between"><span>Subtotal</span><strong>{{ money(selected.subtotal, selected.currency) }}</strong></div>
                                                <div class="d-flex justify-content-between"><span>IVA/traslados</span><strong>{{ money(selected.tax_transferred_total, selected.currency) }}</strong></div>
                                                <div class="d-flex justify-content-between"><span>Retenciones</span><strong>{{ money(selected.tax_withheld_total, selected.currency) }}</strong></div>
                                                <div class="d-flex justify-content-between"><span>Total</span><strong>{{ money(selected.total, selected.currency) }}</strong></div>
                                            </div>
                                            <div class="border rounded p-2 mb-2">
                                                <strong>Mapa fiscal</strong>
                                                <div v-for="rel in selectedContext.relations" :key="rel.id" class="small mt-1">
                                                    {{ rel.relation_type }} <code>{{ rel.related_uuid }}</code>
                                                    <span class="badge" :class="rel.exists_in_system == 1 ? 'badge-success' : 'badge-warning'">{{ rel.exists_in_system == 1 ? 'local' : 'pendiente' }}</span>
                                                </div>
                                                <div v-for="pay in selectedContext.payments" :key="'p-' + pay.id" class="small mt-1">
                                                    REP parcialidad {{ pay.partiality_number }} <code>{{ pay.invoice_uuid }}</code> {{ money(pay.paid_amount, pay.currency) }}
                                                </div>
                                                <div v-for="link in selectedContext.linked" :key="'l-' + link.id" class="small mt-1">
                                                    {{ link.module }} - {{ link.type }} <strong>{{ link.folio }}</strong>
                                                </div>
                                                <div v-if="selectedContext.relations.length === 0 && selectedContext.payments.length === 0 && selectedContext.linked.length === 0" class="text-muted small">Sin relaciones detectadas.</div>
                                            </div>
                                            <button v-if="selected.convertible_purchase == 1" class="btn btn-sm btn-primary btn-block" @click="convertPurchase(selected)">
                                                <i class="bi bi-cart-check"></i> Convertir a compra
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="items.length === 0">
                            <td colspan="8" class="text-center text-muted py-4">Sin CFDI en el periodo seleccionado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small">Mostrando maximo 300 registros. Usa filtros por mes, tipo y busqueda para auditorias grandes.</div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#cfdi-app',
        data: {
            filters: { month: '<?php echo date('Y-m'); ?>', tab: 'received', doc_type: 'invoices', q: '' },
            stats: {},
            items: [],
            selected: null,
            selectedContext: { details: [], payments: [], relations: [], linked: [] },
            message: '',
            error: '',
            tabs: [
                { key: 'received', label: 'Recibidos', icon: 'bi bi-inbox' },
                { key: 'issued', label: 'Emitidos', icon: 'bi bi-send' },
                { key: 'cancelled', label: 'Cancelados', icon: 'bi bi-x-circle' },
                { key: 'payments', label: 'REP', icon: 'bi bi-cash-coin' },
                { key: 'all', label: 'Todos', icon: 'bi bi-collection' }
            ],
            docTypes: [
                { key: 'invoices', label: 'Facturas' },
                { key: 'credit_notes', label: 'Notas' },
                { key: 'payments', label: 'Pagos' },
                { key: 'transfers', label: 'Traslados' },
                { key: 'all', label: 'Todos' }
            ]
        },
        computed: {
            statCards: function() {
                return [
                    { key: 'total_month', label: 'Mes', value: this.stats.total_month || 0, icon: 'bi bi-calendar3' },
                    { key: 'received', label: 'Recibidos', value: this.stats.received || 0, icon: 'bi bi-inbox' },
                    { key: 'issued', label: 'Emitidos', value: this.stats.issued || 0, icon: 'bi bi-send' },
                    { key: 'invoices', label: 'Facturas', value: this.stats.invoices || 0, icon: 'bi bi-file-earmark-text' },
                    { key: 'payments', label: 'REP', value: this.stats.payments || 0, icon: 'bi bi-cash-coin' },
                    { key: 'cancelled', label: 'Cancelados', value: this.stats.cancelled || 0, icon: 'bi bi-x-circle' }
                ];
            }
        },
        mounted: function() { this.load(); },
        methods: {
            selectTab: function(tab) {
                this.filters.tab = tab;
                if (tab === 'payments') this.filters.doc_type = 'payments';
                this.selected = null;
                this.selectedContext = { details: [], payments: [], relations: [], linked: [] };
                this.load();
            },
            selectDocType: function(type) {
                this.filters.doc_type = type;
                this.selected = null;
                this.selectedContext = { details: [], payments: [], relations: [], linked: [] };
                this.load();
            },
            load: function(extra) {
                this.error = '';
                var params = new URLSearchParams(this.filters);
                if (extra && extra.cfdi_id) params.set('cfdi_id', extra.cfdi_id);
                fetch('<?php echo Uri::create('admin/cfdi/data'); ?>?' + params.toString())
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.stats = data.stats || {};
                        this.items = data.items || [];
                        this.selectedContext = data.selected || { details: [], payments: [], relations: [], linked: [] };
                    })
                    .catch(() => { this.error = 'No se pudo cargar Auditoria SAT.'; });
            },
            openDetails: function(item) {
                this.selected = item;
                this.load({ cfdi_id: item.id });
            },
            convertPurchase: function(item) {
                if (!confirm('Convertir este CFDI a orden y factura de compra?')) return;
                this.error = '';
                this.message = '';
                fetch('<?php echo Uri::create('admin/cfdi/convert_purchase'); ?>', window.coreAppFetchOptions({ cfdi_id: item.id }))
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.message = data.message || 'Compra creada.';
                        this.openDetails(item);
                    })
                    .catch(() => { this.error = 'No se pudo convertir el CFDI.'; });
            },
            importXml: function(event) {
                var file = event.target.files[0];
                if (!file) return;
                this.error = '';
                this.message = '';
                var form = new FormData();
                form.append('file', file);
                form.append(window.coreAppCsrfKey, fuel_csrf_token());
                fetch('<?php echo Uri::create('admin/cfdi/import_xml'); ?>', { method: 'POST', body: form })
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.message = data.message || 'XML importado.';
                        this.load({ cfdi_id: data.cfdi_id });
                    })
                    .catch(() => { this.error = 'No se pudo importar el XML.'; });
                event.target.value = '';
            },
            counterpartyRfc: function(item) {
                return item.direction === 'issued' ? item.receiver_rfc : item.emitter_rfc;
            },
            counterpartyName: function(item) {
                return item.direction === 'issued' ? item.receiver_name : item.emitter_name;
            },
            money: function(value, currency) {
                value = parseFloat(value || 0);
                return (currency || 'MXN') + ' ' + value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    });
};
</script>
