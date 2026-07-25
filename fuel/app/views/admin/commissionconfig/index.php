<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1>Configuración de Comisiones</h1>
                <p class="text-muted mb-0">Define planes, versiones, reglas y publicación controlada para el futuro motor de comisiones.</p>
            </div>
            <div class="col-sm-4 text-sm-right mt-3 mt-sm-0">
                <span class="badge badge-info">Solo configuración</span>
                <span class="badge badge-secondary">Sin cálculo</span>
            </div>
        </div>
    </div>
</section>

<section class="content" id="commission-config-app" v-cloak>
    <div class="container-fluid">
        <div class="alert alert-danger" v-if="error">{{ error }}</div>
        <div class="alert alert-success" v-if="message">{{ message }}</div>

        <div class="row">
            <div class="col-md-2 col-sm-6" v-for="card in statCards" :key="card.key">
                <div class="small-box bg-light">
                    <div class="inner">
                        <h3>{{ card.value }}</h3>
                        <p>{{ card.label }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header p-2">
                <ul class="nav nav-pills flex-wrap">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.key">
                        <button type="button" class="nav-link" :class="{active: activeTab === tab.key}" @click="activeTab = tab.key">
                            {{ tab.label }}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div v-if="loading" class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin"></i> Cargando configuración...
                </div>

                <div v-else>
                    <div v-show="activeTab === 'summary'">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="callout callout-info">
                                    <h5>Flujo de configuración</h5>
                                    <p class="mb-2">Configuración → Validación → Publicación → Ejecución futura.</p>
                                    <p class="mb-0">Las versiones publicadas quedan inmutables. Para cambiar reglas se debe crear una nueva versión.</p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="callout callout-warning">
                                    <h5>Alcance de esta fase</h5>
                                    <p class="mb-0">Esta pantalla no calcula, libera ni paga comisiones. Solo prepara la configuración que consumirá el motor en fases futuras.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'plans'">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Plan comercial</th>
                                                <th>Estado</th>
                                                <th>Responsable</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="plan in plans" :key="plan.id">
                                                <td>{{ plan.code }}</td>
                                                <td>{{ plan.name }}</td>
                                                <td><span class="badge" :class="statusClass(plan.status)">{{ statusLabel(plan.status) }}</span></td>
                                                <td>{{ plan.owner_name || 'Sin asignar' }}</td>
                                                <td class="text-right">
                                                    <button type="button" class="btn btn-xs btn-outline-primary" @click="editPlan(plan)" :disabled="planHasPublishedVersion(plan.id)">Editar</button>
                                                </td>
                                            </tr>
                                            <tr v-if="plans.length === 0">
                                                <td colspan="5" class="text-center text-muted py-4">Aún no hay planes comerciales configurados.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>{{ forms.plan.id ? 'Editar plan' : 'Nuevo plan comercial' }}</strong></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Nombre</label>
                                            <input type="text" class="form-control" v-model="forms.plan.name">
                                        </div>
                                        <div class="form-group">
                                            <label>Código</label>
                                            <input type="text" class="form-control" v-model="forms.plan.code" placeholder="Opcional">
                                        </div>
                                        <div class="form-group">
                                            <label>Descripción</label>
                                            <textarea class="form-control" rows="3" v-model="forms.plan.description"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="savePlan" :disabled="saving">Guardar plan</button>
                                        <button type="button" class="btn btn-link" @click="resetPlan">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'versions'">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Plan</th>
                                                <th>Versión</th>
                                                <th>Estado</th>
                                                <th>Vigencia</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="version in versions" :key="version.id">
                                                <td>{{ version.plan_name }}</td>
                                                <td>{{ version.name }} <small class="text-muted">v{{ version.version_number }}</small></td>
                                                <td><span class="badge" :class="statusClass(version.status)">{{ statusLabel(version.status) }}</span></td>
                                                <td>{{ version.valid_from || 'Sin inicio' }} - {{ version.valid_until || 'Sin fin' }}</td>
                                                <td class="text-right">
                                                    <button type="button" class="btn btn-xs btn-outline-primary" @click="editVersion(version)" :disabled="isImmutable(version)">Editar</button>
                                                    <button type="button" class="btn btn-xs btn-success" @click="openPublish(version)" :disabled="isImmutable(version)">Publicar</button>
                                                </td>
                                            </tr>
                                            <tr v-if="versions.length === 0">
                                                <td colspan="5" class="text-center text-muted py-4">Crea una versión para comenzar a definir reglas.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>{{ forms.version.id ? 'Editar versión' : 'Nueva versión' }}</strong></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Plan comercial</label>
                                            <select class="form-control" v-model="forms.version.commercial_plan_id">
                                                <option value="">Selecciona</option>
                                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Nombre</label>
                                            <input type="text" class="form-control" v-model="forms.version.name">
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Inicio</label>
                                                <input type="date" class="form-control" v-model="forms.version.valid_from">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Fin</label>
                                                <input type="date" class="form-control" v-model="forms.version.valid_until">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Notas</label>
                                            <textarea class="form-control" rows="3" v-model="forms.version.notes"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="saveVersion" :disabled="saving">Guardar versión</button>
                                        <button type="button" class="btn btn-link" @click="resetVersion">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'groups'">
                        <div class="row">
                            <div class="col-lg-8">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Versión</th><th>Grupo</th><th>Prioridad</th><th>Activo</th><th></th></tr></thead>
                                    <tbody>
                                        <tr v-for="group in groups" :key="group.id">
                                            <td>{{ group.version_name }}</td>
                                            <td>{{ group.name }}</td>
                                            <td>{{ group.priority }}</td>
                                            <td>{{ group.enabled == 1 ? 'Sí' : 'No' }}</td>
                                            <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editGroup(group)" :disabled="versionIsImmutable(group.version_id)">Editar</button></td>
                                        </tr>
                                        <tr v-if="groups.length === 0"><td colspan="5" class="text-center text-muted py-4">Aún no hay grupos de reglas.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>Grupo de reglas</strong></div>
                                    <div class="card-body">
                                        <select-field label="Versión" v-model="forms.group.version_id" :items="editableVersions"></select-field>
                                        <text-field label="Nombre" v-model="forms.group.name"></text-field>
                                        <number-field label="Prioridad" v-model="forms.group.priority"></number-field>
                                        <text-area label="Descripción" v-model="forms.group.description"></text-area>
                                        <button type="button" class="btn btn-primary" @click="saveGroup" :disabled="saving">Guardar grupo</button>
                                        <button type="button" class="btn btn-link" @click="resetGroup">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'rules'">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Versión</th>
                                                <th>Regla</th>
                                                <th>Evento</th>
                                                <th>Base</th>
                                                <th>Valor</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="rule in rules" :key="rule.id">
                                                <td>{{ rule.version_name }}</td>
                                                <td>{{ rule.name }} <small class="text-muted d-block">{{ rule.group_name || 'Sin grupo' }}</small></td>
                                                <td>{{ optionLabel(options.events, rule.event_code) }}</td>
                                                <td>{{ optionLabel(options.calculation_bases, rule.calculation_base) }}</td>
                                                <td>{{ rule.value }} {{ rule.value_type === 'percent' ? '%' : '' }}</td>
                                                <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editRule(rule)" :disabled="versionIsImmutable(rule.version_id)">Editar</button></td>
                                            </tr>
                                            <tr v-if="rules.length === 0"><td colspan="6" class="text-center text-muted py-4">Configura reglas cuando exista una versión editable.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>Regla de comisión</strong></div>
                                    <div class="card-body">
                                        <select-field label="Versión" v-model="forms.rule.version_id" :items="editableVersions"></select-field>
                                        <div class="form-group">
                                            <label>Grupo</label>
                                            <select class="form-control" v-model="forms.rule.rule_group_id">
                                                <option value="">Sin grupo</option>
                                                <option v-for="group in groupsForVersion(forms.rule.version_id)" :key="group.id" :value="group.id">{{ group.name }}</option>
                                            </select>
                                        </div>
                                        <text-field label="Nombre" v-model="forms.rule.name"></text-field>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Evento</label>
                                                <select class="form-control" v-model="forms.rule.event_code">
                                                    <option v-for="item in options.events" :key="item.value" :value="item.value">{{ item.label }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Base</label>
                                                <select class="form-control" v-model="forms.rule.calculation_base">
                                                    <option v-for="item in options.calculation_bases" :key="item.value" :value="item.value">{{ item.label }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Tipo</label>
                                                <select class="form-control" v-model="forms.rule.value_type">
                                                    <option v-for="item in options.value_types" :key="item.value" :value="item.value">{{ item.label }}</option>
                                                </select>
                                            </div>
                                            <number-field class="col-md-6" label="Valor" v-model="forms.rule.value"></number-field>
                                        </div>
                                        <number-field label="Prioridad" v-model="forms.rule.priority"></number-field>
                                        <text-area label="Notas de negocio" v-model="forms.rule.business_notes"></text-area>
                                        <button type="button" class="btn btn-primary" @click="saveRule" :disabled="saving">Guardar regla</button>
                                        <button type="button" class="btn btn-link" @click="resetRule">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'stages'">
                        <config-table title="Etapas de liberación" empty-text="Aún no hay etapas configuradas."></config-table>
                        <div class="row">
                            <div class="col-lg-8">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Regla</th><th>Etapa</th><th>Evento</th><th>% liberación</th><th></th></tr></thead>
                                    <tbody>
                                        <tr v-for="stage in stages" :key="stage.id">
                                            <td>{{ stage.rule_name }}</td>
                                            <td>{{ stage.name }}</td>
                                            <td>{{ optionLabel(options.events, stage.trigger_event) }}</td>
                                            <td>{{ stage.release_percent }}%</td>
                                            <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editStage(stage)" :disabled="ruleIsImmutable(stage.rule_id)">Editar</button></td>
                                        </tr>
                                        <tr v-if="stages.length === 0"><td colspan="5" class="text-center text-muted py-4">Define etapas como factura, pago parcial o pago completo.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light"><div class="card-header"><strong>Etapa</strong></div><div class="card-body">
                                    <select-field label="Regla" v-model="forms.stage.rule_id" :items="editableRuleOptions"></select-field>
                                    <text-field label="Nombre" v-model="forms.stage.name"></text-field>
                                    <div class="form-group"><label>Evento detonador</label><select class="form-control" v-model="forms.stage.trigger_event"><option v-for="item in options.events" :key="item.value" :value="item.value">{{ item.label }}</option></select></div>
                                    <number-field label="Porcentaje de liberación" v-model="forms.stage.release_percent"></number-field>
                                    <number-field label="Orden" v-model="forms.stage.sort_order"></number-field>
                                    <button type="button" class="btn btn-primary" @click="saveStage" :disabled="saving">Guardar etapa</button>
                                    <button type="button" class="btn btn-link" @click="resetStage">Limpiar</button>
                                </div></div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'beneficiaries'">
                        <div class="row">
                            <div class="col-lg-8">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Regla</th><th>Beneficiario</th><th>Porcentaje</th><th>Monto fijo</th><th></th></tr></thead>
                                    <tbody>
                                        <tr v-for="item in beneficiaries" :key="item.id">
                                            <td>{{ item.rule_name }}</td>
                                            <td>{{ optionLabel(options.beneficiary_types, item.beneficiary_type) }}</td>
                                            <td>{{ item.percentage }}%</td>
                                            <td>{{ item.fixed_amount || 0 }}</td>
                                            <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editBeneficiary(item)" :disabled="ruleIsImmutable(item.rule_id)">Editar</button></td>
                                        </tr>
                                        <tr v-if="beneficiaries.length === 0"><td colspan="5" class="text-center text-muted py-4">Aún no hay beneficiarios definidos.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light"><div class="card-header"><strong>Beneficiario futuro</strong></div><div class="card-body">
                                    <select-field label="Regla" v-model="forms.beneficiary.rule_id" :items="editableRuleOptions"></select-field>
                                    <div class="form-group"><label>Tipo</label><select class="form-control" v-model="forms.beneficiary.beneficiary_type"><option v-for="item in options.beneficiary_types" :key="item.value" :value="item.value">{{ item.label }}</option></select></div>
                                    <select-field label="Vendedor" v-model="forms.beneficiary.seller_id" :items="options.sellers"></select-field>
                                    <select-field label="Usuario" v-model="forms.beneficiary.user_id" :items="options.users"></select-field>
                                    <number-field label="Porcentaje" v-model="forms.beneficiary.percentage"></number-field>
                                    <number-field label="Monto fijo" v-model="forms.beneficiary.fixed_amount"></number-field>
                                    <button type="button" class="btn btn-primary" @click="saveBeneficiary" :disabled="saving">Guardar beneficiario</button>
                                    <button type="button" class="btn btn-link" @click="resetBeneficiary">Limpiar</button>
                                </div></div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'exclusions'">
                        <div class="row">
                            <div class="col-lg-8">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Regla</th><th>Tipo</th><th>Entidad</th><th>Comportamiento</th><th></th></tr></thead>
                                    <tbody>
                                        <tr v-for="item in exclusions" :key="item.id">
                                            <td>{{ item.rule_name }}</td>
                                            <td>{{ optionLabel(options.exclusion_types, item.exclusion_type) }}</td>
                                            <td>{{ item.entity_code || item.entity_id || 'General' }}</td>
                                            <td>{{ optionLabel(options.behaviors, item.behavior) }}</td>
                                            <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editExclusion(item)" :disabled="ruleIsImmutable(item.rule_id)">Editar</button></td>
                                        </tr>
                                        <tr v-if="exclusions.length === 0"><td colspan="5" class="text-center text-muted py-4">No hay exclusiones configuradas.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light"><div class="card-header"><strong>Exclusión</strong></div><div class="card-body">
                                    <select-field label="Regla" v-model="forms.exclusion.rule_id" :items="editableRuleOptions"></select-field>
                                    <div class="form-group"><label>Tipo</label><select class="form-control" v-model="forms.exclusion.exclusion_type"><option v-for="item in options.exclusion_types" :key="item.value" :value="item.value">{{ item.label }}</option></select></div>
                                    <number-field label="ID de entidad" v-model="forms.exclusion.entity_id"></number-field>
                                    <text-field label="Código de entidad" v-model="forms.exclusion.entity_code"></text-field>
                                    <div class="form-group"><label>Comportamiento</label><select class="form-control" v-model="forms.exclusion.behavior"><option v-for="item in options.behaviors" :key="item.value" :value="item.value">{{ item.label }}</option></select></div>
                                    <button type="button" class="btn btn-primary" @click="saveExclusion" :disabled="saving">Guardar exclusión</button>
                                    <button type="button" class="btn btn-link" @click="resetExclusion">Limpiar</button>
                                </div></div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'catalogs'">
                        <div class="row">
                            <div class="col-lg-8">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Tipo</th><th>Código</th><th>Nombre</th><th>Orden</th><th></th></tr></thead>
                                    <tbody>
                                        <tr v-for="item in catalogs" :key="item.id">
                                            <td>{{ item.catalog_type }}</td>
                                            <td>{{ item.code }}</td>
                                            <td>{{ item.name }}</td>
                                            <td>{{ item.sort_order }}</td>
                                            <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editCatalog(item)">Editar</button></td>
                                        </tr>
                                        <tr v-if="catalogs.length === 0"><td colspan="5" class="text-center text-muted py-4">Usa este catálogo para preparar listas auxiliares futuras.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light"><div class="card-header"><strong>Catálogo</strong></div><div class="card-body">
                                    <text-field label="Tipo" v-model="forms.catalog.catalog_type"></text-field>
                                    <text-field label="Código" v-model="forms.catalog.code"></text-field>
                                    <text-field label="Nombre" v-model="forms.catalog.name"></text-field>
                                    <text-area label="Descripción" v-model="forms.catalog.description"></text-area>
                                    <number-field label="Orden" v-model="forms.catalog.sort_order"></number-field>
                                    <button type="button" class="btn btn-primary" @click="saveCatalog" :disabled="saving">Guardar catálogo</button>
                                    <button type="button" class="btn btn-link" @click="resetCatalog">Limpiar</button>
                                </div></div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'simulation'">
                        <div class="row">
                            <div class="col-xl-4 col-lg-5">
                                <div class="card bg-light commission-simulator-form">
                                    <div class="card-header">
                                        <strong><i class="fas fa-flask mr-1"></i> Escenario de simulación</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Evento <span class="text-danger">*</span></label>
                                            <select class="form-control" v-model="simulationForm.event_code">
                                                <option value="">Selecciona un evento</option>
                                                <option v-for="item in options.events" :key="item.value" :value="item.value">{{ item.label }}</option>
                                            </select>
                                        </div>
                                        <select-field label="Vendedor" v-model="simulationForm.seller_id" :items="options.sellers"></select-field>
                                        <select-field label="Cliente (opcional)" v-model="simulationForm.customer_id" :items="options.customers"></select-field>
                                        <select-field label="Producto (opcional)" v-model="simulationForm.product_id" :items="options.products"></select-field>
                                        <select-field label="Marca (opcional)" v-model="simulationForm.brand_id" :items="options.brands"></select-field>
                                        <select-field label="Categoría (opcional)" v-model="simulationForm.category_id" :items="options.categories"></select-field>
                                        <select-field label="Contrato (opcional)" v-model="simulationForm.contract_id" :items="options.contracts"></select-field>

                                        <div class="row">
                                            <div class="col-sm-6"><number-field label="Subtotal" v-model="simulationForm.subtotal"></number-field></div>
                                            <div class="col-sm-6"><number-field label="Total" v-model="simulationForm.total"></number-field></div>
                                            <div class="col-sm-6"><number-field label="Cantidad" v-model="simulationForm.quantity"></number-field></div>
                                            <div class="col-sm-6"><number-field label="Monto recurrente" v-model="simulationForm.recurring_amount"></number-field></div>
                                        </div>
                                        <div class="form-group">
                                            <label>Fecha de simulación</label>
                                            <input type="date" class="form-control" v-model="simulationForm.simulation_date">
                                        </div>

                                        <div class="d-flex flex-wrap align-items-center">
                                            <button type="button" class="btn btn-primary mr-2 mb-2" @click="runSimulation" :disabled="simulating">
                                                <i class="fas fa-spinner fa-spin mr-1" v-if="simulating"></i>
                                                <i class="fas fa-calculator mr-1" v-else></i>
                                                {{ simulating ? 'Simulando...' : 'Simular comisión' }}
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary mb-2" @click="resetSimulation" :disabled="simulating">Limpiar</button>
                                        </div>
                                        <small class="text-muted">La simulación es de solo lectura y no genera movimientos.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-8 col-lg-7">
                                <div class="alert alert-danger" v-if="simulationError">{{ simulationError }}</div>

                                <div class="text-center text-muted py-5" v-if="!simulationResult && !simulationError && !simulating">
                                    <i class="fas fa-chart-line fa-2x mb-3"></i>
                                    <h5>Configura un escenario</h5>
                                    <p class="mb-0">Consulta reglas publicadas sin crear comisiones, liquidaciones ni pagos.</p>
                                </div>

                                <div class="text-center text-muted py-5" v-if="simulating">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                    <p class="mb-0">Evaluando reglas publicadas...</p>
                                </div>

                                <div v-if="simulationResult && !simulating">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="small-box bg-light commission-result-card">
                                                <div class="inner">
                                                    <p class="text-muted mb-1">Comisión total estimada</p>
                                                    <h3>{{ money(simulationResult.estimated_total) }}</h3>
                                                    <small>{{ simulationResult.matched_rules.length }} regla(s) coincidente(s)</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="small-box" :class="simulationResult.writes_performed === 0 ? 'bg-success' : 'bg-danger'">
                                                <div class="inner">
                                                    <p class="mb-1">Escrituras realizadas</p>
                                                    <h3>{{ simulationResult.writes_performed }}</h3>
                                                    <small>{{ simulationResult.writes_performed === 0 ? 'Simulación segura' : 'Resultado inesperado' }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning" v-if="simulationResult.warnings.length">
                                        <strong>Advertencias</strong>
                                        <ul class="mb-0 pl-3">
                                            <li v-for="(warning, index) in simulationResult.warnings" :key="'warning-' + index">{{ warning }}</li>
                                        </ul>
                                    </div>

                                    <div class="callout callout-info" v-if="simulationResult.matched_rules.length === 0">
                                        <h5>Sin reglas coincidentes</h5>
                                        <p class="mb-0">No se encontró una regla publicada aplicable al escenario. Revisa el evento, vendedor, entidades y fecha.</p>
                                    </div>

                                    <h5 v-if="simulationResult.matched_rules.length">Reglas coincidentes</h5>
                                    <div class="card card-outline card-success mb-3" v-for="rule in simulationResult.matched_rules" :key="'matched-' + rule.rule_id">
                                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ rule.rule_name }}</strong>
                                                <small class="text-muted ml-2">{{ rule.rule_code }}</small>
                                            </div>
                                            <span class="badge badge-success">{{ money(rule.estimated_amount) }}</span>
                                        </div>
                                        <div class="card-body py-3">
                                            <div class="row">
                                                <div class="col-md-4"><small class="text-muted d-block">Base usada</small>{{ baseLabel(rule.base_used) }}</div>
                                                <div class="col-md-4"><small class="text-muted d-block">Monto base</small>{{ money(rule.base_amount) }}</div>
                                                <div class="col-md-4"><small class="text-muted d-block">Prioridad</small>{{ rule.priority }}</div>
                                            </div>
                                            <p class="mt-2 mb-1">{{ rule.explanation }}</p>
                                            <div class="mt-2" v-if="beneficiariesForRule(rule.rule_id).length">
                                                <small class="text-muted d-block">Beneficiarios configurados</small>
                                                <span class="badge badge-light border mr-1 mb-1" v-for="beneficiary in beneficiariesForRule(rule.rule_id)" :key="beneficiary.id">
                                                    {{ beneficiaryLabel(beneficiary) }}
                                                </span>
                                            </div>
                                            <div class="mt-2" v-if="rule.stop_processing == 1">
                                                <span class="badge badge-warning">Detuvo el procesamiento de reglas posteriores</span>
                                            </div>
                                            <ul class="text-warning mb-0 mt-2 pl-3" v-if="rule.warnings && rule.warnings.length">
                                                <li v-for="(warning, index) in rule.warnings" :key="rule.rule_id + '-warning-' + index">{{ warning }}</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div v-if="simulationResult.ignored_rules.length">
                                        <h5>Reglas ignoradas</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead><tr><th>Regla</th><th>Evento</th><th>Prioridad</th><th>Motivo</th><th>Comportamiento</th></tr></thead>
                                                <tbody>
                                                    <tr v-for="rule in simulationResult.ignored_rules" :key="'ignored-' + rule.rule_id">
                                                        <td><strong>{{ rule.rule_name }}</strong><br><small class="text-muted">{{ rule.rule_code }}</small></td>
                                                        <td>{{ optionLabel(options.events, rule.event_code) }}</td>
                                                        <td>{{ rule.priority }}</td>
                                                        <td>{{ rule.reason }}</td>
                                                        <td>{{ behaviorLabel(rule.behavior) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="publishCommissionVersionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Publicar versión</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>La versión publicada quedará inmutable. Cualquier cambio futuro deberá realizarse en una nueva versión.</p>
                    <div class="form-group">
                        <label>Motivo de publicación</label>
                        <textarea class="form-control" rows="3" v-model="publish.reason"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" @click="publishVersion" :disabled="saving">Publicar versión</button>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    [v-cloak] { display: none; }
    #commission-config-app .nav-pills .nav-link { border: 0; background: transparent; }
    #commission-config-app .small-box { border: 1px solid #e2e6ea; box-shadow: none; }
    #commission-config-app .small-box .inner h3 { font-size: 1.5rem; margin-bottom: .25rem; }
    #commission-config-app .table td, #commission-config-app .table th { vertical-align: middle; }
    #commission-config-app .commission-simulator-form { position: sticky; top: 1rem; }
    #commission-config-app .commission-result-card h3 { font-size: 2rem; }
    #commission-config-app .commission-result-card, #commission-config-app .commission-simulator-form { border: 1px solid #dfe3e7; box-shadow: none; }
    @media (max-width: 991.98px) {
        #commission-config-app .commission-simulator-form { position: static; }
    }
</style>

<script src="<?php echo \Uri::base(false); ?>assets/js/core-api-client.js"></script>
<script>
window.addEventListener('load', function () {
    Vue.component('text-field', {
        props: ['label', 'value'],
        template: '<div class="form-group"><label>{{ label }}</label><input type="text" class="form-control" :value="value" @input="$emit(\'input\', $event.target.value)"></div>'
    });

    Vue.component('number-field', {
        props: ['label', 'value'],
        template: '<div class="form-group"><label>{{ label }}</label><input type="number" step="0.01" class="form-control" :value="value" @input="$emit(\'input\', $event.target.value)"></div>'
    });

    Vue.component('text-area', {
        props: ['label', 'value'],
        template: '<div class="form-group"><label>{{ label }}</label><textarea class="form-control" rows="3" :value="value" @input="$emit(\'input\', $event.target.value)"></textarea></div>'
    });

    Vue.component('select-field', {
        props: ['label', 'value', 'items'],
        template: '<div class="form-group"><label>{{ label }}</label><select class="form-control" :value="value" @change="$emit(\'input\', $event.target.value)"><option value="">Selecciona</option><option v-for="item in items" :key="item.value" :value="item.value">{{ item.label }}</option></select></div>'
    });

    Vue.component('config-table', {
        props: ['title', 'emptyText'],
        template: '<div class="d-none" aria-hidden="true"></div>'
    });

    new Vue({
        el: '#commission-config-app',
        data: function () {
            return {
                loading: true,
                saving: false,
                error: '',
                message: '',
                activeTab: 'summary',
                tabs: [
                    {key: 'summary', label: 'Resumen'},
                    {key: 'plans', label: 'Planes'},
                    {key: 'versions', label: 'Versiones'},
                    {key: 'groups', label: 'Grupos'},
                    {key: 'rules', label: 'Reglas'},
                    {key: 'stages', label: 'Etapas'},
                    {key: 'beneficiaries', label: 'Beneficiarios'},
                    {key: 'exclusions', label: 'Exclusiones'},
                    {key: 'catalogs', label: 'Catálogos'},
                    {key: 'simulation', label: 'Simulador'}
                ],
                stats: {},
                plans: [],
                versions: [],
                groups: [],
                rules: [],
                stages: [],
                beneficiaries: [],
                exclusions: [],
                catalogs: [],
                options: {
                    events: [],
                    calculation_bases: [],
                    value_types: [],
                    beneficiary_types: [],
                    exclusion_types: [],
                    behaviors: [],
                    users: [],
                    sellers: [],
                    customers: [],
                    products: [],
                    brands: [],
                    categories: [],
                    contracts: []
                },
                publish: {version_id: '', reason: ''},
                forms: {},
                simulating: false,
                simulationError: '',
                simulationResult: null,
                simulationForm: {}
            };
        },
        computed: {
            statCards: function () {
                return [
                    {key: 'published', label: 'Planes publicados', value: this.stats.published_plans || 0},
                    {key: 'drafts', label: 'Planes borrador', value: this.stats.draft_plans || 0},
                    {key: 'rules', label: 'Reglas', value: this.stats.rules || 0},
                    {key: 'active', label: 'Reglas activas', value: this.stats.active_rules || 0},
                    {key: 'expiring', label: 'Por vencer', value: this.stats.expiring_rules || 0},
                    {key: 'changes', label: 'Cambios próximos', value: this.stats.upcoming_changes || 0}
                ];
            },
            editableVersions: function () {
                return this.versions.filter(function (item) {
                    return item.status !== 'published' && item.status !== 'archived';
                }).map(function (item) {
                    return {value: item.id, label: item.plan_name + ' / ' + item.name};
                });
            },
            editableRuleOptions: function () {
                var self = this;
                return this.rules.filter(function (rule) {
                    return !self.versionIsImmutable(rule.version_id);
                }).map(function (rule) {
                    return {value: rule.id, label: rule.name};
                });
            }
        },
        created: function () {
            this.resetForms();
            this.loadData();
        },
        methods: {
            resetForms: function () {
                this.forms = {
                    plan: {id: '', code: '', name: '', description: ''},
                    version: {id: '', commercial_plan_id: '', name: '', valid_from: '', valid_until: '', notes: ''},
                    group: {id: '', version_id: '', name: '', description: '', priority: 100},
                    rule: {id: '', version_id: '', rule_group_id: '', name: '', event_code: 'invoice_issued', calculation_base: 'subtotal', value_type: 'percent', value: 0, priority: 100, business_notes: ''},
                    stage: {id: '', rule_id: '', name: '', trigger_event: 'invoice_issued', release_percent: 100, sort_order: 100},
                    beneficiary: {id: '', rule_id: '', beneficiary_type: 'salesperson', seller_id: '', user_id: '', percentage: 100, fixed_amount: 0},
                    exclusion: {id: '', rule_id: '', exclusion_type: 'product', entity_id: '', entity_code: '', behavior: 'skip_rule'},
                    catalog: {id: '', catalog_type: 'general', code: '', name: '', description: '', sort_order: 100}
                };
                this.resetSimulation();
            },
            loadData: async function () {
                this.loading = true;
                this.error = '';
                try {
                    var result = await CoreApiClient.get('<?php echo \Uri::create('admin/commission-config/data'); ?>');
                    if (!result.ok || !result.payload.success) {
                        throw new Error(CoreApiClient.safeMessage(result.payload, result.message || 'No se pudo cargar la configuración.'));
                    }
                    this.applyData(result.payload.data || {});
                } catch (error) {
                    this.error = CoreApiClient.safeMessage(error, 'No se pudo cargar la configuración.');
                } finally {
                    this.loading = false;
                }
            },
            applyData: function (data) {
                this.stats = data.stats || {};
                this.plans = data.plans || [];
                this.versions = data.versions || [];
                this.groups = data.groups || [];
                this.rules = data.rules || [];
                this.stages = data.stages || [];
                this.beneficiaries = data.beneficiaries || [];
                this.exclusions = data.exclusions || [];
                this.catalogs = data.catalogs || [];
                this.options = Object.assign(this.options, data.options || {});
            },
            resetSimulation: function () {
                var today = new Date();
                var timezoneOffset = today.getTimezoneOffset() * 60000;
                this.simulationForm = {
                    event_code: '',
                    seller_id: '',
                    customer_id: '',
                    product_id: '',
                    brand_id: '',
                    category_id: '',
                    contract_id: '',
                    subtotal: 0,
                    total: 0,
                    quantity: 1,
                    recurring_amount: 0,
                    simulation_date: new Date(today.getTime() - timezoneOffset).toISOString().slice(0, 10)
                };
                this.simulationError = '';
                this.simulationResult = null;
            },
            runSimulation: async function () {
                this.simulationError = '';
                this.simulationResult = null;

                if (!this.simulationForm.event_code) {
                    this.simulationError = 'Selecciona un evento para ejecutar la simulación.';
                    return;
                }

                var numericFields = ['subtotal', 'total', 'quantity', 'recurring_amount'];
                var hasInvalidNumber = numericFields.some(function (field) {
                    var value = Number(this.simulationForm[field]);
                    return !Number.isFinite(value) || value < 0;
                }, this);
                if (hasInvalidNumber) {
                    this.simulationError = 'Los importes y la cantidad deben ser números iguales o mayores a cero.';
                    return;
                }

                this.simulating = true;
                try {
                    var params = new URLSearchParams();
                    Object.keys(this.simulationForm).forEach(function (key) {
                        var value = this.simulationForm[key];
                        if (value !== '' && value !== null && typeof value !== 'undefined') {
                            params.append(key, value);
                        }
                    }, this);

                    var result = await CoreApiClient.get('<?php echo \Uri::create('admin/commission-config/simulate'); ?>?' + params.toString());
                    if (!result.ok || !result.payload.success) {
                        throw new Error(CoreApiClient.safeMessage(result.payload, result.message || 'No se pudo ejecutar la simulación.'));
                    }

                    var data = result.payload.data || {};
                    this.simulationResult = {
                        estimated_total: Number(data.estimated_total || 0),
                        matched_rules: Array.isArray(data.matched_rules) ? data.matched_rules : [],
                        ignored_rules: Array.isArray(data.ignored_rules) ? data.ignored_rules : [],
                        warnings: Array.isArray(data.warnings) ? data.warnings : [],
                        writes_performed: Number(data.writes_performed || 0),
                        context: data.context || {}
                    };
                } catch (error) {
                    this.simulationError = CoreApiClient.safeMessage(error, 'No se pudo ejecutar la simulación.');
                } finally {
                    this.simulating = false;
                }
            },
            postForm: async function (url, form, successMessage) {
                this.saving = true;
                this.error = '';
                this.message = '';
                try {
                    var result = await CoreApiClient.post(url, form);
                    if (!result.ok || !result.payload.success) {
                        throw new Error(CoreApiClient.safeMessage(result.payload, result.message || 'No se pudo guardar.'));
                    }
                    this.applyData(result.payload.data || {});
                    this.message = successMessage;
                    return true;
                } catch (error) {
                    this.error = CoreApiClient.safeMessage(error, 'No se pudo guardar.');
                    return false;
                } finally {
                    this.saving = false;
                }
            },
            savePlan: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_plan'); ?>', this.forms.plan, 'Plan guardado.')) this.resetPlan(); },
            saveVersion: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_version'); ?>', this.forms.version, 'Versión guardada.')) this.resetVersion(); },
            saveGroup: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_group'); ?>', this.forms.group, 'Grupo guardado.')) this.resetGroup(); },
            saveRule: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_rule'); ?>', this.forms.rule, 'Regla guardada.')) this.resetRule(); },
            saveStage: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_stage'); ?>', this.forms.stage, 'Etapa guardada.')) this.resetStage(); },
            saveBeneficiary: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_beneficiary'); ?>', this.forms.beneficiary, 'Beneficiario guardado.')) this.resetBeneficiary(); },
            saveExclusion: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_exclusion'); ?>', this.forms.exclusion, 'Exclusión guardada.')) this.resetExclusion(); },
            saveCatalog: async function () { if (await this.postForm('<?php echo \Uri::create('admin/commission-config/save_catalog'); ?>', this.forms.catalog, 'Catálogo guardado.')) this.resetCatalog(); },
            openPublish: function (version) {
                this.publish = {version_id: version.id, reason: ''};
                $('#publishCommissionVersionModal').modal('show');
            },
            publishVersion: async function () {
                if (await this.postForm('<?php echo \Uri::create('admin/commission-config/publish_version'); ?>', this.publish, 'Versión publicada.')) {
                    $('#publishCommissionVersionModal').modal('hide');
                    this.publish = {version_id: '', reason: ''};
                }
            },
            resetPlan: function () { this.forms.plan = {id: '', code: '', name: '', description: ''}; },
            resetVersion: function () { this.forms.version = {id: '', commercial_plan_id: '', name: '', valid_from: '', valid_until: '', notes: ''}; },
            resetGroup: function () { this.forms.group = {id: '', version_id: '', name: '', description: '', priority: 100}; },
            resetRule: function () { this.forms.rule = {id: '', version_id: '', rule_group_id: '', name: '', event_code: 'invoice_issued', calculation_base: 'subtotal', value_type: 'percent', value: 0, priority: 100, business_notes: ''}; },
            resetStage: function () { this.forms.stage = {id: '', rule_id: '', name: '', trigger_event: 'invoice_issued', release_percent: 100, sort_order: 100}; },
            resetBeneficiary: function () { this.forms.beneficiary = {id: '', rule_id: '', beneficiary_type: 'salesperson', seller_id: '', user_id: '', percentage: 100, fixed_amount: 0}; },
            resetExclusion: function () { this.forms.exclusion = {id: '', rule_id: '', exclusion_type: 'product', entity_id: '', entity_code: '', behavior: 'skip_rule'}; },
            resetCatalog: function () { this.forms.catalog = {id: '', catalog_type: 'general', code: '', name: '', description: '', sort_order: 100}; },
            editPlan: function (item) { this.forms.plan = Object.assign({}, item); },
            editVersion: function (item) { this.forms.version = Object.assign({}, item); },
            editGroup: function (item) { this.forms.group = Object.assign({}, item); },
            editRule: function (item) { this.forms.rule = Object.assign({}, item); },
            editStage: function (item) { this.forms.stage = Object.assign({}, item); },
            editBeneficiary: function (item) { this.forms.beneficiary = Object.assign({}, item); },
            editExclusion: function (item) { this.forms.exclusion = Object.assign({}, item); },
            editCatalog: function (item) { this.forms.catalog = Object.assign({}, item); },
            isImmutable: function (version) { return version && (version.status === 'published' || version.status === 'archived'); },
            versionIsImmutable: function (versionId) {
                var version = this.versions.find(function (item) { return String(item.id) === String(versionId); });
                return this.isImmutable(version);
            },
            ruleIsImmutable: function (ruleId) {
                var rule = this.rules.find(function (item) { return String(item.id) === String(ruleId); });
                return rule ? this.versionIsImmutable(rule.version_id) : true;
            },
            planHasPublishedVersion: function (planId) {
                return this.versions.some(function (item) {
                    return String(item.commercial_plan_id) === String(planId) && item.status === 'published';
                });
            },
            groupsForVersion: function (versionId) {
                return this.groups.filter(function (item) { return String(item.version_id) === String(versionId); });
            },
            optionLabel: function (items, value) {
                var match = (items || []).find(function (item) { return String(item.value) === String(value); });
                return match ? match.label : (value || 'Sin definir');
            },
            money: function (value) {
                return '$' + Number(value || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 4});
            },
            baseLabel: function (value) {
                return this.optionLabel(this.options.calculation_bases, value);
            },
            behaviorLabel: function (value) {
                return this.optionLabel(this.options.behaviors, value);
            },
            beneficiariesForRule: function (ruleId) {
                return this.beneficiaries.filter(function (item) {
                    return String(item.rule_id) === String(ruleId);
                });
            },
            beneficiaryLabel: function (beneficiary) {
                var type = this.optionLabel(this.options.beneficiary_types, beneficiary.beneficiary_type);
                var person = '';
                if (beneficiary.seller_id) {
                    person = this.optionLabel(this.options.sellers, beneficiary.seller_id);
                } else if (beneficiary.user_id) {
                    person = this.optionLabel(this.options.users, beneficiary.user_id);
                }
                var share = Number(beneficiary.percentage || 0) > 0
                    ? Number(beneficiary.percentage).toLocaleString('es-MX') + '%'
                    : this.money(beneficiary.fixed_amount || 0);
                return type + (person ? ': ' + person : '') + ' · ' + share;
            },
            statusLabel: function (status) {
                var labels = {draft: 'Borrador', testing: 'Pruebas', published: 'Publicado', archived: 'Archivado'};
                return labels[status] || status;
            },
            statusClass: function (status) {
                return {
                    draft: 'badge-secondary',
                    testing: 'badge-info',
                    published: 'badge-success',
                    archived: 'badge-dark'
                }[status] || 'badge-light';
            }
        }
    });
});
</script>
