<div id="app-payables">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.documents || 0 }}</h3><p>Documentos pendientes</p></div><div class="icon"><i class="bi bi-files"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ money(stats.balance_due) }}</h3><p>Saldo por pagar</p></div><div class="icon"><i class="bi bi-cash-stack"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3>{{ money(stats.overdue_balance) }}</h3><p>Vencido</p></div><div class="icon"><i class="bi bi-exclamation-triangle"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.actions_pending || 0 }}</h3><p>Programaciones pendientes</p></div><div class="icon"><i class="bi bi-calendar-check"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'suppliers'}" @click.prevent="tab = 'suppliers'">Proveedores</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'documents'}" @click.prevent="tab = 'documents'">Documentos</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'actions'}" @click.prevent="tab = 'actions'">Programacion</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-show="tab === 'suppliers'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Saldos por proveedor</h3>
                    <button class="btn btn-primary btn-sm" @click="openAction({})"><i class="bi bi-plus-lg"></i> Programar</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Proveedor</th><th>Credito</th><th>Saldo</th><th>Vencido</th><th>Disponible</th><th>Prioridad</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="supplier in suppliers" :key="supplier.party_id">
                                <td><strong>{{ supplier.party_name }}</strong><div class="text-muted small">{{ supplier.rfc || '-' }} / {{ supplier.documents_count }} docs</div></td>
                                <td>{{ money(supplier.credit_limit) }} / {{ supplier.credit_days }} dias</td>
                                <td>{{ money(supplier.balance_due) }}</td>
                                <td><span :class="Number(supplier.overdue_balance) > 0 ? 'text-danger font-weight-bold' : ''">{{ money(supplier.overdue_balance) }}</span></td>
                                <td>{{ money(supplier.available_credit) }}</td>
                                <td><span class="badge" :class="priorityClass(supplier.payment_priority)">{{ priorityLabel(supplier.payment_priority) }}</span></td>
                                <td><span class="badge" :class="statusClass(supplier.payment_status)">{{ statusLabel(supplier.payment_status) }}</span></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary" @click="openAction({ party_id: supplier.party_id, planned_amount: supplier.balance_due })">Programar</button>
                                    <button class="btn btn-xs btn-outline-secondary" @click="openSupplier(supplier)">Control</button>
                                </td>
                            </tr>
                            <tr v-if="suppliers.length === 0"><td colspan="8" class="text-center text-muted">Sin proveedores.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'documents'">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Documento</th><th>Proveedor</th><th>Factura</th><th>Vence</th><th>Total</th><th>Saldo</th><th>Validacion</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="doc in documents" :key="doc.id">
                                <td><strong>{{ doc.folio }}</strong><div class="text-muted small">{{ doc.uuid || '-' }}</div></td>
                                <td>{{ doc.party_name }}</td>
                                <td>{{ doc.invoice_date }}</td>
                                <td><span :class="isOverdue(doc) ? 'text-danger font-weight-bold' : ''">{{ doc.due_date || '-' }}</span></td>
                                <td>{{ money(doc.total) }}</td>
                                <td>{{ money(doc.balance_due) }}</td>
                                <td><span class="badge badge-light">{{ doc.validation_status }}</span></td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openAction({ party_id: doc.party_id, purchase_invoice_id: doc.id, planned_amount: doc.balance_due })">Programar</button></td>
                            </tr>
                            <tr v-if="documents.length === 0"><td colspan="8" class="text-center text-muted">Sin documentos pendientes.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'actions'">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Folio</th><th>Proveedor</th><th>Documento</th><th>Tipo</th><th>Estado</th><th>Prioridad</th><th>Fecha pago</th><th>Importe</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="action in actions" :key="action.id">
                                <td><strong>{{ action.folio }}</strong></td>
                                <td>{{ action.party_name }}</td>
                                <td>{{ action.invoice_folio || '-' }}</td>
                                <td>{{ actionTypeLabel(action.action_type) }}</td>
                                <td><span class="badge" :class="actionStatusClass(action.status)">{{ actionStatusLabel(action.status) }}</span></td>
                                <td><span class="badge" :class="priorityClass(action.priority)">{{ priorityLabel(action.priority) }}</span></td>
                                <td>{{ action.scheduled_payment_date || '-' }}</td>
                                <td>{{ money(action.planned_amount) }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openAction(action)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="actions.length === 0"><td colspan="9" class="text-center text-muted">Sin programaciones.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        Cuentas por pagar controla vencimientos y programacion. El pago real se registra en <strong>Finanzas &gt; Bancos y pagos</strong>, para no duplicar conciliacion ni movimientos bancarios.
    </div>

    <div class="modal fade" id="modal-action" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Programacion de pago</h5><button class="close text-white" @click="hideModal('modal-action')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6"><div class="form-group"><label>Proveedor</label><select class="form-control" v-model="actionForm.party_id"><option value="0">Selecciona</option><option v-for="option in options.suppliers" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Documento</label><select class="form-control" v-model="actionForm.purchase_invoice_id"><option value="0">Sin documento especifico</option><option v-for="option in options.documents" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Tipo</label><select class="form-control" v-model="actionForm.action_type"><option value="schedule">Programar</option><option value="approve">Autorizar</option><option value="hold">Retener</option><option value="release">Liberar</option><option value="negotiate">Negociar</option><option value="note">Nota</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Estado</label><select class="form-control" v-model="actionForm.status"><option value="pending">Pendiente</option><option value="scheduled">Programado</option><option value="approved">Autorizado</option><option value="done">Completado</option><option value="cancelled">Cancelado</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Prioridad</label><select class="form-control" v-model="actionForm.priority"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Responsable</label><select class="form-control" v-model="actionForm.assigned_user_id"><option value="0">Sin asignar</option><option v-for="option in options.users" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Fecha accion</label><input type="date" class="form-control" v-model="actionForm.action_date"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Fecha pago</label><input type="date" class="form-control" v-model="actionForm.scheduled_payment_date"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Importe planeado</label><input type="number" step="0.01" class="form-control" v-model="actionForm.planned_amount"></div></div>
                <div class="col-md-8"><div class="form-group"><label>Resultado</label><input class="form-control" v-model="actionForm.result"></div></div>
                <div class="col-md-12"><div class="form-group"><label>Notas</label><textarea class="form-control" rows="3" v-model="actionForm.notes"></textarea></div></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-action')">Cerrar</button><button class="btn btn-primary" @click="saveAction">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-supplier" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-secondary text-white"><h5 class="modal-title">Control de proveedor</h5><button class="close text-white" @click="hideModal('modal-supplier')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><div class="form-group"><label>Estado</label><select class="form-control" v-model="supplierForm.payment_status"><option value="normal">Normal</option><option value="hold">Retener</option><option value="scheduled">Programado</option><option value="blocked">Bloqueado</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Prioridad</label><select class="form-control" v-model="supplierForm.payment_priority"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Proximo pago</label><input type="date" class="form-control" v-model="supplierForm.next_payment_date"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Linea credito</label><input type="number" step="0.01" class="form-control" v-model="supplierForm.credit_limit"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Dias credito</label><input type="number" class="form-control" v-model="supplierForm.credit_days"></div></div>
                <div class="col-md-12"><div class="form-group"><label>Notas</label><textarea class="form-control" rows="3" v-model="supplierForm.notes"></textarea></div></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-supplier')">Cerrar</button><button class="btn btn-primary" @click="saveSupplier">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-payables',
        data: { error: '', tab: 'suppliers', suppliers: [], documents: [], actions: [], options: { suppliers: [], documents: [], users: [] }, stats: {}, actionForm: {}, supplierForm: {} },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                fetch('<?php echo Uri::create('admin/payables/data'); ?>').then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.suppliers = data.suppliers || [];
                    this.documents = data.documents || [];
                    this.actions = data.actions || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                });
            },
            openAction(action) {
                this.actionForm = Object.assign({ id: 0, party_id: 0, purchase_invoice_id: 0, action_type: 'schedule', status: 'pending', priority: 'normal', assigned_user_id: 0, action_date: new Date().toISOString().slice(0, 10), scheduled_payment_date: '', planned_amount: 0, result: '', notes: '', active: true }, action);
                this.showModal('modal-action');
            },
            openSupplier(supplier) {
                this.supplierForm = {
                    party_id: supplier.party_id,
                    payment_status: supplier.payment_status || 'normal',
                    payment_priority: supplier.payment_priority || 'normal',
                    credit_limit: supplier.credit_limit || 0,
                    credit_days: supplier.credit_days || 0,
                    next_payment_date: supplier.next_payment_date || '',
                    notes: supplier.payment_notes || ''
                };
                this.showModal('modal-supplier');
            },
            saveAction() {
                this.error = '';
                fetch('<?php echo Uri::create('admin/payables/save_action'); ?>', window.coreAppFetchOptions(this.actionForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.suppliers = data.suppliers || this.suppliers;
                    this.documents = data.documents || this.documents;
                    this.actions = data.actions || this.actions;
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-action');
                });
            },
            saveSupplier() {
                this.error = '';
                fetch('<?php echo Uri::create('admin/payables/save_supplier_status'); ?>', window.coreAppFetchOptions(this.supplierForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.suppliers = data.suppliers || this.suppliers;
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-supplier');
                });
            },
            isOverdue(doc) { return doc.due_date && doc.due_date < new Date().toISOString().slice(0, 10); },
            money(value) { return Number(value || 0).toFixed(2); },
            priorityLabel(value) { return ({ low: 'Baja', normal: 'Normal', high: 'Alta', urgent: 'Urgente' })[value] || 'Normal'; },
            priorityClass(value) { return ({ low: 'badge-light', normal: 'badge-info', high: 'badge-warning', urgent: 'badge-danger' })[value] || 'badge-info'; },
            statusLabel(value) { return ({ normal: 'Normal', hold: 'Retener', scheduled: 'Programado', blocked: 'Bloqueado' })[value] || 'Normal'; },
            statusClass(value) { return ({ normal: 'badge-success', hold: 'badge-warning', scheduled: 'badge-info', blocked: 'badge-danger' })[value] || 'badge-success'; },
            actionTypeLabel(value) { return ({ schedule: 'Programar', approve: 'Autorizar', hold: 'Retener', release: 'Liberar', negotiate: 'Negociar', note: 'Nota' })[value] || value; },
            actionStatusLabel(value) { return ({ pending: 'Pendiente', scheduled: 'Programado', approved: 'Autorizado', done: 'Completado', cancelled: 'Cancelado' })[value] || value; },
            actionStatusClass(value) { return ({ pending: 'badge-warning', scheduled: 'badge-info', approved: 'badge-primary', done: 'badge-success', cancelled: 'badge-secondary' })[value] || 'badge-light'; },
            showModal(id) { $('#' + id).modal('show'); },
            hideModal(id) { $('#' + id).modal('hide'); },
        }
    });
};
</script>
