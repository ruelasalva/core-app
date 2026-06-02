<?php

/**
 * SERVICE CORE SUPPLIERIMPORT MANAGER
 *
 * Punto de entrada seguro para infraestructura de importacion de proveedores.
 * En Phase 1 no importa productos, no actualiza precios y no toca inventario.
 */
class Service_Core_SupplierImport_Manager
{
    protected $required_tables = [
        'core_supplier_import_runs',
        'core_supplier_import_rows',
    ];

    public function required_tables()
    {
        return $this->required_tables;
    }

    public function schema_status()
    {
        $status = [];
        foreach ($this->required_tables as $table) {
            $status[$table] = \DBUtil::table_exists($table);
        }

        return $status;
    }

    public function schema_ready()
    {
        foreach ($this->schema_status() as $exists) {
            if (!$exists) {
                return false;
            }
        }

        return true;
    }

    public function diagnostics()
    {
        $schema = $this->schema_status();
        $runs_count = 0;
        $rows_count = 0;
        $providers = [];

        if (!empty($schema['core_supplier_import_runs'])) {
            $runs_count = (int) \DB::count_records('core_supplier_import_runs');
        }

        if (!empty($schema['core_supplier_import_rows'])) {
            $rows_count = (int) \DB::count_records('core_supplier_import_rows');
        }

        if (\DBUtil::table_exists('core_integration_providers')) {
            $result = \DB::select('id', 'code', 'name', 'category', 'active')
                ->from('core_integration_providers')
                ->where('active', '=', 1)
                ->order_by('category', 'asc')
                ->order_by('name', 'asc')
                ->execute();

            foreach ($result as $row) {
                $providers[] = [
                    'id' => (int) $row['id'],
                    'code' => (string) $row['code'],
                    'name' => (string) $row['name'],
                    'category' => (string) $row['category'],
                    'active' => (int) $row['active'],
                ];
            }
        }

        \Log::info('Diagnostico de importacion de proveedores ejecutado.');

        return [
            'schema' => $schema,
            'runs_count' => $runs_count,
            'rows_count' => $rows_count,
            'providers' => $providers,
        ];
    }

    public function admin_data()
    {
        if (!$this->schema_ready()) {
            throw new \RuntimeException('Falta ejecutar la migracion 070 de importacion de proveedores.');
        }

        return [
            'runs' => $this->runs(),
            'rows' => $this->rows(),
            'validation' => $this->validation_summary(),
            'providers' => $this->supplier_catalog_sources(),
            'sources' => $this->supplier_catalog_sources(),
            'suppliers' => $this->commercial_suppliers(),
        ];
    }

    public function review_data(array $filters = [])
    {
        if (!$this->schema_ready()) {
            throw new \RuntimeException('Falta ejecutar la migracion 070 de importacion de proveedores.');
        }

        return [
            'rows' => $this->review_rows($filters),
            'filters' => $this->review_filter_options(),
            'status_options' => $this->review_status_options(),
            'applied_filters' => [
                'provider' => trim((string) \Arr::get($filters, 'provider', '')),
                'brand' => trim((string) \Arr::get($filters, 'brand', '')),
                'category' => trim((string) \Arr::get($filters, 'category', '')),
                'row_status' => trim((string) \Arr::get($filters, 'row_status', '')),
                'import_run_id' => (int) \Arr::get($filters, 'import_run_id', 0),
            ],
        ];
    }

    public function approve_rows(array $ids)
    {
        return $this->change_rows_status($ids, 'approved');
    }

    public function reject_rows(array $ids)
    {
        return $this->change_rows_status($ids, 'rejected');
    }

    public function apply_approved_rows()
    {
        $writer = new \Service_Core_SupplierImport_ProductWriter();
        return $writer->apply_approved();
    }

    public function download_product_images()
    {
        $manager = new \Service_Core_SupplierImport_ImageManager();
        return $manager->download_images();
    }

    public function csv_template_rows()
    {
        return [
            ['sku', 'model', 'name', 'brand', 'category', 'description', 'compatibility', 'price', 'currency', 'stock', 'image_url', 'source_url'],
            ['TONER-001', 'CF283A', 'Toner compatible HP 83A', 'Marca ejemplo', 'Toners', 'Cartucho de toner compatible', 'HP LaserJet Pro MFP M125/M127', '250.00', 'MXN', '10', 'https://proveedor.example/imagen.jpg', 'https://proveedor.example/producto/toner-001'],
        ];
    }

    public function normalize_provider_code($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $value = preg_replace('/\s+/', '_', $value);
        $value = preg_replace('/[^a-z0-9_]+/', '', $value);
        $value = preg_replace('/_+/', '_', $value);

        return trim($value, '_');
    }

    public function commercial_suppliers()
    {
        if (!\DBUtil::table_exists('core_parties')) {
            return [];
        }

        $rows = \DB::select('id', 'code', 'name', 'legal_name', 'rfc', 'party_type')
            ->from('core_parties')
            ->where('active', '=', 1)
            ->where('party_type', 'in', ['supplier', 'both'])
            ->order_by('name', 'asc')
            ->limit(500)
            ->execute();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'legal_name' => (string) $row['legal_name'],
                'rfc' => (string) $row['rfc'],
                'party_type' => (string) $row['party_type'],
            ];
        }

        return $items;
    }

    public function supplier_catalog_sources()
    {
        $sources = [];

        if (\DBUtil::table_exists('core_integration_providers')) {
            $rows = \DB::select('id', 'code', 'name', 'category', 'active')
                ->from('core_integration_providers')
                ->where('active', '=', 1)
                ->where('category', '=', 'supplier_catalog')
                ->order_by('sort_order', 'asc')
                ->order_by('name', 'asc')
                ->execute();

            foreach ($rows as $row) {
                $sources[] = [
                    'id' => (int) $row['id'],
                    'code' => $this->normalize_provider_code($row['code']),
                    'name' => (string) $row['name'],
                    'category' => (string) $row['category'],
                    'enabled' => $this->normalize_provider_code($row['code']) === 'csv_manual',
                    'pending' => $this->normalize_provider_code($row['code']) !== 'csv_manual',
                ];
            }
        }

        if (!empty($sources)) {
            $existing_codes = array_map(function($source) {
                return (string) \Arr::get($source, 'code', '');
            }, $sources);

            foreach ($this->default_supplier_catalog_sources() as $default_source) {
                if (!in_array((string) $default_source['code'], $existing_codes, true)) {
                    $sources[] = $default_source;
                }
            }

            return $sources;
        }

        return $this->default_supplier_catalog_sources();
    }

    public function default_supplier_catalog_sources()
    {
        return [
            ['id' => 0, 'code' => 'csv_manual', 'name' => 'CSV / Excel manual', 'category' => 'supplier_catalog', 'enabled' => true, 'pending' => false],
            ['id' => 0, 'code' => 'cva_api', 'name' => 'API CVA', 'category' => 'supplier_catalog', 'enabled' => false, 'pending' => true],
            ['id' => 0, 'code' => 'ct_api', 'name' => 'API CT', 'category' => 'supplier_catalog', 'enabled' => false, 'pending' => true],
            ['id' => 0, 'code' => 'syscom_api', 'name' => 'API Syscom', 'category' => 'supplier_catalog', 'enabled' => false, 'pending' => true],
            ['id' => 0, 'code' => 'tvc_api', 'name' => 'API TVC', 'category' => 'supplier_catalog', 'enabled' => false, 'pending' => true],
            ['id' => 0, 'code' => 'pch_api', 'name' => 'API PCH', 'category' => 'supplier_catalog', 'enabled' => false, 'pending' => true],
            ['id' => 0, 'code' => 'exel_api', 'name' => 'API Exel', 'category' => 'supplier_catalog', 'enabled' => false, 'pending' => true],
            ['id' => 0, 'code' => 'tonersparaimpresoras_scraper', 'name' => 'Scraper TonersParaImpresoras', 'category' => 'supplier_catalog', 'enabled' => false, 'pending' => true],
        ];
    }

    public function supplier_catalog_source($code)
    {
        $code = $this->normalize_provider_code($code);
        foreach ($this->supplier_catalog_sources() as $source) {
            if ((string) \Arr::get($source, 'code', '') === $code) {
                return $source;
            }
        }

        return null;
    }

    public function source_available_for_import($code)
    {
        $source = $this->supplier_catalog_source($code);
        if (!$source) {
            return false;
        }

        return !empty($source['enabled']) && empty($source['pending']);
    }

    protected function runs()
    {
        $rows = \DB::select(
                'id',
                'party_id',
                'provider_code',
                'connection_id',
                'import_type',
                'source_name',
                'file_path',
                'dry_run',
                'status',
                'rows_count',
                'created_count',
                'updated_count',
                'skipped_count',
                'error_count',
                'started_at',
                'finished_at',
                'executed_by',
                'created_at'
            )
            ->from('core_supplier_import_runs')
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'party_id' => (int) $row['party_id'],
                'provider_code' => (string) $row['provider_code'],
                'connection_id' => (int) $row['connection_id'],
                'import_type' => (string) $row['import_type'],
                'source_name' => (string) $row['source_name'],
                'file_path' => (string) $row['file_path'],
                'dry_run' => (int) $row['dry_run'],
                'status' => (string) $row['status'],
                'status_label' => $this->status_label((string) $row['status']),
                'rows_count' => (int) $row['rows_count'],
                'created_count' => (int) $row['created_count'],
                'updated_count' => (int) $row['updated_count'],
                'skipped_count' => (int) $row['skipped_count'],
                'error_count' => (int) $row['error_count'],
                'started_at' => (int) $row['started_at'],
                'started_at_label' => $this->date_label((int) $row['started_at']),
                'finished_at' => (int) $row['finished_at'],
                'finished_at_label' => $this->date_label((int) $row['finished_at']),
                'executed_by' => (int) $row['executed_by'],
                'created_at' => (int) $row['created_at'],
                'created_at_label' => $this->date_label((int) $row['created_at']),
            ];
        }

        return $items;
    }

    protected function rows()
    {
        $rows = \DB::select(
                'id',
                'import_run_id',
                'provider_code',
                'source_hash',
                'supplier_sku',
                'supplier_model',
                'supplier_name',
                'supplier_brand',
                'supplier_category',
                'supplier_currency',
                'supplier_price',
                'supplier_stock',
                'selling_price',
                'product_id',
                'mapping_id',
                'row_status',
                'error_message',
                'created_at'
            )
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(500)
            ->execute();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'import_run_id' => (int) $row['import_run_id'],
                'provider_code' => (string) $row['provider_code'],
                'source_hash' => (string) $row['source_hash'],
                'supplier_sku' => (string) $row['supplier_sku'],
                'supplier_model' => (string) $row['supplier_model'],
                'supplier_name' => (string) $row['supplier_name'],
                'supplier_brand' => (string) $row['supplier_brand'],
                'supplier_category' => (string) $row['supplier_category'],
                'supplier_currency' => (string) $row['supplier_currency'],
                'supplier_price' => (float) $row['supplier_price'],
                'supplier_stock' => (float) $row['supplier_stock'],
                'selling_price' => (float) $row['selling_price'],
                'product_id' => (int) $row['product_id'],
                'mapping_id' => (int) $row['mapping_id'],
                'row_status' => (string) $row['row_status'],
                'row_status_label' => $this->row_status_label((string) $row['row_status']),
                'error_message' => (string) $row['error_message'],
                'created_at' => (int) $row['created_at'],
                'created_at_label' => $this->date_label((int) $row['created_at']),
            ];
        }

        return $items;
    }

    protected function review_rows(array $filters)
    {
        $query = \DB::select(
                'id',
                'import_run_id',
                'party_id',
                'provider_code',
                'source_url',
                'supplier_sku',
                'supplier_model',
                'supplier_name',
                'supplier_brand',
                'supplier_category',
                'supplier_currency',
                'supplier_cost',
                'supplier_price',
                'selling_price',
                'image_url',
                'product_id',
                'mapping_id',
                'row_status',
                'error_message',
                'created_at'
            )
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1);

        $provider = trim((string) \Arr::get($filters, 'provider', ''));
        $brand = trim((string) \Arr::get($filters, 'brand', ''));
        $category = trim((string) \Arr::get($filters, 'category', ''));
        $row_status = trim((string) \Arr::get($filters, 'row_status', ''));
        $import_run_id = (int) \Arr::get($filters, 'import_run_id', 0);

        if ($provider !== '') {
            $query->where('provider_code', '=', $provider);
        }
        if ($brand !== '') {
            $query->where('supplier_brand', '=', $brand);
        }
        if ($category !== '') {
            $query->where('supplier_category', '=', $category);
        }
        if ($row_status !== '') {
            $query->where('row_status', '=', $row_status);
        }
        if ($import_run_id > 0) {
            $query->where('import_run_id', '=', $import_run_id);
        }

        $rows = $query
            ->order_by('id', 'desc')
            ->limit(500)
            ->execute();

        $matcher = new \Service_Core_SupplierImport_Matcher();
        $items = [];
        foreach ($rows as $row) {
            $match = $matcher->match($row);
            $items[] = [
                'id' => (int) $row['id'],
                'import_run_id' => (int) $row['import_run_id'],
                'party_id' => (int) $row['party_id'],
                'provider_code' => (string) $row['provider_code'],
                'source_url' => (string) $row['source_url'],
                'supplier_sku' => (string) $row['supplier_sku'],
                'supplier_model' => (string) $row['supplier_model'],
                'supplier_name' => (string) $row['supplier_name'],
                'supplier_brand' => (string) $row['supplier_brand'],
                'supplier_category' => (string) $row['supplier_category'],
                'supplier_currency' => (string) $row['supplier_currency'],
                'supplier_cost' => (float) $row['supplier_cost'],
                'supplier_price' => (float) $row['supplier_price'],
                'selling_price' => (float) $row['selling_price'],
                'image_url' => (string) $row['image_url'],
                'product_id' => (int) $row['product_id'],
                'mapping_id' => (int) $row['mapping_id'],
                'row_status' => (string) $row['row_status'],
                'row_status_label' => $this->row_status_label((string) $row['row_status']),
                'warning_message' => (string) $row['error_message'],
                'error_message' => (string) $row['error_message'],
                'match' => $match,
                'created_at' => (int) $row['created_at'],
                'created_at_label' => $this->date_label((int) $row['created_at']),
            ];
        }

        return $items;
    }

    protected function review_filter_options()
    {
        return [
            'providers' => $this->distinct_row_values('provider_code'),
            'brands' => $this->distinct_row_values('supplier_brand'),
            'categories' => $this->distinct_row_values('supplier_category'),
            'runs' => $this->review_runs(),
        ];
    }

    protected function distinct_row_values($field)
    {
        $allowed = ['provider_code', 'supplier_brand', 'supplier_category'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $rows = \DB::select([$field, 'value'])
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1)
            ->where($field, '!=', '')
            ->group_by($field)
            ->order_by($field, 'asc')
            ->limit(500)
            ->execute();

        $items = [];
        foreach ($rows as $row) {
            $items[] = (string) $row['value'];
        }

        return $items;
    }

    protected function review_runs()
    {
        $rows = \DB::select('id', 'provider_code', 'source_name', 'created_at')
            ->from('core_supplier_import_runs')
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'provider_code' => (string) $row['provider_code'],
                'source_name' => (string) $row['source_name'],
                'created_at_label' => $this->date_label((int) $row['created_at']),
            ];
        }

        return $items;
    }

    protected function review_status_options()
    {
        $statuses = ['pending', 'mapped', 'approved', 'rejected', 'skipped', 'error'];
        $items = [];
        foreach ($statuses as $status) {
            $items[] = [
                'value' => $status,
                'label' => $this->row_status_label($status),
            ];
        }

        return $items;
    }

    protected function change_rows_status(array $ids, $status)
    {
        if (!$this->schema_ready()) {
            throw new \RuntimeException('Falta ejecutar la migracion 070 de importacion de proveedores.');
        }

        $status = trim((string) $status);
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new \InvalidArgumentException('Estado de aprobacion invalido.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function($id) {
            return $id > 0;
        })));

        if (empty($ids)) {
            throw new \InvalidArgumentException('Selecciona al menos una fila de staging.');
        }

        $allowed_current = ['pending', 'mapped', 'approved', 'rejected', 'skipped'];
        $updated = 0;
        $skipped = 0;
        $skipped_ids = [];

        foreach ($ids as $id) {
            $row = \DB::select('id', 'row_status')
                ->from('core_supplier_import_rows')
                ->where('id', '=', (int) $id)
                ->where('active', '=', 1)
                ->execute()
                ->current();

            if (!$row || !in_array((string) $row['row_status'], $allowed_current, true)) {
                $skipped++;
                $skipped_ids[] = $id;
                continue;
            }

            \DB::update('core_supplier_import_rows')
                ->set([
                    'row_status' => $status,
                    'updated_at' => time(),
                ])
                ->where('id', '=', (int) $id)
                ->where('active', '=', 1)
                ->execute();

            $updated++;
        }

        \Log::info('Revision staging proveedores estado='.$status.' actualizadas='.$updated.' omitidas='.$skipped);

        return [
            'status' => $status,
            'status_label' => $this->row_status_label($status),
            'requested' => count($ids),
            'updated' => $updated,
            'skipped' => $skipped,
            'skipped_ids' => $skipped_ids,
        ];
    }

    protected function validation_summary()
    {
        $total_row = \DB::select(\DB::expr('COUNT(*) AS total'))
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1)
            ->execute()
            ->current();
        $total_rows = $total_row ? (int) $total_row['total'] : 0;

        $invalid_row = \DB::select(\DB::expr('COUNT(*) AS total'))
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1)
            ->where('row_status', '=', 'error')
            ->execute()
            ->current();
        $invalid_rows = $invalid_row ? (int) $invalid_row['total'] : 0;

        $warning_row = \DB::select(\DB::expr('COUNT(*) AS total'))
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1)
            ->where('error_message', '!=', '')
            ->execute()
            ->current();
        $warnings = $warning_row ? (int) $warning_row['total'] : 0;

        $duplicate_row = \DB::query("
            SELECT COUNT(*) AS total
            FROM (
                SELECT source_hash
                FROM core_supplier_import_rows
                WHERE active = 1
                GROUP BY source_hash
                HAVING COUNT(*) > 1
            ) d
        ")->execute()->current();
        $duplicates = $duplicate_row ? (int) $duplicate_row['total'] : 0;

        $dry_run_row = \DB::select(\DB::expr('COUNT(*) AS total'))
            ->from('core_supplier_import_runs')
            ->where('active', '=', 1)
            ->where('dry_run', '=', 1)
            ->execute()
            ->current();

        return [
            'total_rows' => $total_rows,
            'valid_rows' => max(0, $total_rows - $invalid_rows),
            'invalid_rows' => $invalid_rows,
            'duplicates' => $duplicates,
            'warnings' => $warnings,
            'dry_run_runs' => $dry_run_row ? (int) $dry_run_row['total'] : 0,
        ];
    }

    public function import_csv(array $options)
    {
        $file = trim((string) \Arr::get($options, 'file', ''));
        $provider_code = $this->normalize_provider_code(\Arr::get($options, 'provider', ''));
        $source_code = $this->normalize_provider_code(\Arr::get($options, 'source_code', 'csv_manual'));
        $party_id = (int) \Arr::get($options, 'party_id', 0);
        $dry_run = (int) \Arr::get($options, 'dry-run', 1) === 1;

        if ($file === '') {
            throw new \InvalidArgumentException('Debes indicar --file=PATH.');
        }

        if ($provider_code === '' && $party_id > 0) {
            $provider_code = $this->provider_code_from_party($party_id);
        }

        if ($provider_code === '' && $party_id > 0 && $source_code !== '') {
            $provider_code = $source_code;
        }

        if ($provider_code === '' && $party_id < 1) {
            throw new \InvalidArgumentException('Selecciona un proveedor comercial o captura un codigo avanzado de proveedor.');
        }

        if (!is_file($file) || !is_readable($file)) {
            throw new \InvalidArgumentException('No se puede leer el archivo CSV indicado.');
        }

        if (!$dry_run && !$this->schema_ready()) {
            throw new \RuntimeException('Falta ejecutar la migracion 070 de importacion de proveedores.');
        }

        $rows = $this->read_csv($file);
        $normalizer = new \Service_Core_SupplierImport_Normalizer();
        $result = [
            'dry_run' => $dry_run,
            'file' => $file,
            'provider' => $provider_code,
            'party_id' => $party_id,
            'source_code' => $source_code,
            'total_rows' => count($rows),
            'normalized' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'duplicates' => 0,
            'inserted' => 0,
            'skipped' => 0,
            'errors' => 0,
            'warnings' => 0,
            'messages' => [],
            'import_run_id' => 0,
        ];

        $normalized_rows = [];
        foreach ($rows as $index => $row) {
            try {
                $normalized = $normalizer->normalize($row, ['provider_code' => $provider_code]);
                $this->validate_normalized_row($normalized, $index + 2);

                if (!empty($normalized['warnings'])) {
                    $result['warnings'] += count($normalized['warnings']);
                }

                if ($this->source_hash_exists($normalized['source_hash'])) {
                    $result['skipped']++;
                    $result['duplicates']++;
                    $result['messages'][] = 'Fila '.($index + 2).': duplicada por source_hash.';
                    continue;
                }

                $normalized_rows[] = $normalized;
                $result['normalized']++;
                $result['valid_rows']++;
            } catch (\Exception $e) {
                $result['errors']++;
                $result['invalid_rows']++;
                $result['messages'][] = 'Fila '.($index + 2).': '.$e->getMessage();
            }
        }

        if ($dry_run) {
            \Log::info('Dry-run CSV proveedor '.$provider_code.' filas='.$result['total_rows'].' normalizadas='.$result['normalized'].' omitidas='.$result['skipped'].' errores='.$result['errors']);
            return $result;
        }

        $run_id = $this->create_run($provider_code, $file, $result, $party_id, $source_code);
        $result['import_run_id'] = $run_id;

        foreach ($normalized_rows as $row) {
            try {
                $this->insert_row($run_id, $provider_code, $row, $party_id);
                $result['inserted']++;
            } catch (\Database_Exception $e) {
                $result['skipped']++;
                $result['duplicates']++;
                $result['messages'][] = 'source_hash '.$row['source_hash'].': omitido por duplicado.';
            } catch (\Exception $e) {
                $result['errors']++;
                $result['messages'][] = 'source_hash '.$row['source_hash'].': '.$e->getMessage();
            }
        }

        $this->finish_run($run_id, $result);
        \Log::info('Importacion CSV proveedor '.$provider_code.' run='.$run_id.' filas='.$result['total_rows'].' insertadas='.$result['inserted'].' omitidas='.$result['skipped'].' errores='.$result['errors']);

        return $result;
    }

    protected function read_csv($file)
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            throw new \RuntimeException('No se pudo abrir el CSV.');
        }

        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            return [];
        }

        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map([$this, 'csv_key'], $headers);
        $this->validate_csv_headers($headers);
        $rows = [];

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = isset($line[$index]) ? trim((string) $line[$index]) : '';
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    protected function validate_csv_headers(array $headers)
    {
        $allowed = [
            'sku',
            'model',
            'name',
            'brand',
            'category',
            'description',
            'compatibility',
            'price',
            'currency',
            'stock',
            'image_url',
            'source_url',
        ];

        $headers = array_values(array_filter($headers));
        if (empty($headers)) {
            throw new \RuntimeException('El CSV no contiene encabezados validos.');
        }

        $unknown = array_diff($headers, $allowed);
        if (!empty($unknown)) {
            throw new \RuntimeException('Columnas no permitidas en CSV: '.implode(', ', $unknown).'.');
        }

        if (!in_array('sku', $headers, true) && !in_array('model', $headers, true) && !in_array('name', $headers, true)) {
            throw new \RuntimeException('El CSV debe incluir al menos una columna de identidad: sku, model o name.');
        }
    }

    protected function csv_key($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        return trim(preg_replace('/[^a-z0-9]+/', '_', $value), '_');
    }

    protected function validate_normalized_row(array $row, $line_number)
    {
        $has_identity = trim((string) \Arr::get($row, 'supplier_sku', '')) !== ''
            || trim((string) \Arr::get($row, 'supplier_model', '')) !== ''
            || trim((string) \Arr::get($row, 'supplier_name', '')) !== '';

        if (!$has_identity) {
            throw new \RuntimeException('SKU, modelo o nombre son obligatorios.');
        }

        if (trim((string) \Arr::get($row, 'source_hash', '')) === '') {
            throw new \RuntimeException('No se pudo generar source_hash para la fila '.$line_number.'.');
        }
    }

    protected function source_hash_exists($source_hash)
    {
        if (!\DBUtil::table_exists('core_supplier_import_rows')) {
            return false;
        }

        return (bool) \DB::select('id')
            ->from('core_supplier_import_rows')
            ->where('source_hash', '=', (string) $source_hash)
            ->execute()
            ->current();
    }

    protected function provider_code_from_party($party_id)
    {
        if ($party_id < 1 || !\DBUtil::table_exists('core_parties')) {
            return '';
        }

        $row = \DB::select('code', 'name', 'rfc')
            ->from('core_parties')
            ->where('id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->where('party_type', 'in', ['supplier', 'both'])
            ->execute()
            ->current();

        if (!$row) {
            return '';
        }

        $code = $this->normalize_provider_code($row['code']);
        if ($code !== '') {
            return $code;
        }

        $rfc = $this->normalize_provider_code($row['rfc']);
        if ($rfc !== '') {
            return $rfc;
        }

        return $this->normalize_provider_code($row['name']);
    }

    protected function create_run($provider_code, $file, array $result, $party_id = 0, $source_code = 'csv_manual')
    {
        list($id) = \DB::insert('core_supplier_import_runs')->set([
            'party_id' => (int) $party_id,
            'provider_code' => $provider_code,
            'connection_id' => $this->connection_id($source_code ?: $provider_code),
            'import_type' => 'csv',
            'source_name' => basename($file),
            'source_url' => '',
            'file_path' => $file,
            'dry_run' => 0,
            'status' => 'running',
            'rows_count' => (int) \Arr::get($result, 'total_rows', 0),
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'started_at' => time(),
            'finished_at' => 0,
            'executed_by' => 0,
            'summary_json' => null,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        return (int) $id;
    }

    protected function finish_run($run_id, array $result)
    {
        $status = 'completed';
        if ((int) \Arr::get($result, 'errors', 0) > 0) {
            $status = 'error';
        } elseif ((int) \Arr::get($result, 'warnings', 0) > 0 || (int) \Arr::get($result, 'skipped', 0) > 0) {
            $status = 'warning';
        }

        \DB::update('core_supplier_import_runs')->set([
            'status' => $status,
            'created_count' => (int) \Arr::get($result, 'inserted', 0),
            'updated_count' => 0,
            'skipped_count' => (int) \Arr::get($result, 'skipped', 0),
            'error_count' => (int) \Arr::get($result, 'errors', 0),
            'finished_at' => time(),
            'summary_json' => json_encode($result),
            'updated_at' => time(),
        ])->where('id', '=', (int) $run_id)->execute();
    }

    protected function insert_row($run_id, $provider_code, array $row, $party_id = 0)
    {
        $warnings = (array) \Arr::get($row, 'warnings', []);

        \DB::insert('core_supplier_import_rows')->set([
            'import_run_id' => (int) $run_id,
            'party_id' => (int) $party_id,
            'provider_code' => $provider_code,
            'source_hash' => (string) \Arr::get($row, 'source_hash', ''),
            'external_id' => (string) \Arr::get($row, 'external_id', ''),
            'source_url' => (string) \Arr::get($row, 'source_url', ''),
            'supplier_sku' => (string) \Arr::get($row, 'supplier_sku', ''),
            'supplier_model' => (string) \Arr::get($row, 'supplier_model', ''),
            'supplier_name' => (string) \Arr::get($row, 'supplier_name', ''),
            'supplier_brand' => (string) \Arr::get($row, 'supplier_brand', ''),
            'supplier_category' => (string) \Arr::get($row, 'supplier_category', ''),
            'supplier_description' => (string) \Arr::get($row, 'supplier_description', ''),
            'supplier_compatibility' => (string) \Arr::get($row, 'supplier_compatibility', ''),
            'supplier_currency' => (string) \Arr::get($row, 'supplier_currency', 'MXN'),
            'supplier_cost' => (float) \Arr::get($row, 'supplier_cost', 0),
            'supplier_price' => (float) \Arr::get($row, 'supplier_price', 0),
            'supplier_stock' => (float) \Arr::get($row, 'supplier_stock', 0),
            'selling_price' => (float) \Arr::get($row, 'selling_price', 0),
            'image_url' => (string) \Arr::get($row, 'image_url', ''),
            'local_image_path' => '',
            'product_id' => 0,
            'mapping_id' => 0,
            'row_status' => 'pending',
            'error_message' => empty($warnings) ? null : implode(' ', $warnings),
            'raw_json' => (string) \Arr::get($row, 'raw_json', ''),
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function connection_id($provider_code)
    {
        if (!\DBUtil::table_exists('core_integration_providers') || !\DBUtil::table_exists('core_integration_connections')) {
            return 0;
        }

        $row = \DB::select(['c.id', 'connection_id'])
            ->from(['core_integration_connections', 'c'])
            ->join(['core_integration_providers', 'p'], 'inner')
            ->on('p.id', '=', 'c.provider_id')
            ->where(\DB::expr('LOWER(p.code)'), '=', $provider_code)
            ->where('c.active', '=', 1)
            ->order_by('c.enabled', 'desc')
            ->order_by('c.id', 'asc')
            ->execute()
            ->current();

        return $row ? (int) $row['connection_id'] : 0;
    }

    protected function status_label($status)
    {
        $labels = [
            'pending' => 'Pendiente',
            'running' => 'En proceso',
            'completed' => 'Completado',
            'warning' => 'Advertencia',
            'error' => 'Error',
        ];

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    protected function row_status_label($status)
    {
        $labels = [
            'pending' => 'Pendiente',
            'mapped' => 'Mapeada',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'created' => 'Creada',
            'updated' => 'Actualizada',
            'skipped' => 'Omitida',
            'error' => 'Error',
        ];

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    protected function date_label($timestamp)
    {
        return $timestamp > 0 ? date('Y-m-d H:i', $timestamp) : 'Sin fecha';
    }
}
