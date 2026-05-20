<script>
window.addEventListener('load', function() {
    new Vue({
        el: '#app-portal-purchases',
        data: {
            baseUrl: '<?php echo Uri::base(false); ?>',
            loading: true,
            error: '',
            tab: 'orders',
            orders: [],
            invoices: [],
            receipts: [],
            documents: [],
            selectedOrder: null,
            selectedFile: null,
            invoiceForm: {},
            evidenceForm: {}
        },
        mounted: function() {
            this.load();
        },
        methods: {
            load: function() {
                var self = this;
                self.loading = true;
                fetch('<?php echo Uri::create($portal_code.'/compras_data'); ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function(r) {
                        return r.json().then(function(json) {
                            if (!r.ok) {
                                throw json;
                            }
                            return json;
                        });
                    })
                    .then(function(data) {
                        self.orders = data.orders || [];
                        self.invoices = data.invoices || [];
                        self.receipts = data.receipts || [];
                        self.documents = data.documents || [];
                        self.loading = false;
                    })
                    .catch(function(err) {
                        self.error = err && err.error ? err.error : 'No se pudo cargar compras. Revisa sesion, permisos o conexion.';
                        self.loading = false;
                    });
            },
            today: function() {
                return new Date().toISOString().slice(0, 10);
            },
            openOrder: function(order) {
                this.selectedOrder = order;
                this.showModal('modal-portal-order');
            },
            newInvoice: function() {
                this.invoiceForm = {
                    order_id: 0,
                    uuid: '',
                    invoice_date: this.today(),
                    subtotal: 0,
                    tax_total: 0,
                    retention_total: 0,
                    total: 0,
                    message: ''
                };
                this.showModal('modal-portal-invoice');
            },
            openInvoice: function(invoice) {
                this.invoiceForm = JSON.parse(JSON.stringify(invoice));
                this.showModal('modal-portal-invoice');
            },
            saveInvoice: function() {
                var self = this;
                if (!this.invoiceForm.total) {
                    this.invoiceForm.total = Number(this.invoiceForm.subtotal || 0) + Number(this.invoiceForm.tax_total || 0) - Number(this.invoiceForm.retention_total || 0);
                }
                fetch('<?php echo Uri::create($portal_code.'/compras_invoice'); ?>', window.coreAppFetchOptions(this.invoiceForm))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.error) {
                            self.error = data.error;
                            return;
                        }
                        self.hideModal('modal-portal-invoice');
                        self.load();
                    });
            },
            openEvidence: function(entity, id, label) {
                this.evidenceForm = {
                    entity_type: entity,
                    entity_id: id,
                    entity_label: this.entityLabel(entity) + ' ' + (label || ('#' + id)),
                    document_type: entity === 'purchase_invoice' ? 'purchase_invoice' : 'other_evidence',
                    title: '',
                    description: ''
                };
                this.selectedFile = null;
                this.showModal('modal-portal-evidence');
            },
            uploadEvidence: function() {
                if (!this.selectedFile) {
                    this.error = 'Selecciona un archivo.';
                    return;
                }
                var self = this;
                var fd = new FormData();
                fd.append('file', this.selectedFile);
                fd.append('entity_type', this.evidenceForm.entity_type);
                fd.append('entity_id', this.evidenceForm.entity_id);
                fd.append('document_type', this.evidenceForm.document_type);
                fd.append('title', this.evidenceForm.title || '');
                fd.append('description', this.evidenceForm.description || '');
                fd.append('relation_type', this.relationForDocument(this.evidenceForm.document_type));
                fd.append('is_evidence', '1');
                fd.append(window.coreAppCsrfKey, fuel_csrf_token());

                fetch('<?php echo Uri::create($portal_code.'/compras_upload'); ?>', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.error) {
                            self.error = data.error;
                            return;
                        }
                        self.orders = data.orders || self.orders;
                        self.invoices = data.invoices || self.invoices;
                        self.receipts = data.receipts || self.receipts;
                        self.documents = data.documents || [];
                        self.selectedFile = null;
                        self.hideModal('modal-portal-evidence');
                        self.hideModal('modal-portal-order');
                        self.hideModal('modal-portal-invoice');
                        self.tab = 'documents';
                    });
            },
            money: function(v) {
                return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            statusLabel: function(s) {
                return ({draft:'Borrador',authorized:'Autorizada',partial:'Parcial',closed:'Cerrada',cancelled:'Cancelada',submitted:'Recibida',pending:'Pendiente',validated:'Validada',rejected:'Rechazada',in_review:'En revision',in_receipt:'En contrarecibo',paid:'Pagada'})[s] || s;
            },
            statusClass: function(s) {
                if (['validated', 'authorized', 'paid', 'closed'].indexOf(s) >= 0) return 'badge-success';
                if (['rejected', 'cancelled'].indexOf(s) >= 0) return 'badge-danger';
                if (['pending', 'draft', 'in_review'].indexOf(s) >= 0) return 'badge-warning';
                return 'badge-info';
            },
            entityLabel: function(e) {
                return ({purchase_order:'Orden',purchase_invoice:'Factura',purchase_receipt:'Contrarecibo'})[e] || e;
            },
            documentTypeLabel: function(e) {
                return ({purchase_invoice:'Factura',delivery_evidence:'Entrega',payment_evidence:'Pago',tax_document:'Fiscal',other_evidence:'Evidencia'})[e] || e;
            },
            relationForDocument: function(e) {
                return ({purchase_invoice:'invoice_file',delivery_evidence:'delivery_proof',payment_evidence:'payment_proof',tax_document:'evidence',other_evidence:'evidence'})[e] || 'evidence';
            },
            formatSize: function(bytes) {
                bytes = Number(bytes || 0);
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1048576).toFixed(1) + ' MB';
            },
            dateLabel: function(ts) {
                if (!ts) return '';
                return new Date(Number(ts) * 1000).toLocaleString('es-MX');
            },
            showModal: function(id) {
                $('#' + id).modal('show');
            },
            hideModal: function(id) {
                $('#' + id).modal('hide');
            }
        }
    });
});
</script>
