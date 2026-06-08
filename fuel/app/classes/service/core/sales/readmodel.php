<?php

/**
 * SERVICE_CORE_SALES_READMODEL
 *
 * Consultas de lectura para el modulo de ventas.
 *
 * @package  app
 */
class Service_Core_Sales_ReadModel
{
    protected $context = [];

    public function __construct(array $context = [])
    {
        $this->context = array_merge([
            'user_id' => 0,
            'user_group' => 0,
            'is_super_admin' => false,
            'department_id' => 0,
        ], $context);
    }

    public static function forge(array $context = [])
    {
        return new static($context);
    }

    public function dashboard_data(array $filters = [])
    {
        $filters = $filters ?: $this->default_filters();

        return [
            'quotes' => $this->quotes($filters),
            'stats' => $this->stats($filters),
            'options' => $this->options(),
            'orders' => $this->orders($filters),
            'deliveries' => $this->deliveries($filters),
            'period_filters' => $filters,
        ];
    }

    public function quotes(array $filters = [])
    {
        $filters = $filters ?: $this->default_filters();

        $rows = \DB::select(
                ['q.id', 'id'],
                ['q.folio', 'folio'],
                ['q.source', 'source'],
                ['q.offline_uuid', 'offline_uuid'],
                ['q.synced_from_offline', 'synced_from_offline'],
                ['q.status', 'status'],
                ['q.currency_code', 'currency_code'],
                ['q.subtotal', 'subtotal'],
                ['q.discount_total', 'discount_total'],
                ['q.tax_total', 'tax_total'],
                ['q.total', 'total'],
                ['q.party_id', 'party_id'],
                ['q.seller_id', 'seller_id'],
                ['q.customer_notes', 'customer_notes'],
                ['q.internal_notes', 'internal_notes'],
                ['q.expires_at', 'expires_at'],
                ['q.created_at', 'created_at'],
                ['p.name', 'party_name'],
                ['p.email', 'party_email'],
                ['p.phone', 'party_phone'],
                ['p.rfc', 'party_rfc'],
                ['s.name', 'seller_name']
            )
            ->from(['core_sales_quotes', 'q'])
            ->join(['core_parties', 'p'], 'left')
                ->on('q.party_id', '=', 'p.id')
            ->join(['core_sales_sellers', 's'], 'left')
                ->on('q.seller_id', '=', 's.id');

        $this->apply_party_scope($rows, 'p', 'sales');
        $rows->where('q.created_at', '>=', strtotime($filters['start_date'].' 00:00:00'))
            ->where('q.created_at', '<=', strtotime($filters['end_date'].' 23:59:59'));

        $rows = $rows
            ->order_by('q.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['items'] = $this->quote_items((int) $row['id']);
            $row['orders'] = $this->quote_orders((int) $row['id']);
            $row['created_label'] = !empty($row['created_at']) ? date('Y-m-d H:i', (int) $row['created_at']) : '';
            $row['expires_label'] = !empty($row['expires_at']) ? date('Y-m-d', (int) $row['expires_at']) : '';
        }
        unset($row);

        return $rows;
    }

    public function quote_items($quote_id)
    {
        $rows = \DB::select(
                ['i.product_id', 'product_id'],
                ['i.sku', 'sku'],
                ['i.name', 'name'],
                ['i.quantity', 'quantity'],
                ['i.unit_price', 'unit_price'],
                ['i.line_total', 'line_total'],
                ['p.main_image_path', 'image_path'],
                ['p.stock_quantity', 'stock_quantity'],
                ['p.stock_reserved', 'stock_reserved']
            )
            ->from(['core_sales_quote_items', 'i'])
            ->join(['core_commerce_products', 'p'], 'left')->on('i.product_id', '=', 'p.id')
            ->where('i.quote_id', '=', (int) $quote_id)
            ->order_by('i.sort_order', 'asc')
            ->order_by('i.id', 'asc')
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['image_url'] = $this->media_url((string) $row['image_path']);
            $row['available_stock'] = max(0, (float) $row['stock_quantity'] - (float) $row['stock_reserved']);
        }
        unset($row);

        return $rows;
    }

    public function quote_orders($quote_id)
    {
        $orders = [];
        if (!\DBUtil::table_exists('core_sales_orders')) {
            return $orders;
        }

        foreach (\DB::select('id', 'folio', 'status', 'total')->from('core_sales_orders')->where('source_quote_id', '=', (int) $quote_id)->where('active', '=', 1)->execute() as $order) {
            $order['items'] = $this->order_items((int) $order['id']);
            $order['deliveries'] = $this->order_deliveries((int) $order['id']);
            $orders[] = $order;
        }

        return $orders;
    }

    public function orders(array $filters = [])
    {
        $filters = $filters ?: $this->default_filters();
        $query = \DB::select(['o.id', 'id'], ['o.folio', 'folio'], ['o.status', 'status'], ['o.order_date', 'order_date'], ['o.currency_code', 'currency_code'], ['o.total', 'total'], ['o.delivered_total', 'delivered_total'], ['o.billed_total', 'billed_total'], ['q.folio', 'quote_folio'], ['p.name', 'party_name'])
            ->from(['core_sales_orders', 'o'])
            ->join(['core_sales_quotes', 'q'], 'left')->on('o.source_quote_id', '=', 'q.id')
            ->join(['core_parties', 'p'], 'left')->on('o.party_id', '=', 'p.id')
            ->where('o.active', '=', 1)
            ->where('o.order_date', '>=', $filters['start_date'])
            ->where('o.order_date', '<=', $filters['end_date']);
        $this->apply_party_scope($query, 'p', 'sales');

        $rows = $query
            ->order_by('o.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['items'] = $this->order_items((int) $row['id']);
            $pending = 0;
            foreach ($row['items'] as $item) {
                $pending += (float) $item['pending_quantity'];
            }
            $row['pending_quantity'] = $pending;
            $row['backorder'] = $pending > 0 && (float) $row['delivered_total'] > 0 ? 1 : 0;
        }
        unset($row);

        return $rows;
    }

    public function order_items($order_id)
    {
        $items = [];
        foreach (\DB::select(
                ['i.id', 'id'],
                ['i.product_id', 'product_id'],
                ['i.sku', 'sku'],
                ['i.name', 'name'],
                ['i.currency_code', 'currency_code'],
                ['i.unit_price', 'unit_price'],
                ['i.quantity', 'quantity'],
                ['i.delivered_quantity', 'delivered_quantity'],
                ['i.billed_quantity', 'billed_quantity'],
                ['p.main_image_path', 'image_path'],
                ['p.stock_quantity', 'stock_quantity'],
                ['p.stock_reserved', 'stock_reserved']
            )
            ->from(['core_sales_order_items', 'i'])
            ->join(['core_commerce_products', 'p'], 'left')->on('i.product_id', '=', 'p.id')
            ->where('i.order_id', '=', (int) $order_id)
            ->order_by('i.sort_order', 'asc')
            ->order_by('i.id', 'asc')
            ->execute() as $row) {
            $row['pending_quantity'] = max(0, (float) $row['quantity'] - (float) $row['delivered_quantity']);
            $row['available_stock'] = max(0, (float) $row['stock_quantity'] - (float) $row['stock_reserved']);
            $row['image_url'] = $this->media_url((string) $row['image_path']);
            $items[] = $row;
        }

        return $items;
    }

    public function order_deliveries($order_id)
    {
        $deliveries = [];
        foreach (\DB::select('id', 'folio', 'status', 'billing_invoice_id', 'total')->from('core_sales_deliveries')->where('order_id', '=', (int) $order_id)->where('active', '=', 1)->execute() as $delivery) {
            $deliveries[] = $delivery;
        }

        return $deliveries;
    }

    public function deliveries(array $filters = [])
    {
        $filters = $filters ?: $this->default_filters();
        $query = \DB::select(['d.id', 'id'], ['d.folio', 'folio'], ['d.status', 'status'], ['d.delivery_date', 'delivery_date'], ['d.currency_code', 'currency_code'], ['d.total', 'total'], ['d.billing_invoice_id', 'billing_invoice_id'], ['o.folio', 'order_folio'], ['p.name', 'party_name'], ['w.name', 'warehouse_name'])
            ->from(['core_sales_deliveries', 'd'])
            ->join(['core_sales_orders', 'o'], 'left')->on('d.order_id', '=', 'o.id')
            ->join(['core_parties', 'p'], 'left')->on('d.party_id', '=', 'p.id')
            ->join(['core_inventory_warehouses', 'w'], 'left')->on('d.warehouse_id', '=', 'w.id')
            ->where('d.active', '=', 1)
            ->where('d.delivery_date', '>=', $filters['start_date'])
            ->where('d.delivery_date', '<=', $filters['end_date']);
        $this->apply_party_scope($query, 'p', 'sales');

        return $query
            ->order_by('d.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    public function stats(array $filters = [])
    {
        $filters = $filters ?: $this->default_filters();
        $start = strtotime($filters['start_date'].' 00:00:00');
        $end = strtotime($filters['end_date'].' 23:59:59');

        return [
            'quotes' => (int) \DB::select()->from('core_sales_quotes')->where('created_at', '>=', $start)->where('created_at', '<=', $end)->execute()->count(),
            'orders' => (int) \DB::select()->from('core_sales_orders')->where('order_date', '>=', $filters['start_date'])->where('order_date', '<=', $filters['end_date'])->execute()->count(),
            'deliveries' => (int) \DB::select()->from('core_sales_deliveries')->where('delivery_date', '>=', $filters['start_date'])->where('delivery_date', '<=', $filters['end_date'])->execute()->count(),
            'prequote' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'prequote')->where('created_at', '>=', $start)->where('created_at', '<=', $end)->execute()->count(),
            'requested' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'requested')->where('created_at', '>=', $start)->where('created_at', '<=', $end)->execute()->count(),
            'approved' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'approved')->where('created_at', '>=', $start)->where('created_at', '<=', $end)->execute()->count(),
            'rejected' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'rejected')->where('created_at', '>=', $start)->where('created_at', '<=', $end)->execute()->count(),
        ];
    }

    public function options()
    {
        return [
            'customers' => $this->select_rows('core_parties', 'id', 'name', ['party_type' => 'customer']),
            'sellers' => \DBUtil::table_exists('core_sales_sellers') ? $this->select_rows('core_sales_sellers', 'id', 'name') : [],
            'products' => $this->product_options(['limit' => 60]),
            'brands' => $this->select_rows('core_commerce_brands', 'id', 'name'),
            'categories' => $this->select_rows('core_commerce_categories', 'id', 'name'),
            'warehouses' => $this->select_rows('core_inventory_warehouses', 'id', 'name'),
        ];
    }

    public function select_rows($table, $value_field, $label_field, array $where = [])
    {
        $items = [];
        $query = \DB::select($value_field, $label_field)->from($table)->where('active', '=', 1);
        foreach ($where as $field => $value) {
            $query->where($field, '=', $value);
        }
        if ($table === 'core_parties') {
            $this->apply_party_scope($query, $table, 'sales');
        }
        foreach ($query->order_by($label_field, 'asc')->execute() as $row) {
            $items[] = ['value' => (int) $row[$value_field], 'label' => (string) $row[$label_field]];
        }

        return $items;
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

    protected function default_filters()
    {
        return [
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-t'),
        ];
    }

    protected function can_view_all_operational()
    {
        return (bool) $this->context['is_super_admin'] || in_array((int) $this->context['user_group'], [80, 90, 100], true);
    }

    protected function apply_party_scope($query, $alias = 'p', $role = 'any')
    {
        if ($this->can_view_all_operational()) {
            return $query;
        }

        $department_id = (int) $this->context['department_id'];
        $user_id = (int) $this->context['user_id'];
        $department_field = $alias.'.department_id';
        $sales_field = $alias.'.sales_user_id';
        $buyer_field = $alias.'.buyer_user_id';

        $query->where_open();
        if ($role === 'sales') {
            $query->where($sales_field, '=', $user_id);
        } elseif ($role === 'purchases') {
            $query->where($buyer_field, '=', $user_id);
        } else {
            $query->where($sales_field, '=', $user_id)
                ->or_where($buyer_field, '=', $user_id);
        }

        if ($department_id > 0) {
            $query->or_where($department_field, '=', $department_id);
        }
        $query->where_close();

        return $query;
    }
}
