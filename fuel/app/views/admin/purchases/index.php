<div id="app-purchases">
    <div class="row">
        <div class="col-lg-3"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.orders || 0 }}</h3><p>Ordenes</p></div><div class="icon"><i class="bi bi-cart-check"></i></div></div></div>
        <div class="col-lg-3"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.pending_authorizations || 0 }}</h3><p>Por autorizar</p></div><div class="icon"><i class="bi bi-shield-check"></i></div></div></div>
        <div class="col-lg-3"><div class="small-box bg-primary"><div class="inner"><h3>{{ stats.pending_invoices || 0 }}</h3><p>Facturas pendientes</p></div><div class="icon"><i class="bi bi-file-earmark-text"></i></div></div></div>
        <div class="col-lg-3"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.receipts || 0 }}</h3><p>Contrarecibos</p></div><div class="icon"><i class="bi bi-receipt-cutoff"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'orders'}" href="#" @click.prevent="tab = 'orders'">Ordenes</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'invoices'}" href="#" @click.prevent="tab = 'invoices'">Facturas proveedor</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'receipts'}" href="#" @click.prevent="tab = 'receipts'">Contrarecibos</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'documents'}" href="#" @click.prevent="tab = 'documents'">Evidencias</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando compras...</p></div>

            <div v-show="!loading && tab === 'orders'">
                <button class="btn btn-primary btn-sm mb-3" @click="newOrder"><i class="bi bi-plus-lg"></i> Nueva orden</button>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Proveedor</th><th>Depto/Solicita</th><th>Estado</th><th>Total</th><th>Facturado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.id">
                            <td><strong>{{ order.folio }}</strong></td>
                            <td>{{ order.party_name || '-' }}<div class="text-muted small">{{ order.party_rfc || '' }}</div></td>
                            <td>{{ order.department_name || '-' }}<div class="text-muted small">{{ order.requested_by_name || '-' }}</div></td>
                            <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span><div class="text-muted small">{{ approvalLabel(order) }}</div></td>
                            <td>{{ order.currency_code }} {{ money(order.total) }}</td>
                            <td>{{ order.currency_code }} {{ money(order.invoiced_total) }}</td>
                            <td>{{ order.order_date }}</td>
                            <td>
                                <button class="btn btn-xs btn-outline-primary" @click="openOrder(order)">Detalle</button>
                                <button v-if="['draft','rejected'].indexOf(order.status) >= 0" class="btn btn-xs btn-outline-info" @click="orderAction(order, 'submit_order')">Solicitar</button>
                                <button v-if="order.can_authorize == 1 && ['pending_authorization','draft','rejected'].indexOf(order.status) >= 0" class="btn btn-xs btn-success" @click="orderAction(order, 'authorize_order')">Autorizar</button>
                                <button v-if="order.can_authorize == 1 && order.status === 'pending_authorization'" class="btn btn-xs btn-danger" @click="orderAction(order, 'reject_order')">Rechazar</button>
                                <button v-if="['authorized','partial'].indexOf(order.status) >= 0" class="btn btn-xs btn-secondary" @click="orderAction(order, 'close_order')">Cerrar</button>
                            </td>
                        </tr>
                        <tr v-if="orders.length === 0"><td colspan="8" class="text-center text-muted">Sin ordenes.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-show="!loading && tab === 'invoices'">
                <button class="btn btn-primary btn-sm mb-3" @click="newInvoice"><i class="bi bi-plus-lg"></i> Nueva factura</button>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Proveedor</th><th>Mapa</th><th>UUID</th><th>Estado</th><th>Total</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <tr v-for="invoice in invoices" :key="invoice.id">
                            <td><strong>{{ invoice.folio }}</strong></td>
                            <td>{{ invoice.party_name || '-' }}</td>
                            <td><div class="small" v-html="flowLabel(invoice)"></div></td>
                            <td class="small">{{ invoice.uuid || '-' }}</td>
                            <td><span class="badge" :class="statusClass(invoice.validation_status)">{{ statusLabel(invoice.validation_status) }}</span></td>
                            <td>{{ invoice.currency_code }} {{ money(invoice.total) }}</td>
                            <td><button class="btn btn-xs btn-outline-primary" @click="openInvoice(invoice)">Detalle</button></td>
                        </tr>
                        <tr v-if="invoices.length === 0"><td colspan="7" class="text-center text-muted">Sin facturas.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-show="!loading && tab === 'receipts'">
                <button class="btn btn-primary btn-sm mb-3" @click="newReceipt"><i class="bi bi-plus-lg"></i> Nuevo contrarecibo</button>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Proveedor</th><th>Estado</th><th>Pago programado</th><th>Total</th><th>Pago</th><th>Facturas</th></tr></thead>
                    <tbody>
                        <tr v-for="receipt in receipts" :key="receipt.id">
                            <td><strong>{{ receipt.folio }}</strong></td>
                            <td>{{ receipt.party_name || '-' }}</td>
                            <td><span class="badge" :class="statusClass(receipt.status)">{{ statusLabel(receipt.status) }}</span></td>
                            <td>{{ receipt.scheduled_payment_date || '-' }}</td>
                            <td>{{ receipt.currency_code }} {{ money(receipt.total) }}</td>
                            <td>{{ receipt.payment_folio || '-' }}</td>
                            <td><div v-for="item in receipt.items" class="small">{{ item.invoice_folio }} - {{ money(item.amount) }}</div></td>
                        </tr>
                        <tr v-if="receipts.length === 0"><td colspan="7" class="text-center text-muted">Sin contrarecibos.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-show="!loading && tab === 'documents'">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Documento</th><th>Registro</th><th>Visibilidad</th><th>Evidencia</th><th>Fecha</th></tr></thead>
                    <tbody>
                        <tr v-for="document in documents" :key="document.id">
                            <td><a :href="baseUrl + document.file_path" target="_blank">{{ document.title || document.original_name }}</a><div class="text-muted small">{{ document.original_name }}</div></td>
                            <td>{{ entityLabel(document.entity_type) }} #{{ document.entity_id }}</td>
                            <td>{{ document.visibility }}</td>
                            <td>{{ document.is_evidence == 1 ? 'Si' : 'No' }}</td>
                            <td>{{ document.created_label }}</td>
                        </tr>
                        <tr v-if="documents.length === 0"><td colspan="5" class="text-center text-muted">Sin evidencias.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-order" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">{{ orderForm.id ? orderForm.folio : 'Nueva orden de compra' }}</h5><button class="close text-white" @click="hideModal('modal-order')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4"><label>Proveedor</label><select class="form-control" v-model="orderForm.party_id"><option value="">Selecciona</option><option v-for="s in options.suppliers" :value="s.value">{{ s.label }}</option></select></div>
                    <div class="col-md-3"><label>Departamento</label><select class="form-control" v-model="orderForm.department_id"><option value="0">General</option><option v-for="d in options.departments" :value="d.value">{{ d.label }}</option></select></div>
                    <div class="col-md-3"><label>Solicita</label><select class="form-control" v-model="orderForm.requested_by"><option value="0">Usuario actual</option><option v-for="u in options.users" :value="u.value">{{ u.label }}</option></select></div>
                    <div class="col-md-2"><label>Condicion pago</label><select class="form-control" v-model="orderForm.payment_term_id"><option value="0">Sin definir</option><option v-for="t in options.payment_terms" :value="t.value">{{ t.label }}</option></select></div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-2"><label>Fecha</label><input class="form-control" type="date" v-model="orderForm.order_date"></div>
                    <div class="col-md-2"><label>Esperada</label><input class="form-control" type="date" v-model="orderForm.expected_date"></div>
                    <div class="col-md-2"><label>Moneda</label><select class="form-control" v-model="orderForm.currency_code"><option value="MXN">MXN</option><option v-for="c in options.currencies" :value="c.value">{{ c.value }}</option></select></div>
                    <div class="col-md-3"><label>Referencia externa</label><input class="form-control" v-model="orderForm.external_reference"></div>
                    <div class="col-md-3"><label>Estado</label><select class="form-control" v-model="orderForm.status"><option value="draft">Borrador</option><option value="pending_authorization">Por autorizar</option><option value="authorized">Autorizada</option><option value="partial">Parcial</option><option value="closed">Cerrada</option><option value="cancelled">Cancelada</option><option value="rejected">Rechazada</option></select></div>
                </div>
                <div v-if="orderForm.id" class="alert alert-light border mt-3 mb-0">
                    <strong>Autorizacion:</strong> {{ approvalLabel(orderForm) }}
                    <span v-if="orderForm.authorized_by_name"> por {{ orderForm.authorized_by_name }} {{ orderForm.authorized_label }}</span>
                </div>
                <div class="border rounded p-3 my-3">
                    <div class="row">
                        <div class="col-md-4"><label>Concepto</label><input class="form-control" v-model="line.description"></div>
                        <div class="col-md-2"><label>Cantidad</label><input class="form-control" type="number" step="0.01" v-model.number="line.quantity"></div>
                        <div class="col-md-2"><label>Precio unitario</label><input class="form-control" type="number" step="0.01" v-model.number="line.unit_price"></div>
                        <div class="col-md-2"><label>IVA trasladado</label><select class="form-control" v-model="line.tax_code" @change="applyTax"><option value="">Sin IVA</option><option v-for="tax in options.taxes" :value="tax.value">{{ tax.label }} ({{ tax.rate_label }})</option></select></div>
                        <div class="col-md-1"><label>Tasa</label><input class="form-control" type="number" step="0.000001" v-model.number="line.tax_rate"></div>
                        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-outline-primary btn-block" @click="addLine">+</button></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3"><label>Retencion</label><select class="form-control" v-model="line.retention_code" @change="applyRetention"><option value="">Sin retencion</option><option v-for="retention in options.retentions" :value="retention.value">{{ retention.label }} ({{ retention.rate_label }})</option></select></div>
                        <div class="col-md-3"><label>Monto retenido</label><input class="form-control" type="number" step="0.01" v-model.number="line.retention_amount"></div>
                        <div class="col-md-2"><label>Subtotal</label><input class="form-control" type="text" :value="money(lineSubtotal(line))" readonly></div>
                        <div class="col-md-2"><label>IVA</label><input class="form-control" type="text" :value="money(lineTax(line))" readonly></div>
                        <div class="col-md-2"><label>Total linea</label><input class="form-control" type="text" :value="money(lineTotal(line))" readonly></div>
                    </div>
                </div>
                <table class="table table-sm table-bordered" v-if="orderForm.items.length"><thead><tr><th>Concepto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th><th>IVA</th><th>Retencion</th><th>Total</th><th></th></tr></thead><tbody><tr v-for="(item, i) in orderForm.items"><td>{{ item.description }}</td><td>{{ item.quantity }}</td><td>{{ money(item.unit_price) }}</td><td>{{ money(lineSubtotal(item)) }}</td><td>{{ money(lineTax(item)) }}</td><td>{{ money(item.retention_amount) }}</td><td>{{ money(lineTotal(item)) }}</td><td><button class="btn btn-xs btn-danger" @click="orderForm.items.splice(i, 1)">Quitar</button></td></tr></tbody></table>
                <label>Notas</label><textarea class="form-control" rows="2" v-model="orderForm.notes"></textarea>
                <label class="mt-2">Notas internas/autorizacion</label><textarea class="form-control" rows="2" v-model="orderForm.approval_notes"></textarea>
                <div v-if="orderForm.id" class="mt-3"><label>Adjuntar evidencia/documento</label><input type="file" class="form-control-file" @change="selectedFile = $event.target.files[0]"><button class="btn btn-outline-primary btn-sm mt-2" @click="upload('purchase_order', orderForm.id)">Adjuntar</button></div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-order')">Cerrar</button><button class="btn btn-primary" @click="saveOrder">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-invoice" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">{{ invoiceForm.id ? invoiceForm.folio : 'Factura proveedor' }}</h5><button class="close text-white" @click="hideModal('modal-invoice')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6"><label>Proveedor</label><select class="form-control" v-model="invoiceForm.party_id"><option value="">Selecciona</option><option v-for="s in options.suppliers" :value="s.value">{{ s.label }}</option></select></div>
                    <div class="col-md-6"><label>Orden</label><select class="form-control" v-model="invoiceForm.order_id"><option value="0">Sin orden</option><option v-for="o in orders" :value="o.id">{{ o.folio }} - {{ o.party_name }}</option></select></div>
                    <div class="col-md-6"><label>UUID</label><input class="form-control" v-model="invoiceForm.uuid"></div>
                    <div class="col-md-3"><label>Fecha</label><input class="form-control" type="date" v-model="invoiceForm.invoice_date"></div>
                    <div class="col-md-3"><label>Vence</label><input class="form-control" type="date" v-model="invoiceForm.due_date"></div>
                    <div class="col-md-3"><label>Subtotal</label><input class="form-control" type="number" step="0.01" v-model.number="invoiceForm.subtotal"></div>
                    <div class="col-md-3"><label>IVA trasladado</label><input class="form-control" type="number" step="0.01" v-model.number="invoiceForm.tax_total"></div>
                    <div class="col-md-3"><label>Retenciones</label><input class="form-control" type="number" step="0.01" v-model.number="invoiceForm.retention_total"></div>
                    <div class="col-md-3"><label>Total</label><input class="form-control" type="number" step="0.01" v-model.number="invoiceForm.total"></div>
                    <div class="col-md-6"><label>Validacion</label><select class="form-control" v-model="invoiceForm.validation_status"><option value="pending">Pendiente</option><option value="validated">Validada</option><option value="rejected">Rechazada</option></select></div>
                    <div class="col-md-6"><label>Estado</label><select class="form-control" v-model="invoiceForm.status"><option value="submitted">Recibida</option><option value="in_review">En revision</option><option value="in_receipt">En contrarecibo</option><option value="paid">Pagada</option><option value="cancelled">Cancelada</option></select></div>
                </div>
                <label>Mensaje</label><textarea class="form-control" rows="2" v-model="invoiceForm.message"></textarea>
                <div v-if="invoiceForm.id" class="mt-3"><label>Adjuntar PDF/XML/evidencia</label><input type="file" class="form-control-file" @change="selectedFile = $event.target.files[0]"><button class="btn btn-outline-primary btn-sm mt-2" @click="upload('purchase_invoice', invoiceForm.id)">Adjuntar</button></div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-invoice')">Cerrar</button><button class="btn btn-primary" @click="saveInvoice">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-receipt" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Nuevo contrarecibo</h5><button class="close text-white" @click="hideModal('modal-receipt')"><span>&times;</span></button></div>
            <div class="modal-body">
                <label>Proveedor</label><select class="form-control" v-model="receiptForm.party_id"><option value="">Selecciona</option><option v-for="s in options.suppliers" :value="s.value">{{ s.label }}</option></select>
                <label class="mt-2">Facturas pendientes</label>
                <div class="border rounded p-2" style="max-height: 260px; overflow-y: auto;">
                    <div class="form-check" v-for="invoice in pendingInvoices"><input class="form-check-input" type="checkbox" :value="invoice.id" v-model="receiptForm.invoice_ids"><label class="form-check-label">{{ invoice.folio }} - {{ invoice.currency_code }} {{ money(invoice.balance_due) }}</label></div>
                </div>
                <div class="row mt-2"><div class="col-md-4"><label>Fecha</label><input type="date" class="form-control" v-model="receiptForm.issue_date"></div><div class="col-md-4"><label>Pago programado</label><input type="date" class="form-control" v-model="receiptForm.scheduled_payment_date"></div><div class="col-md-4"><label>Pago aplicado</label><select class="form-control" v-model="receiptForm.payment_id"><option value="0">Pendiente de pago</option><option v-for="p in options.payments" :value="p.value">{{ p.label }}</option></select></div></div>
                <label>Notas</label><textarea class="form-control" rows="2" v-model="receiptForm.notes"></textarea>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-receipt')">Cerrar</button><button class="btn btn-primary" @click="saveReceipt">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-purchases',
        data: {
            baseUrl: '<?php echo Uri::base(false); ?>',
            loading: true, error: '', tab: 'orders', selectedFile: null,
            orders: [], invoices: [], receipts: [], documents: [],
            options: { suppliers: [], departments: [], users: [], payment_terms: [], payments: [], approval_rules: [], currencies: [], taxes: [], retentions: [] },
            stats: {},
            orderForm: { items: [] },
            invoiceForm: {},
            receiptForm: { invoice_ids: [] },
            line: { description: '', quantity: 1, unit_price: 0, tax_code: 'iva_16', tax_rate: 0.16, retention_code: '', retention_amount: 0 }
        },
        computed: {
            pendingInvoices: function() {
                var party = String(this.receiptForm.party_id || '');
                return this.invoices.filter(function(i) { return String(i.party_id) === party && i.validation_status === 'validated' && i.status !== 'paid' && Number(i.balance_due) > 0; });
            }
        },
        mounted: function() { this.load(); },
        methods: {
            load: function() {
                var self = this; self.loading = true;
                fetch('<?php echo Uri::create('admin/purchases/data'); ?>').then(function(r){ return r.json(); }).then(function(data){
                    self.orders = data.orders || [];
                    self.invoices = data.invoices || [];
                    self.receipts = (data.receipts || []).map(function(receipt) {
                        receipt.items = receipt.items || [];
                        return receipt;
                    });
                    self.documents = data.documents || [];
                    data.options = data.options || {};
                    self.options = {
                        suppliers: data.options.suppliers || [],
                        departments: data.options.departments || [],
                        users: data.options.users || [],
                        payment_terms: data.options.payment_terms || [],
                        payments: data.options.payments || [],
                        approval_rules: data.options.approval_rules || [],
                        currencies: data.options.currencies || [],
                        taxes: data.options.taxes || [],
                        retentions: data.options.retentions || []
                    };
                    self.stats = data.stats || {};
                    self.loading = false;
                }).catch(function(){ self.error = 'No se pudo cargar compras.'; self.loading = false; });
            },
            today: function(){ return new Date().toISOString().slice(0,10); },
            newOrder: function(){ this.orderForm = { party_id: '', department_id: 0, requested_by: 0, payment_term_id: 0, order_date: this.today(), expected_date: '', currency_code: 'MXN', status: 'draft', notes: '', internal_notes: '', approval_notes: '', external_reference: '', items: [] }; this.resetLine(); this.showModal('modal-order'); },
            openOrder: function(o){ this.orderForm = JSON.parse(JSON.stringify(o)); this.orderForm.items = this.orderForm.items || []; this.resetLine(); this.showModal('modal-order'); },
            newInvoice: function(){ this.invoiceForm = { party_id: '', order_id: 0, uuid: '', invoice_date: this.today(), due_date: '', currency_code: 'MXN', subtotal: 0, tax_total: 0, retention_total: 0, total: 0, status: 'submitted', validation_status: 'pending', message: '' }; this.showModal('modal-invoice'); },
            openInvoice: function(i){ this.invoiceForm = JSON.parse(JSON.stringify(i)); this.showModal('modal-invoice'); },
            newReceipt: function(){ this.receiptForm = { party_id: '', invoice_ids: [], issue_date: this.today(), scheduled_payment_date: '', payment_id: 0, currency_code: 'MXN', status: 'draft', notes: '' }; this.showModal('modal-receipt'); },
            addLine: function(){ if (!this.line.description) return; var item = Object.assign({}, this.line); item.retention_amount = Number(item.retention_amount || 0); this.orderForm.items.push(item); this.resetLine(); },
            saveOrder: function(){ this.post('<?php echo Uri::create('admin/purchases/save_order'); ?>', this.orderForm, 'modal-order'); },
            saveInvoice: function(){ if (!this.invoiceForm.total) this.invoiceForm.total = Number(this.invoiceForm.subtotal || 0) + Number(this.invoiceForm.tax_total || 0) - Number(this.invoiceForm.retention_total || 0); this.post('<?php echo Uri::create('admin/purchases/save_invoice'); ?>', this.invoiceForm, 'modal-invoice'); },
            saveReceipt: function(){ this.post('<?php echo Uri::create('admin/purchases/save_receipt'); ?>', this.receiptForm, 'modal-receipt'); },
            orderAction: function(order, action){ var notes = prompt('Notas de autorizacion', order.approval_notes || ''); if (notes === null) return; this.post('<?php echo Uri::create('admin/purchases'); ?>/' + action, { id: order.id, notes: notes }, null); },
            post: function(url, payload, modal){ var self = this; fetch(url, window.coreAppFetchOptions(payload)).then(function(r){ return r.json(); }).then(function(data){ if (data.error) { self.error = data.error; return; } if (modal) self.hideModal(modal); self.load(); }); },
            upload: function(entity, id){ if (!this.selectedFile) return; var self = this, fd = new FormData(); fd.append('file', this.selectedFile); fd.append('entity_type', entity); fd.append('entity_id', id); fd.append('is_evidence', '1'); fd.append(window.coreAppCsrfKey, fuel_csrf_token()); fetch('<?php echo Uri::create('admin/purchases/upload_document'); ?>', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(data){ if (data.error) self.error = data.error; self.selectedFile = null; self.load(); }); },
            resetLine: function(){ this.line = { description: '', quantity: 1, unit_price: 0, tax_code: 'iva_16', tax_rate: 0.16, retention_code: '', retention_amount: 0 }; },
            applyTax: function(){ var self = this; var selected = this.options.taxes.find(function(tax){ return tax.value === self.line.tax_code; }); this.line.tax_rate = selected ? Number(selected.rate || 0) : 0; },
            applyRetention: function(){ var self = this; var selected = this.options.retentions.find(function(retention){ return retention.value === self.line.retention_code; }); this.line.retention_amount = selected ? Number((this.lineSubtotal(this.line) * Number(selected.rate || 0)).toFixed(2)) : 0; },
            lineSubtotal: function(i){ return Math.max(0, (Number(i.quantity || 0) * Number(i.unit_price || 0)) - Number(i.discount_amount || 0)); },
            lineTax: function(i){ return Number((this.lineSubtotal(i) * Number(i.tax_rate || 0)).toFixed(2)); },
            lineTotal: function(i){ return this.lineSubtotal(i) + this.lineTax(i) - Number(i.retention_amount || 0); },
            money: function(v){ return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            approvalLabel: function(order){ if (!order) return ''; return ({not_required:'No requiere',pending:'Pendiente',approved:'Aprobada',rejected:'Rechazada',cancelled:'Cancelada'})[order.approval_status] || order.approval_status || 'Sin regla'; },
            flowLabel: function(invoice){ var parts = []; if (invoice.flow && invoice.flow.cfdi) parts.push('CFDI ' + invoice.flow.cfdi.sat_status); else if (invoice.uuid) parts.push('CFDI pendiente'); if (invoice.flow && invoice.flow.order) parts.push('OC ' + invoice.flow.order.folio + ' ' + this.statusLabel(invoice.flow.order.status)); else if (invoice.order_folio) parts.push('OC ' + invoice.order_folio); if (invoice.flow && invoice.flow.receipts && invoice.flow.receipts.length) parts.push('CR ' + invoice.flow.receipts.map(function(r){ return r.folio; }).join(', ')); if (invoice.flow && invoice.flow.payments && invoice.flow.payments.length) parts.push('Pago ' + invoice.flow.payments.map(function(p){ return p.folio; }).join(', ')); return parts.length ? parts.join('<br>') : '-'; },
            statusLabel: function(s){ return ({draft:'Borrador',pending_authorization:'Por autorizar',authorized:'Autorizada',partial:'Parcial',closed:'Cerrada',cancelled:'Cancelada',submitted:'Recibida',pending:'Pendiente',validated:'Validada',rejected:'Rechazada',in_review:'En revision',in_receipt:'En contrarecibo',paid:'Pagada'})[s] || s; },
            statusClass: function(s){ if (['validated','authorized','paid','closed'].indexOf(s) >= 0) return 'badge-success'; if (['rejected','cancelled'].indexOf(s) >= 0) return 'badge-danger'; if (['pending','draft','in_review','pending_authorization'].indexOf(s) >= 0) return 'badge-warning'; return 'badge-info'; },
            entityLabel: function(e){ return ({purchase_order:'Orden',purchase_invoice:'Factura',purchase_receipt:'Contrarecibo'})[e] || e; },
            showModal: function(id){ $('#' + id).modal('show'); },
            hideModal: function(id){ $('#' + id).modal('hide'); }
        }
    });
};
</script>
