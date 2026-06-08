<script>
window.onload = function() {
    new Vue({
        el: '#app-purchases',
        data: {
            baseUrl: '<?php echo Uri::base(false); ?>',
            loading: true, error: '', tab: 'orders', selectedFile: null,
            orders: [], invoices: [], receipts: [], documents: [],
            periodFilters: { start_date: '', end_date: '' },
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
                var params = [];
                if (self.periodFilters.start_date) params.push('start_date=' + encodeURIComponent(self.periodFilters.start_date));
                if (self.periodFilters.end_date) params.push('end_date=' + encodeURIComponent(self.periodFilters.end_date));
                var url = '<?php echo Uri::create('admin/purchases/data'); ?>';
                if (params.length) url += '?' + params.join('&');
                fetch(url).then(function(r){ return r.json(); }).then(function(data){
                    self.orders = data.orders || [];
                    self.invoices = data.invoices || [];
                    self.receipts = (data.receipts || []).map(function(receipt) {
                        receipt.items = receipt.items || [];
                        return receipt;
                    });
                    self.documents = data.documents || [];
                    self.periodFilters = data.period_filters || self.periodFilters;
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
