<div id="app-receivables">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.documents || 0 }}</h3><p>Documentos abiertos</p></div><div class="icon"><i class="bi bi-files"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ money(stats.balance_due) }}</h3><p>Saldo por cobrar</p></div><div class="icon"><i class="bi bi-cash-stack"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3>{{ money(stats.overdue_balance) }}</h3><p>Cartera vencida</p></div><div class="icon"><i class="bi bi-exclamation-triangle"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.actions_pending || 0 }}</h3><p>Gestiones pendientes</p></div><div class="icon"><i class="bi bi-telephone-outbound"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'customers'}" @click.prevent="tab = 'customers'">Clientes</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'documents'}" @click.prevent="tab = 'documents'">Documentos</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'actions'}" @click.prevent="tab = 'actions'">Cobranza</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-show="tab === 'customers'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Saldos por cliente</h3>
                    <button class="btn btn-primary btn-sm" @click="openAction({})"><i class="bi bi-plus-lg"></i> Gestion</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Cliente</th><th>Credito</th><th>Saldo</th><th>Vencido</th><th>Disponible</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="customer in customers" :key="customer.party_id">
                                <td><strong>{{ customer.party_name }}</strong><div class="text-muted small">{{ customer.rfc || '-' }} / {{ customer.documents_count }} docs</div></td>
                                <td>{{ money(customer.credit_limit) }} / {{ customer.credit_days }} dias</td>
                                <td>{{ money(customer.balance_due) }}</td>
                                <td><span :class="Number(customer.overdue_balance) > 0 ? 'text-danger font-weight-bold' : ''">{{ money(customer.overdue_balance) }}</span></td>
                                <td>{{ money(customer.available_credit) }}</td>
                                <td><span class="badge" :class="creditStatusClass(customer.credit_status)">{{ creditStatusLabel(customer.credit_status) }}</span></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary" @click="openAction({ party_id: customer.party_id })">Gestion</button>
                                    <button class="btn btn-xs btn-outline-secondary" @click="openCredit(customer)">Credito</button>
                                </td>
                            </tr>
                            <tr v-if="customers.length === 0"><td colspan="7" class="text-center text-muted">Sin clientes.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'documents'">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Documento</th><th>Cliente</th><th>Emision</th><th>Vence</th><th>Total</th><th>Saldo</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="doc in documents" :key="doc.id">
                                <td><strong>{{ doc.folio }}</strong><div class="text-muted small">{{ doc.uuid || '-' }}</div></td>
                                <td>{{ doc.party_name }}</td>
                                <td>{{ doc.issue_date }}</td>
                                <td><span :class="isOverdue(doc) ? 'text-danger font-weight-bold' : ''">{{ doc.due_date || '-' }}</span></td>
                                <td>{{ money(doc.total) }}</td>
                                <td>{{ money(doc.balance_due) }}</td>
                                <td><span class="badge badge-light">{{ doc.status }}</span></td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openAction({ party_id: doc.party_id, invoice_id: doc.id, promise_amount: doc.balance_due })">Gestion</button></td>
                            </tr>
                            <tr v-if="documents.length === 0"><td colspan="8" class="text-center text-muted">Sin documentos por cobrar.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'actions'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Gestiones de cobranza</h3>
                    <button class="btn btn-primary btn-sm" @click="openAction({})"><i class="bi bi-plus-lg"></i> Gestion</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Folio</th><th>Cliente</th><th>Documento</th><th>Tipo</th><th>Fecha</th><th>Promesa</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="action in actions" :key="action.id">
                                <td><strong>{{ action.folio }}</strong><div class="text-muted small">{{ action.assigned_user_name || '-' }}</div></td>
                                <td>{{ action.party_name }}</td>
                                <td>{{ action.invoice_folio || '-' }}</td>
                                <td>{{ actionTypeLabel(action.action_type) }}</td>
                                <td>{{ action.action_date }}<div class="text-muted small" v-if="action.next_action_date">Sig. {{ action.next_action_date }}</div></td>
                                <td>{{ action.promise_date || '-' }}<div class="text-muted small" v-if="Number(action.promise_amount) > 0">{{ money(action.promise_amount) }}</div></td>
                                <td><span class="badge" :class="actionStatusClass(action.status)">{{ actionStatusLabel(action.status) }}</span></td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openAction(action)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="actions.length === 0"><td colspan="8" class="text-center text-muted">Sin gestiones.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-action" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Gestion de cobranza</h5><button class="close text-white" @click="hideModal('modal-action')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6"><label>Cliente</label><select class="form-control" v-model="actionForm.party_id"><option value="0">Selecciona</option><option v-for="c in options.customers" :value="c.value">{{ c.label }}</option></select></div>
                <div class="col-md-6"><label>Documento</label><select class="form-control" v-model="actionForm.invoice_id"><option value="0">General</option><option v-for="d in options.documents" :value="d.value">{{ d.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Tipo</label><select class="form-control" v-model="actionForm.action_type"><option value="call">Llamada</option><option value="email">Correo</option><option value="whatsapp">WhatsApp</option><option value="visit">Visita</option><option value="promise">Promesa</option><option value="note">Nota</option></select></div>
                <div class="col-md-3 mt-2"><label>Estado</label><select class="form-control" v-model="actionForm.status"><option value="pending">Pendiente</option><option value="scheduled">Programada</option><option value="done">Realizada</option><option value="cancelled">Cancelada</option></select></div>
                <div class="col-md-3 mt-2"><label>Prioridad</label><select class="form-control" v-model="actionForm.priority"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></div>
                <div class="col-md-3 mt-2"><label>Responsable</label><select class="form-control" v-model="actionForm.assigned_user_id"><option value="0">Sin asignar</option><option v-for="u in options.users" :value="u.value">{{ u.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Fecha gestion</label><input type="date" class="form-control" v-model="actionForm.action_date"></div>
                <div class="col-md-3 mt-2"><label>Siguiente accion</label><input type="date" class="form-control" v-model="actionForm.next_action_date"></div>
                <div class="col-md-3 mt-2"><label>Promesa pago</label><input type="date" class="form-control" v-model="actionForm.promise_date"></div>
                <div class="col-md-3 mt-2"><label>Importe promesa</label><input type="number" step="0.01" class="form-control" v-model="actionForm.promise_amount"></div>
                <div class="col-md-12 mt-2"><label>Resultado</label><input class="form-control" v-model="actionForm.result"></div>
                <div class="col-md-12 mt-2"><label>Notas</label><textarea class="form-control" rows="3" v-model="actionForm.notes"></textarea></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-action')">Cerrar</button><button class="btn btn-primary" @click="saveAction">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-credit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-secondary text-white"><h5 class="modal-title">Estado de credito</h5><button class="close text-white" @click="hideModal('modal-credit')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><label>Estado</label><select class="form-control" v-model="creditForm.credit_status"><option value="normal">Normal</option><option value="watch">Observacion</option><option value="hold">Retener</option><option value="blocked">Bloqueado</option></select></div>
                <div class="col-md-4"><label>Limite credito</label><input type="number" step="0.01" class="form-control" v-model="creditForm.credit_limit"></div>
                <div class="col-md-4"><label>Dias credito</label><input type="number" class="form-control" v-model="creditForm.credit_days"></div>
                <div class="col-md-12 mt-2"><label>Notas</label><textarea class="form-control" rows="3" v-model="creditForm.notes"></textarea></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-credit')">Cerrar</button><button class="btn btn-primary" @click="saveCredit">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-receivables',
        data: { tab: 'customers', error: '', customers: [], documents: [], actions: [], options: { customers: [], documents: [], users: [] }, stats: {}, actionForm: {}, creditForm: {} },
        mounted: function() { this.load(); },
        methods: {
            load: function() {
                fetch('<?php echo Uri::create('admin/receivables/data'); ?>').then(function(r) { return r.json(); }).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.customers = data.customers || [];
                    this.documents = data.documents || [];
                    this.actions = data.actions || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                });
            },
            openAction: function(action) {
                this.actionForm = Object.assign({ id: 0, party_id: 0, invoice_id: 0, action_type: 'call', status: 'pending', priority: 'normal', assigned_user_id: 0, action_date: new Date().toISOString().slice(0, 10), next_action_date: '', promise_date: '', promise_amount: 0, result: '', notes: '', active: true }, action);
                this.showModal('modal-action');
            },
            openCredit: function(customer) {
                this.creditForm = { party_id: customer.party_id, credit_status: customer.credit_status || 'normal', credit_limit: customer.credit_limit || 0, credit_days: customer.credit_days || 0, notes: customer.credit_notes || '' };
                this.showModal('modal-credit');
            },
            saveAction: function() { this.post('save_action', this.actionForm, 'modal-action'); },
            saveCredit: function() { this.post('save_customer_status', this.creditForm, 'modal-credit'); },
            post: function(action, payload, modal) {
                fetch('<?php echo Uri::create('admin/receivables'); ?>/' + action, window.coreAppFetchOptions(payload)).then(function(r) { return r.json(); }).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    if (data.customers) this.customers = data.customers;
                    if (data.documents) this.documents = data.documents;
                    if (data.actions) this.actions = data.actions;
                    if (data.stats) this.stats = data.stats;
                    if (modal) this.hideModal(modal);
                });
            },
            isOverdue: function(doc) { return doc.due_date && doc.due_date < new Date().toISOString().slice(0, 10); },
            money: function(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            creditStatusLabel: function(v) { return ({normal:'Normal', watch:'Observacion', hold:'Retener', blocked:'Bloqueado'})[v] || v; },
            creditStatusClass: function(v) { return ({normal:'badge-success', watch:'badge-warning', hold:'badge-danger', blocked:'badge-dark'})[v] || 'badge-light'; },
            actionTypeLabel: function(v) { return ({call:'Llamada', email:'Correo', whatsapp:'WhatsApp', visit:'Visita', promise:'Promesa', note:'Nota'})[v] || v; },
            actionStatusLabel: function(v) { return ({pending:'Pendiente', scheduled:'Programada', done:'Realizada', cancelled:'Cancelada'})[v] || v; },
            actionStatusClass: function(v) { return ({pending:'badge-warning', scheduled:'badge-info', done:'badge-success', cancelled:'badge-secondary'})[v] || 'badge-light'; },
            showModal: function(id) { $('#' + id).modal('show'); },
            hideModal: function(id) { $('#' + id).modal('hide'); }
        }
    });
};
</script>
