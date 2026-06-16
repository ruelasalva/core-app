<?php

class Service_Core_Workspace_Widgets_Erp_Lowstock extends \Service_Core_Workspace_Widgets_Erp_Base
{
    public static function manifest()
    {
        return [
            'code' => 'low_stock',
            'title' => 'Inventario bajo',
            'description' => 'Productos con existencia igual o menor al mínimo configurado.',
            'category' => 'inventory',
            'type' => 'list',
            'icon' => 'bi bi-box-seam',
            'color' => 'warning',
            'permission_key' => 'inventory.access[view]',
            'refresh_time' => 300,
            'dependencies' => [],
            'exportable' => false,
            'configurable' => false,
            'settings_schema' => [],
            'version' => 1,
            'status' => 'active',
        ];
    }

    public function load($context, array $filters = [], array $settings = [])
    {
        if (!$this->table_exists('core_commerce_products')) {
            return $this->payload([]);
        }

        $sku = $this->field('core_commerce_products', ['sku']);
        $name = $this->field('core_commerce_products', ['name']);
        $minimum = $this->field('core_commerce_products', ['stock_min', 'minimum_stock', 'min_stock']);
        $stock = $this->field('core_commerce_products', ['stock_quantity', 'stock']);

        if (!$sku || !$name || !$minimum) {
            return $this->payload([]);
        }

        try {
            if ($this->table_exists('core_inventory_stock_balances') && $this->field('core_inventory_stock_balances', ['quantity_on_hand'])) {
                $rows = \DB::select(['p.'.$sku, 'sku'], ['p.'.$name, 'product'], ['p.'.$minimum, 'minimum'], ['b.quantity_on_hand', 'stock'])
                    ->from(['core_inventory_stock_balances', 'b'])
                    ->join(['core_commerce_products', 'p'], 'inner')->on('b.product_id', '=', 'p.id')
                    ->where('p.active', '=', 1)
                    ->order_by('b.quantity_on_hand', 'asc')
                    ->limit(100)
                    ->execute()
                    ->as_array();
            } elseif ($stock) {
                $rows = \DB::select([$sku, 'sku'], [$name, 'product'], [$minimum, 'minimum'], [$stock, 'stock'])
                    ->from('core_commerce_products')
                    ->where('active', '=', 1)
                    ->order_by($stock, 'asc')
                    ->limit(100)
                    ->execute()
                    ->as_array();
            } else {
                return $this->payload([]);
            }
        } catch (\Exception $e) {
            $this->safe_query_error('low_stock', $e);
            return $this->payload([]);
        }

        $items = [];
        foreach ($rows as $row) {
            $stock_value = (float) \Arr::get($row, 'stock', 0);
            $minimum_value = (float) \Arr::get($row, 'minimum', 0);
            if ($minimum_value <= 0 || $stock_value > $minimum_value) {
                continue;
            }

            $items[] = [
                'sku' => (string) \Arr::get($row, 'sku', '-'),
                'product' => (string) \Arr::get($row, 'product', '-'),
                'stock' => $this->decimal($stock_value),
                'minimum' => $this->decimal($minimum_value),
            ];

            if (count($items) >= 5) {
                break;
            }
        }

        return $this->payload($items);
    }

    protected function payload(array $rows)
    {
        return $this->compact_table_payload(
            [
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'product', 'label' => 'Producto'],
                ['key' => 'stock', 'label' => 'Stock'],
                ['key' => 'minimum', 'label' => 'Mínimo'],
            ],
            $rows,
            [
                'icon' => 'bi bi-box-seam',
                'title' => 'Sin alertas de inventario bajo.',
                'message' => 'No hay productos por debajo del mínimo configurado o falta configurar mínimos.',
            ],
            [
                'label' => 'Abrir Inventario',
                'url' => \Uri::create('admin/inventory'),
                'icon' => 'bi bi-arrow-right',
            ]
        );
    }
}
