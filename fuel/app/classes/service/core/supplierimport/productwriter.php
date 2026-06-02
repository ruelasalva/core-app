<?php

/**
 * SERVICE CORE SUPPLIERIMPORT PRODUCTWRITER
 *
 * Materializa filas aprobadas de staging hacia catalogo real.
 * No actualiza productos existentes, no toca inventario y no descarga imagenes.
 */
class Service_Core_SupplierImport_ProductWriter
{
    public function apply_approved()
    {
        $this->assert_tables();

        $rows = \DB::select()
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1)
            ->where('row_status', '=', 'approved')
            ->order_by('id', 'asc')
            ->limit(500)
            ->execute();

        $result = [
            'approved_found' => 0,
            'products_created' => 0,
            'existing_products_mapped' => 0,
            'skipped' => 0,
            'errors' => 0,
            'messages' => [],
        ];

        foreach ($rows as $row) {
            $result['approved_found']++;

            try {
                $action = $this->apply_row($row);
                if ($action === 'created') {
                    $result['products_created']++;
                } elseif ($action === 'mapped') {
                    $result['existing_products_mapped']++;
                } else {
                    $result['skipped']++;
                }
            } catch (\Exception $e) {
                $result['errors']++;
                $result['messages'][] = 'Fila '.$row['id'].': '.$e->getMessage();
                \Log::error('Error creando producto desde staging proveedor fila='.$row['id'].': '.$e->getMessage());
                $this->append_row_warning((int) $row['id'], $e->getMessage());
            }
        }

        \Log::info('Aplicacion de staging aprobado: aprobadas='.$result['approved_found'].' creadas='.$result['products_created'].' mapeadas='.$result['existing_products_mapped'].' omitidas='.$result['skipped'].' errores='.$result['errors']);

        return $result;
    }

    protected function apply_row(array $row)
    {
        if ((string) \Arr::get($row, 'row_status', '') !== 'approved') {
            return 'skipped';
        }

        if ((int) \Arr::get($row, 'product_id', 0) > 0) {
            $mapping_id = $this->ensure_mapping($row, (int) $row['product_id']);
            $this->update_staging_row((int) $row['id'], (int) $row['product_id'], $mapping_id, 'mapped', '');
            return 'mapped';
        }

        $strong_match = $this->strong_match($row);
        if ((int) \Arr::get($strong_match, 'product_id', 0) > 0) {
            $product_id = (int) $strong_match['product_id'];
            $mapping_id = $this->ensure_mapping($row, $product_id);
            $this->update_staging_row((int) $row['id'], $product_id, $mapping_id, 'mapped', 'Producto existente ligado por coincidencia fuerte.');
            return 'mapped';
        }

        $product_id = $this->create_product($row);
        $mapping_id = $this->ensure_mapping($row, $product_id);
        $this->update_staging_row((int) $row['id'], $product_id, $mapping_id, 'created', '');
        return 'created';
    }

    protected function strong_match(array $row)
    {
        $matcher = new \Service_Core_SupplierImport_Matcher();
        $match = $matcher->match($row);
        $type = (string) \Arr::get($match, 'match_type', '');

        if (in_array($type, ['supplier_mapping', 'product_sku'], true) && (int) \Arr::get($match, 'product_id', 0) > 0) {
            return $match;
        }

        return [];
    }

    protected function create_product(array $row)
    {
        $sku = $this->unique_product_sku($this->product_sku($row));
        $name = trim((string) \Arr::get($row, 'supplier_name', ''));
        if ($name === '') {
            throw new \RuntimeException('La fila aprobada no tiene nombre de producto.');
        }

        $description = trim((string) \Arr::get($row, 'supplier_description', ''));
        $compatibility = trim((string) \Arr::get($row, 'supplier_compatibility', ''));
        $full_description = $description;
        if ($compatibility !== '') {
            $full_description .= ($full_description !== '' ? "\n\n" : '').'Compatibilidad: '.$compatibility;
        }

        $data = [
            'sku' => $sku,
            'name' => $name,
            'slug' => $this->unique_slug_for_table('core_commerce_products', $name ?: $sku),
            'short_description' => substr($description !== '' ? $description : $name, 0, 255),
            'description' => $full_description,
            'brand_id' => $this->ensure_named_catalog('core_commerce_brands', 'Model_Core_Commerce_Brand', \Arr::get($row, 'supplier_brand', '')),
            'category_id' => $this->ensure_named_catalog('core_commerce_categories', 'Model_Core_Commerce_Category', \Arr::get($row, 'supplier_category', '')),
            'subcategory_id' => 0,
            'product_type' => 'product',
            'is_internal_service' => 0,
            'unit_code' => 'pieza',
            'sat_product_service_code' => '01010101',
            'sat_unit_code' => 'H87',
            'sat_object_tax_code' => '02',
            'currency_code' => strtoupper(substr((string) \Arr::get($row, 'supplier_currency', 'MXN'), 0, 3)) ?: 'MXN',
            'price' => max(0, (float) \Arr::get($row, 'selling_price', 0)),
            'cost' => max(0, (float) \Arr::get($row, 'supplier_cost', \Arr::get($row, 'supplier_price', 0))),
            'tax_code' => 'iva_16',
            'sat_tax_code' => '002',
            'sat_tax_factor_type' => 'Tasa',
            'sat_tax_rate' => 0.16,
            'main_image_path' => '',
            'show_in_home' => 0,
            'featured' => 0,
            'published' => 0,
            'active' => 1,
            'sort_order' => 0,
        ];
        $data = $this->filter_existing_fields('core_commerce_products', $data);

        $product = \Model_Core_Commerce_Product::forge($data);
        $product->save();

        return (int) $product->id;
    }

    protected function ensure_mapping(array $row, $product_id)
    {
        if ($product_id < 1 || !\DBUtil::table_exists('core_purchase_supplier_product_mappings')) {
            return 0;
        }

        $product = \Model_Core_Commerce_Product::find((int) $product_id);
        if (!$product) {
            return 0;
        }

        $supplier_sku = trim((string) \Arr::get($row, 'supplier_sku', ''));
        $description = trim((string) \Arr::get($row, 'supplier_description', ''));
        if ($description === '') {
            $description = trim((string) \Arr::get($row, 'supplier_name', ''));
        }
        $description_hash = $this->supplier_description_hash($description);
        $party_id = (int) \Arr::get($row, 'party_id', 0);

        $query = \DB::select('id')->from('core_purchase_supplier_product_mappings')->where('active', '=', 1);
        if ($party_id > 0 && $supplier_sku !== '') {
            $query->where('party_id', '=', $party_id)->where('supplier_sku', '=', $supplier_sku);
        } elseif ($supplier_sku !== '') {
            $query->where('supplier_sku', '=', $supplier_sku);
        } else {
            $query->where('supplier_description_hash', '=', $description_hash);
        }

        $existing = $query->execute()->current();
        $now = time();
        $data = [
            'party_id' => $party_id,
            'supplier_rfc' => '',
            'supplier_sku' => $supplier_sku,
            'supplier_description' => substr($description, 0, 255),
            'supplier_description_hash' => $description_hash,
            'sat_product_service_code' => (string) $product->sat_product_service_code,
            'sat_unit_code' => (string) $product->sat_unit_code,
            'product_id' => (int) $product->id,
            'internal_sku' => (string) $product->sku,
            'internal_name' => (string) $product->name,
            'unit_code' => (string) $product->unit_code ?: 'pieza',
            'conversion_factor' => 1,
            'last_unit_cost' => max(0, (float) \Arr::get($row, 'supplier_cost', \Arr::get($row, 'supplier_price', 0))),
            'last_seen_at' => $now,
            'active' => 1,
            'updated_at' => $now,
        ];

        if ($existing) {
            \DB::update('core_purchase_supplier_product_mappings')
                ->set($data)
                ->where('id', '=', (int) $existing['id'])
                ->execute();
            return (int) $existing['id'];
        }

        $data['created_by'] = 0;
        $data['created_at'] = $now;
        list($mapping_id) = \DB::insert('core_purchase_supplier_product_mappings')->set($data)->execute();
        return (int) $mapping_id;
    }

    protected function update_staging_row($row_id, $product_id, $mapping_id, $status, $warning)
    {
        \DB::update('core_supplier_import_rows')
            ->set([
                'product_id' => (int) $product_id,
                'mapping_id' => (int) $mapping_id,
                'row_status' => $status,
                'error_message' => trim((string) $warning) ?: null,
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $row_id)
            ->where('active', '=', 1)
            ->execute();
    }

    protected function append_row_warning($row_id, $message)
    {
        $row = \DB::select('error_message')
            ->from('core_supplier_import_rows')
            ->where('id', '=', (int) $row_id)
            ->execute()
            ->current();

        $current = $row ? trim((string) $row['error_message']) : '';
        $message = trim((string) $message);
        $combined = trim($current.($current !== '' && $message !== '' ? ' ' : '').$message);

        \DB::update('core_supplier_import_rows')
            ->set([
                'error_message' => $combined,
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $row_id)
            ->execute();
    }

    protected function product_sku(array $row)
    {
        $sku = strtoupper(trim((string) \Arr::get($row, 'supplier_sku', '')));
        if ($sku !== '') {
            return $sku;
        }

        $model = strtoupper(trim((string) \Arr::get($row, 'supplier_model', '')));
        if ($model !== '') {
            return $model;
        }

        throw new \RuntimeException('La fila aprobada no tiene SKU ni modelo.');
    }

    protected function ensure_named_catalog($table, $model, $name)
    {
        $name = trim((string) $name);
        if ($name === '' || !\DBUtil::table_exists($table)) {
            return 0;
        }

        $row = \DB::select('id')->from($table)->where('name', '=', $name)->execute()->current();
        if ($row) {
            return (int) $row['id'];
        }

        $item = $model::forge([
            'name' => $name,
            'slug' => $this->unique_slug_for_table($table, $name),
            'description' => '',
            'active' => 1,
        ]);
        $item->save();

        return (int) $item->id;
    }

    protected function unique_product_sku($seed)
    {
        $base = strtoupper(trim((string) $seed));
        $base = preg_replace('/[^A-Z0-9\-_]+/', '-', $base);
        $base = trim($base, '-') ?: 'PROD';
        $sku = substr($base, 0, 80);
        $i = 2;

        while (\DB::select('id')->from('core_commerce_products')->where('sku', '=', $sku)->execute()->current()) {
            $suffix = '-'.$i++;
            $sku = substr($base, 0, 80 - strlen($suffix)).$suffix;
        }

        return $sku;
    }

    protected function unique_slug_for_table($table, $seed)
    {
        $base = $this->slugify($seed) ?: 'registro';
        $slug = substr($base, 0, 220);
        $i = 2;

        while (\DB::select('id')->from($table)->where('slug', '=', $slug)->execute()->current()) {
            $suffix = '-'.$i++;
            $slug = substr($base, 0, 220 - strlen($suffix)).$suffix;
        }

        return $slug;
    }

    protected function slugify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    protected function supplier_description_hash($description)
    {
        $clean = strtolower(trim(preg_replace('/\s+/', ' ', (string) $description)));
        return sha1($clean);
    }

    protected function assert_tables()
    {
        foreach (['core_supplier_import_rows', 'core_commerce_products'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla requerida: '.$table.'.');
            }
        }
    }

    protected function filter_existing_fields($table, array $data)
    {
        if (!\DBUtil::table_exists($table)) {
            return $data;
        }

        $filtered = [];
        foreach ($data as $field => $value) {
            if (\DBUtil::field_exists($table, [$field])) {
                $filtered[$field] = $value;
            }
        }

        return $filtered;
    }
}
