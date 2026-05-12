<div id="app-sales">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.quotes || 0 }}</h3>
                    <p>Cotizaciones</p>
                </div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.requested || 0 }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Solicitudes de cotizacion</h3>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando ventas...</p>
            </div>

            <table v-show="!loading" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="quote in quotes" :key="quote.id">
                        <td><strong>{{ quote.folio }}</strong><div class="text-muted small">{{ quote.source }}</div></td>
                        <td>{{ quote.party_name || '-' }}<div class="text-muted small">{{ quote.party_email || '' }}</div></td>
                        <td><span class="badge badge-warning">{{ quote.status }}</span></td>
                        <td>{{ quote.currency_code }} {{ money(quote.total) }}</td>
                        <td>{{ quote.created_label }}</td>
                        <td>
                            <div v-for="item in quote.items" :key="item.sku + item.name" class="small">
                                {{ item.quantity }} x {{ item.name }}
                            </div>
                        </td>
                    </tr>
                    <tr v-if="quotes.length === 0">
                        <td colspan="6" class="text-center text-muted">Todavia no hay cotizaciones.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-sales',
        data: {
            loading: true,
            quotes: [],
            stats: { quotes: 0, requested: 0 }
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/sales/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                    });
            },
            money(value) {
                return Number(value || 0).toFixed(2);
            }
        }
    });
};
</script>
