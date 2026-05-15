<div id="app-inventory" class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Inventario</h3>
    </div>
    <div class="card-body">
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="loading" class="text-muted">Cargando inventario...</div>
        <div v-if="!loading">
            <h5>Existencias</h5>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-hover">
                    <thead><tr><th>SKU</th><th>Producto</th><th class="text-right">Existencia</th><th class="text-right">Reservado</th><th class="text-right">Disponible</th></tr></thead>
                    <tbody>
                        <tr v-for="row in stock" :key="row.product_id">
                            <td>{{ row.sku }}</td>
                            <td>{{ row.name }}</td>
                            <td class="text-right">{{ qty(row.stock_quantity) }}</td>
                            <td class="text-right">{{ qty(row.stock_reserved) }}</td>
                            <td class="text-right">{{ qty(Number(row.stock_quantity || 0) - Number(row.stock_reserved || 0)) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h5>Movimientos recientes</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Fecha</th><th>Almacen</th><th>Producto</th><th>Tipo</th><th class="text-right">Cantidad</th><th>Referencia</th></tr></thead>
                    <tbody>
                        <tr v-for="row in movements" :key="row.id">
                            <td>{{ date(row.created_at) }}</td>
                            <td>{{ row.warehouse_name }}</td>
                            <td>{{ row.sku }} {{ row.product_name }}</td>
                            <td>{{ row.movement_type }}</td>
                            <td class="text-right">{{ qty(row.quantity) }}</td>
                            <td>{{ row.related_entity_type }} #{{ row.related_entity_id }}<div class="text-muted small">{{ row.notes }}</div></td>
                        </tr>
                        <tr v-if="movements.length === 0"><td colspan="6" class="text-muted">Sin movimientos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#app-inventory',
    data: { loading: true, error: '', warehouses: [], stock: [], movements: [] },
    mounted: function() { this.load(); },
    methods: {
        load: function() {
            fetch('<?php echo Uri::create('admin/inventory/data'); ?>')
                .then(res => res.json())
                .then(data => {
                    if (data.error) this.error = data.error;
                    this.warehouses = data.warehouses || [];
                    this.stock = data.stock || [];
                    this.movements = data.movements || [];
                })
                .catch(() => { this.error = 'No se pudo cargar inventario.'; })
                .finally(() => { this.loading = false; });
        },
        qty: function(value) { return Number(value || 0).toFixed(2); },
        date: function(value) {
            if (!value) return '';
            return new Date(Number(value) * 1000).toLocaleString('es-MX');
        }
    }
});
</script>
