<div id="app-accounting">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.accounts || 0 }}</h3><p>Cuentas</p></div><div class="icon"><i class="bi bi-diagram-3"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ stats.entries || 0 }}</h3><p>Polizas</p></div><div class="icon"><i class="bi bi-journal-text"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.periods_open || 0 }}</h3><p>Periodos abiertos</p></div><div class="icon"><i class="bi bi-calendar-check"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.cost_centers || 0 }}</h3><p>Centros costo</p></div><div class="icon"><i class="bi bi-bullseye"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'entries'}" @click.prevent="tab = 'entries'">Polizas</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'accounts'}" @click.prevent="tab = 'accounts'">Catalogo de cuentas</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'periods'}" @click.prevent="tab = 'periods'">Periodos</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'cost_centers'}" @click.prevent="tab = 'cost_centers'">Centros de costo</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'rules'}" @click.prevent="tab = 'rules'">Reglas</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-show="tab === 'entries'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Polizas contables</h3>
                    <button class="btn btn-primary btn-sm" @click="openEntry({})"><i class="bi bi-plus-lg"></i> Nueva poliza</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Folio</th><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Debe</th><th>Haber</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <tr v-for="entry in entries" :key="entry.id" :class="selectedEntry && selectedEntry.id == entry.id ? 'table-info' : ''">
                                <td><strong>{{ entry.folio }}</strong><div class="text-muted small">{{ entry.description }}</div></td>
                                <td>{{ entry.entry_date }}</td>
                                <td>{{ entry.entry_type }}</td>
                                <td>{{ entry.source_module }} <span v-if="entry.source_entity_id">#{{ entry.source_entity_id }}</span></td>
                                <td>{{ money(entry.total_debit) }}</td>
                                <td>{{ money(entry.total_credit) }}</td>
                                <td><span class="badge" :class="entry.status === 'posted' ? 'badge-success' : 'badge-warning'">{{ statusLabel(entry.status) }}</span></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary" @click="selectEntry(entry)">Partidas</button>
                                    <button class="btn btn-xs btn-outline-secondary" @click="openEntry(entry)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-xs btn-success" v-if="entry.status !== 'posted'" @click="postEntry(entry)">Contabilizar</button>
                                </td>
                            </tr>
                            <tr v-if="entries.length === 0"><td colspan="8" class="text-center text-muted">Sin polizas registradas.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="card card-secondary card-outline mt-3" v-if="selectedEntry">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title h6 mb-0">Partidas de {{ selectedEntry.folio }}</h3>
                        <button class="btn btn-secondary btn-sm ml-auto" @click="openLine({ entry_id: selectedEntry.id })"><i class="bi bi-plus"></i> Partida</button>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Cuenta</th><th>Tercero</th><th>Centro costo</th><th>Descripcion</th><th>Debe</th><th>Haber</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="line in lines" :key="line.id">
                                    <td>{{ line.account_code }} - {{ line.account_name }}</td>
                                    <td>{{ line.party_name || '-' }}</td>
                                    <td>{{ line.cost_center_code ? line.cost_center_code + ' - ' + line.cost_center_name : '-' }}</td>
                                    <td>{{ line.description }}</td>
                                    <td>{{ money(line.debit) }}</td>
                                    <td>{{ money(line.credit) }}</td>
                                    <td><button class="btn btn-xs btn-outline-secondary" @click="openLine(line)"><i class="bi bi-pencil"></i></button></td>
                                </tr>
                                <tr v-if="lines.length === 0"><td colspan="7" class="text-center text-muted">Sin partidas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-show="tab === 'accounts'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Catalogo de cuentas</h3>
                    <button class="btn btn-primary btn-sm" @click="openAccount({})"><i class="bi bi-plus-lg"></i> Cuenta</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Codigo</th><th>Nombre</th><th>Tipo</th><th>Naturaleza</th><th>Auxiliares</th><th>Activa</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="account in accounts" :key="account.id">
                                <td><strong>{{ account.code }}</strong></td>
                                <td>{{ account.name }}</td>
                                <td>{{ accountTypeLabel(account.account_type) }}</td>
                                <td>{{ natureLabel(account.nature) }}</td>
                                <td><span v-if="account.requires_party == 1" class="badge badge-info">Tercero</span> <span v-if="account.requires_cost_center == 1" class="badge badge-secondary">Centro costo</span></td>
                                <td>{{ account.active == 1 ? 'Si' : 'No' }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openAccount(account)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'periods'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Periodos contables</h3>
                    <button class="btn btn-primary btn-sm" @click="openPeriod({})"><i class="bi bi-plus-lg"></i> Periodo</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Periodo</th><th>Ejercicio</th><th>Fechas</th><th>Estado</th><th>Permisos</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="period in periods" :key="period.id">
                                <td><strong>{{ period.period_key }}</strong><div class="text-muted small">{{ period.name }}</div></td>
                                <td>{{ period.fiscal_year_code || '-' }}</td>
                                <td>{{ period.start_date }} a {{ period.end_date }}</td>
                                <td><span class="badge" :class="period.locked == 1 || period.status === 'closed' ? 'badge-danger' : 'badge-success'">{{ periodStatusLabel(period.status) }}{{ period.locked == 1 ? ' / Bloqueado' : '' }}</span></td>
                                <td>
                                    <span class="badge" :class="period.allow_manual_entries == 1 ? 'badge-info' : 'badge-secondary'">Manual</span>
                                    <span class="badge" :class="period.allow_operational_posting == 1 ? 'badge-info' : 'badge-secondary'">Operativo</span>
                                </td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openPeriod(period)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="periods.length === 0"><td colspan="6" class="text-center text-muted">Sin periodos.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'cost_centers'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Centros de costo</h3>
                    <button class="btn btn-primary btn-sm" @click="openCostCenter({})"><i class="bi bi-plus-lg"></i> Centro</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Codigo</th><th>Nombre</th><th>Tipo</th><th>Departamento</th><th>Sucursal</th><th>Presupuesto</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="center in costCenters" :key="center.id">
                                <td><strong>{{ center.code }}</strong></td>
                                <td>{{ center.name }}</td>
                                <td>{{ costCenterTypeLabel(center.center_type) }}</td>
                                <td>{{ center.department_name || '-' }}</td>
                                <td>{{ center.branch_name || '-' }}</td>
                                <td>{{ money(center.budget_amount) }} {{ center.currency_code }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openCostCenter(center)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="costCenters.length === 0"><td colspan="7" class="text-center text-muted">Sin centros de costo.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'rules'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Reglas de contabilizacion</h3>
                    <button class="btn btn-primary btn-sm" @click="openRule({})"><i class="bi bi-plus-lg"></i> Regla</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Codigo</th><th>Evento</th><th>Debe</th><th>Haber</th><th>Importe</th><th>Auto</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="rule in rules" :key="rule.id">
                                <td><strong>{{ rule.rule_code }}</strong><div class="text-muted small">{{ rule.name }}</div></td>
                                <td>{{ rule.source_module }} / {{ rule.source_event }}</td>
                                <td>{{ rule.debit_code }} - {{ rule.debit_name }}</td>
                                <td>{{ rule.credit_code }} - {{ rule.credit_name }}</td>
                                <td>{{ rule.amount_source }}</td>
                                <td>{{ rule.auto_post == 1 ? 'Si' : 'No' }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openRule(rule)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="rules.length === 0"><td colspan="7" class="text-center text-muted">Sin reglas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-account" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Cuenta contable</h5><button class="close text-white" @click="hideModal('modal-account')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-3"><label>Codigo</label><input class="form-control" v-model="accountForm.code"></div>
                <div class="col-md-6"><label>Nombre</label><input class="form-control" v-model="accountForm.name"></div>
                <div class="col-md-3"><label>Tipo</label><select class="form-control" v-model="accountForm.account_type"><option value="asset">Activo</option><option value="liability">Pasivo</option><option value="equity">Capital</option><option value="income">Ingreso</option><option value="expense">Gasto</option><option value="cost">Costo</option></select></div>
                <div class="col-md-3 mt-2"><label>Cuenta padre</label><select class="form-control" v-model="accountForm.parent_id"><option value="0">Sin padre</option><option v-for="a in accounts" :value="a.id">{{ a.code }} - {{ a.name }}</option></select></div>
                <div class="col-md-2 mt-2"><label>Nivel</label><input type="number" class="form-control" v-model="accountForm.level"></div>
                <div class="col-md-3 mt-2"><label>Naturaleza</label><select class="form-control" v-model="accountForm.nature"><option value="debit">Deudora</option><option value="credit">Acreedora</option></select></div>
                <div class="col-md-2 mt-2"><label>Moneda</label><select class="form-control" v-model="accountForm.currency_code"><option v-for="c in options.currencies" :value="c.value">{{ c.value }}</option></select></div>
                <div class="col-md-2 mt-2"><label>SAT</label><input class="form-control" v-model="accountForm.sat_group_code"></div>
                <div class="col-md-12 mt-3">
                    <label class="mr-3"><input type="checkbox" v-model="accountForm.requires_party"> Requiere tercero</label>
                    <label class="mr-3"><input type="checkbox" v-model="accountForm.requires_cost_center"> Requiere centro de costo</label>
                    <label class="mr-3"><input type="checkbox" v-model="accountForm.is_postable"> Acepta movimientos</label>
                    <label><input type="checkbox" v-model="accountForm.active"> Activa</label>
                </div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-account')">Cerrar</button><button class="btn btn-primary" @click="saveAccount">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-entry" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Poliza</h5><button class="close text-white" @click="hideModal('modal-entry')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-3"><label>Tipo</label><select class="form-control" v-model="entryForm.entry_type"><option value="diario">Diario</option><option value="ingreso">Ingreso</option><option value="egreso">Egreso</option><option value="venta">Venta</option><option value="compra">Compra</option><option value="inventario">Inventario</option></select></div>
                <div class="col-md-3"><label>Fecha</label><input type="date" class="form-control" v-model="entryForm.entry_date"></div>
                <div class="col-md-3"><label>Estado</label><select class="form-control" v-model="entryForm.status"><option value="draft">Borrador</option><option value="posted">Contabilizada</option><option value="cancelled">Cancelada</option></select></div>
                <div class="col-md-3"><label>Origen</label><input class="form-control" v-model="entryForm.source_module"></div>
                <div class="col-md-12 mt-2"><label>Descripcion</label><input class="form-control" v-model="entryForm.description"></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-entry')">Cerrar</button><button class="btn btn-primary" @click="saveEntry">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-line" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-secondary text-white"><h5 class="modal-title">Partida contable</h5><button class="close text-white" @click="hideModal('modal-line')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6"><label>Cuenta</label><select class="form-control" v-model="lineForm.account_id"><option value="0">Selecciona</option><option v-for="a in options.accounts" :value="a.value">{{ a.label }}</option></select></div>
                <div class="col-md-6"><label>Tercero</label><select class="form-control" v-model="lineForm.party_id"><option value="0">Sin tercero</option><option v-for="p in options.parties" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-6 mt-2"><label>Centro de costo</label><select class="form-control" v-model="lineForm.cost_center_id"><option value="0">Sin centro</option><option v-for="cc in options.cost_centers" :value="cc.value">{{ cc.label }}</option></select></div>
                <div class="col-md-6 mt-2"><label>Departamento</label><select class="form-control" v-model="lineForm.department_id"><option value="0">Sin departamento</option><option v-for="d in options.departments" :value="d.value">{{ d.label }}</option></select></div>
                <div class="col-md-12 mt-2"><label>Descripcion</label><input class="form-control" v-model="lineForm.description"></div>
                <div class="col-md-4 mt-2"><label>Debe</label><input type="number" step="0.01" class="form-control" v-model="lineForm.debit"></div>
                <div class="col-md-4 mt-2"><label>Haber</label><input type="number" step="0.01" class="form-control" v-model="lineForm.credit"></div>
                <div class="col-md-4 mt-2"><label>Moneda</label><select class="form-control" v-model="lineForm.currency_code"><option v-for="c in options.currencies" :value="c.value">{{ c.value }}</option></select></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-line')">Cerrar</button><button class="btn btn-primary" @click="saveLine">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-period" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Periodo contable</h5><button class="close text-white" @click="hideModal('modal-period')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-3"><label>Periodo</label><input class="form-control" v-model="periodForm.period_key" placeholder="2026-05"></div>
                <div class="col-md-3"><label>Ejercicio</label><select class="form-control" v-model="periodForm.fiscal_year_id"><option value="0">Sin ejercicio</option><option v-for="y in options.fiscal_years" :value="y.value">{{ y.label }}</option></select></div>
                <div class="col-md-6"><label>Nombre</label><input class="form-control" v-model="periodForm.name"></div>
                <div class="col-md-3 mt-2"><label>Inicio</label><input type="date" class="form-control" v-model="periodForm.start_date"></div>
                <div class="col-md-3 mt-2"><label>Fin</label><input type="date" class="form-control" v-model="periodForm.end_date"></div>
                <div class="col-md-3 mt-2"><label>Estado</label><select class="form-control" v-model="periodForm.status"><option value="open">Abierto</option><option value="soft_closed">Precerrado</option><option value="closed">Cerrado</option></select></div>
                <div class="col-md-12 mt-3">
                    <label class="mr-3"><input type="checkbox" v-model="periodForm.allow_manual_entries"> Permite manuales</label>
                    <label class="mr-3"><input type="checkbox" v-model="periodForm.allow_operational_posting"> Permite operativas</label>
                    <label class="mr-3"><input type="checkbox" v-model="periodForm.locked"> Bloqueado</label>
                    <label><input type="checkbox" v-model="periodForm.active"> Activo</label>
                </div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-period')">Cerrar</button><button class="btn btn-primary" @click="savePeriod">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-cost-center" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Centro de costo</h5><button class="close text-white" @click="hideModal('modal-cost-center')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-3"><label>Codigo</label><input class="form-control" v-model="costCenterForm.code"></div>
                <div class="col-md-6"><label>Nombre</label><input class="form-control" v-model="costCenterForm.name"></div>
                <div class="col-md-3"><label>Tipo</label><select class="form-control" v-model="costCenterForm.center_type"><option value="department">Departamento</option><option value="branch">Sucursal</option><option value="project">Proyecto</option><option value="sales_channel">Canal venta</option><option value="other">Otro</option></select></div>
                <div class="col-md-4 mt-2"><label>Padre</label><select class="form-control" v-model="costCenterForm.parent_id"><option value="0">Sin padre</option><option v-for="cc in costCenters" :value="cc.id">{{ cc.code }} - {{ cc.name }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Departamento</label><select class="form-control" v-model="costCenterForm.department_id"><option value="0">Sin departamento</option><option v-for="d in options.departments" :value="d.value">{{ d.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Sucursal</label><select class="form-control" v-model="costCenterForm.branch_id"><option value="0">Sin sucursal</option><option v-for="b in options.branches" :value="b.value">{{ b.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Presupuesto</label><input type="number" step="0.01" class="form-control" v-model="costCenterForm.budget_amount"></div>
                <div class="col-md-2 mt-2"><label>Moneda</label><select class="form-control" v-model="costCenterForm.currency_code"><option v-for="c in options.currencies" :value="c.value">{{ c.value }}</option></select></div>
                <div class="col-md-6 mt-2"><label>Notas</label><input class="form-control" v-model="costCenterForm.notes"></div>
                <div class="col-md-12 mt-3"><label><input type="checkbox" v-model="costCenterForm.active"> Activo</label></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-cost-center')">Cerrar</button><button class="btn btn-primary" @click="saveCostCenter">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-rule" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Regla de contabilizacion</h5><button class="close text-white" @click="hideModal('modal-rule')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><label>Codigo</label><input class="form-control" v-model="ruleForm.rule_code"></div>
                <div class="col-md-8"><label>Nombre</label><input class="form-control" v-model="ruleForm.name"></div>
                <div class="col-md-4 mt-2"><label>Modulo</label><input class="form-control" v-model="ruleForm.source_module" placeholder="billing, purchases, payments"></div>
                <div class="col-md-4 mt-2"><label>Evento</label><input class="form-control" v-model="ruleForm.source_event" placeholder="invoice_posted"></div>
                <div class="col-md-4 mt-2"><label>Importe</label><select class="form-control" v-model="ruleForm.amount_source"><option value="total">Total</option><option value="subtotal">Subtotal</option><option value="tax">Impuesto</option><option value="retention">Retencion</option><option value="cost">Costo</option></select></div>
                <div class="col-md-6 mt-2"><label>Debe</label><select class="form-control" v-model="ruleForm.debit_account_id"><option value="0">Selecciona</option><option v-for="a in options.accounts" :value="a.value">{{ a.label }}</option></select></div>
                <div class="col-md-6 mt-2"><label>Haber</label><select class="form-control" v-model="ruleForm.credit_account_id"><option value="0">Selecciona</option><option v-for="a in options.accounts" :value="a.value">{{ a.label }}</option></select></div>
                <div class="col-md-12 mt-3">
                    <label class="mr-3"><input type="checkbox" v-model="ruleForm.requires_party"> Requiere tercero</label>
                    <label class="mr-3"><input type="checkbox" v-model="ruleForm.auto_post"> Contabilizar automatico</label>
                </div>
                <div class="col-md-12 mt-2"><label>Notas</label><input class="form-control" v-model="ruleForm.notes"></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-rule')">Cerrar</button><button class="btn btn-primary" @click="saveRule">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-accounting',
        data: { tab: 'entries', error: '', accounts: [], fiscalYears: [], periods: [], costCenters: [], entries: [], lines: [], rules: [], options: { accounts: [], parties: [], currencies: [], departments: [], branches: [], cost_centers: [], fiscal_years: [] }, stats: {}, selectedEntry: null, accountForm: {}, entryForm: {}, lineForm: {}, periodForm: {}, costCenterForm: {}, ruleForm: {} },
        mounted: function() { this.load(); },
        methods: {
            load: function(entryId) {
                var url = '<?php echo Uri::create('admin/accounting/data'); ?>';
                if (entryId) url += '?entry_id=' + entryId;
                fetch(url).then(function(r) { return r.json(); }).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.accounts = data.accounts || [];
                    this.fiscalYears = data.fiscal_years || [];
                    this.periods = data.periods || [];
                    this.costCenters = data.cost_centers || [];
                    this.entries = data.entries || [];
                    this.lines = data.lines || [];
                    this.rules = data.rules || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                });
            },
            selectEntry: function(entry) { this.selectedEntry = entry; this.load(entry.id); },
            openAccount: function(account) { this.accountForm = Object.assign({ id: 0, code: '', name: '', account_type: 'asset', parent_id: 0, level: 1, nature: 'debit', currency_code: 'MXN', sat_group_code: '', requires_party: false, requires_cost_center: false, is_postable: true, active: true }, account); this.normalizeBooleans(this.accountForm, ['requires_party', 'requires_cost_center', 'is_postable', 'active']); this.showModal('modal-account'); },
            openEntry: function(entry) { this.entryForm = Object.assign({ id: 0, entry_type: 'diario', entry_date: new Date().toISOString().slice(0, 10), status: 'draft', source_module: 'manual', source_entity_type: '', source_entity_id: 0, currency_code: 'MXN', exchange_rate: 1, description: '', active: true }, entry); this.showModal('modal-entry'); },
            openLine: function(line) { this.lineForm = Object.assign({ id: 0, entry_id: this.selectedEntry ? this.selectedEntry.id : 0, account_id: 0, party_id: 0, department_id: 0, cost_center_id: 0, cost_center: '', description: '', debit: 0, credit: 0, currency_code: 'MXN', exchange_rate: 1, sort_order: 0, active: true }, line); this.showModal('modal-line'); },
            openPeriod: function(period) { var key = new Date().toISOString().slice(0, 7); this.periodForm = Object.assign({ id: 0, fiscal_year_id: 0, period_key: key, name: 'Periodo ' + key, start_date: key + '-01', end_date: '', status: 'open', allow_manual_entries: true, allow_operational_posting: true, locked: false, active: true }, period); this.normalizeBooleans(this.periodForm, ['allow_manual_entries', 'allow_operational_posting', 'locked', 'active']); this.showModal('modal-period'); },
            openCostCenter: function(center) { this.costCenterForm = Object.assign({ id: 0, code: '', name: '', center_type: 'department', parent_id: 0, department_id: 0, branch_id: 0, manager_user_id: 0, budget_amount: 0, currency_code: 'MXN', notes: '', active: true }, center); this.normalizeBooleans(this.costCenterForm, ['active']); this.showModal('modal-cost-center'); },
            openRule: function(rule) { this.ruleForm = Object.assign({ id: 0, rule_code: '', name: '', source_module: '', source_event: '', debit_account_id: 0, credit_account_id: 0, amount_source: 'total', requires_party: false, auto_post: false, priority: 100, notes: '', active: true }, rule); this.normalizeBooleans(this.ruleForm, ['requires_party', 'auto_post', 'active']); this.showModal('modal-rule'); },
            saveAccount: function() { this.post('save_account', this.accountForm, 'modal-account'); },
            saveEntry: function() { this.post('save_entry', this.entryForm, 'modal-entry'); },
            saveLine: function() { this.post('save_line', this.lineForm, 'modal-line', this.lineForm.entry_id); },
            savePeriod: function() { this.post('save_period', this.periodForm, 'modal-period'); },
            saveCostCenter: function() { this.post('save_cost_center', this.costCenterForm, 'modal-cost-center'); },
            saveRule: function() { this.post('save_rule', this.ruleForm, 'modal-rule'); },
            postEntry: function(entry) { this.post('post_entry', { id: entry.id }, '', entry.id); },
            post: function(action, payload, modal, entryId) {
                fetch('<?php echo Uri::create('admin/accounting'); ?>/' + action, window.coreAppFetchOptions(payload)).then(function(r) { return r.json(); }).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    if (data.accounts) this.accounts = data.accounts;
                    if (data.fiscal_years) this.fiscalYears = data.fiscal_years;
                    if (data.periods) this.periods = data.periods;
                    if (data.cost_centers) this.costCenters = data.cost_centers;
                    if (data.entries) this.entries = data.entries;
                    if (data.lines) this.lines = data.lines;
                    if (data.rules) this.rules = data.rules;
                    if (data.options) this.options = data.options;
                    if (data.stats) this.stats = data.stats;
                    if (entryId) {
                        var current = this.entries.find(e => String(e.id) === String(entryId));
                        if (current) this.selectedEntry = current;
                    }
                    if (modal) this.hideModal(modal);
                });
            },
            accountTypeLabel: function(v) { return ({asset:'Activo', liability:'Pasivo', equity:'Capital', income:'Ingreso', expense:'Gasto', cost:'Costo'})[v] || v; },
            natureLabel: function(v) { return v === 'credit' ? 'Acreedora' : 'Deudora'; },
            statusLabel: function(v) { return ({draft:'Borrador', posted:'Contabilizada', cancelled:'Cancelada'})[v] || v; },
            periodStatusLabel: function(v) { return ({open:'Abierto', soft_closed:'Precerrado', closed:'Cerrado'})[v] || v; },
            costCenterTypeLabel: function(v) { return ({department:'Departamento', branch:'Sucursal', project:'Proyecto', sales_channel:'Canal venta', other:'Otro'})[v] || v; },
            asBool: function(v) { return v === true || v === 1 || v === '1' || v === 'true'; },
            normalizeBooleans: function(target, keys) { keys.forEach(k => { target[k] = this.asBool(target[k]); }); },
            money: function(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            showModal: function(id) { $('#' + id).modal('show'); },
            hideModal: function(id) { $('#' + id).modal('hide'); }
        }
    });
};
</script>
