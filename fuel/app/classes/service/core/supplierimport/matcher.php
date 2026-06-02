<?php

/**
 * SERVICE CORE SUPPLIERIMPORT MATCHER
 *
 * Busca equivalencias existentes entre proveedor y producto interno.
 * No crea ni actualiza productos.
 */
class Service_Core_SupplierImport_Matcher
{
    public function match(array $row)
    {
        $party_id = (int) \Arr::get($row, 'party_id', 0);
        $supplier_rfc = strtoupper(trim((string) \Arr::get($row, 'supplier_rfc', '')));
        $supplier_sku = trim((string) \Arr::get($row, 'supplier_sku', ''));
        $supplier_name = trim((string) \Arr::get($row, 'supplier_name', ''));
        $supplier_brand = trim((string) \Arr::get($row, 'supplier_brand', ''));
        $supplier_description = trim((string) \Arr::get($row, 'supplier_description', \Arr::get($row, 'supplier_name', '')));

        $mapping = $this->find_mapping($party_id, $supplier_rfc, $supplier_sku, $supplier_description);
        if ($mapping) {
            $product = $this->find_product_by_id((int) $mapping['product_id']);
            return [
                'matched' => true,
                'match_status' => 'existing',
                'match_label' => 'Producto existente por equivalencia',
                'match_type' => 'supplier_mapping',
                'mapping_id' => (int) $mapping['id'],
                'product_id' => (int) $mapping['product_id'],
                'product_sku' => $product ? (string) $product['sku'] : '',
                'product_name' => $product ? (string) $product['name'] : '',
                'product_brand' => $product ? (string) $product['brand_name'] : '',
            ];
        }

        $product = $this->find_product_by_sku($supplier_sku);
        if ($product) {
            return [
                'matched' => true,
                'match_status' => 'existing',
                'match_label' => 'Producto existente por SKU',
                'match_type' => 'product_sku',
                'mapping_id' => 0,
                'product_id' => (int) $product['id'],
                'product_sku' => (string) $product['sku'],
                'product_name' => (string) $product['name'],
                'product_brand' => (string) $product['brand_name'],
            ];
        }

        $product = $this->find_product_by_name_and_brand($supplier_name, $supplier_brand);
        if ($product) {
            return [
                'matched' => true,
                'match_status' => 'possible',
                'match_label' => 'Posible coincidencia por nombre y marca',
                'match_type' => 'product_name_brand',
                'mapping_id' => 0,
                'product_id' => (int) $product['id'],
                'product_sku' => (string) $product['sku'],
                'product_name' => (string) $product['name'],
                'product_brand' => (string) $product['brand_name'],
            ];
        }

        return [
            'matched' => false,
            'match_status' => 'new',
            'match_label' => 'Candidato a producto nuevo',
            'match_type' => '',
            'mapping_id' => 0,
            'product_id' => 0,
            'product_sku' => '',
            'product_name' => '',
            'product_brand' => '',
        ];
    }

    protected function find_mapping($party_id, $supplier_rfc, $supplier_sku, $supplier_description)
    {
        if (!\DBUtil::table_exists('core_purchase_supplier_product_mappings')) {
            return null;
        }

        if ($party_id > 0 && $supplier_sku !== '') {
            $row = \DB::select('id', 'product_id')
                ->from('core_purchase_supplier_product_mappings')
                ->where('party_id', '=', $party_id)
                ->where('supplier_sku', '=', $supplier_sku)
                ->where('active', '=', 1)
                ->execute()
                ->current();

            if ($row) {
                return $row;
            }
        }

        if ($supplier_rfc !== '' && $supplier_sku !== '') {
            $row = \DB::select('id', 'product_id')
                ->from('core_purchase_supplier_product_mappings')
                ->where('supplier_rfc', '=', $supplier_rfc)
                ->where('supplier_sku', '=', $supplier_sku)
                ->where('active', '=', 1)
                ->execute()
                ->current();

            if ($row) {
                return $row;
            }
        }

        $description_hash = sha1($supplier_description);
        if ($supplier_description !== '') {
            return \DB::select('id', 'product_id')
                ->from('core_purchase_supplier_product_mappings')
                ->where('supplier_description_hash', '=', $description_hash)
                ->where('active', '=', 1)
                ->execute()
                ->current();
        }

        return null;
    }

    protected function find_product_by_sku($sku)
    {
        $sku = strtoupper(trim((string) $sku));
        if ($sku === '' || !\DBUtil::table_exists('core_commerce_products')) {
            return null;
        }

        $query = \DB::select(['p.id', 'id'], ['p.sku', 'sku'], ['p.name', 'name']);
        if (\DBUtil::table_exists('core_commerce_brands')) {
            $query->select(['b.name', 'brand_name'])
                ->from(['core_commerce_products', 'p'])
                ->join(['core_commerce_brands', 'b'], 'left')->on('p.brand_id', '=', 'b.id');
        } else {
            $query->select([\DB::expr("''"), 'brand_name'])
                ->from(['core_commerce_products', 'p']);
        }

        return $query
            ->where('p.sku', '=', $sku)
            ->where('p.active', '=', 1)
            ->execute()
            ->current();
    }

    protected function find_product_by_id($product_id)
    {
        if ($product_id < 1 || !\DBUtil::table_exists('core_commerce_products')) {
            return null;
        }

        $query = \DB::select(['p.id', 'id'], ['p.sku', 'sku'], ['p.name', 'name']);
        if (\DBUtil::table_exists('core_commerce_brands')) {
            $query->select(['b.name', 'brand_name'])
                ->from(['core_commerce_products', 'p'])
                ->join(['core_commerce_brands', 'b'], 'left')->on('p.brand_id', '=', 'b.id');
        } else {
            $query->select([\DB::expr("''"), 'brand_name'])
                ->from(['core_commerce_products', 'p']);
        }

        return $query
            ->where('p.id', '=', (int) $product_id)
            ->where('p.active', '=', 1)
            ->execute()
            ->current();
    }

    protected function find_product_by_name_and_brand($name, $brand)
    {
        $name_key = $this->normalize_key($name);
        $brand_key = $this->normalize_key($brand);
        if ($name_key === '' || $brand_key === '' || !\DBUtil::table_exists('core_commerce_products')) {
            return null;
        }

        $query = \DB::select(['p.id', 'id'], ['p.sku', 'sku'], ['p.name', 'name']);
        if (\DBUtil::table_exists('core_commerce_brands')) {
            $query->select(['b.name', 'brand_name'])
                ->from(['core_commerce_products', 'p'])
                ->join(['core_commerce_brands', 'b'], 'left')->on('p.brand_id', '=', 'b.id');
        } else {
            return null;
        }

        $rows = $query
            ->where('p.active', '=', 1)
            ->where('p.name', 'like', substr(trim((string) $name), 0, 80).'%')
            ->limit(50)
            ->execute();

        foreach ($rows as $row) {
            if ($this->normalize_key($row['name']) === $name_key && $this->normalize_key($row['brand_name']) === $brand_key) {
                return $row;
            }
        }

        return null;
    }

    protected function normalize_key($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        return preg_replace('/[^a-z0-9]+/', '', $value);
    }
}
