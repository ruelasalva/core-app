<div id="app-config" v-cloak>
    <style>
        [v-cloak] { display: none; }
        .config-check-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .35rem .75rem; }
        .config-month-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(52px, 1fr)); gap: .35rem; }
        .config-chip { display: inline-flex; align-items: center; gap: .35rem; margin: .15rem; padding: .25rem .5rem; border: 1px solid #ced4da; border-radius: .25rem; background: #f8f9fa; }
    </style>

    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-primary">
                <div class="inner"><h3>{{ company.name || '-' }}</h3><p>Empresa</p></div>
                <div class="icon"><i class="bi bi-building"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ company.rfc || '-' }}</h3><p>RFC fiscal</p></div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ departments.length }}</h3><p>Departamentos</p></div>
                <div class="icon"><i class="bi bi-diagram-3"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ groups.length }}</h3><p>Grupos</p></div>
                <div class="icon"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-company" role="tab"><i class="bi bi-building mr-1"></i> Empresa</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-fiscal" role="tab"><i class="bi bi-receipt-cutoff mr-1"></i> Fiscal</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-operations" role="tab"><i class="bi bi-sliders mr-1"></i> Operacion</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-payments" role="tab"><i class="bi bi-cash-coin mr-1"></i> Pagos</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-departments" role="tab"><i class="bi bi-diagram-3 mr-1"></i> Departamentos</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-security" role="tab"><i class="bi bi-shield-lock mr-1"></i> Seguridad</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-integrations" role="tab"><i class="bi bi-plug mr-1"></i> Integraciones</a></li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando configuracion...</p>
            </div>

            <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-company" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Datos generales</h5>
                            <p class="text-muted mb-0">Informacion comercial y administrativa de la empresa activa.</p>
                        </div>
                        <button class="btn btn-primary btn-sm" @click="saveCompany"><i class="bi bi-save"></i> Guardar</button>
                    </div>

                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Nombre comercial</label><input class="form-control" v-model="company.name"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Telefono</label><input class="form-control" v-model="company.contact_phone"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Correo general</label><input class="form-control" type="email" v-model="company.contact_email"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Archivo de politicas</label><input class="form-control" v-model="company.policy_file"></div></div>
                        <div class="col-md-12"><div class="form-group"><label>Aviso general</label><textarea class="form-control" rows="3" v-model="company.announcement_message"></textarea></div></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-fiscal" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Configuracion fiscal</h5>
                            <p class="text-muted mb-0">Datos usados por SAT, CFDI y el motor fiscal.</p>
                        </div>
                        <button class="btn btn-primary btn-sm" @click="saveCompany"><i class="bi bi-save"></i> Guardar</button>
                    </div>

                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>RFC</label><input class="form-control text-uppercase" v-model="company.rfc"></div></div>
                        <div class="col-md-5"><div class="form-group"><label>Razon social</label><input class="form-control" v-model="company.legal_name"></div></div>
                        <div class="col-md-2"><div class="form-group"><label>Codigo postal SAT</label><input class="form-control" v-model="company.postal_code"></div></div>
                        <div class="col-md-2"><div class="form-group"><label>Correo fiscal</label><input class="form-control" type="email" v-model="company.contact_email"></div></div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Regimen fiscal SAT</label>
                                <select class="form-control" v-model="company.tax_regime_id">
                                    <option value="">Selecciona regimen fiscal</option>
                                    <option v-for="option in options.sat_tax_regimes" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-operations" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Reglas operativas</h5>
                            <p class="text-muted mb-0">Recepcion de documentos y controles de operacion.</p>
                        </div>
                        <button class="btn btn-primary btn-sm" @click="saveCompany"><i class="bi bi-save"></i> Guardar</button>
                    </div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label>Dias de recepcion de facturas</label>
                                <div class="config-check-grid">
                                    <div class="custom-control custom-checkbox" v-for="day in options.weekdays" :key="'receive-'+day.value">
                                        <input type="checkbox" class="custom-control-input" :id="'receive-'+day.value" :value="day.value" v-model="company.invoice_receive_days">
                                        <label class="custom-control-label" :for="'receive-'+day.value">{{ day.label }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3"><div class="form-group"><label>Hora limite de recepcion</label><input class="form-control" type="time" v-model="company.invoice_receive_limit_time"></div></div>
                        <div class="col-md-2">
                            <div class="custom-control custom-switch mt-4">
                                <input type="checkbox" class="custom-control-input" id="blocked-reception" v-model="company.blocked_reception">
                                <label class="custom-control-label" for="blocked-reception">Bloquear recepcion</label>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-warning">
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="allow-negative-sales" v-model="operations.allow_negative_inventory_sales">
                                <label class="custom-control-label" for="allow-negative-sales">Permitir entregas con inventario negativo</label>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Este control se guarda en configuracion operativa y no modifica reglas fiscales.</p>
                            <button class="btn btn-outline-primary btn-sm mt-3" @click="saveOperations"><i class="bi bi-save"></i> Guardar regla operativa</button>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-payments" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Reglas de pagos</h5>
                            <p class="text-muted mb-0">Agenda base para programacion de pagos y proveedores.</p>
                        </div>
                        <button class="btn btn-primary btn-sm" @click="saveCompany"><i class="bi bi-save"></i> Guardar</button>
                    </div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label>Dias de pago</label>
                                <div class="config-check-grid">
                                    <div class="custom-control custom-checkbox" v-for="day in options.weekdays" :key="'payment-'+day.value">
                                        <input type="checkbox" class="custom-control-input" :id="'payment-'+day.value" :value="day.value" v-model="company.payment_days">
                                        <label class="custom-control-label" :for="'payment-'+day.value">{{ day.label }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Frecuencia de pago</label>
                                <select class="form-control" v-model="company.payment_frequency">
                                    <option value="">Selecciona frecuencia</option>
                                    <option v-for="option in options.payment_frequencies" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2"><div class="form-group"><label>Plazo en dias</label><input class="form-control" type="number" min="0" v-model="company.payment_terms_days"></div></div>
                    </div>

                    <div class="form-group">
                        <label>Dias del mes para pago</label>
                        <div class="config-month-grid">
                            <div class="custom-control custom-checkbox" v-for="day in options.month_days" :key="'month-'+day">
                                <input type="checkbox" class="custom-control-input" :id="'month-'+day" :value="String(day)" v-model="company.payment_days_of_month">
                                <label class="custom-control-label" :for="'month-'+day">{{ day }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Dias inhabiles</label>
                        <div class="input-group mb-2">
                            <input class="form-control" type="date" v-model="holidayInput">
                            <div class="input-group-append"><button class="btn btn-outline-primary" type="button" @click="addHoliday">Agregar</button></div>
                        </div>
                        <div>
                            <span class="config-chip" v-for="date in company.holidays" :key="date">
                                {{ date }} <button type="button" class="close" @click="removeHoliday(date)">&times;</button>
                            </span>
                            <span v-if="company.holidays.length === 0" class="text-muted">Sin dias inhabiles capturados.</span>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        La sincronizacion de dias inhabiles y tipo de cambio se administrara despues desde Catalogos economicos/fiscales.
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-departments" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Estructura organizacional</h5>
                        <button class="btn btn-primary btn-sm" @click="newDepartment"><i class="bi bi-plus-lg"></i> Nuevo</button>
                    </div>

                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Nombre</th><th>Clave</th><th>Descripcion</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                        <tbody>
                            <tr v-for="department in departments" :key="department.id">
                                <td>{{ department.name }}</td>
                                <td><code>{{ department.slug }}</code></td>
                                <td>{{ department.description || '-' }}</td>
                                <td><span class="badge" :class="department.active == 1 ? 'badge-success' : 'badge-secondary'">{{ department.active == 1 ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-center"><button class="btn btn-xs btn-warning" @click="editDepartment(department)"><i class="fas fa-edit"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-security" role="tabpanel">
                    <h5>Seguridad y accesos</h5>
                    <p class="text-muted">Los grupos y backends se conservan aqui como referencia. La asignacion fina se administra en usuarios, permisos y portales.</p>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-outline card-secondary">
                                <div class="card-header"><h3 class="card-title mb-0">Grupos</h3></div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <tbody><tr v-for="group in groups" :key="group.id"><td><span class="badge badge-dark">{{ group.id }}</span></td><td>{{ group.name }}</td><td>{{ groupPurpose(group.id) }}</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-outline card-secondary">
                                <div class="card-header d-flex align-items-center"><h3 class="card-title mb-0">Backends</h3><button class="btn btn-primary btn-sm ml-auto" @click="newBackend"><i class="bi bi-plus-lg"></i> Nuevo</button></div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr v-for="backend in backends" :key="backend.id">
                                                <td><code>{{ backend.code }}</code></td><td>{{ backend.name }}</td><td>{{ backend.base_route || '-' }}</td>
                                                <td><button class="btn btn-xs btn-warning" @click="editBackend(backend)"><i class="fas fa-edit"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-integrations" role="tabpanel">
                    <h5>Integraciones relacionadas</h5>
                    <p class="text-muted">Este apartado concentra accesos rapidos. La configuracion tecnica permanece en sus modulos especializados.</p>
                    <div class="row">
                        <div class="col-md-4"><a class="btn btn-outline-primary btn-block" href="<?php echo Uri::create('admin/sat'); ?>"><i class="bi bi-receipt"></i> SAT y CFDI</a></div>
                        <div class="col-md-4"><a class="btn btn-outline-primary btn-block" href="<?php echo Uri::create('admin/integrations'); ?>"><i class="bi bi-plug"></i> Integraciones</a></div>
                        <div class="col-md-4"><a class="btn btn-outline-primary btn-block" href="<?php echo Uri::create('admin/communications'); ?>"><i class="bi bi-envelope"></i> Correos y avisos</a></div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        La sincronizacion de dias inhabiles y tipo de cambio se administrara despues desde Catalogos economicos/fiscales.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-department" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ departmentForm.id ? 'Editar' : 'Nuevo' }} Departamento</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-department')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Nombre</label><input class="form-control" v-model="departmentForm.name"></div>
                    <div class="form-group"><label>Descripcion</label><input class="form-control" v-model="departmentForm.description"></div>
                    <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="department-active" v-model="departmentForm.active"><label class="custom-control-label" for="department-active">Activo</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-department')">Cerrar</button><button class="btn btn-primary" @click="saveDepartment">Guardar</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-backend" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ backendForm.id ? 'Editar' : 'Nuevo' }} Backend</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-backend')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Codigo</label><input class="form-control" v-model="backendForm.code"></div>
                    <div class="form-group"><label>Nombre</label><input class="form-control" v-model="backendForm.name"></div>
                    <div class="form-group"><label>Ruta base</label><input class="form-control" v-model="backendForm.base_route"></div>
                    <div class="form-group"><label>Descripcion</label><input class="form-control" v-model="backendForm.description"></div>
                    <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="backend-active" v-model="backendForm.active"><label class="custom-control-label" for="backend-active">Activo</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-backend')">Cerrar</button><button class="btn btn-primary" @click="saveBackend">Guardar</button></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#app-config',
        data: {
            loading: true,
            errorMessage: '',
            company: {
                id: null, name: '', legal_name: '', rfc: '', postal_code: '', tax_regime_id: '',
                contact_email: '', contact_phone: '', invoice_receive_days: [], invoice_receive_limit_time: '',
                payment_days: [], payment_terms_days: null, payment_frequency: '', payment_days_of_month: [],
                announcement_message: '', blocked_reception: false, holidays: [], policy_file: ''
            },
            options: { sat_tax_regimes: [], weekdays: [], payment_frequencies: [], month_days: [] },
            departments: [],
            backends: [],
            groups: [],
            operations: { allow_negative_inventory_sales: false },
            departmentForm: { id: null, name: '', description: '', active: true },
            backendForm: { id: null, code: '', name: '', base_route: '', description: '', active: true },
            holidayInput: ''
        },
        mounted: function() {
            this.loadData();
        },
        methods: {
            loadData: function() {
                this.loading = true;
                this.errorMessage = '';
                fetch('<?php echo Uri::create('admin/config/data'); ?>')
                    .then(window.coreAppParseJsonResponse)
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            this.errorMessage = data.error;
                            return;
                        }
                        this.options = data.options || this.options;
                        this.company = this.prepareCompany(data.company || this.company);
                        this.departments = data.departments || [];
                        this.backends = data.backends || [];
                        this.groups = data.groups || [];
                        this.operations = data.operations || this.operations;
                        this.operations.allow_negative_inventory_sales = this.operations.allow_negative_inventory_sales == 1;
                    });
            },
            prepareCompany: function(company) {
                company = Object.assign({}, this.company, company || {});
                company.blocked_reception = company.blocked_reception == 1 || company.blocked_reception === true;
                company.tax_regime_id = company.tax_regime_id ? String(company.tax_regime_id) : '';
                company.invoice_receive_days = this.csvToArray(company.invoice_receive_days, true);
                company.payment_days = this.csvToArray(company.payment_days, true);
                company.payment_days_of_month = this.csvToArray(company.payment_days_of_month, false);
                company.holidays = this.csvToArray(company.holidays, false);
                return company;
            },
            csvToArray: function(value, normalizeWeekdays) {
                if (Array.isArray(value)) {
                    return value.map(String).filter(Boolean);
                }
                const map = {
                    lunes: 'monday', monday: 'monday',
                    martes: 'tuesday', tuesday: 'tuesday',
                    miercoles: 'wednesday', 'miércoles': 'wednesday', wednesday: 'wednesday',
                    jueves: 'thursday', thursday: 'thursday',
                    viernes: 'friday', friday: 'friday',
                    sabado: 'saturday', 'sábado': 'saturday', saturday: 'saturday',
                    domingo: 'sunday', sunday: 'sunday'
                };
                return String(value || '').split(',').map(item => item.trim()).filter(Boolean).map(item => {
                    const key = item.toLowerCase();
                    return normalizeWeekdays && map[key] ? map[key] : item;
                });
            },
            savePayload: function() {
                const payload = Object.assign({}, this.company);
                payload.invoice_receive_days = (payload.invoice_receive_days || []).join(',');
                payload.payment_days = (payload.payment_days || []).join(',');
                payload.payment_days_of_month = (payload.payment_days_of_month || []).join(',');
                payload.holidays = (payload.holidays || []).join(',');
                return payload;
            },
            groupPurpose: function(id) {
                const purposes = { 100: 'Administrador General', 90: 'Administrador de Configuracion', 80: 'Administrador de Finanzas', 70: 'Administrador de Compras', 60: 'Administrador de Ventas', 50: 'Gerente', 40: 'Supervisor', 25: 'Operador', 15: 'Portal Externo', 5: 'Consulta' };
                return purposes[id] || 'Grupo operativo';
            },
            saveCompany: function() {
                fetch('<?php echo Uri::create('admin/config/save_company'); ?>', {
                    ...window.coreAppFetchOptions(this.savePayload())
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) {
                        this.errorMessage = data.error;
                        return;
                    }
                    this.company = this.prepareCompany(data.company || this.company);
                    this.errorMessage = '';
                });
            },
            saveOperations: function() {
                fetch('<?php echo Uri::create('admin/config/save_operations'); ?>', {
                    ...window.coreAppFetchOptions(this.operations)
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) {
                        this.errorMessage = data.error;
                        return;
                    }
                    this.operations = data.operations || this.operations;
                    this.operations.allow_negative_inventory_sales = this.operations.allow_negative_inventory_sales == 1;
                    this.errorMessage = '';
                });
            },
            addHoliday: function() {
                if (!this.holidayInput) return;
                if (!this.company.holidays.includes(this.holidayInput)) {
                    this.company.holidays.push(this.holidayInput);
                    this.company.holidays.sort();
                }
                this.holidayInput = '';
            },
            removeHoliday: function(date) {
                this.company.holidays = this.company.holidays.filter(item => item !== date);
            },
            newDepartment: function() {
                this.departmentForm = { id: null, name: '', description: '', active: true };
                this.showModal('modal-department');
            },
            editDepartment: function(department) {
                this.departmentForm = { id: department.id, name: department.name, description: department.description || '', active: department.active == 1 };
                this.showModal('modal-department');
            },
            saveDepartment: function() {
                fetch('<?php echo Uri::create('admin/config/save_department'); ?>', {
                    ...window.coreAppFetchOptions(this.departmentForm)
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) {
                        this.errorMessage = data.error;
                        return;
                    }
                    this.departments = data.departments || [];
                    this.hideModal('modal-department');
                });
            },
            newBackend: function() {
                this.backendForm = { id: null, code: '', name: '', base_route: '', description: '', active: true };
                this.showModal('modal-backend');
            },
            editBackend: function(backend) {
                this.backendForm = { id: backend.id, code: backend.code, name: backend.name, base_route: backend.base_route || '', description: backend.description || '', active: backend.active == 1 };
                this.showModal('modal-backend');
            },
            saveBackend: function() {
                fetch('<?php echo Uri::create('admin/config/save_backend'); ?>', {
                    ...window.coreAppFetchOptions(this.backendForm)
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) {
                        this.errorMessage = data.error;
                        return;
                    }
                    this.backends = data.backends || [];
                    this.hideModal('modal-backend');
                });
            },
            showModal: function(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(element).show();
                    return;
                }
                if (window.jQuery && $.fn.modal) $('#' + id).modal('show');
            },
            hideModal: function(id) {
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
});
</script>
