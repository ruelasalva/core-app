<div id="app-commissions">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.sellers || 0 }}</h3><p>Vendedores</p></div><div class="icon"><i class="bi bi-person-check"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ stats.rules || 0 }}</h3><p>Reglas activas</p></div><div class="icon"><i class="bi bi-diagram-3"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ money(stats.earned || 0) }}</h3><p>Por liquidar</p></div><div class="icon"><i class="bi bi-cash-coin"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ money(stats.settled || 0) }}</h3><p>Liquidado</p></div><div class="icon"><i class="bi bi-check2-circle"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'sellers'}" @click.prevent="tab = 'sellers'">Vendedores</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'plans'}" @click.prevent="tab = 'plans'">Planes</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'rules'}" @click.prevent="tab = 'rules'">Reglas</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'quotas'}" @click.prevent="tab = 'quotas'">Cuotas</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'entries'}" @click.prevent="tab = 'entries'">Movimientos</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'settlements'}" @click.prevent="tab = 'settlements'">Liquidaciones</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-show="tab === 'sellers'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Vendedores internos y revendedores</h3>
                    <button class="btn btn-primary btn-sm" @click="openSeller({})"><i class="bi bi-plus-lg"></i> Vendedor</button>
                </div>
                <div class="table-responsive">
                    <table id="sellers-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Codigo</th><th>Nombre</th><th>Tipo</th><th>Relacion</th><th>Plan</th><th>Base %</th><th>Pago %</th><th>Cuota %</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="seller in sellers" :key="seller.id">
                                <td><strong>{{ seller.code }}</strong></td>
                                <td>{{ seller.name }}</td>
                                <td><span class="badge badge-secondary">{{ sellerTypeLabel(seller.seller_type) }}</span></td>
                                <td>{{ seller.employee_name || seller.party_name || seller.username || '-' }}</td>
                                <td>{{ seller.plan_name || '-' }}</td>
                                <td>{{ percent(seller.base_commission_percent) }}</td>
                                <td>{{ percent(seller.payment_commission_percent) }}</td>
                                <td>{{ percent(seller.quota_commission_percent) }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openSeller(seller)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="sellers.length === 0"><td colspan="9" class="text-center text-muted">Sin vendedores registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'plans'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Planes de comision</h3>
                    <button class="btn btn-primary btn-sm" @click="openPlan({})"><i class="bi bi-plus-lg"></i> Plan</button>
                </div>
                <div class="table-responsive">
                    <table id="plans-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Codigo</th><th>Nombre</th><th>Aplica a</th><th>Desde</th><th>Hasta</th><th>Descripcion</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="plan in plans" :key="plan.id">
                                <td><strong>{{ plan.code }}</strong></td>
                                <td>{{ plan.name }}</td>
                                <td>{{ appliesToLabel(plan.applies_to) }}</td>
                                <td>{{ plan.valid_from || '-' }}</td>
                                <td>{{ plan.valid_until || '-' }}</td>
                                <td>{{ plan.description || '-' }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openPlan(plan)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="plans.length === 0"><td colspan="7" class="text-center text-muted">Sin planes.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'rules'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Reglas por venta, pago, producto, cliente o vendedor</h3>
                    <button class="btn btn-primary btn-sm" @click="openRule({})"><i class="bi bi-plus-lg"></i> Regla</button>
                </div>
                <div class="table-responsive">
                    <table id="rules-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Prioridad</th><th>Regla</th><th>Evento</th><th>Plan</th><th>Alcance</th><th>Filtro</th><th>Valor</th><th>Acumulable</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="rule in rules" :key="rule.id">
                                <td>{{ rule.priority }}</td>
                                <td><strong>{{ rule.name }}</strong><div class="text-muted small">{{ rule.code }}</div></td>
                                <td><span class="badge badge-info">{{ eventLabel(rule.trigger_event) }}</span></td>
                                <td>{{ rule.plan_name || '-' }}</td>
                                <td>{{ scopeLabel(rule.rule_scope) }}</td>
                                <td>{{ ruleFilter(rule) }}</td>
                                <td>{{ rule.value_type === 'percent' ? percent(rule.value) : money(rule.value) }}</td>
                                <td>{{ rule.stackable == 1 ? 'Si' : 'No' }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openRule(rule)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="rules.length === 0"><td colspan="9" class="text-center text-muted">Sin reglas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'quotas'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Cuotas y bonos por cumplimiento</h3>
                    <button class="btn btn-primary btn-sm" @click="openQuota({})"><i class="bi bi-plus-lg"></i> Cuota</button>
                </div>
                <div class="table-responsive">
                    <table id="quotas-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Vendedor</th><th>Periodo</th><th>Desde</th><th>Hasta</th><th>Meta $</th><th>Meta piezas</th><th>Bono %</th><th>Bono fijo</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="quota in quotas" :key="quota.id">
                                <td>{{ quota.seller_name }}</td>
                                <td><strong>{{ quota.period_code }}</strong></td>
                                <td>{{ quota.date_from }}</td>
                                <td>{{ quota.date_to }}</td>
                                <td>{{ money(quota.target_amount) }}</td>
                                <td>{{ quota.target_quantity }}</td>
                                <td>{{ percent(quota.bonus_percent) }}</td>
                                <td>{{ money(quota.bonus_amount) }}</td>
                                <td><span class="badge badge-secondary">{{ quota.status }}</span></td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openQuota(quota)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="quotas.length === 0"><td colspan="10" class="text-center text-muted">Sin cuotas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'entries'">
                <div class="row mb-3">
                    <div class="col-md-4"><select class="form-control" v-model="generateForm.order_id"><option value="0">Pedido para calcular venta</option><option v-for="o in options.orders" :value="o.value">{{ o.label }}</option></select></div>
                    <div class="col-md-3"><button class="btn btn-outline-primary" @click="generateFromOrder"><i class="bi bi-calculator"></i> Generar por pedido</button></div>
                    <div class="col-md-5 text-md-right"><button class="btn btn-outline-secondary" @click="openAdjustment({})"><i class="bi bi-plus-circle"></i> Ajuste manual</button></div>
                </div>
                <div class="table-responsive">
                    <table id="entries-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Fecha</th><th>Vendedor</th><th>Evento</th><th>Origen</th><th>Cliente</th><th>Producto</th><th>Base</th><th>%</th><th>Comision</th><th>Estado</th></tr></thead>
                        <tbody>
                            <tr v-for="entry in entries" :key="entry.id">
                                <td>{{ dateTime(entry.created_at) }}</td>
                                <td>{{ entry.seller_name }}</td>
                                <td>{{ eventLabel(entry.trigger_event) }}</td>
                                <td>{{ entry.source_entity_type }} #{{ entry.source_entity_id }}</td>
                                <td>{{ entry.party_name || '-' }}</td>
                                <td>{{ entry.product_name || '-' }}</td>
                                <td>{{ money(entry.base_amount) }}</td>
                                <td>{{ percent(entry.commission_percent) }}</td>
                                <td><strong>{{ money(entry.commission_amount) }}</strong></td>
                                <td><span class="badge" :class="entry.status === 'settled' ? 'badge-success' : (entry.status === 'earned' ? 'badge-warning' : 'badge-secondary')">{{ entry.status }}</span></td>
                            </tr>
                            <tr v-if="entries.length === 0"><td colspan="10" class="text-center text-muted">Sin movimientos de comision.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'settlements'">
                <div class="row mb-3">
                    <div class="col-md-3"><select class="form-control" v-model="settlementForm.seller_id"><option value="0">Vendedor</option><option v-for="s in options.sellers" :value="s.value">{{ s.label }}</option></select></div>
                    <div class="col-md-2"><input type="date" class="form-control" v-model="settlementForm.date_from"></div>
                    <div class="col-md-2"><input type="date" class="form-control" v-model="settlementForm.date_to"></div>
                    <div class="col-md-3"><input class="form-control" v-model="settlementForm.notes" placeholder="Notas"></div>
                    <div class="col-md-2"><button class="btn btn-primary btn-block" @click="createSettlement">Liquidar</button></div>
                </div>
                <div class="table-responsive">
                    <table id="settlements-table" class="table table-bordered table-hover table-sm">
                        <thead><tr><th>Folio</th><th>Vendedor</th><th>Periodo</th><th>Subtotal</th><th>Ajustes</th><th>Total</th><th>Estado</th><th>Notas</th></tr></thead>
                        <tbody>
                            <tr v-for="item in settlements" :key="item.id">
                                <td><strong>{{ item.folio }}</strong></td>
                                <td>{{ item.seller_name }}</td>
                                <td>{{ item.date_from }} a {{ item.date_to }}</td>
                                <td>{{ money(item.subtotal) }}</td>
                                <td>{{ money(item.adjustment_total) }}</td>
                                <td><strong>{{ money(item.total) }}</strong></td>
                                <td><span class="badge badge-secondary">{{ item.status }}</span></td>
                                <td>{{ item.notes || '-' }}</td>
                            </tr>
                            <tr v-if="settlements.length === 0"><td colspan="8" class="text-center text-muted">Sin liquidaciones.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-seller" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Vendedor</h5><button class="close text-white" @click="hideModal('modal-seller')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-3"><label>Codigo</label><input class="form-control" v-model="sellerForm.code" placeholder="Automatico"></div>
                <div class="col-md-5"><label>Nombre</label><input class="form-control" v-model="sellerForm.name"></div>
                <div class="col-md-4"><label>Tipo</label><select class="form-control" v-model="sellerForm.seller_type"><option value="employee">Empleado</option><option value="reseller">Revendedor</option><option value="external">Externo</option></select></div>
                <div class="col-md-4 mt-2"><label>Empleado</label><select class="form-control" v-model="sellerForm.employee_id"><option value="0">Sin empleado</option><option v-for="e in options.employees" :value="e.value">{{ e.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Tercero / revendedor</label><select class="form-control" v-model="sellerForm.party_id"><option value="0">Sin tercero</option><option v-for="p in options.resellers" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Usuario</label><select class="form-control" v-model="sellerForm.user_id"><option value="0">Sin usuario</option><option v-for="u in options.users" :value="u.value">{{ u.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Plan default</label><select class="form-control" v-model="sellerForm.default_commission_plan_id"><option value="0">Sin plan</option><option v-for="p in options.plans" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-2 mt-2"><label>% venta</label><input type="number" step="0.01" class="form-control" v-model.number="sellerForm.base_commission_percent"></div>
                <div class="col-md-2 mt-2"><label>% pago</label><input type="number" step="0.01" class="form-control" v-model.number="sellerForm.payment_commission_percent"></div>
                <div class="col-md-2 mt-2"><label>% cuota</label><input type="number" step="0.01" class="form-control" v-model.number="sellerForm.quota_commission_percent"></div>
                <div class="col-md-12 mt-3"><label><input type="checkbox" v-model="sellerForm.active"> Activo</label></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-seller')">Cerrar</button><button class="btn btn-primary" @click="saveSeller">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-plan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Plan de comision</h5><button class="close text-white" @click="hideModal('modal-plan')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-3"><label>Codigo</label><input class="form-control" v-model="planForm.code" placeholder="Automatico"></div>
                <div class="col-md-5"><label>Nombre</label><input class="form-control" v-model="planForm.name"></div>
                <div class="col-md-4"><label>Aplica a</label><select class="form-control" v-model="planForm.applies_to"><option value="all">Todos</option><option value="employees">Empleados</option><option value="resellers">Revendedores</option></select></div>
                <div class="col-md-3 mt-2"><label>Desde</label><input type="date" class="form-control" v-model="planForm.valid_from"></div>
                <div class="col-md-3 mt-2"><label>Hasta</label><input type="date" class="form-control" v-model="planForm.valid_until"></div>
                <div class="col-md-6 mt-2"><label>Descripcion</label><input class="form-control" v-model="planForm.description"></div>
                <div class="col-md-12 mt-3"><label><input type="checkbox" v-model="planForm.active"> Activo</label></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-plan')">Cerrar</button><button class="btn btn-primary" @click="savePlan">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-rule" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Regla de comision</h5><button class="close text-white" @click="hideModal('modal-rule')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-2"><label>Codigo</label><input class="form-control" v-model="ruleForm.code" placeholder="Automatico"></div>
                <div class="col-md-4"><label>Nombre</label><input class="form-control" v-model="ruleForm.name"></div>
                <div class="col-md-3"><label>Plan</label><select class="form-control" v-model="ruleForm.plan_id"><option value="0">Cualquier plan</option><option v-for="p in options.plans" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-3"><label>Evento</label><select class="form-control" v-model="ruleForm.trigger_event"><option value="sale">Venta</option><option value="delivery">Entrega</option><option value="invoice">Factura</option><option value="payment">Pago</option></select></div>
                <div class="col-md-3 mt-2"><label>Alcance</label><select class="form-control" v-model="ruleForm.rule_scope"><option value="general">General</option><option value="seller">Vendedor</option><option value="customer">Cliente</option><option value="product">Producto</option><option value="brand">Marca</option><option value="category">Categoria</option><option value="subcategory">Subcategoria</option></select></div>
                <div class="col-md-3 mt-2"><label>Vendedor</label><select class="form-control" v-model="ruleForm.seller_id"><option value="0">Cualquiera</option><option v-for="s in options.sellers" :value="s.value">{{ s.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Cliente</label><select class="form-control" v-model="ruleForm.party_id"><option value="0">Cualquiera</option><option v-for="p in options.customers" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Producto</label><select class="form-control" v-model="ruleForm.product_id"><option value="0">Cualquiera</option><option v-for="p in options.products" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Marca</label><select class="form-control" v-model="ruleForm.brand_id"><option value="0">Cualquiera</option><option v-for="b in options.brands" :value="b.value">{{ b.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Categoria</label><select class="form-control" v-model="ruleForm.category_id"><option value="0">Cualquiera</option><option v-for="c in options.categories" :value="c.value">{{ c.label }}</option></select></div>
                <div class="col-md-3 mt-2"><label>Subcategoria</label><select class="form-control" v-model="ruleForm.subcategory_id"><option value="0">Cualquiera</option><option v-for="s in options.subcategories" :value="s.value">{{ s.label }}</option></select></div>
                <div class="col-md-2 mt-2"><label>Base</label><select class="form-control" v-model="ruleForm.calculation_base"><option value="line_total">Importe</option><option value="quantity">Cantidad</option></select></div>
                <div class="col-md-2 mt-2"><label>Tipo</label><select class="form-control" v-model="ruleForm.value_type"><option value="percent">Porcentaje</option><option value="fixed">Fijo</option></select></div>
                <div class="col-md-2 mt-2"><label>Valor</label><input type="number" step="0.01" class="form-control" v-model.number="ruleForm.value"></div>
                <div class="col-md-2 mt-2"><label>Min. cantidad</label><input type="number" step="0.01" class="form-control" v-model.number="ruleForm.min_quantity"></div>
                <div class="col-md-2 mt-2"><label>Min. importe</label><input type="number" step="0.01" class="form-control" v-model.number="ruleForm.min_amount"></div>
                <div class="col-md-2 mt-2"><label>Prioridad</label><input type="number" class="form-control" v-model.number="ruleForm.priority"></div>
                <div class="col-md-3 mt-2"><label>Desde</label><input type="date" class="form-control" v-model="ruleForm.valid_from"></div>
                <div class="col-md-3 mt-2"><label>Hasta</label><input type="date" class="form-control" v-model="ruleForm.valid_until"></div>
                <div class="col-md-3 mt-4"><label><input type="checkbox" v-model="ruleForm.stackable"> Acumulable</label></div>
                <div class="col-md-3 mt-4"><label><input type="checkbox" v-model="ruleForm.active"> Activa</label></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-rule')">Cerrar</button><button class="btn btn-primary" @click="saveRule">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-quota" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Cuota de comision</h5><button class="close text-white" @click="hideModal('modal-quota')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><label>Vendedor</label><select class="form-control" v-model="quotaForm.seller_id"><option value="0">Selecciona</option><option v-for="s in options.sellers" :value="s.value">{{ s.label }}</option></select></div>
                <div class="col-md-4"><label>Plan</label><select class="form-control" v-model="quotaForm.plan_id"><option value="0">Sin plan</option><option v-for="p in options.plans" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-4"><label>Periodo</label><input class="form-control" v-model="quotaForm.period_code"></div>
                <div class="col-md-3 mt-2"><label>Desde</label><input type="date" class="form-control" v-model="quotaForm.date_from"></div>
                <div class="col-md-3 mt-2"><label>Hasta</label><input type="date" class="form-control" v-model="quotaForm.date_to"></div>
                <div class="col-md-3 mt-2"><label>Meta importe</label><input type="number" step="0.01" class="form-control" v-model.number="quotaForm.target_amount"></div>
                <div class="col-md-3 mt-2"><label>Meta cantidad</label><input type="number" step="0.01" class="form-control" v-model.number="quotaForm.target_quantity"></div>
                <div class="col-md-3 mt-2"><label>Bono %</label><input type="number" step="0.01" class="form-control" v-model.number="quotaForm.bonus_percent"></div>
                <div class="col-md-3 mt-2"><label>Bono fijo</label><input type="number" step="0.01" class="form-control" v-model.number="quotaForm.bonus_amount"></div>
                <div class="col-md-3 mt-2"><label>Estado</label><select class="form-control" v-model="quotaForm.status"><option value="open">Abierta</option><option value="met">Cumplida</option><option value="missed">No cumplida</option><option value="cancelled">Cancelada</option></select></div>
                <div class="col-md-3 mt-4"><label><input type="checkbox" v-model="quotaForm.active"> Activa</label></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-quota')">Cerrar</button><button class="btn btn-primary" @click="saveQuota">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-adjustment" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-secondary text-white"><h5 class="modal-title">Ajuste manual</h5><button class="close text-white" @click="hideModal('modal-adjustment')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><label>Vendedor</label><select class="form-control" v-model="adjustmentForm.seller_id"><option value="0">Selecciona</option><option v-for="s in options.sellers" :value="s.value">{{ s.label }}</option></select></div>
                <div class="col-md-3"><label>Tipo</label><select class="form-control" v-model="adjustmentForm.adjustment_type"><option value="manual">Manual</option><option value="bonus">Bono</option><option value="penalty">Descuento</option><option value="correction">Correccion</option></select></div>
                <div class="col-md-3"><label>Importe</label><input type="number" step="0.01" class="form-control" v-model.number="adjustmentForm.amount"></div>
                <div class="col-md-12 mt-2"><label>Motivo</label><input class="form-control" v-model="adjustmentForm.reason"></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-adjustment')">Cerrar</button><button class="btn btn-primary" @click="saveAdjustment">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-commissions',
        data: {
            tab: 'sellers',
            error: '',
            sellers: [],
            plans: [],
            rules: [],
            quotas: [],
            entries: [],
            settlements: [],
            options: {},
            stats: {},
            sellerForm: {},
            planForm: {},
            ruleForm: {},
            quotaForm: {},
            adjustmentForm: {},
            generateForm: { order_id: 0 },
            settlementForm: { seller_id: 0, date_from: '<?php echo date('Y-m-01'); ?>', date_to: '<?php echo date('Y-m-t'); ?>', notes: '' }
        },
        mounted: function() { this.load(); },
        methods: {
            load: function() {
                fetch('<?php echo Uri::create('admin/commissions/data'); ?>').then(function(r) { return r.json(); }).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.sellers = data.sellers || [];
                    this.plans = data.plans || [];
                    this.rules = data.rules || [];
                    this.quotas = data.quotas || [];
                    this.entries = data.entries || [];
                    this.settlements = data.settlements || [];
                    this.options = data.options || {};
                    this.stats = data.stats || {};
                });
            },
            openSeller: function(item) { this.sellerForm = Object.assign({ id: 0, code: '', name: '', seller_type: 'employee', employee_id: 0, party_id: 0, user_id: 0, default_commission_plan_id: 0, base_commission_percent: 0, payment_commission_percent: 0, quota_commission_percent: 0, active: true }, item); this.showModal('modal-seller'); },
            openPlan: function(item) { this.planForm = Object.assign({ id: 0, code: '', name: '', applies_to: 'all', valid_from: '', valid_until: '', description: '', active: true }, item); this.showModal('modal-plan'); },
            openRule: function(item) { this.ruleForm = Object.assign({ id: 0, plan_id: 0, code: '', name: '', rule_scope: 'general', seller_id: 0, party_id: 0, product_id: 0, brand_id: 0, category_id: 0, subcategory_id: 0, trigger_event: 'sale', calculation_base: 'line_total', value_type: 'percent', value: 0, min_quantity: 0, min_amount: 0, priority: 100, stackable: true, valid_from: '', valid_until: '', active: true }, item); this.showModal('modal-rule'); },
            openQuota: function(item) { this.quotaForm = Object.assign({ id: 0, seller_id: 0, plan_id: 0, period_code: '<?php echo date('Ym'); ?>', date_from: '<?php echo date('Y-m-01'); ?>', date_to: '<?php echo date('Y-m-t'); ?>', target_amount: 0, target_quantity: 0, bonus_percent: 0, bonus_amount: 0, status: 'open', active: true }, item); this.showModal('modal-quota'); },
            openAdjustment: function(item) { this.adjustmentForm = Object.assign({ seller_id: 0, adjustment_type: 'manual', amount: 0, reason: '' }, item); this.showModal('modal-adjustment'); },
            saveSeller: function() { this.post('save_seller', this.sellerForm, 'modal-seller'); },
            savePlan: function() { this.post('save_plan', this.planForm, 'modal-plan'); },
            saveRule: function() { this.post('save_rule', this.ruleForm, 'modal-rule'); },
            saveQuota: function() { this.post('save_quota', this.quotaForm, 'modal-quota'); },
            saveAdjustment: function() { this.post('save_adjustment', this.adjustmentForm, 'modal-adjustment'); },
            generateFromOrder: function() { this.post('generate_from_order', this.generateForm); },
            createSettlement: function() { this.post('create_settlement', this.settlementForm); },
            post: function(action, payload, modal) {
                this.error = '';
                fetch('<?php echo Uri::create('admin/commissions'); ?>/' + action, window.coreAppFetchOptions(payload)).then(window.coreAppJson).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    if (data.sellers) this.sellers = data.sellers;
                    if (data.plans) this.plans = data.plans;
                    if (data.rules) this.rules = data.rules;
                    if (data.quotas) this.quotas = data.quotas;
                    if (data.entries) this.entries = data.entries;
                    if (data.settlements) this.settlements = data.settlements;
                    if (data.options) this.options = data.options;
                    if (data.stats) this.stats = data.stats;
                    if (modal) this.hideModal(modal);
                }).catch(err => { this.error = err && err.error ? err.error : 'No se pudo guardar la informacion.'; });
            },
            ruleFilter: function(rule) { return rule.seller_name || rule.party_name || rule.product_name || rule.brand_name || rule.category_name || rule.subcategory_name || 'General'; },
            money: function(v) { return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            percent: function(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) + '%'; },
            dateTime: function(ts) { if (!ts) return '-'; return new Date(Number(ts) * 1000).toLocaleString('es-MX'); },
            sellerTypeLabel: function(v) { return ({ employee: 'Empleado', reseller: 'Revendedor', external: 'Externo' })[v] || v; },
            appliesToLabel: function(v) { return ({ all: 'Todos', employees: 'Empleados', resellers: 'Revendedores' })[v] || v; },
            eventLabel: function(v) { return ({ sale: 'Venta', delivery: 'Entrega', invoice: 'Factura', payment: 'Pago', adjustment: 'Ajuste' })[v] || v; },
            scopeLabel: function(v) { return ({ general: 'General', seller: 'Vendedor', customer: 'Cliente', product: 'Producto', brand: 'Marca', category: 'Categoria', subcategory: 'Subcategoria' })[v] || v; },
            showModal: function(id) { $('#' + id).modal('show'); },
            hideModal: function(id) { $('#' + id).modal('hide'); }
        }
    });
};
</script>
