        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title mb-0">Contratos</h3>
                    <div class="ml-auto">
                        <button v-if="permissions.create" class="btn btn-primary btn-sm" @click="openForm()"><i class="bi bi-plus-circle"></i> Nuevo contrato</button>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="message" class="alert alert-success">{{ message }}</div>
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    <div v-if="options.contract_type_catalog_empty" class="alert alert-warning">
                        No hay tipos de contrato configurados. Ejecuta <code>php oil refine contractsseed</code>.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small mb-1">Estado</label>
                            <select class="form-control form-control-sm" v-model="filters.status">
                                <option value="all">Todos</option>
                                <option v-for="status in options.statuses" :value="status.value">{{ status.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small mb-1">Tipo</label>
                            <select class="form-control form-control-sm" v-model="filters.contract_type">
                                <option value="all">Todos</option>
                                <option v-for="type in options.contract_types" :value="type.value">{{ type.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small mb-1">Vencimiento</label>
                            <select class="form-control form-control-sm" v-model="filters.expiration">
                                <option v-for="option in options.expiration_filters" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Numero</th>
                                    <th>Tipo</th>
                                    <th>Tercero</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="contract in filteredContracts" :key="contract.id" :class="{ 'table-primary': selected && selected.id === contract.id }">
                                    <td><strong>{{ contract.contract_number }}</strong><div class="text-muted small">{{ contract.title }}</div></td>
                                    <td>{{ contract.contract_type_label || contract.contract_type }}</td>
                                    <td>{{ contract.party_name || '-' }}</td>
                                    <td>
                                        {{ contract.end_date || '-' }}
                                        <div><span class="badge" :class="expirationClass(contract.expiration_status)">{{ contract.expiration_label }}</span></div>
                                        <div class="text-muted small">{{ contract.expiration_days_label }}</div>
                                    </td>
                                    <td><span class="badge" :class="statusClass(contract.status)">{{ contract.status_label }}</span></td>
                                    <td>
                                        <button class="btn btn-outline-secondary btn-xs" @click="selectContract(contract)">Detalle</button>
                                        <button v-if="permissions.edit" class="btn btn-outline-primary btn-xs" @click="openForm(contract)">Editar</button>
                                        <select v-if="permissions.status" class="form-control form-control-sm mt-1" v-model="contract.next_status" @change="changeStatus(contract)">
                                            <option value="">Cambiar estado</option>
                                            <option v-for="status in options.statuses" :value="status.value">{{ status.label }}</option>
                                        </select>
                                        <span v-if="!permissions.edit && !permissions.status" class="text-muted small">Solo lectura</span>
                                    </td>
                                </tr>
                                <tr v-if="filteredContracts.length === 0">
                                    <td colspan="6" class="text-center text-muted">Sin contratos registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
