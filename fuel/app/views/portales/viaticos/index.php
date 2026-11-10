<style>
    .viaticos-fab {
        position: fixed;
        right: 18px;
        bottom: 86px;
        width: 54px;
        height: 54px;
        border-radius: 50%;
        z-index: 1050;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .25);
        padding: 0;
    }
    .viaticos-step-pill {
        display: inline-block;
        min-width: 32px;
        text-align: center;
        border-radius: 999px;
        padding: 2px 10px;
        font-size: 12px;
        background: #e9ecef;
        color: #495057;
    }
    .viaticos-step-pill.active {
        background: #007bff;
        color: #fff;
    }
</style>

<div id="app-portal-viaticos">
    <div class="portal-page-hero">
        <h1 class="h4 mb-2">Viáticos</h1>
        <p class="text-muted mb-2">Captura rápida para vendedor en campo: viaje, anticipo y gastos.</p>
        <div class="portal-page-actions">
            <button class="btn btn-primary btn-sm" @click="openTripWizard()"><i class="bi bi-plus-lg"></i> Nuevo viaje</button>
            <button class="btn btn-outline-secondary btn-sm" @click="loadData"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
        </div>
    </div>

    <div class="alert alert-info py-2 px-3">
        <strong>Flujo sugerido:</strong> 1) Crea viaje, 2) Registra anticipo, 3) Captura gastos.
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="message" class="alert alert-success">{{ message }}</div>

    <div class="portal-kpi-grid">
        <div class="portal-kpi"><div><div class="portal-kpi-label">Viajes</div><div class="portal-kpi-value">{{ stats.trips || 0 }}</div></div><i class="portal-kpi-icon bi bi-airplane"></i></div>
        <div class="portal-kpi"><div><div class="portal-kpi-label">Abiertos</div><div class="portal-kpi-value">{{ stats.open || 0 }}</div></div><i class="portal-kpi-icon bi bi-clock-history"></i></div>
        <div class="portal-kpi"><div class="portal-kpi-label">Anticipos</div><div class="portal-kpi-value">{{ money(stats.advance_total || 0) }}</div></div>
        <div class="portal-kpi"><div class="portal-kpi-label">Gastos</div><div class="portal-kpi-value">{{ money(stats.expense_total || 0) }}</div></div>
    </div>

    <div class="portal-panel">
        <div class="portal-panel-header"><strong>Mis viajes</strong></div>
        <div class="portal-panel-body">
            <div class="row mb-2">
                <div class="col-md-4 mb-2 mb-md-0">
                    <select class="form-control form-control-sm" v-model="statusFilter">
                        <option value="all">Todos los estados</option>
                        <option value="draft">Borrador</option>
                        <option value="in_progress">En proceso</option>
                        <option value="settled">Liquidado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <input class="form-control form-control-sm" v-model.trim="searchText" placeholder="Buscar por folio, título o destino">
                </div>
            </div>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm table-bordered portal-table">
                    <thead><tr><th>Folio</th><th>Título</th><th>Fechas</th><th>Estatus</th><th>Saldo</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="trip in filteredTrips" :key="trip.id">
                            <td>{{ trip.folio }}</td>
                            <td>{{ trip.title }}</td>
                            <td>{{ trip.start_date }} a {{ trip.end_date }}</td>
                            <td><span class="badge badge-light">{{ statusLabel(trip.status) }}</span></td>
                            <td :class="Number(trip.balance_total) < 0 ? 'text-danger font-weight-bold' : ''">{{ trip.currency_code }} {{ money(trip.balance_total) }}</td>
                            <td><button class="btn btn-xs btn-outline-primary" @click="selectTrip(trip)">Abrir</button></td>
                        </tr>
                        <tr v-if="filteredTrips.length === 0"><td colspan="6" class="text-center text-muted">Sin viajes registrados.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="d-md-none">
                <div v-for="trip in filteredTrips" :key="'card-'+trip.id" class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ trip.folio }}</strong>
                            <span class="badge badge-light">{{ statusLabel(trip.status) }}</span>
                        </div>
                        <div>{{ trip.title }}</div>
                        <small class="text-muted d-block">{{ trip.start_date }} a {{ trip.end_date }}</small>
                        <small class="d-block" :class="Number(trip.balance_total) < 0 ? 'text-danger font-weight-bold' : 'text-muted'">{{ trip.currency_code }} {{ money(trip.balance_total) }}</small>
                        <button class="btn btn-sm btn-outline-primary btn-block mt-2" @click="selectTrip(trip)">Abrir detalle</button>
                    </div>
                </div>
                <div v-if="filteredTrips.length === 0" class="text-muted text-center py-3">Sin viajes registrados.</div>
            </div>
        </div>
    </div>

    <div v-if="selectedTrip" class="portal-panel">
        <div class="portal-panel-header d-flex justify-content-between">
            <strong>{{ selectedTrip.folio }} - {{ selectedTrip.title }}</strong>
            <span class="badge badge-secondary">{{ statusLabel(selectedTrip.status) }}</span>
        </div>
        <div class="portal-panel-body">
            <p class="mb-1"><strong>Destino:</strong> {{ selectedTrip.destination || '-' }}</p>
            <p class="mb-1"><strong>Motivo:</strong> {{ selectedTrip.travel_reason || '-' }}</p>
            <p class="mb-2"><strong>Saldo:</strong> <span :class="selectedTripBalanceClass">{{ selectedTrip.currency_code }} {{ money(selectedTrip.balance_total) }}</span></p>

            <div class="row mb-3">
                <div class="col-md-6 mb-2 mb-md-0">
                    <button class="btn btn-success btn-block" :disabled="working" @click="openAdvance({ trip_id: selectedTrip.id, currency_code: selectedTrip.currency_code })">+ Registrar anticipo</button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-primary btn-block" :disabled="working" @click="openExpense({ trip_id: selectedTrip.id, expense_date: today(), currency_code: selectedTrip.currency_code, exchange_rate: 1 })">+ Registrar gasto</button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6>Anticipos</h6>
                    <ul class="list-group">
                        <li class="list-group-item py-1" v-for="a in selectedAdvances" :key="a.id">{{ dateFromTs(a.requested_at) }} - {{ a.currency_code }} {{ money(a.amount) }}</li>
                        <li class="list-group-item py-1 text-muted" v-if="selectedAdvances.length === 0">Sin anticipos</li>
                    </ul>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <h6>Gastos</h6>
                    <ul class="list-group">
                        <li class="list-group-item py-1" v-for="e in selectedExpenses" :key="e.id">{{ e.expense_date }} - {{ e.concept }} - {{ e.currency_code }} {{ money(e.total) }}</li>
                        <li class="list-group-item py-1 text-muted" v-if="selectedExpenses.length === 0">Sin gastos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="alert alert-secondary">
        Selecciona un viaje para registrar anticipos y gastos.
    </div>

    <button type="button" class="btn btn-primary d-md-none viaticos-fab" @click="openQuickActions">
        <i class="bi bi-plus-lg"></i>
    </button>

    <div class="modal fade" id="modal-portal-quick-actions" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acciones rápidas</h5>
                <button class="close" @click="hideModal('modal-portal-quick-actions')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <button class="btn btn-primary btn-block mb-2" @click="quickCreateTrip">Nuevo viaje</button>
                <button class="btn btn-success btn-block mb-2" @click="quickCreateAdvance">Registrar anticipo</button>
                <button class="btn btn-info btn-block" @click="quickCreateExpense">Registrar gasto</button>
                <small class="text-muted d-block mt-2" v-if="!selectedTrip">Para anticipo/gasto primero crea o selecciona un viaje.</small>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-portal-trip" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Nuevo viaje (paso {{ tripWizardStep }} de 3)</h5>
                <button class="close text-white" @click="hideModal('modal-portal-trip')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <span :class="['viaticos-step-pill', tripWizardStep === 1 ? 'active' : '']">1. Básico</span>
                    <span :class="['viaticos-step-pill', tripWizardStep === 2 ? 'active' : '']">2. Fechas</span>
                    <span :class="['viaticos-step-pill', tripWizardStep === 3 ? 'active' : '']">3. Motivo</span>
                </div>
                <div class="progress mb-3" style="height:6px;">
                    <div class="progress-bar" role="progressbar" :style="{ width: tripProgress + '%' }"></div>
                </div>

                <div class="row" v-if="tripWizardStep === 1">
                    <div class="col-md-8"><label>Título</label><input class="form-control" v-model.trim="tripForm.title" placeholder="Ej. Visita comercial Puebla"></div>
                    <div class="col-md-4"><label>Moneda</label><select class="form-control" v-model="tripForm.currency_code"><option v-for="opt in options.currencies" :value="opt.value">{{ opt.value }}</option></select></div>
                    <div class="col-md-12 mt-2"><label>Destino</label><input class="form-control" v-model.trim="tripForm.destination" placeholder="Ciudad o zona"></div>
                </div>

                <div class="row" v-if="tripWizardStep === 2">
                    <div class="col-md-6"><label>Fecha inicio</label><input type="date" class="form-control" v-model="tripForm.start_date"></div>
                    <div class="col-md-6"><label>Fecha fin</label><input type="date" class="form-control" v-model="tripForm.end_date"></div>
                </div>

                <div class="row" v-if="tripWizardStep === 3">
                    <div class="col-md-12"><label>Motivo</label><textarea class="form-control" rows="4" v-model.trim="tripForm.travel_reason" placeholder="Describe objetivo del viaje"></textarea></div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                    <button class="btn btn-light" v-if="tripWizardStep > 1" @click="prevTripWizardStep">Atrás</button>
                </div>
                <div>
                    <button class="btn btn-secondary" @click="hideModal('modal-portal-trip')">Cancelar</button>
                    <button class="btn btn-primary" v-if="tripWizardStep < 3" @click="nextTripWizardStep">Siguiente</button>
                    <button class="btn btn-primary" v-if="tripWizardStep === 3" :disabled="working" @click="saveTrip">Guardar viaje</button>
                </div>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-portal-advance" tabindex="-1" role="dialog">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Anticipo</h5><button class="close text-white" @click="hideModal('modal-portal-advance')"><span>&times;</span></button></div>
            <div class="modal-body">
                <label>Monto</label><input type="number" step="0.01" class="form-control mb-2" v-model="advanceForm.amount">
                <label>Moneda</label><select class="form-control mb-2" v-model="advanceForm.currency_code"><option v-for="opt in options.currencies" :value="opt.value">{{ opt.value }}</option></select>
                <label>Cuenta banco</label><select class="form-control mb-2" v-model="advanceForm.bank_account_id"><option value="0">Sin cuenta</option><option v-for="opt in options.bank_accounts" :value="toInt(opt.value)">{{ opt.label }}</option></select>
                <label>Fecha solicitud</label><input type="date" class="form-control" v-model="advanceForm.requested_at_date">
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-portal-advance')">Cancelar</button><button class="btn btn-success" :disabled="working" @click="saveAdvance">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-portal-expense" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Gasto</h5><button class="close text-white" @click="hideModal('modal-portal-expense')"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4"><label>Fecha</label><input type="date" class="form-control" v-model="expenseForm.expense_date"></div>
                    <div class="col-md-8"><label>Concepto</label><input class="form-control" v-model.trim="expenseForm.concept"></div>
                    <div class="col-md-6 mt-2"><label>Cuenta contable</label><select class="form-control" v-model="expenseForm.account_id"><option value="0">Selecciona cuenta</option><option v-for="opt in options.accounts" :value="toInt(opt.value)">{{ opt.label }}</option></select></div>
                    <div class="col-md-3 mt-2"><label>Subtotal</label><input type="number" step="0.01" class="form-control" v-model="expenseForm.subtotal"></div>
                    <div class="col-md-3 mt-2"><label>Impuestos</label><input type="number" step="0.01" class="form-control" v-model="expenseForm.tax_total"></div>
                    <div class="col-md-6 mt-2"><label>Proveedor</label><input class="form-control" v-model.trim="expenseForm.vendor_name"></div>
                    <div class="col-md-6 mt-2"><label>Folio comprobante</label><input class="form-control" v-model.trim="expenseForm.document_folio"></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-portal-expense')">Cancelar</button><button class="btn btn-primary" :disabled="working" @click="saveExpense">Guardar</button></div>
        </div></div>
    </div>
</div>

<script src="<?php echo Uri::base(false); ?>assets/js/core-api-client.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        el: '#app-portal-viaticos',
        data: {
            trips: [], advances: [], expenses: [],
            options: { currencies: [], accounts: [], bank_accounts: [] },
            stats: {}, selectedTrip: null, working: false, error: '', message: '',
            tripForm: {}, advanceForm: {}, expenseForm: {},
            statusFilter: 'all', searchText: '', tripWizardStep: 1
        },
        mounted: function () { this.loadData(); },
        computed: {
            selectedAdvances: function () { return this.selectedTrip ? this.advances.filter(a => Number(a.trip_id) === Number(this.selectedTrip.id)) : []; },
            selectedExpenses: function () { return this.selectedTrip ? this.expenses.filter(a => Number(a.trip_id) === Number(this.selectedTrip.id)) : []; },
            filteredTrips: function () {
                var filter = (this.statusFilter || 'all').toLowerCase();
                var search = (this.searchText || '').toLowerCase();
                return this.trips.filter(function (trip) {
                    var okStatus = filter === 'all' || String(trip.status || '').toLowerCase() === filter;
                    if (!okStatus) {
                        return false;
                    }
                    if (!search) {
                        return true;
                    }
                    var haystack = [trip.folio, trip.title, trip.destination].join(' ').toLowerCase();
                    return haystack.indexOf(search) !== -1;
                });
            },
            selectedTripBalanceClass: function () {
                if (!this.selectedTrip) {
                    return '';
                }
                return Number(this.selectedTrip.balance_total || 0) < 0 ? 'text-danger font-weight-bold' : 'text-success font-weight-bold';
            },
            tripProgress: function () {
                return this.tripWizardStep === 1 ? 33 : (this.tripWizardStep === 2 ? 66 : 100);
            }
        },
        methods: {
            apiGet: function (url) { return window.CoreApiClient.get(url).then(r => r.payload || {}); },
            apiPost: function (url, data) { return window.CoreApiClient.post(url, data || {}).then(r => r.payload || {}); },
            loadData: function () {
                var self = this;
                self.error = '';
                self.message = '';
                self.apiGet('<?php echo Uri::create('revendedores/viaticos_data'); ?>').then(function (payload) {
                    if (payload.error) {
                        self.error = payload.error;
                        return;
                    }
                    self.trips = payload.trips || [];
                    self.advances = payload.advances || [];
                    self.expenses = payload.expenses || [];
                    self.options = payload.options || self.options;
                    self.stats = payload.stats || {};
                    if (self.selectedTrip) {
                        self.selectedTrip = self.trips.find(x => Number(x.id) === Number(self.selectedTrip.id)) || null;
                    }
                    if (!self.selectedTrip && self.trips.length > 0) {
                        self.selectedTrip = self.trips[0];
                    }
                }).catch(function (err) { self.error = String(err); });
            },
            selectTrip: function (trip) {
                this.selectedTrip = trip;
                this.message = '';
                this.error = '';
            },
            openTrip: function (trip) {
                this.tripWizardStep = 1;
                this.tripForm = Object.assign({ title: '', destination: '', travel_reason: '', start_date: this.today(), end_date: this.today(), currency_code: 'MXN' }, trip || {});
                this.showModal('modal-portal-trip');
            },
            openTripWizard: function () {
                this.openTrip({});
            },
            nextTripWizardStep: function () {
                if (this.tripWizardStep === 1 && !this.tripForm.title) {
                    this.error = 'Captura el título del viaje.';
                    return;
                }
                if (this.tripWizardStep === 2) {
                    if (!this.tripForm.start_date || !this.tripForm.end_date) {
                        this.error = 'Captura fecha inicio y fecha fin.';
                        return;
                    }
                    if (this.tripForm.end_date < this.tripForm.start_date) {
                        this.error = 'La fecha fin no puede ser menor a la fecha inicio.';
                        return;
                    }
                }
                this.error = '';
                this.tripWizardStep = Math.min(this.tripWizardStep + 1, 3);
            },
            prevTripWizardStep: function () {
                this.error = '';
                this.tripWizardStep = Math.max(this.tripWizardStep - 1, 1);
            },
            saveTrip: function () {
                if (!this.tripForm.title || !this.tripForm.start_date || !this.tripForm.end_date) {
                    this.error = 'Captura título, fecha inicio y fecha fin.';
                    return;
                }
                if (this.tripForm.end_date < this.tripForm.start_date) {
                    this.error = 'La fecha fin no puede ser menor a la fecha inicio.';
                    return;
                }
                this.working = true;
                this.error = '';
                this.message = '';
                this.apiPost('<?php echo Uri::create('revendedores/viaticos_save_trip'); ?>', this.tripForm).then(payload => this.applyResult(payload, 'Viaje guardado.')).finally(() => this.working = false);
            },
            openAdvance: function (item) {
                if (!this.selectedTrip) {
                    this.error = 'Primero selecciona un viaje.';
                    return;
                }
                this.advanceForm = Object.assign({ trip_id: this.selectedTrip.id, amount: 0, currency_code: this.selectedTrip.currency_code, bank_account_id: 0, requested_at_date: this.today(), status: 'draft' }, item || {});
                this.showModal('modal-portal-advance');
            },
            saveAdvance: function () {
                if (!this.selectedTrip) {
                    this.error = 'Primero selecciona un viaje.';
                    return;
                }
                if (Number(this.advanceForm.amount || 0) <= 0) {
                    this.error = 'El anticipo debe ser mayor a cero.';
                    return;
                }
                var payload = Object.assign({}, this.advanceForm, { requested_at: this.advanceForm.requested_at_date });
                this.working = true;
                this.error = '';
                this.message = '';
                this.apiPost('<?php echo Uri::create('revendedores/viaticos_save_advance'); ?>', payload).then(payload => this.applyResult(payload, 'Anticipo guardado.')).finally(() => this.working = false);
            },
            openExpense: function (item) {
                if (!this.selectedTrip) {
                    this.error = 'Primero selecciona un viaje.';
                    return;
                }
                this.expenseForm = Object.assign({ trip_id: this.selectedTrip.id, expense_date: this.today(), concept: '', account_id: 0, subtotal: 0, tax_total: 0, currency_code: this.selectedTrip.currency_code, payment_method: 'cash' }, item || {});
                this.showModal('modal-portal-expense');
            },
            saveExpense: function () {
                if (!this.selectedTrip) {
                    this.error = 'Primero selecciona un viaje.';
                    return;
                }
                if (!this.expenseForm.concept) {
                    this.error = 'Captura el concepto del gasto.';
                    return;
                }
                if (Number(this.expenseForm.account_id || 0) < 1) {
                    this.error = 'Selecciona una cuenta contable.';
                    return;
                }
                var total = Number(this.expenseForm.total || 0) || (Number(this.expenseForm.subtotal || 0) + Number(this.expenseForm.tax_total || 0));
                if (total <= 0) {
                    this.error = 'El gasto debe ser mayor a cero.';
                    return;
                }
                var payload = Object.assign({}, this.expenseForm, { total: total });
                this.working = true;
                this.error = '';
                this.message = '';
                this.apiPost('<?php echo Uri::create('revendedores/viaticos_save_expense'); ?>', payload).then(payload => this.applyResult(payload, 'Gasto guardado.')).finally(() => this.working = false);
            },
            applyResult: function (payload, message) {
                if (payload.error) {
                    this.error = payload.error;
                    return;
                }
                this.error = '';
                this.message = message;
                this.hideModal('modal-portal-trip');
                this.hideModal('modal-portal-advance');
                this.hideModal('modal-portal-expense');
                this.hideModal('modal-portal-quick-actions');
                this.trips = payload.trips || this.trips;
                this.advances = payload.advances || this.advances;
                this.expenses = payload.expenses || this.expenses;
                this.options = payload.options || this.options;
                this.stats = payload.stats || this.stats;
                if (this.selectedTrip) {
                    this.selectedTrip = this.trips.find(x => Number(x.id) === Number(this.selectedTrip.id)) || null;
                }
                if (!this.selectedTrip && this.trips.length > 0) {
                    this.selectedTrip = this.trips[0];
                }
            },
            openQuickActions: function () {
                this.error = '';
                this.message = '';
                this.showModal('modal-portal-quick-actions');
            },
            quickCreateTrip: function () {
                this.hideModal('modal-portal-quick-actions');
                this.openTripWizard();
            },
            quickCreateAdvance: function () {
                this.hideModal('modal-portal-quick-actions');
                this.openAdvance({});
            },
            quickCreateExpense: function () {
                this.hideModal('modal-portal-quick-actions');
                this.openExpense({});
            },
            statusLabel: function (status) {
                var key = String(status || '').toLowerCase();
                if (key === 'draft') { return 'Borrador'; }
                if (key === 'in_progress') { return 'En proceso'; }
                if (key === 'settled') { return 'Liquidado'; }
                if (key === 'cancelled') { return 'Cancelado'; }
                return status || '-';
            },
            showModal: function (id) { $('#'+id).modal('show'); },
            hideModal: function (id) { $('#'+id).modal('hide'); },
            money: function (v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            dateFromTs: function (ts) { return ts ? new Date(ts * 1000).toISOString().slice(0,10) : '-'; },
            today: function () { return new Date().toISOString().slice(0,10); },
            toInt: function (v) { return parseInt(v || 0, 10); }
        }
    });
});
</script>

