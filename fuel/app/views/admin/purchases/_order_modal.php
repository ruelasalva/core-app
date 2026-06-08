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
                <?php echo View::forge('admin/purchases/_order_items'); ?>
                <label>Notas</label><textarea class="form-control" rows="2" v-model="orderForm.notes"></textarea>
                <label class="mt-2">Notas internas/autorizacion</label><textarea class="form-control" rows="2" v-model="orderForm.approval_notes"></textarea>
                <div v-if="orderForm.id" class="mt-3"><label>Adjuntar evidencia/documento</label><input type="file" class="form-control-file" @change="selectedFile = $event.target.files[0]"><button class="btn btn-outline-primary btn-sm mt-2" @click="upload('purchase_order', orderForm.id)">Adjuntar</button></div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-order')">Cerrar</button><button class="btn btn-primary" @click="saveOrder">Guardar</button></div>
        </div></div>
    </div>
