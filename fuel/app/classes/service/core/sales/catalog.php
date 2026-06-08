<?php

/**
 * SERVICE_CORE_SALES_CATALOG
 *
 * Consultas de catalogo para captura comercial.
 *
 * @package  app
 */
class Service_Core_Sales_Catalog
{
    public static function forge()
    {
        return new static();
    }

    public function product_options(array $filters = [])
    {
        $items = [];
        $limit = min(120, max(10, (int) \Arr::get($filters, 'limit', 60)));
        $query = \DB::select(
                ['p.id', 'id'],
                ['p.sku', 'sku'],
                ['p.name', 'name'],
                ['p.currency_code', 'currency_code'],
                ['p.price', 'price'],
                ['p.main_image_path', 'main_image_path'],
                ['p.brand_id', 'brand_id'],
                ['p.category_id', 'category_id'],
                ['p.stock_quantity', 'stock_quantity'],
                ['p.stock_reserved', 'stock_reserved'],
                ['b.name', 'brand_name'],
                ['c.name', 'category_name']
            )
            ->from(['core_commerce_products', 'p'])
            ->join(['core_commerce_brands', 'b'], 'left')->on('p.brand_id', '=', 'b.id')
            ->join(['core_commerce_categories', 'c'], 'left')->on('p.category_id', '=', 'c.id')
            ->where('p.active', '=', 1)
            ->where('p.published', '=', 1)
            ->order_by('p.name', 'asc')
            ->limit($limit);

        $q = trim((string) \Arr::get($filters, 'q', ''));
        if ($q !== '') {
            $query->and_where_open()
                ->where('p.name', 'like', '%'.$q.'%')
                ->or_where('p.sku', 'like', '%'.$q.'%')
                ->or_where('b.name', 'like', '%'.$q.'%')
                ->or_where('c.name', 'like', '%'.$q.'%')
                ->and_where_close();
        }
        if ((int) \Arr::get($filters, 'brand_id', 0) > 0) {
            $query->where('p.brand_id', '=', (int) \Arr::get($filters, 'brand_id', 0));
        }
        if ((int) \Arr::get($filters, 'category_id', 0) > 0) {
            $query->where('p.category_id', '=', (int) \Arr::get($filters, 'category_id', 0));
        }
        if (\Arr::get($filters, 'stock', '') === 'available') {
            $query->where(\DB::expr('(p.stock_quantity - p.stock_reserved)'), '>', 0);
        } elseif (\Arr::get($filters, 'stock', '') === 'zero') {
            $query->where(\DB::expr('(p.stock_quantity - p.stock_reserved)'), '<=', 0);
        }

        $rows = $query->execute();

        foreach ($rows as $row) {
            $items[] = [
                'value' => (int) $row['id'],
                'sku' => (string) $row['sku'],
                'label' => trim($row['name'].' '.($row['sku'] ? '('.$row['sku'].')' : '')),
                'currency_code' => (string) $row['currency_code'],
                'price' => (float) $row['price'],
                'brand_id' => (int) $row['brand_id'],
                'brand_name' => (string) $row['brand_name'],
                'category_id' => (int) $row['category_id'],
                'category_name' => (string) $row['category_name'],
                'stock_quantity' => (float) $row['stock_quantity'],
                'stock_reserved' => (float) $row['stock_reserved'],
                'available_stock' => max(0, (float) $row['stock_quantity'] - (float) $row['stock_reserved']),
                'image_url' => $this->media_url((string) $row['main_image_path']),
                'price_ranges' => $this->product_price_ranges((int) $row['id']),
            ];
        }

        return $items;
    }

    public function product_price_ranges($product_id)
    {
        if (!\DBUtil::table_exists('core_commerce_product_prices')) {
            return [];
        }

        return \DB::select('price_list_id', 'currency_code', 'price', 'min_quantity', 'max_quantity')
            ->from('core_commerce_product_prices')
            ->where('product_id', '=', (int) $product_id)
            ->where('active', '=', 1)
            ->order_by('min_quantity', 'asc')
            ->limit(8)
            ->execute()
            ->as_array();
    }

    public function media_url($path)
    {
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return \Uri::base(false).ltrim($path, '/');
    }
}
