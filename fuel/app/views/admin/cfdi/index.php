<div id="cfdi-app">
    <div class="row">
        <div class="col-md-2 col-6" v-for="card in statCards" :key="card.key">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3>{{ card.value }}</h3>
                    <p>{{ card.label }}</p>
                </div>
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
                <button type="button" class="btn btn-sm btn-outline-primary mr-2" @click="load">
                    <i class="bi bi-search"></i>
                </button>
                <label class="btn btn-sm btn-primary mb-0">
                    <i class="bi bi-upload"></i>
                    <input type="file" accept=".xml,text/xml" class="d-none" @change="importXml">
                </label>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item" v-for="tab in tabs" :key="tab.key">
                    <a href="#" class="nav-link" :class="{ active: filters.tab === tab.key }" @click.prevent="selectTab(tab.key)">
                        <i :class="tab.icon"></i> {{ tab.label }}
                    </a>
                </li>
            </ul>

            <div v-if="message" class="alert alert-info py-2">{{ message }}</div>
            <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>UUID</th>
                            <th>Emisor</th>
                            <th>Receptor</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id" :class="{ 'table-active': selected && selected.id === item.id }">
                            <td>{{ item.issued_label }}</td>
                            <td><span class="badge badge-secondary">{{ item.type_label }}</span></td>
                            <td><code>{{ item.uuid }}</code></td>
                            <td>
                                <strong>{{ item.emitter_rfc }}</strong>
                                <div class="text-muted small">{{ item.emitter_name }}</div>
                            </td>
                            <td>
                                <strong>{{ item.receiver_rfc }}</strong>
                                <div class="text-muted small">{{ item.receiver_name }}</div>
                            </td>
                            <td class="text-right">{{ money(item.total, item.currency) }}</td>
                            <td>
                                <span class="badge" :class="item.sat_status === 'cancelado' ? 'badge-danger' : 'badge-success'">{{ item.sat_status }}</span>
                                <span v-if="item.has_payment_complement == 1" class="badge badge-info">REP</span>
                                <span v-if="item.has_waybill == 1" class="badge badge-warning">Carta porte</span>
                            </td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-secondary" @click="openDetails(item)">
                                    <i class="bi bi-list-ul"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td colspan="8" class="text-center text-muted py-4">Sin CFDI en el periodo seleccionado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row" v-if="selected">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detalle CFDI {{ selected.uuid }}</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Clave</th>
                                <th>Descripcion</th>
                                <th class="text-right">Importe</th>
                                <th class="text-right">IVA</th>
                                <th class="text-right">Retencion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in details" :key="line.id">
                                <td>{{ line.line_type }}</td>
                                <td>{{ line.product_service_code || line.related_uuid || line.payment_uuid }}</td>
                                <td>{{ line.description || line.relation_type || line.payment_folio }}</td>
                                <td class="text-right">{{ money(line.amount, selected.currency) }}</td>
                                <td class="text-right">{{ money(line.vat_amount, selected.currency) }}</td>
                                <td class="text-right">{{ money(line.retention_amount, selected.currency) }}</td>
                            </tr>
                            <tr v-if="details.length === 0">
                                <td colspan="6" class="text-muted text-center">Sin detalle cargado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Totales</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Subtotal</dt>
                        <dd class="col-6 text-right">{{ money(selected.subtotal, selected.currency) }}</dd>
                        <dt class="col-6">IVA/traslados</dt>
                        <dd class="col-6 text-right">{{ money(selected.tax_transferred_total, selected.currency) }}</dd>
                        <dt class="col-6">Retenciones</dt>
                        <dd class="col-6 text-right">{{ money(selected.tax_withheld_total, selected.currency) }}</dd>
                        <dt class="col-6">Total</dt>
                        <dd class="col-6 text-right">{{ money(selected.total, selected.currency) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Relaciones recientes</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr v-for="rel in relations" :key="rel.id">
                                <td>{{ rel.relation_type }}</td>
                                <td><code>{{ rel.uuid }}</code></td>
                                <td><code>{{ rel.related_uuid }}</code></td>
                                <td><span class="badge" :class="rel.exists_in_system == 1 ? 'badge-success' : 'badge-warning'">{{ rel.exists_in_system == 1 ? 'local' : 'pendiente' }}</span></td>
                            </tr>
                            <tr v-if="relations.length === 0"><td class="text-muted">Sin relaciones.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Pagos REP recientes</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr v-for="pay in payments" :key="pay.id">
                                <td><code>{{ pay.payment_uuid }}</code></td>
                                <td><code>{{ pay.invoice_uuid }}</code></td>
                                <td>{{ pay.partiality_number }}</td>
                                <td class="text-right">{{ money(pay.paid_amount, pay.currency) }}</td>
                            </tr>
                            <tr v-if="payments.length === 0"><td class="text-muted">Sin pagos REP.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#cfdi-app',
        data: {
            filters: { month: '<?php echo date('Y-m'); ?>', tab: 'received', q: '' },
            stats: {},
            items: [],
            details: [],
            payments: [],
            relations: [],
            selected: null,
            message: '',
            error: '',
            tabs: [
                { key: 'received', label: 'Recibidos', icon: 'bi bi-inbox' },
                { key: 'issued', label: 'Emitidos', icon: 'bi bi-send' },
                { key: 'cancelled', label: 'Cancelados', icon: 'bi bi-x-circle' },
                { key: 'payments', label: 'Pagos REP', icon: 'bi bi-cash-coin' },
                { key: 'all', label: 'Todos', icon: 'bi bi-collection' }
            ]
        },
        computed: {
            statCards: function() {
                return [
                    { key: 'total_month', label: 'Mes', value: this.stats.total_month || 0, icon: 'bi bi-calendar3' },
                    { key: 'received', label: 'Recibidos', value: this.stats.received || 0, icon: 'bi bi-inbox' },
                    { key: 'issued', label: 'Emitidos', value: this.stats.issued || 0, icon: 'bi bi-send' },
                    { key: 'cancelled', label: 'Cancelados', value: this.stats.cancelled || 0, icon: 'bi bi-x-circle' },
                    { key: 'payments', label: 'REP', value: this.stats.payments || 0, icon: 'bi bi-cash-coin' },
                    { key: 'details', label: 'Detalles', value: this.stats.details || 0, icon: 'bi bi-list-ul' }
                ];
            }
        },
        mounted: function() {
            this.load();
        },
        methods: {
            selectTab: function(tab) {
                this.filters.tab = tab;
                this.selected = null;
                this.details = [];
                this.load();
            },
            load: function(extra) {
                this.error = '';
                var params = new URLSearchParams(this.filters);
                if (extra && extra.cfdi_id) {
                    params.set('cfdi_id', extra.cfdi_id);
                }
                fetch('<?php echo Uri::create('admin/cfdi/data'); ?>?' + params.toString())
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.stats = data.stats || {};
                        this.items = data.items || [];
                        this.payments = data.payments || [];
                        this.relations = data.relations || [];
                        this.details = data.details || [];
                    })
                    .catch(() => { this.error = 'No se pudo cargar Auditoria SAT.'; });
            },
            openDetails: function(item) {
                this.selected = item;
                this.load({ cfdi_id: item.id });
            },
            importXml: function(event) {
                var file = event.target.files[0];
                if (!file) return;
                this.error = '';
                this.message = '';
                var form = new FormData();
                form.append('file', file);
                form.append(window.coreAppCsrfKey, fuel_csrf_token());
                fetch('<?php echo Uri::create('admin/cfdi/import_xml'); ?>', {
                    method: 'POST',
                    body: form
                })
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.message = data.message || 'XML importado.';
                        this.load({ cfdi_id: data.cfdi_id });
                    })
                    .catch(() => { this.error = 'No se pudo importar el XML.'; });
                event.target.value = '';
            },
            money: function(value, currency) {
                value = parseFloat(value || 0);
                return (currency || 'MXN') + ' ' + value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    });
};
</script>
