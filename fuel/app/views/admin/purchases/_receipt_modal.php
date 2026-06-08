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
