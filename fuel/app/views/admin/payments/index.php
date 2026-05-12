<div id="app-payments">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.payments || 0 }}</h3><p>Pagos</p></div><div class="icon"><i class="bi bi-cash-coin"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.pending || 0 }}</h3><p>Pendientes</p></div><div class="icon"><i class="bi bi-hourglass-split"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.movements || 0 }}</h3><p>Movimientos</p></div><div class="icon"><i class="bi bi-bank"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ stats.unreconciled || 0 }}</h3><p>Sin conciliar</p></div><div class="icon"><i class="bi bi-check2-square"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Pagos</h3>
            <button class="btn btn-primary btn-sm ml-auto" @click="openPayment({})"><i class="bi bi-plus-lg"></i> Pago</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Folio</th><th>Tipo</th><th>Tercero</th><th>Fecha</th><th>Importe</th><th>Forma SAT</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id">
                        <td><strong>{{ payment.folio }}</strong><div class="text-muted small">{{ payment.reference }}</div></td>
                        <td>{{ payment.payment_type }}</td>
                        <td>{{ label(options.parties, payment.party_id) || '-' }}</td>
                        <td>{{ payment.payment_date }}</td>
                        <td>{{ payment.amount }} {{ payment.currency_code }}</td>
                        <td>{{ payment.sat_payment_form_code }}</td>
                        <td><span class="badge badge-light">{{ payment.status }}</span></td>
                        <td class="text-center"><button class="btn btn-xs btn-outline-primary" @click="openPayment(payment)"><i class="bi bi-pencil"></i></button></td>
                    </tr>
                    <tr v-if="payments.length === 0"><td colspan="8" class="text-center text-muted">Sin pagos</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-success card-outline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Movimientos bancarios</h3>
            <button class="btn btn-success btn-sm ml-auto" @click="openMovement({})"><i class="bi bi-plus-lg"></i> Movimiento</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Cuenta</th><th>Fecha</th><th>Tipo</th><th>Importe</th><th>Referencia</th><th>Conciliado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="movement in movements" :key="movement.id">
                        <td>{{ label(options.bank_accounts, movement.bank_account_id) }}</td>
                        <td>{{ movement.movement_date }}</td>
                        <td>{{ movement.movement_type }}</td>
                        <td>{{ movement.amount }} {{ movement.currency_code }}</td>
                        <td>{{ movement.reference }}</td>
                        <td>{{ movement.reconciled == 1 ? 'Si' : 'No' }}</td>
                        <td class="text-center"><button class="btn btn-xs btn-outline-primary" @click="openMovement(movement)"><i class="bi bi-pencil"></i></button></td>
                    </tr>
                    <tr v-if="movements.length === 0"><td colspan="7" class="text-center text-muted">Sin movimientos</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-payment" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Pago</h5><button class="close text-white" @click="hideModal('modal-payment')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><div class="form-group"><label>Tipo</label><select class="form-control" v-model="paymentForm.payment_type"><option value="received">Recibido</option><option value="sent">Enviado</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Tercero</label><select class="form-control" v-model="paymentForm.party_id"><option value="0">Sin tercero</option><option v-for="option in options.parties" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Cuenta</label><select class="form-control" v-model="paymentForm.bank_account_id"><option value="0">Sin cuenta</option><option v-for="option in options.bank_accounts" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-3"><div class="form-group"><label>Fecha</label><input type="date" class="form-control" v-model="paymentForm.payment_date"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Moneda</label><select class="form-control" v-model="paymentForm.currency_code"><option v-for="option in options.currencies" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-3"><div class="form-group"><label>Importe</label><input type="number" step="0.01" class="form-control" v-model="paymentForm.amount"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Estado</label><select class="form-control" v-model="paymentForm.status"><option value="pending">Pendiente</option><option value="confirmed">Confirmado</option><option value="cancelled">Cancelado</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Forma pago SAT</label><select class="form-control" v-model="paymentForm.sat_payment_form_code"><option v-for="option in options.sat_payment_forms" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Referencia</label><input class="form-control" v-model="paymentForm.reference"></div></div>
                <div class="col-md-4"><div class="form-group"><label>ID externo</label><input class="form-control" v-model="paymentForm.external_id"></div></div>
                <div class="col-md-12"><div class="form-group"><label>Notas</label><textarea class="form-control" rows="2" v-model="paymentForm.notes"></textarea></div></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-payment')">Cerrar</button><button class="btn btn-primary" @click="savePayment">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-movement" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Movimiento bancario</h5><button class="close text-white" @click="hideModal('modal-movement')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><div class="form-group"><label>Cuenta</label><select class="form-control" v-model="movementForm.bank_account_id"><option value="0">Selecciona</option><option v-for="option in options.bank_accounts" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-3"><div class="form-group"><label>Fecha</label><input type="date" class="form-control" v-model="movementForm.movement_date"></div></div>
                <div class="col-md-3"><div class="form-group"><label>Tipo</label><select class="form-control" v-model="movementForm.movement_type"><option value="deposit">Deposito</option><option value="withdrawal">Retiro</option><option value="fee">Comision</option><option value="adjustment">Ajuste</option></select></div></div>
                <div class="col-md-2"><div class="form-group"><label>Importe</label><input type="number" step="0.01" class="form-control" v-model="movementForm.amount"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Referencia</label><input class="form-control" v-model="movementForm.reference"></div></div>
                <div class="col-md-8"><div class="form-group"><label>Descripcion</label><input class="form-control" v-model="movementForm.description"></div></div>
                <div class="col-md-6"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="movement-reconciled" v-model="movementForm.reconciled"><label class="custom-control-label" for="movement-reconciled">Conciliado</label></div></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-movement')">Cerrar</button><button class="btn btn-success" @click="saveMovement">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-payments',
        data: { error: '', payments: [], movements: [], reconciliations: [], options: { parties: [], bank_accounts: [], currencies: [], sat_payment_forms: [], integrations: [] }, stats: {}, paymentForm: {}, movementForm: {} },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                fetch('<?php echo Uri::create('admin/payments/data'); ?>').then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.payments = data.payments || [];
                    this.movements = data.movements || [];
                    this.reconciliations = data.reconciliations || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                });
            },
            openPayment(payment) {
                this.paymentForm = Object.assign({ id: 0, payment_type: 'received', party_id: 0, bank_account_id: 0, integration_connection_id: 0, payment_date: new Date().toISOString().slice(0, 10), currency_code: 'MXN', exchange_rate: 1, amount: 0, sat_payment_form_code: '99', reference: '', external_id: '', status: 'pending', notes: '', active: true }, payment);
                this.showModal('modal-payment');
            },
            openMovement(movement) {
                this.movementForm = Object.assign({ id: 0, bank_account_id: 0, movement_date: new Date().toISOString().slice(0, 10), movement_type: 'deposit', amount: 0, currency_code: 'MXN', reference: '', description: '', source: 'manual', payment_id: 0, reconciled: false, active: true }, movement);
                this.showModal('modal-movement');
            },
            savePayment() {
                fetch('<?php echo Uri::create('admin/payments/save_payment'); ?>', window.coreAppFetchOptions(this.paymentForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.payments = data.payments || [];
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-payment');
                });
            },
            saveMovement() {
                fetch('<?php echo Uri::create('admin/payments/save_movement'); ?>', window.coreAppFetchOptions(this.movementForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.movements = data.movements || [];
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-movement');
                });
            },
            label(options, value) {
                const found = (options || []).find(option => String(option.value) === String(value));
                return found ? found.label : '';
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(element).show(); return; }
                if (window.jQuery && $.fn.modal) $('#' + id).modal('show');
            },
            hideModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) {
                    const instance = bootstrap.Modal.getInstance(element);
                    if (instance) instance.hide();
                } else if (window.jQuery && $.fn.modal) {
                    $('#' + id).modal('hide');
                }
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            }
        }
    });
};
</script>
