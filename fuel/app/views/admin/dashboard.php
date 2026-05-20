<?php
    $has_executive = false;
    $has_executive = !empty($modules['executive_dashboard']);
    foreach ((array) $assigned_dashboards as $dashboard) {
        if (isset($dashboard['dashboard_type']) && $dashboard['dashboard_type'] === 'executive_commercial') {
            $has_executive = true;
        }
    }
?>
<div class="row" id="app-dashboard">
    <div class="col-lg-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Bienvenido</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo Auth::get_screen_name(); ?></h5>
                <p class="card-text">Dashboards asignados: <?php echo count((array) $assigned_dashboards); ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Estado del sistema</div>
            <div class="card-body">
                <p v-if="!executive.enabled">Dashboard generico activo. Utiliza el menu lateral para navegar.</p>
                <p v-if="executive.enabled">Dashboard ejecutivo comercial activo con ventas, inventario, cobranza y tendencias.</p>
                <?php \Log::info("Dashboard renderizado visualmente para el usuario."); ?>
            </div>
        </div>
    </div>

    <?php if ($has_executive): ?>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success"><div class="inner"><h3>{{ money(executive.sales.order_total_30d) }}</h3><p>Pedidos 30 dias</p></div><div class="icon"><i class="bi bi-graph-up-arrow"></i></div></div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info"><div class="inner"><h3>{{ money(executive.collections.receivable_total) }}</h3><p>Cuentas por cobrar</p></div><div class="icon"><i class="bi bi-cash-coin"></i></div></div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger"><div class="inner"><h3>{{ executive.inventory.low_stock.length + executive.inventory.negative_stock.length }}</h3><p>Alertas inventario</p></div><div class="icon"><i class="bi bi-box-seam"></i></div></div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning"><div class="inner"><h3>{{ money(executive.trends.next_month_projection) }}</h3><p>Proyeccion siguiente mes</p></div><div class="icon"><i class="bi bi-bar-chart-line"></i></div></div>
    </div>

    <div class="col-lg-8">
        <div class="card card-success card-outline">
            <div class="card-header"><h3 class="card-title mb-0">Ventas por zona, producto y canal</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><h6>Canal</h6><table class="table table-sm"><tr v-for="row in executive.sales.by_channel" :key="row.label"><td>{{ row.label || 'Sin canal' }}</td><td class="text-right">{{ money(row.total) }}</td></tr></table></div>
                    <div class="col-md-4"><h6>Producto</h6><table class="table table-sm"><tr v-for="row in executive.sales.by_product" :key="row.label"><td>{{ row.label }}</td><td class="text-right">{{ money(row.total) }}</td></tr></table></div>
                    <div class="col-md-4"><h6>Zona</h6><table class="table table-sm"><tr v-for="row in executive.sales.by_zone" :key="row.label"><td>{{ row.label }}</td><td class="text-right">{{ money(row.total) }}</td></tr></table></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-danger card-outline">
            <div class="card-header"><h3 class="card-title mb-0">Inventario critico</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item" v-for="row in executive.inventory.negative_stock" :key="'n'+row.product_id">
                        <strong>{{ row.sku }}</strong> {{ row.name }}<div class="text-danger small">{{ row.warehouse_name }} disponible {{ qty(row.available) }}</div>
                    </li>
                    <li class="list-group-item" v-for="row in executive.inventory.low_stock" :key="'l'+row.product_id">
                        <strong>{{ row.sku }}</strong> {{ row.name }}<div class="text-warning small">{{ row.warehouse_name }} disponible {{ qty(row.available) }} / min {{ qty(row.stock_min) }}</div>
                    </li>
                    <li v-if="executive.inventory.low_stock.length === 0 && executive.inventory.negative_stock.length === 0" class="list-group-item text-muted">Sin alertas de inventario.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-info card-outline">
            <div class="card-header"><h3 class="card-title mb-0">Cobranza y credito</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Factura</th><th>Cliente</th><th>Vence</th><th>Credito</th><th class="text-right">Saldo</th></tr></thead>
                    <tbody>
                        <tr v-for="row in executive.collections.top_overdue" :key="row.folio">
                            <td>{{ row.folio }}</td><td>{{ row.party_name }}</td><td>{{ row.due_date || '-' }}</td><td>{{ row.credit_days || 0 }} dias</td><td class="text-right">{{ money(row.balance_due) }}</td>
                        </tr>
                        <tr v-if="executive.collections.top_overdue.length === 0"><td colspan="5" class="text-center text-muted">Sin cobranza pendiente.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-warning card-outline">
            <div class="card-header"><h3 class="card-title mb-0">Tendencia de ventas</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Mes</th><th class="text-right">Cotizado</th><th class="text-right">Pedido</th></tr></thead>
                    <tbody><tr v-for="row in executive.trends.months" :key="row.label"><td>{{ row.label }}</td><td class="text-right">{{ money(row.sales) }}</td><td class="text-right">{{ money(row.orders) }}</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($modules['calendar'])): ?>
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">Mi calendario</h3>
                <a class="btn btn-sm btn-outline-primary ml-auto" href="<?php echo Uri::create('admin/calendar'); ?>"><i class="bi bi-calendar3"></i> Abrir</a>
            </div>
            <div class="card-body">
                <div id="dashboard-mini-calendar" class="dashboard-mini-calendar"></div>
                <div v-if="calendarError" class="alert alert-warning mt-3 mb-0">{{ calendarError }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title mb-0">Pendientes proximos</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li v-for="item in upcoming" :key="item.id" class="list-group-item">
                        <div class="d-flex align-items-start">
                            <span class="badge mr-2 mt-1" :style="{ backgroundColor: item.color, color: '#fff' }">&nbsp;</span>
                            <div><strong>{{ item.title }}</strong><div class="text-muted small">{{ item.start_label }}</div></div>
                        </div>
                    </li>
                    <li v-if="upcoming.length === 0" class="list-group-item text-muted">Sin pendientes proximos</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .dashboard-mini-calendar { min-height: 420px; }
    .dashboard-mini-calendar .fc-toolbar-title { font-size: 1rem; }
    .dashboard-mini-calendar .fc-header-toolbar { margin-bottom: .75rem; }
    .dashboard-mini-calendar .fc-button { padding: .2rem .45rem; font-size: .8rem; }
    .dashboard-mini-calendar .fc-daygrid-event { cursor: pointer; }
</style>

<script>
window.onload = function() {
    new Vue({
        el: '#app-dashboard',
        data: {
            calendar: null,
            calendarError: '',
            events: [],
            executive: {
                enabled: false,
                sales: { order_total_30d: 0, by_channel: [], by_product: [], by_zone: [] },
                inventory: { low_stock: [], negative_stock: [] },
                collections: { receivable_total: 0, top_overdue: [] },
                trends: { months: [], next_month_projection: 0 }
            }
        },
        computed: {
            upcoming: function() {
                return this.events.slice().sort((a, b) => new Date(a.start) - new Date(b.start)).slice(0, 8).map(event => Object.assign({}, event, {
                    start_label: new Date(event.start).toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
                }));
            }
        },
        mounted: function() {
            this.loadDashboard();
            <?php if (!empty($modules['calendar'])): ?>this.loadCalendar();<?php endif; ?>
        },
        methods: {
            loadDashboard: function() {
                fetch('<?php echo Uri::create('admin/dashboard/data'); ?>').then(res => res.json()).then(data => {
                    if (data.executive) this.executive = Object.assign(this.executive, data.executive);
                });
            },
            loadCalendar: function() {
                fetch('<?php echo Uri::create('admin/dashboard/calendar_data'); ?>').then(res => res.json()).then(data => {
                    if (data.error) { this.calendarError = data.error; return; }
                    this.events = data.events || [];
                    this.$nextTick(this.renderCalendar);
                });
            },
            renderCalendar: function() {
                const element = document.getElementById('dashboard-mini-calendar');
                if (!element || typeof FullCalendar === 'undefined') { return; }
                if (!this.calendar) {
                    this.calendar = new FullCalendar.Calendar(element, {
                        locale: 'es',
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        headerToolbar: { left: 'prev,next today', center: 'title', right: 'listWeek,dayGridMonth' },
                        buttonText: { today: 'Hoy', month: 'Mes', list: 'Lista' },
                        eventClick: function(info) {
                            if (info.event.url) {
                                window.location.href = info.event.url;
                                info.jsEvent.preventDefault();
                            }
                        }
                    });
                    this.calendar.render();
                }
                this.calendar.removeAllEvents();
                this.calendar.addEventSource(this.events);
            },
            money: function(value) {
                return Number(value || 0).toLocaleString('es-MX', { maximumFractionDigits: 0 });
            },
            qty: function(value) {
                return Number(value || 0).toFixed(2);
            }
        }
    });
};
</script>
