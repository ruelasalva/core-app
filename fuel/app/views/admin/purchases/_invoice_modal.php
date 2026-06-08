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
