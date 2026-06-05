    <div class="card card-outline card-info">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title mb-0">Filas de staging</h3>
            <span class="badge badge-light ml-2">{{ rows.length }} filas</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0 supplier-review-table">
                    <thead>
                        <tr>
                            <th style="width: 36px;">
                                <input type="checkbox" :checked="allVisibleSelected" @change="toggleAllVisible">
                            </th>
                            <th>Proveedor</th>
                            <th>SKU</th>
                            <th>Modelo</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Categor&iacute;a</th>
                            <th class="text-right">Costo proveedor</th>
                            <th class="text-right">Precio sugerido</th>
                            <th>Estado</th>
                            <th>Advertencia</th>
                            <th>Producto</th>
                            <th class="supplier-review-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td><input type="checkbox" :value="row.id" v-model="selectedIds"></td>
                            <td>{{ row.provider_code || '-' }}</td>
                            <td>{{ row.supplier_sku || '-' }}</td>
                            <td>{{ row.supplier_model || '-' }}</td>
                            <td class="supplier-review-name">{{ row.supplier_name || '-' }}</td>
                            <td>{{ row.supplier_brand || '-' }}</td>
                            <td>{{ row.supplier_category || '-' }}</td>
                            <td class="text-right">{{ money(row.supplier_cost || row.supplier_price, row.supplier_currency) }}</td>
                            <td class="text-right">{{ money(row.selling_price, row.supplier_currency) }}</td>
                            <td><span class="badge" :class="statusBadge(row.row_status)">{{ row.row_status_label }}</span></td>
                            <td>{{ row.warning_message || '-' }}</td>
                            <td>
                                <span class="badge" :class="matchBadge(row.match)">{{ row.match.match_label }}</span>
                                <div v-if="row.match.product_id" class="small text-muted">
                                    #{{ row.match.product_id }} {{ row.match.product_sku || '' }} {{ row.match.product_name || '' }}
                                </div>
                            </td>
                            <td class="supplier-review-actions">
                                <button type="button" class="btn btn-xs btn-outline-info" @click="openDetail(row)">
                                    Ver detalle
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-success" :disabled="loadingAction || row.row_status === 'approved' || row.row_status === 'error'" @click="approveRows([row.id])">
                                    Aprobar
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-danger" :disabled="loadingAction || row.row_status === 'rejected' || row.row_status === 'error'" @click="rejectRows([row.id])">
                                    Rechazar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="13" class="text-center text-muted">No hay filas de staging con los filtros seleccionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Se muestran hasta 500 filas. La aprobaci&oacute;n solo cambia el estado de staging; no crea productos ni modifica precios o inventario.
        </div>
    </div>
