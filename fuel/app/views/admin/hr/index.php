<div id="app-hr">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.employees || 0 }}</h3><p>Empleados</p></div><div class="icon"><i class="bi bi-person-badge"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.active_payroll || 0 }}</h3><p>En nomina</p></div><div class="icon"><i class="bi bi-cash-stack"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.periods_open || 0 }}</h3><p>Periodos abiertos</p></div><div class="icon"><i class="bi bi-calendar-range"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ money(stats.net_pending || 0) }}</h3><p>Neto pendiente</p></div><div class="icon"><i class="bi bi-bank"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'employees'}" @click.prevent="tab = 'employees'">Empleados</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'periods'}" @click.prevent="tab = 'periods'">Periodos</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'runs'}" @click.prevent="tab = 'runs'">Nomina</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-show="tab === 'employees'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Expediente laboral</h3>
                    <div>
                        <button class="btn btn-primary btn-sm" @click="openEmployee({})"><i class="bi bi-plus-lg"></i> Empleado</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="employees-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>No.</th><th>Nombre</th><th>RFC</th><th>CURP</th><th>NSS</th><th>Departamento</th><th>Puesto</th><th>Salario diario</th><th>Nomina</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="employee in employees" :key="employee.id">
                                <td><strong>{{ employee.employee_number }}</strong></td>
                                <td>{{ employee.full_name }}<div class="text-muted small" v-if="employee.username">Usuario: {{ employee.username }}</div></td>
                                <td>{{ employee.rfc || '-' }}</td>
                                <td>{{ employee.curp || '-' }}</td>
                                <td>{{ employee.nss || '-' }}</td>
                                <td>{{ employee.department_name || '-' }}</td>
                                <td>{{ employee.position || '-' }}</td>
                                <td>{{ money(employee.salary_daily) }}</td>
                                <td><span class="badge" :class="employee.payroll_status === 'active' ? 'badge-success' : 'badge-secondary'">{{ payrollStatusLabel(employee.payroll_status) }}</span></td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openEmployee(employee)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="employees.length === 0"><td colspan="10" class="text-center text-muted">Sin empleados registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'periods'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Periodos de nomina</h3>
                    <div>
                        <button class="btn btn-primary btn-sm" @click="openPeriod({})"><i class="bi bi-plus-lg"></i> Periodo</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="periods-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Codigo</th><th>Nombre</th><th>Tipo</th><th>Desde</th><th>Hasta</th><th>Pago</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="period in periods" :key="period.id">
                                <td><strong>{{ period.code }}</strong></td>
                                <td>{{ period.name }}</td>
                                <td>{{ period.period_type }}</td>
                                <td>{{ period.date_from }}</td>
                                <td>{{ period.date_to }}</td>
                                <td>{{ period.payment_date || '-' }}</td>
                                <td><span class="badge" :class="period.status === 'open' ? 'badge-success' : 'badge-secondary'">{{ periodStatusLabel(period.status) }}</span></td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openPeriod(period)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="periods.length === 0"><td colspan="8" class="text-center text-muted">Sin periodos.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'runs'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Corridas de nomina</h3>
                    <div>
                        <button class="btn btn-primary btn-sm" @click="openRun({})"><i class="bi bi-plus-lg"></i> Corrida</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="runs-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Folio</th><th>Periodo</th><th>Departamento</th><th>Tipo</th><th>Percepciones</th><th>Deducciones</th><th>Neto</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <tr v-for="run in runs" :key="run.id" :class="selectedRun && selectedRun.id == run.id ? 'table-info' : ''">
                                <td><strong>{{ run.folio }}</strong></td>
                                <td>{{ run.period_name || '-' }}</td>
                                <td>{{ run.department_name || 'Todos' }}</td>
                                <td>{{ run.run_type }}</td>
                                <td>{{ money(run.perception_total) }}</td>
                                <td>{{ money(run.deduction_total) }}</td>
                                <td>{{ money(run.net_total) }}</td>
                                <td><span class="badge" :class="run.status === 'paid' ? 'badge-success' : 'badge-warning'">{{ runStatusLabel(run.status) }}</span></td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="selectRun(run)">Detalle</button> <button class="btn btn-xs btn-outline-secondary" @click="openRun(run)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="runs.length === 0"><td colspan="9" class="text-center text-muted">Sin corridas.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="card card-secondary card-outline mt-3" v-if="selectedRun">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title h6 mb-0">Detalle de {{ selectedRun.folio }}</h3>
                        <button class="btn btn-secondary btn-sm ml-auto" @click="openItem({ run_id: selectedRun.id })"><i class="bi bi-plus"></i> Empleado</button>
                    </div>
                    <div class="card-body table-responsive">
                        <table id="items-table" class="table table-sm table-bordered">
                            <thead><tr><th>Empleado</th><th>RFC</th><th>Dias</th><th>Percepciones</th><th>Deducciones</th><th>Neto</th><th>CFDI</th><th>Pago</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id">
                                    <td>{{ item.employee_name }}</td>
                                    <td>{{ item.employee_rfc || '-' }}</td>
                                    <td>{{ item.days_paid }}</td>
                                    <td>{{ money(item.perception_total) }}</td>
                                    <td>{{ money(item.deduction_total) }}</td>
                                    <td>{{ money(item.net_total) }}</td>
                                    <td><span class="badge badge-info">{{ item.sat_status }}</span><div class="text-muted small">{{ item.cfdi_uuid || '-' }}</div></td>
                                    <td><span class="badge badge-secondary">{{ item.payment_status }}</span><div class="text-muted small">{{ item.payment_folio || '-' }}</div></td>
                                    <td><button class="btn btn-xs btn-outline-primary" @click="openItem(item)"><i class="bi bi-pencil"></i></button></td>
                                </tr>
                                <tr v-if="items.length === 0"><td colspan="9" class="text-center text-muted">Sin empleados en esta corrida.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-employee" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Empleado</h5><button class="close text-white" @click="hideModal('modal-employee')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-3"><label>No. empleado</label><input class="form-control" v-model="employeeForm.employee_number" placeholder="Automatico"></div>
                <div class="col-md-5"><label>Nombre completo</label><input class="form-control" v-model="employeeForm.full_name"></div>
                <div class="col-md-4"><label>Correo</label><input type="email" class="form-control" v-model="employeeForm.email"></div>
                <div class="col-md-3 mt-2"><label>RFC</label><input class="form-control text-uppercase" v-model="employeeForm.rfc"></div>
                <div class="col-md-3 mt-2"><label>CURP</label><input class="form-control text-uppercase" v-model="employeeForm.curp"></div>
                <div class="col-md-3 mt-2"><label>NSS</label><input class="form-control" v-model="employeeForm.nss"></div>
                <div class="col-md-3 mt-2"><label>Puesto</label><input class="form-control" v-model="employeeForm.position"></div>
                <div class="col-md-3 mt-2"><label>Usuario</label><select class="form-control" v-model="employeeForm.user_id"><option value="0">Sin usuario</option><option v-for="u in options.users" :value="u.value">{{ u.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Departamento</label><select class="form-control" v-model="employeeForm.department_id"><option value="0">Sin departamento</option><option v-for="d in options.departments" :value="d.value">{{ d.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Sucursal</label><select class="form-control" v-model="employeeForm.branch_id"><option value="0">Sin sucursal</option><option v-for="b in options.branches" :value="b.value">{{ b.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Cuenta de pago</label><select class="form-control" v-model="employeeForm.bank_account_id"><option value="0">Sin cuenta</option><option v-for="b in options.bank_accounts" :value="b.value">{{ b.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Alta</label><input type="date" class="form-control" v-model="employeeForm.hire_date"></div>
                <div class="col-md-3 mt-2"><label>Baja</label><input type="date" class="form-control" v-model="employeeForm.termination_date"></div>
                <div class="col-md-3 mt-2"><label>Estado nomina</label><select class="form-control" v-model="employeeForm.payroll_status"><option value="active">Activo</option><option value="inactive">Inactivo</option><option value="suspended">Suspendido</option><option value="terminated">Baja</option></select></div>
                <div class="col-md-3 mt-2"><label>Frecuencia</label><select class="form-control" v-model="employeeForm.payment_frequency"><option value="semanal">Semanal</option><option value="quincenal">Quincenal</option><option value="mensual">Mensual</option></select></div>
                <div class="col-md-3 mt-2"><label>Salario diario</label><input type="number" step="0.01" class="form-control" v-model.number="employeeForm.salary_daily"></div>
                <div class="col-md-3 mt-2"><label>Salario integrado</label><input type="number" step="0.01" class="form-control" v-model.number="employeeForm.salary_integrated"></div>
                <div class="col-md-3 mt-2"><label>Regimen SAT nomina</label><input class="form-control" v-model="employeeForm.sat_regime_code"></div>
                <div class="col-md-3 mt-2"><label>Contrato</label><select class="form-control" v-model="employeeForm.contract_type"><option value="indefinido">Indefinido</option><option value="determinado">Determinado</option><option value="obra">Obra</option><option value="prueba">Prueba</option></select></div>
                <div class="col-md-3 mt-2"><label>Jornada</label><select class="form-control" v-model="employeeForm.work_shift"><option value="diurna">Diurna</option><option value="nocturna">Nocturna</option><option value="mixta">Mixta</option></select></div>
                <div class="col-md-3 mt-2"><label>Riesgo</label><input class="form-control" v-model="employeeForm.risk_class"></div>
                <div class="col-md-12 mt-3"><label><input type="checkbox" v-model="employeeForm.active"> Activo en RH</label></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-employee')">Cerrar</button><button class="btn btn-primary" @click="saveEmployee">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-period" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Periodo de nomina</h5><button class="close text-white" @click="hideModal('modal-period')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-5"><label>Nombre</label><input class="form-control" v-model="periodForm.name"></div>
                <div class="col-md-3"><label>Tipo</label><select class="form-control" v-model="periodForm.period_type"><option value="semanal">Semanal</option><option value="quincenal">Quincenal</option><option value="mensual">Mensual</option></select></div>
                <div class="col-md-2"><label>Desde</label><input type="date" class="form-control" v-model="periodForm.date_from"></div>
                <div class="col-md-2"><label>Hasta</label><input type="date" class="form-control" v-model="periodForm.date_to"></div>
                <div class="col-md-3 mt-2"><label>Fecha pago</label><input type="date" class="form-control" v-model="periodForm.payment_date"></div>
                <div class="col-md-3 mt-2"><label>Estado</label><select class="form-control" v-model="periodForm.status"><option value="open">Abierto</option><option value="closed">Cerrado</option><option value="cancelled">Cancelado</option></select></div>
                <div class="col-md-6 mt-2"><label>Notas</label><input class="form-control" v-model="periodForm.notes"></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-period')">Cerrar</button><button class="btn btn-primary" @click="savePeriod">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-run" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Corrida de nomina</h5><button class="close text-white" @click="hideModal('modal-run')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><label>Periodo</label><select class="form-control" v-model="runForm.period_id"><option value="0">Selecciona</option><option v-for="p in options.periods" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-4"><label>Departamento</label><select class="form-control" v-model="runForm.department_id"><option value="0">Todos</option><option v-for="d in options.departments" :value="d.value">{{ d.label }}</option></select></div>
                <div class="col-md-4"><label>Tipo</label><select class="form-control" v-model="runForm.run_type"><option value="ordinary">Ordinaria</option><option value="extraordinary">Extraordinaria</option><option value="settlement">Finiquito</option></select></div>
                <div class="col-md-4 mt-2"><label>Estado</label><select class="form-control" v-model="runForm.status"><option value="draft">Borrador</option><option value="calculated">Calculada</option><option value="stamped">Timbrada</option><option value="paid">Pagada</option><option value="cancelled">Cancelada</option></select></div>
                <div class="col-md-4 mt-2"><label>Lote de pago</label><input class="form-control" v-model.number="runForm.payment_batch_id"></div>
                <div class="col-md-4 mt-2"><label>Poliza contable</label><input class="form-control" v-model.number="runForm.accounting_entry_id"></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-run')">Cerrar</button><button class="btn btn-primary" @click="saveRun">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-secondary text-white"><h5 class="modal-title">Empleado en nomina</h5><button class="close text-white" @click="hideModal('modal-item')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6"><label>Empleado</label><select class="form-control" v-model="itemForm.employee_id"><option value="0">Selecciona</option><option v-for="e in options.employees" :value="e.value">{{ e.label }}</option></select></div>
                <div class="col-md-2"><label>Dias</label><input type="number" step="0.01" class="form-control" v-model.number="itemForm.days_paid"></div>
                <div class="col-md-2"><label>Percepciones</label><input type="number" step="0.01" class="form-control" v-model.number="itemForm.perception_total"></div>
                <div class="col-md-2"><label>Deducciones</label><input type="number" step="0.01" class="form-control" v-model.number="itemForm.deduction_total"></div>
                <div class="col-md-6 mt-2"><label>CFDI nomina</label><select class="form-control" v-model="itemForm.cfdi_id"><option value="0">Sin CFDI</option><option v-for="c in options.cfdi_payroll" :value="c.value">{{ c.label }}</option></select></div>
                <div class="col-md-6 mt-2"><label>Pago banco</label><select class="form-control" v-model="itemForm.payment_id"><option value="0">Sin pago</option><option v-for="p in options.payments" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Estado SAT</label><select class="form-control" v-model="itemForm.sat_status"><option value="pending">Pendiente</option><option value="stamped">Timbrado</option><option value="cancelled">Cancelado</option></select></div>
                <div class="col-md-4 mt-2"><label>Estado pago</label><select class="form-control" v-model="itemForm.payment_status"><option value="pending">Pendiente</option><option value="scheduled">Programado</option><option value="paid">Pagado</option></select></div>
                <div class="col-md-4 mt-2"><label>Documento fiscal ID</label><input class="form-control" v-model.number="itemForm.fiscal_document_id"></div>
                <div class="col-md-12 mt-2"><label>Notas</label><input class="form-control" v-model="itemForm.notes"></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-item')">Cerrar</button><button class="btn btn-primary" @click="saveItem">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-hr',
        data: { tab: 'employees', error: '', employees: [], periods: [], runs: [], items: [], options: {}, stats: {}, selectedRun: null, employeeForm: {}, periodForm: {}, runForm: {}, itemForm: {} },
        mounted: function() { this.load(); },
        methods: {
            load: function(runId) {
                var url = '<?php echo Uri::create('admin/hr/data'); ?>';
                if (runId) url += '?run_id=' + runId;
                fetch(url).then(function(r) { return r.json(); }).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.employees = data.employees || [];
                    this.periods = data.periods || [];
                    this.runs = data.runs || [];
                    this.items = data.items || [];
                    this.options = data.options || {};
                    this.stats = data.stats || {};
                });
            },
            selectRun: function(run) { this.selectedRun = run; this.load(run.id); },
            openEmployee: function(item) { this.employeeForm = Object.assign({ id: 0, user_id: 0, party_id: 0, department_id: 0, branch_id: 0, employee_number: '', full_name: '', email: '', rfc: '', curp: '', nss: '', position: '', hire_date: '', termination_date: '', payroll_status: 'active', salary_daily: 0, salary_integrated: 0, payment_frequency: 'quincenal', bank_account_id: 0, sat_regime_code: '02', contract_type: 'indefinido', work_shift: 'diurna', risk_class: '', active: true }, item); this.showModal('modal-employee'); },
            openPeriod: function(item) { this.periodForm = Object.assign({ id: 0, name: '', period_type: 'quincenal', date_from: '', date_to: '', payment_date: '', status: 'open', notes: '', active: true }, item); this.showModal('modal-period'); },
            openRun: function(item) { this.runForm = Object.assign({ id: 0, period_id: 0, department_id: 0, run_type: 'ordinary', status: 'draft', currency_code: 'MXN', payment_batch_id: 0, accounting_entry_id: 0, active: true }, item); this.showModal('modal-run'); },
            openItem: function(item) { this.itemForm = Object.assign({ id: 0, run_id: this.selectedRun ? this.selectedRun.id : 0, employee_id: 0, cfdi_id: 0, fiscal_document_id: 0, payment_id: 0, days_paid: 15, perception_total: 0, deduction_total: 0, sat_status: 'pending', payment_status: 'pending', notes: '', active: true }, item); this.showModal('modal-item'); },
            saveEmployee: function() { this.post('save_employee', this.employeeForm, 'modal-employee'); },
            savePeriod: function() { this.post('save_period', this.periodForm, 'modal-period'); },
            saveRun: function() { this.post('save_run', this.runForm, 'modal-run'); },
            saveItem: function() { this.post('save_item', this.itemForm, 'modal-item', this.itemForm.run_id); },
            post: function(action, payload, modal, runId) {
                fetch('<?php echo Uri::create('admin/hr'); ?>/' + action, window.coreAppFetchOptions(payload)).then(function(r) { return r.json(); }).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    if (data.employees) this.employees = data.employees;
                    if (data.periods) this.periods = data.periods;
                    if (data.runs) this.runs = data.runs;
                    if (data.items) this.items = data.items;
                    if (data.options) this.options = data.options;
                    if (data.stats) this.stats = data.stats;
                    if (runId) {
                        var current = this.runs.find(r => String(r.id) === String(runId));
                        if (current) this.selectedRun = current;
                    }
                    if (modal) this.hideModal(modal);
                });
            },
            payrollStatusLabel: function(v) { return ({active:'Activo', inactive:'Inactivo', suspended:'Suspendido', terminated:'Baja'})[v] || v; },
            periodStatusLabel: function(v) { return ({open:'Abierto', closed:'Cerrado', cancelled:'Cancelado'})[v] || v; },
            runStatusLabel: function(v) { return ({draft:'Borrador', calculated:'Calculada', stamped:'Timbrada', paid:'Pagada', cancelled:'Cancelada'})[v] || v; },
            money: function(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            showModal: function(id) { $('#' + id).modal('show'); },
            hideModal: function(id) { $('#' + id).modal('hide'); }
        }
    });
};
</script>
