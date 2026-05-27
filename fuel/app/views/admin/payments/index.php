<div id="app-payments">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.receivables || 0 }}</h3><p>Cuentas por cobrar</p></div><div class="icon"><i class="bi bi-file-earmark-text"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.payables || 0 }}</h3><p>Cuentas por pagar</p></div><div class="icon"><i class="bi bi-receipt-cutoff"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.movements || 0 }}</h3><p>Movimientos</p></div><div class="icon"><i class="bi bi-bank"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ stats.reconciliation_suggestions || 0 }}</h3><p>Sugerencias</p></div><div class="icon"><i class="bi bi-shuffle"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="message" class="alert alert-info">{{ message }}</div>
    <div class="card card-light mb-3">
        <div class="card-body py-2">
            <div class="row align-items-end">
                <div class="col-md-3"><label>Desde</label><input type="date" class="form-control" v-model="periodFilters.start_date"></div>
                <div class="col-md-3"><label>Hasta</label><input type="date" class="form-control" v-model="periodFilters.end_date"></div>
                <div class="col-md-3"><button class="btn btn-primary" @click="loadData"><i class="bi bi-funnel"></i> Consultar</button></div>
            </div>
        </div>
    </div>

    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">Estados de cuenta bancarios</h3>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Cuenta bancaria</label>
                        <select class="form-control" v-model="statementForm.bank_account_id">
                            <option value="0">Selecciona</option>
                            <option v-for="option in options.bank_accounts" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Archivo CSV/TXT/PDF</label>
                        <input type="file" class="form-control" accept=".csv,.txt,.pdf,text/csv,application/pdf" @change="selectStatementFile">
                    </div>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-info mr-2" @click="importStatement"><i class="bi bi-upload"></i> Importar estado</button>
                    <button class="btn btn-outline-info" @click="suggestReconciliation"><i class="bi bi-shuffle"></i> Sugerir cruces</button>
                </div>
            </div>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Archivo</th><th>Cuenta</th><th>Periodo</th><th>Filas</th><th>Nuevos</th><th>Duplicados</th><th>Carga</th></tr></thead>
                    <tbody>
                        <tr v-for="statement in statement_imports" :key="statement.id">
                            <td>{{ statement.original_name }}</td>
                            <td>{{ label(options.bank_accounts, statement.bank_account_id) }}</td>
                            <td>{{ statement.period_start || '-' }} a {{ statement.period_end || '-' }}</td>
                            <td>{{ statement.rows_count }}</td>
                            <td>{{ statement.imported_count }}</td>
                            <td>{{ statement.duplicate_count }}</td>
                            <td>{{ statement.created_at_label }}</td>
                        </tr>
                        <tr v-if="statement_imports.length === 0"><td colspan="7" class="text-center text-muted">Sin estados de cuenta importados</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">Conciliacion asistida</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Movimiento</th><th>Sugerencia</th><th>Tercero</th><th>Importe</th><th>Confianza</th><th>Motivos</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="suggestion in suggestions" :key="suggestion.id">
                        <td>
                            <strong>{{ suggestion.movement_date }}</strong>
                            <div>{{ suggestion.movement_reference || '-' }}</div>
                            <div class="text-muted small">{{ suggestion.movement_description }}</div>
                        </td>
                        <td>{{ suggestion.entity_label }}</td>
                        <td>{{ suggestion.party_name || label(options.parties, suggestion.party_id) || '-' }}</td>
                        <td>{{ suggestion.currency_code }} {{ money(suggestion.amount) }}</td>
                        <td><span class="badge" :class="suggestion.score >= 85 ? 'badge-success' : 'badge-warning'">{{ suggestion.score }}%</span></td>
                        <td><span v-for="reason in suggestion.reasons" class="badge badge-light mr-1">{{ reason }}</span></td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-success" @click="applySuggestion(suggestion)">
                                <i class="bi bi-check2-circle"></i> Aplicar
                            </button>
                        </td>
                    </tr>
                    <tr v-if="suggestions.length === 0"><td colspan="7" class="text-center text-muted">Sin sugerencias pendientes</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-danger card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">Cuentas por pagar</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Factura proveedor</th><th>Proveedor</th><th>Factura</th><th>Vence</th><th>Total</th><th>Saldo</th><th>Validacion</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="invoice in payables" :key="invoice.id">
                        <td><strong>{{ invoice.folio }}</strong><div class="text-muted small">{{ invoice.uuid || '' }}</div></td>
                        <td>{{ invoice.party_name || label(options.parties, invoice.party_id) || '-' }}</td>
                        <td>{{ invoice.invoice_date || '-' }}</td>
                        <td>{{ invoice.due_date || '-' }}</td>
                        <td>{{ invoice.currency_code }} {{ money(invoice.total) }}</td>
                        <td><strong>{{ invoice.currency_code }} {{ money(invoice.balance_due) }}</strong></td>
                        <td><span class="badge badge-light">{{ invoice.validation_status }}</span></td>
                        <td class="text-center"><button class="btn btn-xs btn-outline-primary" @click="openPayablePayment(invoice)">Registrar pago</button></td>
                    </tr>
                    <tr v-if="payables.length === 0"><td colspan="8" class="text-center text-muted">Sin facturas pendientes de pago</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">Cuentas por cobrar</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Factura</th><th>Cliente</th><th>Emision</th><th>Vence</th><th>Metodo</th><th>Total</th><th>Saldo</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="invoice in receivables" :key="invoice.id">
                        <td><strong>{{ invoice.folio }}</strong><div class="text-muted small">{{ invoice.uuid || '' }}</div></td>
                        <td>{{ invoice.party_name || label(options.parties, invoice.party_id) || '-' }}</td>
                        <td>{{ invoice.issue_date || '-' }}</td>
                        <td>{{ invoice.due_date || '-' }}</td>
                        <td><span class="badge" :class="invoice.sat_payment_method_code === 'PPD' ? 'badge-info' : 'badge-success'">{{ paymentMethodLabel(invoice.sat_payment_method_code) }}</span></td>
                        <td>{{ invoice.currency_code }} {{ money(invoice.total) }}</td>
                        <td><strong>{{ invoice.currency_code }} {{ money(invoice.balance_due) }}</strong></td>
                        <td><span class="badge badge-light">{{ invoice.status }}</span></td>
                        <td class="text-center"><button class="btn btn-xs btn-outline-primary" @click="openReceivablePayment(invoice)">Registrar cobro</button></td>
                    </tr>
                    <tr v-if="receivables.length === 0"><td colspan="9" class="text-center text-muted">Sin facturas pendientes de cobro</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Pagos</h3>
            <button class="btn btn-primary btn-sm ml-auto" @click="openPayment({})"><i class="bi bi-plus-lg"></i> Pago</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Folio</th><th>Tipo</th><th>Tercero</th><th>Fecha</th><th>Importe</th><th>Forma SAT</th><th>Fiscal</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="payment in payments" :key="payment.id">
                        <td><strong>{{ payment.folio }}</strong><div class="text-muted small">{{ payment.reference }}</div></td>
                        <td>{{ payment.payment_type }}</td>
                        <td>{{ label(options.parties, payment.party_id) || '-' }}</td>
                        <td>{{ payment.payment_date }}</td>
                        <td>{{ payment.amount }} {{ payment.currency_code }}</td>
                        <td>{{ label(options.sat_payment_forms, payment.sat_payment_form_code) || payment.sat_payment_form_code }}</td>
                        <td><span class="badge" :class="payment.fiscal_mode === 'fiscal_required' ? 'badge-info' : 'badge-light'">{{ fiscalModeLabel(payment.fiscal_mode) }}</span><div class="small text-muted" v-if="payment.rep_status && payment.rep_status !== 'not_required'">REP {{ repStatusLabel(payment.rep_status) }}</div></td>
                        <td><span class="badge badge-light">{{ payment.status }}</span></td>
                        <td class="text-center"><button class="btn btn-xs btn-outline-primary" @click="openPayment(payment)"><i class="bi bi-pencil"></i></button></td>
                    </tr>
                    <tr v-if="payments.length === 0"><td colspan="9" class="text-center text-muted">Sin pagos</td></tr>
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
                <div class="col-md-4"><div class="form-group"><label>Tratamiento fiscal</label><select class="form-control" v-model="paymentForm.fiscal_mode"><option value="system_only">Solo afectacion sistema</option><option value="fiscal_optional">REP opcional</option><option value="fiscal_required">Generar REP fiscal</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Estado REP</label><select class="form-control" v-model="paymentForm.rep_status"><option value="not_required">No requerido</option><option value="pending">Pendiente</option><option value="prepared">Preparado</option><option value="stamped">Timbrado</option><option value="cancelled">Cancelado</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Referencia</label><input class="form-control" v-model="paymentForm.reference"></div></div>
                <div class="col-md-4"><div class="form-group"><label>ID externo</label><input class="form-control" v-model="paymentForm.external_id"></div></div>
                <div class="col-md-12" v-if="paymentForm.allocation_entity_id"><div class="alert alert-info py-2 mb-2">Este movimiento se aplicara al documento {{ paymentForm.reference }}. CxC/CxP controla saldos; Bancos y Pagos registra el flujo real.</div></div>
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
        data: { error: '', message: '', payments: [], receivables: [], payables: [], movements: [], reconciliations: [], statement_imports: [], suggestions: [], periodFilters: { start_date: '', end_date: '' }, options: { parties: [], bank_accounts: [], currencies: [], sat_payment_forms: [], integrations: [] }, stats: {}, paymentForm: {}, movementForm: {}, statementForm: { bank_account_id: 0, file: null } },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                var url = '<?php echo Uri::create('admin/payments/data'); ?>';
                var params = [];
                if (this.periodFilters.start_date) params.push('start_date=' + encodeURIComponent(this.periodFilters.start_date));
                if (this.periodFilters.end_date) params.push('end_date=' + encodeURIComponent(this.periodFilters.end_date));
                if (params.length) url += '?' + params.join('&');
                fetch(url).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.payments = data.payments || [];
                    this.receivables = data.receivables || [];
                    this.payables = data.payables || [];
                    this.movements = data.movements || [];
                    this.reconciliations = data.reconciliations || [];
                    this.statement_imports = data.statement_imports || [];
                    this.suggestions = data.suggestions || [];
                    this.periodFilters = data.period_filters || this.periodFilters;
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                });
            },
            openPayment(payment) {
                this.paymentForm = Object.assign({ id: 0, payment_type: 'received', party_id: 0, bank_account_id: 0, integration_connection_id: 0, fiscal_document_id: 0, fiscal_mode: 'system_only', rep_status: 'not_required', payment_date: new Date().toISOString().slice(0, 10), currency_code: 'MXN', exchange_rate: 1, amount: 0, sat_payment_form_code: '99', reference: '', external_id: '', status: 'pending', notes: '', allocation_entity_type: '', allocation_entity_id: 0, active: true }, payment);
                this.normalizePaymentForm();
                this.showModal('modal-payment');
            },
            openReceivablePayment(invoice) {
                this.paymentForm = {
                    id: 0,
                    payment_type: 'received',
                    party_id: invoice.party_id || 0,
                    bank_account_id: 0,
                    integration_connection_id: 0,
                    payment_date: new Date().toISOString().slice(0, 10),
                    currency_code: invoice.currency_code || 'MXN',
                    exchange_rate: 1,
                    amount: invoice.balance_due || invoice.total || 0,
                    sat_payment_form_code: invoice.sat_payment_form_code || '03',
                    fiscal_document_id: 0,
                    fiscal_mode: invoice.sat_payment_method_code === 'PPD' ? 'fiscal_required' : 'system_only',
                    rep_status: invoice.sat_payment_method_code === 'PPD' ? 'pending' : 'not_required',
                    reference: invoice.folio || '',
                    external_id: invoice.uuid || '',
                    status: 'confirmed',
                    notes: invoice.sat_payment_method_code === 'PPD' ? 'Cobro de factura PPD. Revisar complemento de pago.' : 'Cobro de factura PUE.',
                    allocation_entity_type: 'billing_invoice',
                    allocation_entity_id: invoice.id,
                    active: true
                };
                this.normalizePaymentForm();
                this.showModal('modal-payment');
            },
            openPayablePayment(invoice) {
                this.paymentForm = {
                    id: 0,
                    payment_type: 'sent',
                    party_id: invoice.party_id || 0,
                    bank_account_id: 0,
                    integration_connection_id: 0,
                    payment_date: new Date().toISOString().slice(0, 10),
                    currency_code: invoice.currency_code || 'MXN',
                    exchange_rate: 1,
                    amount: invoice.balance_due || invoice.total || 0,
                    sat_payment_form_code: '03',
                    fiscal_document_id: 0,
                    fiscal_mode: 'system_only',
                    rep_status: 'not_required',
                    reference: invoice.folio || '',
                    external_id: invoice.uuid || '',
                    status: 'confirmed',
                    notes: 'Pago a proveedor aplicado desde Bancos y Pagos.',
                    allocation_entity_type: 'purchase_invoice',
                    allocation_entity_id: invoice.id,
                    active: true
                };
                this.normalizePaymentForm();
                this.showModal('modal-payment');
            },
            normalizePaymentForm() {
                this.paymentForm.party_id = String(this.paymentForm.party_id || 0);
                this.paymentForm.bank_account_id = String(this.paymentForm.bank_account_id || 0);
                this.paymentForm.integration_connection_id = String(this.paymentForm.integration_connection_id || 0);
                this.paymentForm.fiscal_document_id = String(this.paymentForm.fiscal_document_id || 0);
                this.paymentForm.allocation_entity_id = String(this.paymentForm.allocation_entity_id || 0);
            },
            openMovement(movement) {
                this.movementForm = Object.assign({ id: 0, bank_account_id: 0, movement_date: new Date().toISOString().slice(0, 10), movement_type: 'deposit', amount: 0, balance_after: 0, currency_code: 'MXN', reference: '', description: '', source: 'manual', statement_import_id: 0, checksum: '', source_row_json: '', payment_id: 0, reconciled: false, active: true }, movement);
                this.showModal('modal-movement');
            },
            savePayment() {
                fetch('<?php echo Uri::create('admin/payments/save_payment'); ?>', window.coreAppFetchOptions(this.paymentForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.payments = data.payments || [];
                    this.receivables = data.receivables || this.receivables;
                    this.payables = data.payables || this.payables;
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
            selectStatementFile(event) {
                this.statementForm.file = event.target.files[0] || null;
            },
            importStatement() {
                this.error = '';
                this.message = '';
                if (!this.statementForm.bank_account_id || this.statementForm.bank_account_id == 0) { this.error = 'Selecciona una cuenta bancaria.'; return; }
                if (!this.statementForm.file) { this.error = 'Selecciona un CSV, TXT o PDF de estado de cuenta.'; return; }
                const form = new FormData();
                form.append('bank_account_id', this.statementForm.bank_account_id);
                form.append('file', this.statementForm.file);
                form.append(window.coreAppCsrfKey, fuel_csrf_token());
                fetch('<?php echo Uri::create('admin/payments/import_statement'); ?>', { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-Token': fuel_csrf_token() }, body: form }).then(window.coreAppParseJsonResponse).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.message = data.message || 'Estado importado.';
                    this.movements = data.movements || this.movements;
                    this.statement_imports = data.statement_imports || [];
                    this.suggestions = data.suggestions || [];
                    this.stats = data.stats || this.stats;
                    this.statementForm.file = null;
                });
            },
            suggestReconciliation() {
                this.error = '';
                this.message = '';
                fetch('<?php echo Uri::create('admin/payments/suggest_reconciliation'); ?>', window.coreAppFetchOptions({})).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.message = data.message || 'Sugerencias actualizadas.';
                    this.suggestions = data.suggestions || [];
                    this.stats = data.stats || this.stats;
                });
            },
            applySuggestion(suggestion) {
                if (!confirm('Aplicar esta conciliacion?')) return;
                this.error = '';
                this.message = '';
                fetch('<?php echo Uri::create('admin/payments/apply_suggestion'); ?>', window.coreAppFetchOptions({ id: suggestion.id })).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.message = data.message || 'Movimiento conciliado.';
                    this.payments = data.payments || this.payments;
                    this.receivables = data.receivables || this.receivables;
                    this.payables = data.payables || this.payables;
                    this.movements = data.movements || this.movements;
                    this.suggestions = data.suggestions || [];
                    this.stats = data.stats || this.stats;
                });
            },
            label(options, value) {
                const found = (options || []).find(option => String(option.value) === String(value));
                return found ? found.label : '';
            },
            paymentMethodLabel(value) {
                return ({ PUE: 'PUE - Pago en una sola exhibicion', PPD: 'PPD - Pago en parcialidades o diferido' })[value] || value || '-';
            },
            money(value) {
                return Number(value || 0).toFixed(2);
            },
            fiscalModeLabel(value) {
                return ({ system_only: 'Sistema', fiscal_optional: 'Opcional', fiscal_required: 'Fiscal' })[value] || value || 'Sistema';
            },
            repStatusLabel(value) {
                return ({ not_required: 'no requerido', pending: 'pendiente', prepared: 'preparado', stamped: 'timbrado', cancelled: 'cancelado' })[value] || value;
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
