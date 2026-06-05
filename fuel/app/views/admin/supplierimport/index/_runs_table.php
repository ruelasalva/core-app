    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Importaciones</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0 supplier-import-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Tipo</th>
                            <th>Archivo</th>
                            <th>Estado</th>
                            <th class="text-right">Filas</th>
                            <th class="text-right">Insertadas</th>
                            <th class="text-right">Omitidas</th>
                            <th class="text-right">Errores</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="run in runs" :key="run.id">
                            <td>{{ run.id }}</td>
                            <td>{{ run.provider_code || '-' }}</td>
                            <td>{{ run.import_type }}</td>
                            <td>{{ run.source_name || run.file_path || '-' }}</td>
                            <td><span class="badge" :class="statusBadge(run.status)">{{ run.status_label }}</span></td>
                            <td class="text-right">{{ number(run.rows_count) }}</td>
                            <td class="text-right">{{ number(run.created_count) }}</td>
                            <td class="text-right">{{ number(run.skipped_count) }}</td>
                            <td class="text-right">{{ number(run.error_count) }}</td>
                            <td>{{ run.created_at_label }}</td>
                        </tr>
                        <tr v-if="runs.length === 0">
                            <td colspan="10" class="text-center text-muted">Sin importaciones registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Las ejecuciones dry-run por consola validan el archivo sin guardar filas. Las ejecuciones con dry-run=0 guardan solo staging.
        </div>
    </div>
