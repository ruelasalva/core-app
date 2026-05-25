<div id="app-treasury">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ money(stats.cash_position) }}</h3><p>Posicion bancaria</p></div><div class="icon"><i class="bi bi-bank"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ money(stats.inflow_30) }}</h3><p>Entradas periodo</p></div><div class="icon"><i class="bi bi-arrow-down-circle"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3>{{ money(stats.outflow_30) }}</h3><p>Salidas periodo</p></div><div class="icon"><i class="bi bi-arrow-up-circle"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box" :class="Number(stats.net_30) >= 0 ? 'bg-primary' : 'bg-warning'"><div class="inner"><h3>{{ money(stats.net_30) }}</h3><p>Neto periodo</p></div><div class="icon"><i class="bi bi-graph-up-arrow"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-light mb-3">
        <div class="card-body py-2">
            <div class="row align-items-end">
                <div class="col-md-3"><label>Desde</label><input type="date" class="form-control" v-model="periodFilters.start_date"></div>
                <div class="col-md-3"><label>Hasta</label><input type="date" class="form-control" v-model="periodFilters.end_date"></div>
                <div class="col-md-3"><button class="btn btn-success" @click="loadData"><i class="bi bi-funnel"></i> Consultar</button></div>
            </div>
        </div>
    </div>

    <div class="card card-success card-outline">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Posicion por cuenta</h3>
            <button class="btn btn-success btn-sm ml-auto" @click="openItem({})"><i class="bi bi-plus-lg"></i> Proyeccion manual</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Cuenta</th><th>Banco</th><th>Moneda</th><th>Cuenta/CLABE</th><th>Saldo estimado</th></tr></thead>
                <tbody>
                    <tr v-for="account in bank_accounts" :key="account.id">
                        <td><strong>{{ account.name }}</strong></td>
                        <td>{{ account.bank_name || '-' }}</td>
                        <td>{{ account.currency_code }}</td>
                        <td><span class="text-muted">{{ account.account_number || account.clabe || '-' }}</span></td>
                        <td><strong>{{ money(account.balance) }}</strong></td>
                    </tr>
                    <tr v-if="bank_accounts.length === 0"><td colspan="5" class="text-center text-muted">Sin cuentas bancarias activas.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'forecast'}" @click.prevent="tab = 'forecast'">Flujo proyectado</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'manual'}" @click.prevent="tab = 'manual'">Proyecciones manuales</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-show="tab === 'forecast'" class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Folio</th><th>Tercero</th><th>Importe</th><th>Prob.</th><th>Impacto</th><th>Saldo proyectado</th></tr></thead>
                    <tbody>
                        <tr v-for="row in forecast" :key="row.entity_type + '-' + row.entity_id + '-' + row.flow_type">
                            <td>{{ row.planned_date }}</td>
                            <td><span class="badge" :class="row.flow_type === 'inflow' ? 'badge-success' : 'badge-danger'">{{ flowLabel(row.flow_type) }}</span></td>
                            <td>{{ row.source }}</td>
                            <td><strong>{{ row.folio }}</strong></td>
                            <td>{{ row.party_name || '-' }}</td>
                            <td>{{ row.currency_code }} {{ money(row.amount) }}</td>
                            <td>{{ Number(row.probability || 0).toFixed(0) }}%</td>
                            <td :class="row.flow_type === 'inflow' ? 'text-success' : 'text-danger'">{{ row.flow_type === 'inflow' ? '+' : '-' }}{{ money(row.weighted_amount) }}</td>
                            <td><strong>{{ money(row.running_balance) }}</strong></td>
                        </tr>
                        <tr v-if="forecast.length === 0"><td colspan="9" class="text-center text-muted">Sin movimientos proyectados.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-show="tab === 'manual'" class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Folio</th><th>Fecha</th><th>Tipo</th><th>Tercero</th><th>Cuenta</th><th>Importe</th><th>Estado</th><th>Referencia</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="item in manual_items" :key="item.id">
                            <td><strong>{{ item.folio }}</strong></td>
                            <td>{{ item.planned_date }}</td>
                            <td><span class="badge" :class="item.flow_type === 'inflow' ? 'badge-success' : 'badge-danger'">{{ flowLabel(item.flow_type) }}</span></td>
                            <td>{{ item.party_name || label(options.parties, item.party_id) || '-' }}</td>
                            <td>{{ label(options.bank_accounts, item.bank_account_id) || '-' }}</td>
                            <td>{{ item.currency_code }} {{ money(item.amount) }}</td>
                            <td><span class="badge badge-light">{{ statusLabel(item.status) }}</span></td>
                            <td>{{ item.reference || '-' }}</td>
                            <td><button class="btn btn-xs btn-outline-primary" @click="openItem(item)"><i class="bi bi-pencil"></i></button></td>
                        </tr>
                        <tr v-if="manual_items.length === 0"><td colspan="9" class="text-center text-muted">Sin proyecciones manuales.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        Tesoreria proyecta caja usando CxC, CxP y Bancos/Pagos. Las proyecciones manuales son escenarios; no crean pagos ni movimientos bancarios reales.
    </div>

    <div class="modal fade" id="modal-item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Proyeccion de flujo</h5><button class="close text-white" @click="hideModal('modal-item')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-4"><div class="form-group"><label>Tipo</label><select class="form-control" v-model="itemForm.flow_type"><option value="inflow">Entrada</option><option value="outflow">Salida</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Fecha</label><input type="date" class="form-control" v-model="itemForm.planned_date"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Estado</label><select class="form-control" v-model="itemForm.status"><option value="planned">Planeado</option><option value="confirmed">Confirmado</option><option value="completed">Completado</option><option value="cancelled">Cancelado</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Importe</label><input type="number" step="0.01" class="form-control" v-model="itemForm.amount"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Probabilidad %</label><input type="number" min="0" max="100" class="form-control" v-model="itemForm.probability"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Moneda</label><select class="form-control" v-model="itemForm.currency_code"><option v-for="option in options.currencies" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Tercero</label><select class="form-control" v-model="itemForm.party_id"><option value="0">Sin tercero</option><option v-for="option in options.parties" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Cuenta bancaria</label><select class="form-control" v-model="itemForm.bank_account_id"><option value="0">Sin cuenta especifica</option><option v-for="option in options.bank_accounts" :value="option.value">{{ option.label }}</option></select></div></div>
                <div class="col-md-12"><div class="form-group"><label>Referencia</label><input class="form-control" v-model="itemForm.reference"></div></div>
                <div class="col-md-12"><div class="form-group"><label>Notas</label><textarea class="form-control" rows="3" v-model="itemForm.notes"></textarea></div></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-item')">Cerrar</button><button class="btn btn-success" @click="saveItem">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-treasury',
        data: { error: '', tab: 'forecast', bank_accounts: [], forecast: [], manual_items: [], periodFilters: { start_date: '', end_date: '' }, options: { bank_accounts: [], parties: [], currencies: [] }, stats: {}, itemForm: {} },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                var url = '<?php echo Uri::create('admin/treasury/data'); ?>';
                var params = [];
                if (this.periodFilters.start_date) params.push('start_date=' + encodeURIComponent(this.periodFilters.start_date));
                if (this.periodFilters.end_date) params.push('end_date=' + encodeURIComponent(this.periodFilters.end_date));
                if (params.length) url += '?' + params.join('&');
                fetch(url).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.bank_accounts = data.bank_accounts || [];
                    this.forecast = data.forecast || [];
                    this.manual_items = data.manual_items || [];
                    this.periodFilters = data.period_filters || this.periodFilters;
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                });
            },
            openItem(item) {
                this.itemForm = Object.assign({ id: 0, flow_type: 'inflow', party_id: 0, bank_account_id: 0, planned_date: new Date().toISOString().slice(0, 10), currency_code: 'MXN', amount: 0, probability: 100, status: 'planned', reference: '', notes: '', active: true }, item);
                this.itemForm.party_id = String(this.itemForm.party_id || 0);
                this.itemForm.bank_account_id = String(this.itemForm.bank_account_id || 0);
                this.showModal('modal-item');
            },
            saveItem() {
                this.error = '';
                fetch('<?php echo Uri::create('admin/treasury/save_item'); ?>', window.coreAppFetchOptions(this.itemForm)).then(res => res.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.bank_accounts = data.bank_accounts || this.bank_accounts;
                    this.forecast = data.forecast || this.forecast;
                    this.manual_items = data.manual_items || this.manual_items;
                    this.stats = data.stats || this.stats;
                    this.hideModal('modal-item');
                });
            },
            label(options, value) {
                const found = (options || []).find(option => String(option.value) === String(value));
                return found ? found.label : '';
            },
            flowLabel(value) { return value === 'outflow' ? 'Salida' : 'Entrada'; },
            statusLabel(value) { return ({ planned: 'Planeado', confirmed: 'Confirmado', completed: 'Completado', cancelled: 'Cancelado' })[value] || value; },
            money(value) { return Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            showModal(id) { $('#' + id).modal('show'); },
            hideModal(id) { $('#' + id).modal('hide'); },
        }
    });
};
</script>
