<div id="app-config">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ company.name || '-' }}</h3>
                    <p>Empresa</p>
                </div>
                <div class="icon"><i class="bi bi-building"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ departments.length }}</h3>
                    <p>Departamentos</p>
                </div>
                <div class="icon"><i class="bi bi-diagram-3"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ groups.length }}</h3>
                    <p>Grupos de acceso</p>
                </div>
                <div class="icon"><i class="bi bi-shield-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ backends.length }}</h3>
                    <p>Backends ERP</p>
                </div>
                <div class="icon"><i class="bi bi-window-sidebar"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-company" role="tab">
                        <i class="bi bi-building mr-1"></i> Empresa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-departments" role="tab">
                        <i class="bi bi-diagram-3 mr-1"></i> Departamentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-groups" role="tab">
                        <i class="bi bi-shield-lock mr-1"></i> Grupos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-backends" role="tab">
                        <i class="bi bi-window-sidebar mr-1"></i> Backends
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-operations" role="tab">
                        <i class="bi bi-sliders mr-1"></i> Operacion
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-model" role="tab">
                        <i class="bi bi-layers mr-1"></i> Modelo
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando configuracion...</p>
            </div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-company" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Datos generales de la empresa</h5>
                        <button class="btn btn-primary btn-sm" @click="saveCompany">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre comercial</label>
                                <input class="form-control" v-model="company.name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Razon social</label>
                                <input class="form-control" v-model="company.legal_name">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>RFC</label>
                                <input class="form-control text-uppercase" v-model="company.rfc">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Codigo postal</label>
                                <input class="form-control" v-model="company.postal_code">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Regimen SAT</label>
                                <input class="form-control" type="number" v-model="company.tax_regime_id">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Telefono</label>
                                <input class="form-control" v-model="company.contact_phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Correo de contacto</label>
                                <input class="form-control" type="email" v-model="company.contact_email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Archivo de politicas</label>
                                <input class="form-control" v-model="company.policy_file">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6>Operacion de proveedores y facturas</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dias de recepcion de facturas</label>
                                <input class="form-control" v-model="company.invoice_receive_days" placeholder="lunes,martes">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Hora limite de recepcion</label>
                                <input class="form-control" type="time" v-model="company.invoice_receive_limit_time">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dias de pago</label>
                                <input class="form-control" v-model="company.payment_days" placeholder="viernes">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Plazo de pago en dias</label>
                                <input class="form-control" type="number" min="0" v-model="company.payment_terms_days">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Frecuencia de pago</label>
                                <input class="form-control" v-model="company.payment_frequency" placeholder="semanal, quincenal, mensual">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dias del mes para pago</label>
                                <input class="form-control" v-model="company.payment_days_of_month" placeholder="15,30">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dias inhábiles</label>
                                <input class="form-control" v-model="company.holidays" placeholder="2026-01-01,2026-12-25">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-switch mt-4">
                                <input type="checkbox" class="custom-control-input" id="blocked-reception" v-model="company.blocked_reception">
                                <label class="custom-control-label" for="blocked-reception">Bloquear recepcion</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Aviso general</label>
                                <textarea class="form-control" rows="3" v-model="company.announcement_message"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-departments" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Estructura organizacional</h5>
                        <button class="btn btn-primary btn-sm" @click="newDepartment">
                            <i class="bi bi-plus-lg"></i> Nuevo
                        </button>
                    </div>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Clave</th>
                                <th>Descripcion</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="department in departments" :key="department.id">
                                <td>{{ department.name }}</td>
                                <td><code>{{ department.slug }}</code></td>
                                <td>{{ department.description || '-' }}</td>
                                <td>
                                    <span class="badge" :class="department.active == 1 ? 'badge-success' : 'badge-secondary'">
                                        {{ department.active == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-warning" @click="editDepartment(department)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-groups" role="tabpanel">
                    <h5>Grupos de acceso</h5>
                    <p class="text-muted">Los grupos controlan permisos del sistema. Su asignacion detallada se administra en Roles y Permisos.</p>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Nivel</th>
                                <th>Grupo</th>
                                <th>Uso recomendado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="group in groups" :key="group.id">
                                <td><span class="badge badge-dark">{{ group.id }}</span></td>
                                <td>{{ group.name }}</td>
                                <td>{{ groupPurpose(group.id) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-backends" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Areas operativas del ERP</h5>
                        <button class="btn btn-primary btn-sm" @click="newBackend">
                            <i class="bi bi-plus-lg"></i> Nuevo
                        </button>
                    </div>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Backend</th>
                                <th>Ruta base</th>
                                <th>Descripcion</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="backend in backends" :key="backend.id">
                                <td><code>{{ backend.code }}</code></td>
                                <td>{{ backend.name }}</td>
                                <td>{{ backend.base_route || '-' }}</td>
                                <td>{{ backend.description || '-' }}</td>
                                <td>
                                    <span class="badge" :class="backend.active == 1 ? 'badge-success' : 'badge-secondary'">
                                        {{ backend.active == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-warning" @click="editBackend(backend)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-operations" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Reglas operativas</h5>
                            <p class="text-muted mb-0">Controles transversales para ventas, entregas, inventario y facturacion.</p>
                        </div>
                        <button class="btn btn-primary btn-sm" @click="saveOperations">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>

                    <div class="card card-outline card-warning">
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="allow-negative-sales" v-model="operations.allow_negative_inventory_sales">
                                <label class="custom-control-label" for="allow-negative-sales">Permitir entregas con inventario negativo</label>
                            </div>
                            <p class="text-muted small mt-2 mb-0">
                                Si esta apagado, Ventas no podra surtir cantidades mayores a la existencia disponible del almacen seleccionado.
                                Si esta encendido, la entrega se registra y el saldo del almacen puede quedar negativo para resolver despues con entradas o ajustes.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-model" role="tabpanel">
                    <h5>Jerarquia base</h5>
                    <ol class="mb-0">
                        <li>Empresa</li>
                        <li>Sucursales</li>
                        <li>Departamentos</li>
                        <li>Empleados</li>
                        <li>Usuarios</li>
                        <li>Grupos</li>
                        <li>Backends</li>
                        <li>Modulos</li>
                        <li>Permisos</li>
                        <li>Logs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-department" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ departmentForm.id ? 'Editar' : 'Nuevo' }} Departamento</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-department')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input class="form-control" v-model="departmentForm.name">
                    </div>
                    <div class="form-group">
                        <label>Descripcion</label>
                        <input class="form-control" v-model="departmentForm.description">
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="department-active" v-model="departmentForm.active">
                        <label class="custom-control-label" for="department-active">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-department')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveDepartment">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-backend" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ backendForm.id ? 'Editar' : 'Nuevo' }} Backend</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-backend')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Codigo</label>
                        <input class="form-control" v-model="backendForm.code">
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input class="form-control" v-model="backendForm.name">
                    </div>
                    <div class="form-group">
                        <label>Ruta base</label>
                        <input class="form-control" v-model="backendForm.base_route">
                    </div>
                    <div class="form-group">
                        <label>Descripcion</label>
                        <input class="form-control" v-model="backendForm.description">
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="backend-active" v-model="backendForm.active">
                        <label class="custom-control-label" for="backend-active">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-backend')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveBackend">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-config',
        data: {
            loading: true,
            company: {
                id: null,
                name: '',
                legal_name: '',
                rfc: '',
                postal_code: '',
                tax_regime_id: null,
                contact_email: '',
                contact_phone: '',
                invoice_receive_days: '',
                invoice_receive_limit_time: '',
                payment_days: '',
                payment_terms_days: null,
                payment_frequency: '',
                payment_days_of_month: '',
                announcement_message: '',
                blocked_reception: false,
                holidays: '',
                policy_file: ''
            },
            departments: [],
            backends: [],
            groups: [],
            operations: { allow_negative_inventory_sales: false },
            departmentForm: { id: null, name: '', description: '', active: true },
            backendForm: { id: null, code: '', name: '', base_route: '', description: '', active: true }
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/config/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.company = data.company || this.company;
                        this.company.blocked_reception = this.company.blocked_reception == 1;
                        this.departments = data.departments || [];
                        this.backends = data.backends || [];
                        this.groups = data.groups || [];
                        this.operations = data.operations || this.operations;
                        this.operations.allow_negative_inventory_sales = this.operations.allow_negative_inventory_sales == 1;
                    });
            },
            groupPurpose(id) {
                const purposes = {
                    100: 'Administrador General',
                    90: 'Administrador de Configuracion',
                    80: 'Administrador de Finanzas',
                    70: 'Administrador de Compras',
                    60: 'Administrador de Ventas',
                    50: 'Gerente',
                    40: 'Supervisor',
                    25: 'Operador',
                    15: 'Portal Externo',
                    5: 'Consulta'
                };
                return purposes[id] || 'Grupo operativo';
            },
            saveCompany() {
                fetch('<?php echo Uri::create('admin/config/save_company'); ?>', {
                    ...window.coreAppFetchOptions(this.company)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.company = data.company || this.company;
                    this.company.blocked_reception = this.company.blocked_reception == 1;
                });
            },
            saveOperations() {
                fetch('<?php echo Uri::create('admin/config/save_operations'); ?>', {
                    ...window.coreAppFetchOptions(this.operations)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.operations = data.operations || this.operations;
                    this.operations.allow_negative_inventory_sales = this.operations.allow_negative_inventory_sales == 1;
                });
            },
            newDepartment() {
                this.departmentForm = { id: null, name: '', description: '', active: true };
                this.showModal('modal-department');
            },
            editDepartment(department) {
                this.departmentForm = {
                    id: department.id,
                    name: department.name,
                    description: department.description || '',
                    active: department.active == 1
                };
                this.showModal('modal-department');
            },
            saveDepartment() {
                fetch('<?php echo Uri::create('admin/config/save_department'); ?>', {
                    ...window.coreAppFetchOptions(this.departmentForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.departments = data.departments || [];
                    this.hideModal('modal-department');
                });
            },
            newBackend() {
                this.backendForm = { id: null, code: '', name: '', base_route: '', description: '', active: true };
                this.showModal('modal-backend');
            },
            editBackend(backend) {
                this.backendForm = {
                    id: backend.id,
                    code: backend.code,
                    name: backend.name,
                    base_route: backend.base_route || '',
                    description: backend.description || '',
                    active: backend.active == 1
                };
                this.showModal('modal-backend');
            },
            saveBackend() {
                fetch('<?php echo Uri::create('admin/config/save_backend'); ?>', {
                    ...window.coreAppFetchOptions(this.backendForm)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.backends = data.backends || [];
                    this.hideModal('modal-backend');
                });
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(element).show();
                    return;
                }
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
