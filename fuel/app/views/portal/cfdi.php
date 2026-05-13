<div id="app-portal-cfdi">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0"><?php echo e($portal_title); ?></h3>
        </div>
        <div class="card-body">
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Serie/Folio</th>
                            <th>UUID</th>
                            <th class="text-right">Subtotal</th>
                            <th class="text-right">IVA</th>
                            <th class="text-right">Retenciones</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td>{{ item.issued_label }}</td>
                            <td><span class="badge badge-secondary">{{ voucherLabel(item.voucher_type) }}</span></td>
                            <td>{{ [item.serie, item.folio].filter(Boolean).join('-') }}</td>
                            <td><code>{{ item.uuid }}</code></td>
                            <td class="text-right">{{ money(item.subtotal, item.currency) }}</td>
                            <td class="text-right">{{ money(item.tax_transferred_total, item.currency) }}</td>
                            <td class="text-right">{{ money(item.tax_withheld_total, item.currency) }}</td>
                            <td class="text-right">{{ money(item.total, item.currency) }}</td>
                            <td>
                                <span class="badge" :class="item.sat_status === 'cancelado' ? 'badge-danger' : 'badge-success'">{{ item.sat_status }}</span>
                                <span v-if="item.has_payment_complement == 1" class="badge badge-info">REP</span>
                                <span v-if="item.has_waybill == 1" class="badge badge-warning">Carta porte</span>
                            </td>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td colspan="9" class="text-center text-muted py-4">Sin CFDI disponibles para este portal.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-portal-cfdi',
        data: { items: [], error: '' },
        mounted: function() { this.load(); },
        methods: {
            load: function() {
                fetch('<?php echo Uri::create($portal_code.'/cfdi_data'); ?>')
                    .then(function(res) { return res.json(); })
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.items = data.items || [];
                    })
                    .catch(() => { this.error = 'No se pudo cargar CFDI.'; });
            },
            voucherLabel: function(type) {
                var labels = { I: 'Ingreso', E: 'Egreso', T: 'Traslado', P: 'Pago', N: 'Nomina' };
                return labels[type] || type;
            },
            money: function(value, currency) {
                value = parseFloat(value || 0);
                return (currency || 'MXN') + ' ' + value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    });
};
</script>
