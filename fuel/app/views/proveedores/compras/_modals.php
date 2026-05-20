<div class="modal fade" id="modal-portal-order" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" v-if="selectedOrder">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">{{ selectedOrder.folio }}</h5>
                <button class="close text-white" @click="hideModal('modal-portal-order')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p><strong>Estado:</strong> <span class="badge" :class="statusClass(selectedOrder.status)">{{ statusLabel(selectedOrder.status) }}</span></p>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr><th>Concepto</th><th>Cantidad</th><th>Precio</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in selectedOrder.items">
                            <td>{{ item.description }}</td>
                            <td>{{ item.quantity }}</td>
                            <td>{{ money(item.unit_price) }}</td>
                            <td>{{ money(item.line_total) }}</td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-outline-primary btn-sm mt-2" @click="openEvidence('purchase_order', selectedOrder.id, selectedOrder.folio)">Adjuntar evidencia</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-portal-invoice" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">{{ invoiceForm.id ? 'Adjuntar factura' : 'Nueva factura' }}</h5>
                <button class="close text-white" @click="hideModal('modal-portal-invoice')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div v-if="!invoiceForm.id" class="row">
                    <div class="col-md-6">
                        <label>Orden</label>
                        <select class="form-control" v-model="invoiceForm.order_id">
                            <option value="0">Sin orden</option>
                            <option v-for="o in orders" :value="o.id">{{ o.folio }}</option>
                        </select>
                    </div>
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
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="hideModal('modal-portal-invoice')">Cerrar</button>
                <button v-if="!invoiceForm.id" class="btn btn-primary" @click="saveInvoice">Guardar factura</button>
                <button v-if="invoiceForm.id" class="btn btn-primary" @click="openEvidence('purchase_invoice', invoiceForm.id, invoiceForm.folio)">Adjuntar evidencia</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-portal-evidence" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Adjuntar evidencia</h5>
                <button class="close text-white" @click="hideModal('modal-portal-evidence')"><span>&times;</span></button>
            </div>
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
                <div class="form-group">
                    <label>Titulo</label>
                    <input class="form-control" v-model="evidenceForm.title" placeholder="Ej. XML factura, remision firmada">
                </div>
                <div class="form-group">
                    <label>Descripcion</label>
                    <textarea class="form-control" rows="2" v-model="evidenceForm.description"></textarea>
                </div>
                <div class="form-group">
                    <label>Archivo</label>
                    <input type="file" class="form-control-file" @change="selectedFile = $event.target.files[0]">
                    <small class="text-muted">PDF, XML, imagen, Office, CSV o TXT. Maximo 15 MB.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="hideModal('modal-portal-evidence')">Cancelar</button>
                <button class="btn btn-primary" @click="uploadEvidence">Subir evidencia</button>
            </div>
        </div>
    </div>
</div>
