<div id="app-portal-purchases">
    <div class="row">
        <div class="col-md-3"><div class="small-box bg-info"><div class="inner"><h3>{{ orders.length }}</h3><p>Ordenes</p></div><div class="icon"><i class="bi bi-cart-check"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-primary"><div class="inner"><h3>{{ invoices.length }}</h3><p>Facturas</p></div><div class="icon"><i class="bi bi-file-earmark-text"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-success"><div class="inner"><h3>{{ receipts.length }}</h3><p>Contrarecibos</p></div><div class="icon"><i class="bi bi-receipt-cutoff"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-secondary"><div class="inner"><h3>{{ documents.length }}</h3><p>Evidencias</p></div><div class="icon"><i class="bi bi-folder2-open"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'orders'}" href="#" @click.prevent="tab = 'orders'">Ordenes</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'invoices'}" href="#" @click.prevent="tab = 'invoices'">Facturas</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'receipts'}" href="#" @click.prevent="tab = 'receipts'">Contrarecibos</a></li>
                <li class="nav-item"><a class="nav-link" :class="{active: tab === 'documents'}" href="#" @click.prevent="tab = 'documents'">Documentos</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando compras...</p></div>

            <div v-show="!loading && tab === 'orders'">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Estado</th><th>Total</th><th>Facturado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.id">
                            <td><strong>{{ order.folio }}</strong></td>
                            <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                            <td>{{ order.currency_code }} {{ money(order.total) }}</td>
                            <td>{{ order.currency_code }} {{ money(order.invoiced_total) }}</td>
                            <td>{{ order.order_date }}</td>
                            <td><button class="btn btn-xs btn-outline-primary" @click="openOrder(order)">Detalle</button></td>
                        </tr>
                        <tr v-if="orders.length === 0"><td colspan="6" class="text-center text-muted">Sin ordenes asignadas.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-show="!loading && tab === 'invoices'">
                <button class="btn btn-primary btn-sm mb-3" @click="newInvoice"><i class="bi bi-plus-lg"></i> Subir factura</button>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>OC</th><th>UUID</th><th>Validacion</th><th>Total</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <tr v-for="invoice in invoices" :key="invoice.id">
                            <td><strong>{{ invoice.folio }}</strong></td>
                            <td>{{ invoice.order_folio || '-' }}</td>
                            <td class="small">{{ invoice.uuid || '-' }}</td>
                            <td><span class="badge" :class="statusClass(invoice.validation_status)">{{ statusLabel(invoice.validation_status) }}</span></td>
                            <td>{{ invoice.currency_code }} {{ money(invoice.total) }}</td>
                            <td><button class="btn btn-xs btn-outline-primary" @click="openInvoice(invoice)">Adjuntar</button></td>
                        </tr>
                        <tr v-if="invoices.length === 0"><td colspan="6" class="text-center text-muted">Sin facturas registradas.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-show="!loading && tab === 'receipts'">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Estado</th><th>Pago programado</th><th>Total</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <tr v-for="receipt in receipts" :key="receipt.id">
                            <td><strong>{{ receipt.folio }}</strong></td>
                            <td><span class="badge" :class="statusClass(receipt.status)">{{ statusLabel(receipt.status) }}</span></td>
                            <td>{{ receipt.scheduled_payment_date || '-' }}</td>
                            <td>{{ receipt.currency_code }} {{ money(receipt.total) }}</td>
                            <td><button class="btn btn-xs btn-outline-primary" @click="openEvidence('purchase_receipt', receipt.id, receipt.folio)">Evidencia</button></td>
                        </tr>
                        <tr v-if="receipts.length === 0"><td colspan="5" class="text-center text-muted">Sin contrarecibos.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-show="!loading && tab === 'documents'">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Documento</th><th>Tipo</th><th>Registro</th><th>Tamano</th><th>Fecha</th></tr></thead>
                    <tbody>
                        <tr v-for="document in documents" :key="document.id">
                            <td><a :href="baseUrl + document.file_path" target="_blank">{{ document.title || document.original_name }}</a><div class="text-muted small">{{ document.original_name }}</div><div class="small">{{ document.description || '' }}</div></td>
                            <td><span class="badge badge-secondary">{{ documentTypeLabel(document.document_type) }}</span><div class="text-muted small">{{ document.file_extension }}</div></td>
                            <td>{{ entityLabel(document.entity_type) }} #{{ document.entity_id }}</td>
                            <td>{{ formatSize(document.file_size) }}</td>
                            <td>{{ dateLabel(document.created_at) }}</td>
                        </tr>
                        <tr v-if="documents.length === 0"><td colspan="5" class="text-center text-muted">Sin documentos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-portal-order" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content" v-if="selectedOrder">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">{{ selectedOrder.folio }}</h5><button class="close text-white" @click="hideModal('modal-portal-order')"><span>&times;</span></button></div>
            <div class="modal-body">
                <p><strong>Estado:</strong> <span class="badge" :class="statusClass(selectedOrder.status)">{{ statusLabel(selectedOrder.status) }}</span></p>
                <table class="table table-sm table-bordered"><thead><tr><th>Concepto</th><th>Cantidad</th><th>Precio</th><th>Total</th></tr></thead><tbody><tr v-for="item in selectedOrder.items"><td>{{ item.description }}</td><td>{{ item.quantity }}</td><td>{{ money(item.unit_price) }}</td><td>{{ money(item.line_total) }}</td></tr></tbody></table>
                <button class="btn btn-outline-primary btn-sm mt-2" @click="openEvidence('purchase_order', selectedOrder.id, selectedOrder.folio)">Adjuntar evidencia</button>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-portal-invoice" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">{{ invoiceForm.id ? 'Adjuntar factura' : 'Nueva factura' }}</h5><button class="close text-white" @click="hideModal('modal-portal-invoice')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div v-if="!invoiceForm.id" class="row">
                    <div class="col-md-6"><label>Orden</label><select class="form-control" v-model="invoiceForm.order_id"><option value="0">Sin orden</option><option v-for="o in orders" :value="o.id">{{ o.folio }}</option></select></div>
                    <div class="col-md-6"><label>UUID</label><input class="form-control" v-model="invoiceForm.uuid"></div>
                    <div class="col-md-4"><label>Fecha</label><input type="date" class="form-control" v-model="invoiceForm.invoice_date"></div>
                    <div class="col-md-4"><label>Subtotal</label><input type="number" step="0.01" class="form-control" v-model.number="invoiceForm.subtotal"></div>
                    <div class="col-md-4"><label>IVA</label><input type="number" step="0.01" class="form-control" v-model.number="invoiceForm.tax_total"></div>
                    <div class="col-md-4"><label>Retencion</label><input type="number" step="0.01" class="form-control" v-model.number="invoiceForm.retention_total"></div>
                    <div class="col-md-4"><label>Total</label><input type="number" step="0.01" class="form-control" v-model.number="invoiceForm.total"></div>
                    <div class="col-md-12"><label>Mensaje</label><textarea class="form-control" rows="2" v-model="invoiceForm.message"></textarea></div>
                </div>
                <div v-if="invoiceForm.id" class="alert alert-light border mt-3">
                    Adjunta PDF, XML, acuse o evidencia relacionada a esta factura.
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-portal-invoice')">Cerrar</button><button v-if="!invoiceForm.id" class="btn btn-primary" @click="saveInvoice">Guardar factura</button><button v-if="invoiceForm.id" class="btn btn-primary" @click="openEvidence('purchase_invoice', invoiceForm.id, invoiceForm.folio)">Adjuntar evidencia</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-portal-evidence" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Adjuntar evidencia</h5><button class="close text-white" @click="hideModal('modal-portal-evidence')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="alert alert-light border py-2">
                    Registro: <strong>{{ evidenceForm.entity_label }}</strong>
                </div>
                <div class="form-group">
                    <label>Tipo de evidencia</label>
                    <select class="form-control" v-model="evidenceForm.document_type">
                        <option value="purchase_invoice">Factura PDF/XML</option>
                        <option value="delivery_evidence">Entrega / remision</option>
                        <option value="payment_evidence">Pago / complemento</option>
                        <option value="tax_document">Documento fiscal</option>
                        <option value="other_evidence">Otra evidencia</option>
                    </select>
                </div>
                <div class="form-group"><label>Titulo</label><input class="form-control" v-model="evidenceForm.title" placeholder="Ej. XML factura, remision firmada"></div>
                <div class="form-group"><label>Descripcion</label><textarea class="form-control" rows="2" v-model="evidenceForm.description"></textarea></div>
                <div class="form-group"><label>Archivo</label><input type="file" class="form-control-file" @change="selectedFile = $event.target.files[0]"><small class="text-muted">PDF, XML, imagen, Office, CSV o TXT. Maximo 15 MB.</small></div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-portal-evidence')">Cancelar</button><button class="btn btn-primary" @click="uploadEvidence">Subir evidencia</button></div>
        </div></div>
    </div>
</div>

<script src="<?php echo Uri::base(false); ?>assets/js/vue.min.js"></script>
<script>
window.onload = function() {
    new Vue({
        el: '#app-portal-purchases',
        data: { baseUrl: '<?php echo Uri::base(false); ?>', loading: true, error: '', tab: 'orders', orders: [], invoices: [], receipts: [], documents: [], selectedOrder: null, selectedFile: null, invoiceForm: {}, evidenceForm: {} },
        mounted: function(){ this.load(); },
        methods: {
            load: function(){ var self = this; self.loading = true; fetch('<?php echo Uri::create($portal_code.'/compras_data'); ?>').then(function(r){ return r.json(); }).then(function(data){ self.orders = data.orders || []; self.invoices = data.invoices || []; self.receipts = data.receipts || []; self.documents = data.documents || []; self.loading = false; }).catch(function(){ self.error = 'No se pudo cargar compras.'; self.loading = false; }); },
            today: function(){ return new Date().toISOString().slice(0,10); },
            openOrder: function(order){ this.selectedOrder = order; this.showModal('modal-portal-order'); },
            newInvoice: function(){ this.invoiceForm = { order_id: 0, uuid: '', invoice_date: this.today(), subtotal: 0, tax_total: 0, retention_total: 0, total: 0, message: '' }; this.showModal('modal-portal-invoice'); },
            openInvoice: function(invoice){ this.invoiceForm = JSON.parse(JSON.stringify(invoice)); this.showModal('modal-portal-invoice'); },
            saveInvoice: function(){ var self = this; if (!this.invoiceForm.total) this.invoiceForm.total = Number(this.invoiceForm.subtotal || 0) + Number(this.invoiceForm.tax_total || 0) - Number(this.invoiceForm.retention_total || 0); fetch('<?php echo Uri::create($portal_code.'/compras_invoice'); ?>', window.coreAppFetchOptions(this.invoiceForm)).then(function(r){ return r.json(); }).then(function(data){ if (data.error) { self.error = data.error; return; } self.hideModal('modal-portal-invoice'); self.load(); }); },
            openEvidence: function(entity, id, label){ this.evidenceForm = { entity_type: entity, entity_id: id, entity_label: this.entityLabel(entity) + ' ' + (label || ('#' + id)), document_type: entity === 'purchase_invoice' ? 'purchase_invoice' : 'other_evidence', title: '', description: '' }; this.selectedFile = null; this.showModal('modal-portal-evidence'); },
            uploadEvidence: function(){ if (!this.selectedFile) { this.error = 'Selecciona un archivo.'; return; } var self = this, fd = new FormData(); fd.append('file', this.selectedFile); fd.append('entity_type', this.evidenceForm.entity_type); fd.append('entity_id', this.evidenceForm.entity_id); fd.append('document_type', this.evidenceForm.document_type); fd.append('title', this.evidenceForm.title || ''); fd.append('description', this.evidenceForm.description || ''); fd.append('relation_type', this.relationForDocument(this.evidenceForm.document_type)); fd.append('is_evidence', '1'); fd.append(window.coreAppCsrfKey, fuel_csrf_token()); fetch('<?php echo Uri::create($portal_code.'/compras_upload'); ?>', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(data){ if (data.error) { self.error = data.error; return; } self.orders = data.orders || self.orders; self.invoices = data.invoices || self.invoices; self.receipts = data.receipts || self.receipts; self.documents = data.documents || []; self.selectedFile = null; self.hideModal('modal-portal-evidence'); self.hideModal('modal-portal-order'); self.hideModal('modal-portal-invoice'); self.tab = 'documents'; }); },
            money: function(v){ return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            statusLabel: function(s){ return ({draft:'Borrador',authorized:'Autorizada',partial:'Parcial',closed:'Cerrada',cancelled:'Cancelada',submitted:'Recibida',pending:'Pendiente',validated:'Validada',rejected:'Rechazada',in_review:'En revision',in_receipt:'En contrarecibo',paid:'Pagada'})[s] || s; },
            statusClass: function(s){ if (['validated','authorized','paid','closed'].indexOf(s) >= 0) return 'badge-success'; if (['rejected','cancelled'].indexOf(s) >= 0) return 'badge-danger'; if (['pending','draft','in_review'].indexOf(s) >= 0) return 'badge-warning'; return 'badge-info'; },
            entityLabel: function(e){ return ({purchase_order:'Orden',purchase_invoice:'Factura',purchase_receipt:'Contrarecibo'})[e] || e; },
            documentTypeLabel: function(e){ return ({purchase_invoice:'Factura',delivery_evidence:'Entrega',payment_evidence:'Pago',tax_document:'Fiscal',other_evidence:'Evidencia'})[e] || e; },
            relationForDocument: function(e){ return ({purchase_invoice:'invoice_file',delivery_evidence:'delivery_proof',payment_evidence:'payment_proof',tax_document:'evidence',other_evidence:'evidence'})[e] || 'evidence'; },
            formatSize: function(bytes){ bytes = Number(bytes || 0); if (bytes < 1024) return bytes + ' B'; if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'; return (bytes / 1048576).toFixed(1) + ' MB'; },
            dateLabel: function(ts){ if (!ts) return ''; return new Date(Number(ts) * 1000).toLocaleString('es-MX'); },
            showModal: function(id){ $('#' + id).modal('show'); },
            hideModal: function(id){ $('#' + id).modal('hide'); }
        }
    });
};
</script>
