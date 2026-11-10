<div id="app-viatics">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.trips || 0 }}</h3><p>Viajes</p></div>
                <div class="icon"><i class="bi bi-airplane"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.open || 0 }}</h3><p>Abiertos</p></div>
                <div class="icon"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ money(stats.advance_total || 0) }}</h3><p>Anticipos</p></div>
                <div class="icon"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner"><h3>{{ money(stats.expense_total || 0) }}</h3><p>Gastos</p></div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="message" class="alert alert-success">{{ message }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Asistente de viáticos</h3>
                <button class="btn btn-primary btn-sm" @click="openTrip({})"><i class="bi bi-plus-lg"></i> Nuevo viaje</button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-light border mb-3">
                <strong>Modo guiado:</strong> usa los pasos 1 a 5 para registrar un viaje de forma simple.
                Si es tu primer viaje, inicia con <strong>Nuevo viaje</strong>, después captura anticipos y gastos.
            </div>
            <div class="wizard-steps mb-3">
                <div class="wizard-step"><span>1</span> Datos del viaje</div>
                <div class="wizard-step"><span>2</span> Anticipos</div>
                <div class="wizard-step"><span>3</span> Gastos</div>
                <div class="wizard-step"><span>4</span> Resumen</div>
                <div class="wizard-step"><span>5</span> Cierre / Borrador contable</div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <h6>Mis viajes y viajes del área</h6>
                    <div class="btn-group btn-group-sm mb-2">
                        <button class="btn" :class="tripStatusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'" @click="tripStatusFilter='all'">Todos</button>
                        <button class="btn" :class="tripStatusFilter === 'open' ? 'btn-primary' : 'btn-outline-primary'" @click="tripStatusFilter='open'">En curso</button>
                        <button class="btn" :class="tripStatusFilter === 'settled' ? 'btn-primary' : 'btn-outline-primary'" @click="tripStatusFilter='settled'">Finalizados</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr><th>Folio</th><th>Viajero</th><th>Estado</th><th></th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="trip in visibleTrips" :key="trip.id" :class="selectedTrip && selectedTrip.id == trip.id ? 'table-info' : ''">
                                    <td>
                                        <strong>{{ trip.folio }}</strong>
                                        <div class="small text-muted">{{ trip.destination || 'Sin destino' }}</div>
                                    </td>
                                    <td>{{ trip.employee_name || trip.seller_name || trip.username || '-' }}</td>
                                    <td><span class="badge" :class="trip.status === 'settled' ? 'badge-success' : 'badge-warning'">{{ statusLabel(trip.status) }}</span></td>
                                    <td><button class="btn btn-xs btn-outline-primary" @click="selectTrip(trip)">Abrir</button></td>
                                </tr>
                                <tr v-if="visibleTrips.length === 0"><td colspan="4" class="text-center text-muted">Sin viajes en este filtro.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-8" v-if="selectedTrip">
                    <div class="card card-light">
                        <div class="card-header d-flex justify-content-between">
                            <strong>{{ selectedTrip.folio }} - {{ selectedTrip.title }}</strong>
                            <button class="btn btn-xs btn-outline-secondary" @click="openTrip(selectedTrip)"><i class="bi bi-pencil"></i> Editar viaje</button>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Destino:</strong> {{ selectedTrip.destination || '-' }}</p>
                            <p class="mb-1"><strong>Fechas:</strong> {{ selectedTrip.start_date }} a {{ selectedTrip.end_date }}</p>
                            <p class="mb-2"><strong>Motivo:</strong> {{ selectedTrip.travel_reason || '-' }}</p>

                            <div class="row">
                                <div class="col-md-4"><div class="alert alert-info py-2 mb-2">Anticipos<br><strong>{{ money(selectedTrip.advance_total) }}</strong></div></div>
                                <div class="col-md-4"><div class="alert alert-primary py-2 mb-2">Gastos<br><strong>{{ money(selectedTrip.expense_total) }}</strong></div></div>
                                <div class="col-md-4"><div class="alert py-2 mb-2" :class="Number(selectedTrip.balance_total) >= 0 ? 'alert-success' : 'alert-warning'">Saldo<br><strong>{{ money(selectedTrip.balance_total) }}</strong></div></div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-pills mb-2">
                        <li class="nav-item"><a href="#" class="nav-link" :class="{active: detailTab==='advances'}" @click.prevent="detailTab='advances'">Paso 2: Anticipos</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" :class="{active: detailTab==='expenses'}" @click.prevent="detailTab='expenses'">Paso 3: Gastos</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" :class="{active: detailTab==='close'}" @click.prevent="detailTab='close'">Paso 4 y 5: Cierre</a></li>
                    </ul>

                    <div v-show="detailTab==='advances'" class="card card-outline card-success">
                        <div class="card-header d-flex justify-content-between">
                            <strong>Anticipos del viaje</strong>
                            <button class="btn btn-success btn-xs" @click="openAdvance({ trip_id: selectedTrip.id, currency_code: selectedTrip.currency_code })"><i class="bi bi-plus-lg"></i> Agregar anticipo</button>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Fecha</th><th>Cuenta banco</th><th>Monto</th><th>Estatus</th></tr></thead>
                                <tbody>
                                    <tr v-for="item in selectedAdvances" :key="item.id">
                                        <td>{{ dateFromTs(item.requested_at) }}</td>
                                        <td>{{ optionLabel(options.bank_accounts, item.bank_account_id) }}</td>
                                        <td>{{ item.currency_code }} {{ money(item.amount) }}</td>
                                        <td>{{ item.status }}</td>
                                    </tr>
                                    <tr v-if="selectedAdvances.length === 0"><td colspan="4" class="text-center text-muted">Sin anticipos.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-show="detailTab==='expenses'" class="card card-outline card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <strong>Gastos del viaje</strong>
                            <button class="btn btn-primary btn-xs" @click="openExpense({ trip_id: selectedTrip.id, expense_date: today(), currency_code: selectedTrip.currency_code, exchange_rate: 1 })"><i class="bi bi-plus-lg"></i> Agregar gasto</button>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Fecha</th><th>Concepto</th><th>Cuenta contable</th><th>Total</th><th>Folio doc.</th></tr></thead>
                                <tbody>
                                    <tr v-for="item in selectedExpenses" :key="item.id">
                                        <td>{{ item.expense_date }}</td>
                                        <td>{{ item.concept }}</td>
                                        <td>{{ optionLabel(options.accounts, item.account_id) }}</td>
                                        <td>{{ item.currency_code }} {{ money(item.total) }}</td>
                                        <td>{{ item.document_folio || '-' }}</td>
                                    </tr>
                                    <tr v-if="selectedExpenses.length === 0"><td colspan="5" class="text-center text-muted">Sin gastos.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-show="detailTab==='close'" class="card card-outline card-warning">
                        <div class="card-header"><strong>Resumen y cierre guiado</strong></div>
                        <div class="card-body">
                            <ol>
                                <li>Confirma que los anticipos y gastos están completos.</li>
                                <li>Selecciona cuenta contable de liquidación en el viaje.</li>
                                <li>Genera borrador contable para revisión.</li>
                                <li>Cierra el viaje cuando el saldo esté conciliado.</li>
                            </ol>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary" :disabled="working" @click="generateDraft(selectedTrip.id)">Generar borrador contable</button>
                                <button class="btn btn-outline-success" :disabled="working" @click="settleTrip(selectedTrip.id)">Cerrar viaje</button>
                            </div>
                            <p class="small text-muted mt-2 mb-0">Este módulo no publica pólizas automáticamente. Solo deja borrador para contabilidad.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-trip" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Paso 1: Datos del viaje</h5><button class="close text-white" @click="hideModal('modal-trip')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4"><label>Vendedor</label><select class="form-control" v-model="tripForm.seller_id"><option value="0">Sin vendedor</option><option v-for="opt in options.sellers" :value="toInt(opt.value)">{{ opt.label }}</option></select></div>
                    <div class="col-md-4"><label>Empleado</label><select class="form-control" v-model="tripForm.employee_id"><option value="0">Sin empleado</option><option v-for="opt in options.employees" :value="toInt(opt.value)">{{ opt.label }}</option></select></div>
                    <div class="col-md-4"><label>Usuario responsable</label><select class="form-control" v-model="tripForm.user_id"><option v-for="opt in options.users" :value="toInt(opt.value)">{{ opt.label }}</option></select></div>
                    <div class="col-md-8 mt-2"><label>Título del viaje</label><input class="form-control" v-model="tripForm.title"></div>
                    <div class="col-md-4 mt-2"><label>Departamento</label><select class="form-control" v-model="tripForm.department_id"><option value="0">General</option><option v-for="opt in options.departments" :value="toInt(opt.value)">{{ opt.label }}</option></select></div>
                    <div class="col-md-6 mt-2"><label>Destino</label><input class="form-control" v-model="tripForm.destination"></div>
                    <div class="col-md-3 mt-2"><label>Inicio</label><input type="date" class="form-control" v-model="tripForm.start_date"></div>
                    <div class="col-md-3 mt-2"><label>Fin</label><input type="date" class="form-control" v-model="tripForm.end_date"></div>
                    <div class="col-md-4 mt-2"><label>Moneda</label><select class="form-control" v-model="tripForm.currency_code"><option v-for="opt in options.currencies" :value="opt.value">{{ opt.value }} - {{ opt.label }}</option></select></div>
                    <div class="col-md-4 mt-2"><label>Monto solicitado</label><input type="number" step="0.01" class="form-control" v-model="tripForm.requested_amount"></div>
                    <div class="col-md-4 mt-2"><label>Cuenta liquidación</label><select class="form-control" v-model="tripForm.settlement_account_id"><option value="0">Selecciona cuenta</option><option v-for="opt in options.accounts" :value="toInt(opt.value)">{{ opt.label }}</option></select></div>
                    <div class="col-md-12 mt-2"><label>Motivo del viaje</label><textarea class="form-control" rows="3" v-model="tripForm.travel_reason"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="hideModal('modal-trip')">Cancelar</button>
                <button class="btn btn-primary" :disabled="working" @click="saveTrip">Guardar y continuar</button>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-advance" tabindex="-1" role="dialog">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Paso 2: Anticipo</h5><button class="close text-white" @click="hideModal('modal-advance')"><span>&times;</span></button></div>
            <div class="modal-body">
                <label>Cuenta banco</label>
                <select class="form-control mb-2" v-model="advanceForm.bank_account_id"><option value="0">Sin cuenta</option><option v-for="opt in options.bank_accounts" :value="toInt(opt.value)">{{ opt.label }}</option></select>
                <label>Monto</label>
                <input type="number" step="0.01" class="form-control mb-2" v-model="advanceForm.amount">
                <label>Moneda</label>
                <select class="form-control mb-2" v-model="advanceForm.currency_code"><option v-for="opt in options.currencies" :value="opt.value">{{ opt.value }}</option></select>
                <label>Fecha solicitud</label>
                <input type="date" class="form-control mb-2" v-model="advanceForm.requested_at_date">
                <label>Estatus</label>
                <select class="form-control" v-model="advanceForm.status">
                    <option value="draft">Borrador</option>
                    <option value="paid">Pagado</option>
                    <option value="applied">Aplicado</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="hideModal('modal-advance')">Cancelar</button>
                <button class="btn btn-success" :disabled="working" @click="saveAdvance">Guardar</button>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-expense" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Paso 3: Gasto</h5><button class="close text-white" @click="hideModal('modal-expense')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4"><label>Fecha</label><input type="date" class="form-control" v-model="expenseForm.expense_date"></div>
                    <div class="col-md-8"><label>Concepto</label><input class="form-control" v-model="expenseForm.concept"></div>
                    <div class="col-md-6 mt-2"><label>Cuenta contable</label><select class="form-control" v-model="expenseForm.account_id"><option value="0">Selecciona cuenta</option><option v-for="opt in options.accounts" :value="toInt(opt.value)">{{ opt.label }}</option></select></div>
                    <div class="col-md-3 mt-2"><label>Subtotal</label><input type="number" step="0.01" class="form-control" v-model="expenseForm.subtotal"></div>
                    <div class="col-md-3 mt-2"><label>Impuestos</label><input type="number" step="0.01" class="form-control" v-model="expenseForm.tax_total"></div>
                    <div class="col-md-4 mt-2"><label>Total</label><input type="number" step="0.01" class="form-control" v-model="expenseForm.total"></div>
                    <div class="col-md-4 mt-2"><label>Moneda</label><select class="form-control" v-model="expenseForm.currency_code"><option v-for="opt in options.currencies" :value="opt.value">{{ opt.value }}</option></select></div>
                    <div class="col-md-4 mt-2"><label>Tipo de pago</label><select class="form-control" v-model="expenseForm.payment_method"><option value="cash">Efectivo</option><option value="card">Tarjeta</option><option value="transfer">Transferencia</option><option value="other">Otro</option></select></div>
                    <div class="col-md-6 mt-2"><label>Proveedor</label><input class="form-control" v-model="expenseForm.vendor_name"></div>
                    <div class="col-md-6 mt-2"><label>Folio comprobante</label><input class="form-control" v-model="expenseForm.document_folio"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="hideModal('modal-expense')">Cancelar</button>
                <button class="btn btn-primary" :disabled="working" @click="saveExpense">Guardar</button>
            </div>
        </div></div>
    </div>
</div>

<style>
.wizard-steps { display: flex; gap: 8px; flex-wrap: wrap; }
.wizard-step { background: #f1f5f9; border: 1px solid #dbe3ec; border-radius: 6px; padding: 6px 10px; font-size: 12px; }
.wizard-step span { background: #0d6efd; color: #fff; border-radius: 999px; padding: 0 6px; margin-right: 6px; font-weight: 600; }
</style>

<script src="<?php echo Uri::base(false); ?>assets/js/core-api-client.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        el: '#app-viatics',
        data: {
            trips: [],
            advances: [],
            expenses: [],
            options: { currencies: [], accounts: [], bank_accounts: [], departments: [], users: [], employees: [], sellers: [] },
            stats: {},
            selectedTrip: null,
            tripStatusFilter: 'all',
            detailTab: 'advances',
            working: false,
            error: '',
            message: '',
            tripForm: {},
            advanceForm: {},
            expenseForm: {}
        },
        mounted() {
            this.loadData();
        },
        computed: {
            visibleTrips() {
                if (this.tripStatusFilter === 'open') {
                    return this.trips.filter(t => String(t.status) !== 'settled' && String(t.status) !== 'cancelled');
                }
                if (this.tripStatusFilter === 'settled') {
                    return this.trips.filter(t => String(t.status) === 'settled');
                }
                return this.trips;
            },
            selectedAdvances() {
                if (!this.selectedTrip) return [];
                return this.advances.filter(a => Number(a.trip_id) === Number(this.selectedTrip.id));
            },
            selectedExpenses() {
                if (!this.selectedTrip) return [];
                return this.expenses.filter(a => Number(a.trip_id) === Number(this.selectedTrip.id));
            }
        },
        methods: {
            apiGet(url) {
                return window.CoreApiClient.get(url).then(r => r.payload || {});
            },
            apiPost(url, data) {
                return window.CoreApiClient.post(url, data || {}).then(r => r.payload || {});
            },
            loadData() {
                this.error = '';
                this.apiGet('<?php echo Uri::create('admin/viatics/data'); ?>').then(payload => {
                    if (!payload.success) {
                        this.error = payload.message || 'No se pudo cargar viáticos.';
                        return;
                    }
                    const data = payload.data || {};
                    this.trips = data.trips || [];
                    this.advances = data.advances || [];
                    this.expenses = data.expenses || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                    if (this.selectedTrip) {
                        this.selectedTrip = this.trips.find(x => Number(x.id) === Number(this.selectedTrip.id)) || null;
                    }
                }).catch(err => this.error = String(err));
            },
            selectTrip(trip) {
                this.selectedTrip = trip;
                this.detailTab = 'advances';
            },
            openTrip(trip) {
                this.tripForm = Object.assign({
                    id: 0, seller_id: 0, employee_id: 0, user_id: 0, department_id: 0, title: '',
                    destination: '', travel_reason: '', start_date: this.today(), end_date: this.today(),
                    currency_code: 'MXN', requested_amount: 0, settlement_account_id: 0
                }, trip || {});
                this.showModal('modal-trip');
            },
            saveTrip() {
                this.working = true;
                this.apiPost('<?php echo Uri::create('admin/viatics/save_trip'); ?>', this.tripForm)
                    .then(payload => this.applySaveResult(payload, 'Viaje guardado.'))
                    .finally(() => this.working = false);
            },
            openAdvance(item) {
                this.advanceForm = Object.assign({
                    id: 0, trip_id: this.selectedTrip ? this.selectedTrip.id : 0, bank_account_id: 0,
                    currency_code: this.selectedTrip ? this.selectedTrip.currency_code : 'MXN', amount: 0,
                    requested_at_date: this.today(), status: 'draft'
                }, item || {});
                this.showModal('modal-advance');
            },
            saveAdvance() {
                const payload = Object.assign({}, this.advanceForm, { requested_at: this.advanceForm.requested_at_date });
                this.working = true;
                this.apiPost('<?php echo Uri::create('admin/viatics/save_advance'); ?>', payload)
                    .then(payload => this.applySaveResult(payload, 'Anticipo guardado.'))
                    .finally(() => this.working = false);
            },
            openExpense(item) {
                this.expenseForm = Object.assign({
                    id: 0, trip_id: this.selectedTrip ? this.selectedTrip.id : 0, expense_date: this.today(),
                    concept: '', vendor_name: '', document_folio: '', payment_method: 'cash', account_id: 0,
                    currency_code: this.selectedTrip ? this.selectedTrip.currency_code : 'MXN', exchange_rate: 1,
                    subtotal: 0, tax_total: 0, total: 0
                }, item || {});
                this.showModal('modal-expense');
            },
            saveExpense() {
                this.working = true;
                this.apiPost('<?php echo Uri::create('admin/viatics/save_expense'); ?>', this.expenseForm)
                    .then(payload => this.applySaveResult(payload, 'Gasto guardado.'))
                    .finally(() => this.working = false);
            },
            settleTrip(id) {
                this.working = true;
                this.apiPost('<?php echo Uri::create('admin/viatics/settle_trip'); ?>/' + id, {})
                    .then(payload => this.applySaveResult(payload, 'Viaje conciliado.'))
                    .finally(() => this.working = false);
            },
            generateDraft(id) {
                this.working = true;
                this.apiPost('<?php echo Uri::create('admin/viatics/generate_draft'); ?>/' + id, {})
                    .then(payload => this.applySaveResult(payload, 'Borrador contable generado.'))
                    .finally(() => this.working = false);
            },
            applySaveResult(payload, okMessage) {
                if (!payload.success) {
                    this.error = payload.message || 'No se pudo guardar.';
                    return;
                }
                this.message = okMessage;
                this.hideModal('modal-trip');
                this.hideModal('modal-advance');
                this.hideModal('modal-expense');
                const data = payload.data || {};
                this.trips = data.trips || this.trips;
                this.advances = data.advances || this.advances;
                this.expenses = data.expenses || this.expenses;
                this.options = data.options || this.options;
                this.stats = data.stats || this.stats;
                if (this.selectedTrip) {
                    this.selectedTrip = this.trips.find(x => Number(x.id) === Number(this.selectedTrip.id)) || null;
                }
            },
            showModal(id) { $('#'+id).modal('show'); },
            hideModal(id) { $('#'+id).modal('hide'); },
            statusLabel(v) {
                if (v === 'submitted') return 'En proceso';
                if (v === 'settled') return 'Conciliado';
                if (v === 'cancelled') return 'Cancelado';
                return 'Borrador';
            },
            optionLabel(options, value) {
                const item = (options || []).find(x => String(x.value) === String(value));
                return item ? item.label : '-';
            },
            money(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            dateFromTs(ts) { return ts ? new Date(ts * 1000).toISOString().slice(0,10) : '-'; },
            today() { return new Date().toISOString().slice(0,10); },
            toInt(v) { return parseInt(v || 0, 10); }
        }
    });
});
</script>
