    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">Filas de staging</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0 supplier-import-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Run</th>
                            <th>Proveedor</th>
                            <th>SKU</th>
                            <th>Modelo</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Categor&iacute;a</th>
                            <th class="text-right">Precio proveedor</th>
                            <th class="text-right">Precio sugerido</th>
                            <th class="text-right">Stock proveedor</th>
                            <th>Estado</th>
                            <th>Errores / advertencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>{{ row.id }}</td>
                            <td>{{ row.import_run_id }}</td>
                            <td>{{ row.provider_code || '-' }}</td>
                            <td>{{ row.supplier_sku || '-' }}</td>
                            <td>{{ row.supplier_model || '-' }}</td>
                            <td>{{ row.supplier_name || '-' }}</td>
                            <td>{{ row.supplier_brand || '-' }}</td>
                            <td>{{ row.supplier_category || '-' }}</td>
                            <td class="text-right">{{ money(row.supplier_price, row.supplier_currency) }}</td>
                            <td class="text-right">{{ money(row.selling_price, row.supplier_currency) }}</td>
                            <td class="text-right">{{ number(row.supplier_stock) }}</td>
                            <td><span class="badge badge-light">{{ row.row_status_label }}</span></td>
                            <td>{{ row.error_message || '-' }}</td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="13" class="text-center text-muted">Sin filas de staging.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Se muestran hasta 500 filas recientes. Esta pantalla no permite crear productos ni actualizar datos reales.
        </div>
    </div>
