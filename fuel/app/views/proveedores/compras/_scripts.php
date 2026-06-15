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
        computed: {
            openOrders: function() {
                return this.orders.filter(function(order) {
                    return ['authorized', 'partial', 'pending', 'in_review'].indexOf(String(order.status || '')) >= 0;
                }).length;
            },
            pendingInvoices: function() {
                return this.invoices.filter(function(invoice) {
                    return ['pending', 'submitted', 'in_review', ''].indexOf(String(invoice.validation_status || '')) >= 0;
                }).length;
            },
            validatedInvoices: function() {
                return this.invoices.filter(function(invoice) {
                    return String(invoice.validation_status || '') === 'validated';
                }).length;
            },
            issuedReceipts: function() {
                return this.receipts.length;
            },
            scheduledReceipts: function() {
                return this.receipts.filter(function(receipt) {
                    return String(receipt.scheduled_payment_date || '').trim() !== '';
                }).length;
            },
            evidenceCount: function() {
                return this.documents.length;
            }
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
                        self.error = err && err.error ? err.error : 'No se pudo cargar compras. Revisa sesión, permisos o conexión.';
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
            newInvoiceForOrder: function(order) {
                this.invoiceForm = {
                    order_id: order && order.id ? order.id : 0,
                    uuid: '',
                    invoice_date: this.today(),
                    subtotal: 0,
                    tax_total: 0,
                    retention_total: 0,
                    total: order && order.balance_total ? Number(order.balance_total) : 0,
                    message: ''
                };
                this.hideModal('modal-portal-order');
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
                    document_type: entity === 'purchase_invoice' ? 'purchase_invoice' : (entity === 'purchase_order' ? 'delivery_evidence' : 'payment_evidence'),
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
                return ({draft:'Borrador',authorized:'Autorizada',partial:'Parcial',closed:'Cerrada',cancelled:'Cancelada',submitted:'Recibida',pending:'Pendiente',validated:'Validada',rejected:'Rechazada',in_review:'En revisión',in_receipt:'En contrarecibo',paid:'Pagada'})[s] || s;
            },
            statusClass: function(s) {
                if (['validated', 'authorized', 'paid', 'closed'].indexOf(s) >= 0) return 'badge-success';
                if (['rejected', 'cancelled'].indexOf(s) >= 0) return 'badge-danger';
                if (['pending', 'draft', 'in_review'].indexOf(s) >= 0) return 'badge-warning';
                return 'badge-info';
            },
            entityLabel: function(e) {
                return ({purchase_order:'OC',purchase_invoice:'Factura',purchase_receipt:'Contrarecibo'})[e] || e;
            },
            documentTypeLabel: function(e) {
                return ({purchase_invoice:'Factura',delivery_evidence:'Entrega',payment_evidence:'Pago',tax_document:'Fiscal',other_evidence:'Evidencia'})[e] || e;
            },
            relationLabel: function(e) {
                return ({attachment:'Adjunto',evidence:'Evidencia',invoice_file:'XML/PDF factura',xml_file:'XML',delivery_proof:'Entrega / remisión',payment_proof:'Comprobante de pago'})[e] || e || '-';
            },
            validationLabel: function(s) {
                return ({pending:'Pendiente',submitted:'Recibida',in_review:'En revisión',validated:'Validada',rejected:'Rechazada',in_receipt:'En contrarecibo',paid:'Pagada'})[s] || this.statusLabel(s);
            },
            validationHelp: function(s) {
                return ({
                    pending: 'Compras revisará XML/PDF y datos fiscales.',
                    submitted: 'Factura recibida para revisión.',
                    in_review: 'Compras está validando la documentación.',
                    validated: 'Factura validada para contrarecibo si aplica.',
                    rejected: 'Revisa el motivo y adjunta corrección.',
                    in_receipt: 'Factura relacionada a contrarecibo.',
                    paid: 'Factura pagada.'
                })[s] || 'Estado recibido desde compras.';
            },
            evidenceTitle: function() {
                return ({
                    purchase_order: 'Adjuntar evidencia a OC',
                    purchase_invoice: 'Adjuntar XML/PDF a factura',
                    purchase_receipt: 'Adjuntar evidencia a contrarecibo'
                })[this.evidenceForm.entity_type] || 'Adjuntar evidencia';
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
