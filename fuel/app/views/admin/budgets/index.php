<div id="app-budgets">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.plans || 0 }}</h3><p>Presupuestos</p></div><div class="icon"><i class="bi bi-clipboard-data"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.approved || 0 }}</h3><p>Aprobados</p></div><div class="icon"><i class="bi bi-check2-circle"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ money(stats.total_amount) }}</h3><p>Total presupuestado</p></div><div class="icon"><i class="bi bi-cash-stack"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ stats.lines || 0 }}</h3><p>Partidas</p></div><div class="icon"><i class="bi bi-list-check"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-light mb-3">
        <div class="card-body py-2">
            <div class="row align-items-end">
                <div class="col-md-3"><label>Desde</label><input type="date" class="form-control" v-model="periodFilters.start_date"></div>
                <div class="col-md-3"><label>Hasta</label><input type="date" class="form-control" v-model="periodFilters.end_date"></div>
                <div class="col-md-3"><button class="btn btn-primary" @click="loadData(selectedPlan ? selectedPlan.id : 0)"><i class="bi bi-funnel"></i> Consultar</button></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Planes presupuestales</h3>
            <button class="btn btn-primary btn-sm ml-auto" @click="openPlan({})"><i class="bi bi-plus-lg"></i> Presupuesto</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Codigo</th><th>Nombre</th><th>Ejercicio</th><th>Departamento</th><th>Centro costo</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="plan in plans" :key="plan.id" :class="selectedPlan && selectedPlan.id == plan.id ? 'table-primary' : ''">
                        <td><strong>{{ plan.code }}</strong></td>
                        <td>{{ plan.name }}</td>
                        <td>{{ plan.fiscal_year_name || '-' }}</td>
                        <td>{{ plan.department_name || '-' }}</td>
                        <td>{{ plan.cost_center_code ? plan.cost_center_code + ' - ' + plan.cost_center_name : '-' }}</td>
                        <td>{{ plan.currency_code }} {{ money(plan.total_amount) }}</td>
                        <td><span class="badge" :class="statusClass(plan.status)">{{ statusLabel(plan.status) }}</span></td>
                        <td>
                            <button class="btn btn-xs btn-outline-info" @click="selectPlan(plan)">Ver</button>
                            <button class="btn btn-xs btn-outline-primary" @click="openPlan(plan)"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <tr v-if="plans.length === 0"><td colspan="8" class="text-center text-muted">Sin presupuestos.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row" v-if="selectedPlan">
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ money(summary.budget_amount) }}</h3><p>Presupuesto seleccionado</p></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3>{{ money(summary.actual_amount) }}</h3><p>Ejercido contable</p></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box" :class="Number(summary.available_amount) >= 0 ? 'bg-success' : 'bg-warning'"><div class="inner"><h3>{{ money(summary.available_amount) }}</h3><p>Disponible</p></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ Number(summary.used_percent || 0).toFixed(1) }}%</h3><p>Uso</p></div></div></div>
    </div>

    <div class="card card-success card-outline" v-if="selectedPlan">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Partidas de {{ selectedPlan.code }}</h3>
            <button class="btn btn-success btn-sm ml-auto" @click="openLine({ plan_id: selectedPlan.id })"><i class="bi bi-plus-lg"></i> Partida</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Periodo</th><th>Cuenta</th><th>Departamento</th><th>Centro costo</th><th>Presupuesto</th><th>Ejercido</th><th>Disponible</th><th>Uso</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="line in lines" :key="line.id">
                        <td>{{ line.period_start }} a {{ line.period_end }}</td>
                        <td>{{ line.account_code ? line.account_code + ' - ' + line.account_name : 'General' }}</td>
                        <td>{{ line.department_name || '-' }}</td>
                        <td>{{ line.cost_center_code ? line.cost_center_code + ' - ' + line.cost_center_name : '-' }}</td>
                        <td>{{ line.currency_code }} {{ money(line.amount) }}</td>
                        <td>{{ line.currency_code }} {{ money(line.actual_amount) }}</td>
                        <td :class="Number(line.available_amount) < 0 ? 'text-danger font-weight-bold' : ''">{{ line.currency_code }} {{ money(line.available_amount) }}</td>
                        <td><span class="badge" :class="usageClass(line)">{{ Number(line.used_percent || 0).toFixed(1) }}%</span></td>
                        <td><button class="btn btn-xs btn-outline-primary" @click="openLine(line)"><i class="bi bi-pencil"></i></button></td>
                    </tr>
                    <tr v-if="lines.length === 0"><td colspan="9" class="text-center text-muted">Sin partidas.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-info">
        Presupuestos no contabiliza ni paga. Controla limites por cuenta, departamento y centro de costo; el ejercido se toma de polizas contables.
    </div>

    <div class="modal fade" id="modal-plan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Presupuesto</h5><button class="close text-white" @click="hideModal('modal-plan')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><label>Codigo</label><input class="form-control" v-model="planForm.code"></div>
                <div class="col-md-8"><label>Nombre</label><input class="form-control" v-model="planForm.name"></div>
                <div class="col-md-4 mt-2"><label>Ejercicio</label><select class="form-control" v-model="planForm.fiscal_year_id"><option value="0">Sin ejercicio</option><option v-for="option in options.fiscal_years" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Departamento</label><select class="form-control" v-model="planForm.department_id"><option value="0">General</option><option v-for="option in options.departments" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Centro costo</label><select class="form-control" v-model="planForm.cost_center_id"><option value="0">General</option><option v-for="option in options.cost_centers" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Moneda</label><select class="form-control" v-model="planForm.currency_code"><option v-for="option in options.currencies" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Estado</label><select class="form-control" v-model="planForm.status"><option value="draft">Borrador</option><option value="approved">Aprobado</option><option value="closed">Cerrado</option><option value="cancelled">Cancelado</option></select></div>
                <div class="col-md-12 mt-2"><label>Notas</label><textarea class="form-control" rows="3" v-model="planForm.notes"></textarea></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-plan')">Cerrar</button><button class="btn btn-primary" @click="savePlan">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-line" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Partida presupuestal</h5><button class="close text-white" @click="hideModal('modal-line')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6"><label>Cuenta contable</label><select class="form-control" v-model="lineForm.account_id"><option value="0">General</option><option v-for="option in options.accounts" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-6"><label>Centro costo</label><select class="form-control" v-model="lineForm.cost_center_id"><option value="0">General</option><option v-for="option in options.cost_centers" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Departamento</label><select class="form-control" v-model="lineForm.department_id"><option value="0">General</option><option v-for="option in options.departments" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Desde</label><input type="date" class="form-control" v-model="lineForm.period_start"></div>
                <div class="col-md-4 mt-2"><label>Hasta</label><input type="date" class="form-control" v-model="lineForm.period_end"></div>
                <div class="col-md-4 mt-2"><label>Moneda</label><select class="form-control" v-model="lineForm.currency_code"><option v-for="option in options.currencies" :value="option.value">{{ option.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Importe</label><input type="number" step="0.01" class="form-control" v-model="lineForm.amount"></div>
                <div class="col-md-2 mt-2"><label>Alerta %</label><input type="number" class="form-control" v-model="lineForm.warning_threshold"></div>
                <div class="col-md-2 mt-2"><label>Bloqueo %</label><input type="number" class="form-control" v-model="lineForm.block_threshold"></div>
                <div class="col-md-12 mt-2"><label>Notas</label><textarea class="form-control" rows="3" v-model="lineForm.notes"></textarea></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-line')">Cerrar</button><button class="btn btn-success" @click="saveLine">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-budgets',
        data: { error: '', plans: [], lines: [], summary: {}, periodFilters: { start_date: '', end_date: '' }, options: { fiscal_years: [], departments: [], cost_centers: [], accounts: [], currencies: [] }, stats: {}, selectedPlan: null, planForm: {}, lineForm: {} },
        mounted() { this.loadData(); },
        methods: {
            loadData(planId) {
                var url = '<?php echo Uri::create('admin/budgets/data'); ?>';
                var params = [];
                if (planId) params.push('plan_id=' + encodeURIComponent(planId));
                if (this.periodFilters.start_date) params.push('start_date=' + encodeURIComponent(this.periodFilters.start_date));
                if (this.periodFilters.end_date) params.push('end_date=' + encodeURIComponent(this.periodFilters.end_date));
                if (params.length) url += '?' + params.join('&');
                fetch(url).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.plans = data.plans || [];
                    this.lines = data.lines || [];
                    this.summary = data.summary || {};
                    this.periodFilters = data.period_filters || this.periodFilters;
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                    if (!this.selectedPlan && this.plans.length) this.selectedPlan = this.plans[0];
                });
            },
            selectPlan(plan) { this.selectedPlan = plan; this.loadData(plan.id); },
            openPlan(plan) {
                this.planForm = Object.assign({ id: 0, code: '', name: '', fiscal_year_id: 0, department_id: 0, cost_center_id: 0, currency_code: 'MXN', status: 'draft', notes: '', active: true }, plan);
                this.normalizeIds(this.planForm, ['fiscal_year_id', 'department_id', 'cost_center_id']);
                this.showModal('modal-plan');
            },
            openLine(line) {
                this.lineForm = Object.assign({ id: 0, plan_id: this.selectedPlan ? this.selectedPlan.id : 0, account_id: 0, department_id: 0, cost_center_id: 0, period_start: new Date().getFullYear() + '-01-01', period_end: new Date().getFullYear() + '-12-31', currency_code: 'MXN', amount: 0, warning_threshold: 80, block_threshold: 100, notes: '', active: true }, line);
                this.normalizeIds(this.lineForm, ['plan_id', 'account_id', 'department_id', 'cost_center_id']);
                this.showModal('modal-line');
            },
            savePlan() {
                this.error = '';
                fetch('<?php echo Uri::create('admin/budgets/save_plan'); ?>', window.coreAppFetchOptions(this.planForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.plans = data.plans || this.plans;
                    this.stats = data.stats || this.stats;
                    var id = data.plan_id || (this.selectedPlan ? this.selectedPlan.id : 0);
                    this.selectedPlan = this.plans.find(plan => String(plan.id) === String(id)) || this.selectedPlan;
                    this.loadData(id);
                    this.hideModal('modal-plan');
                });
            },
            saveLine() {
                this.error = '';
                fetch('<?php echo Uri::create('admin/budgets/save_line'); ?>', window.coreAppFetchOptions(this.lineForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.plans = data.plans || this.plans;
                    this.lines = data.lines || this.lines;
                    this.summary = data.summary || this.summary;
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-line');
                });
            },
            normalizeIds(obj, fields) { fields.forEach(field => { obj[field] = String(obj[field] || 0); }); },
            statusLabel(value) { return ({ draft: 'Borrador', approved: 'Aprobado', closed: 'Cerrado', cancelled: 'Cancelado' })[value] || value; },
            statusClass(value) { return ({ draft: 'badge-secondary', approved: 'badge-success', closed: 'badge-dark', cancelled: 'badge-danger' })[value] || 'badge-light'; },
            usageClass(line) {
                var used = Number(line.used_percent || 0);
                if (used >= Number(line.block_threshold || 100)) return 'badge-danger';
                if (used >= Number(line.warning_threshold || 80)) return 'badge-warning';
                return 'badge-success';
            },
            money(value) { return Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            showModal(id) { $('#' + id).modal('show'); },
            hideModal(id) { $('#' + id).modal('hide'); },
        }
    });
};
</script>
