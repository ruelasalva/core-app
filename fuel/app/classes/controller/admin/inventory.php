<?php

class Controller_Admin_Inventory extends Controller_Adminbase
{
    public function before()
    {
        parent::before();
        $this->require_access('inventory.access[view]');
    }

    public function action_index()
    {
        $this->template->title = 'Inventario';
        $this->template->content = View::forge('admin/inventory/index');
    }

    public function action_data()
    {
        try {
            return $this->json_response([
                'warehouses' => $this->warehouses(),
                'products' => $this->products(),
                'stock' => $this->stock(),
                'movements' => $this->movements(),
                'deliveries' => $this->deliveries(),
                'audit' => $this->audit(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando inventario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar inventario.'], 500);
        }
    }

    public function action_save_movement()
    {
        $this->require_access('inventory.access[edit]');

        try {
            $val = (array) \Input::json();
            $type = $this->movement_type((string) \Arr::get($val, 'movement_type', 'adjustment_in'));
            $warehouse_id = (int) \Arr::get($val, 'warehouse_id', 0);
            $target_warehouse_id = (int) \Arr::get($val, 'target_warehouse_id', 0);
            $notes = trim((string) \Arr::get($val, 'notes', ''));
            $items = $this->movement_items($val);

            if ($warehouse_id < 1 || !Model_Core_Inventory_Warehouse::find($warehouse_id)) {
                return $this->json_response(['error' => 'Almacen invalido.'], 422);
            }
            if (empty($items)) {
                return $this->json_response(['error' => 'Agrega al menos un producto al movimiento.'], 422);
            }

            $ref = time();
            if ($type === 'transfer') {
                if ($target_warehouse_id < 1 || $target_warehouse_id === $warehouse_id || !Model_Core_Inventory_Warehouse::find($target_warehouse_id)) {
                    return $this->json_response(['error' => 'Selecciona almacen destino diferente.'], 422);
                }
                foreach ($items as $item) {
                    $this->insert_movement($warehouse_id, $item['product_id'], 'transfer_out', -$item['quantity'], 'inventory', 'transfer', $ref, $notes);
                    $this->adjust_balance($warehouse_id, $item['product_id'], -$item['quantity']);
                    $this->insert_movement($target_warehouse_id, $item['product_id'], 'transfer_in', $item['quantity'], 'inventory', 'transfer', $ref, $notes);
                    $this->adjust_balance($target_warehouse_id, $item['product_id'], $item['quantity']);
                    $this->refresh_product_stock($item['product_id']);
                }
            } else {
                foreach ($items as $item) {
                    $signed = in_array($type, ['adjustment_out', 'sale_out', 'damage_out'], true) ? -$item['quantity'] : $item['quantity'];
                    $this->insert_movement($warehouse_id, $item['product_id'], $type, $signed, 'inventory', 'manual', $ref, $notes);
                    $this->adjust_balance($warehouse_id, $item['product_id'], $signed);
                    $this->refresh_product_stock($item['product_id']);
                }
            }

            Helper_Core_Audit::log([
                'module' => 'inventory',
                'action' => 'save_movement',
                'entity_type' => 'inventory_movement',
                'summary' => 'Movimiento de inventario '.$type,
                'new_values' => $val,
            ]);

            return $this->json_response([
                'status' => 'ok',
                'stock' => $this->stock(),
                'movements' => $this->movements(),
                'audit' => $this->audit(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando movimiento de inventario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el movimiento.'], 400);
        }
    }

    protected function warehouses()
    {
        return \DB::select('id', 'code', 'name', 'is_default', 'active')
            ->from('core_inventory_warehouses')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->execute()
            ->as_array();
    }

    protected function products()
    {
        return \DB::select('id', 'sku', 'name', 'stock_quantity', 'stock_reserved')
            ->from('core_commerce_products')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->limit(500)
            ->execute()
            ->as_array();
    }

    protected function stock()
    {
        if (\DBUtil::table_exists('core_inventory_stock_balances')) {
            return \DB::select(['b.id', 'balance_id'], ['b.product_id', 'product_id'], ['b.warehouse_id', 'warehouse_id'], ['w.name', 'warehouse_name'], ['p.sku', 'sku'], ['p.name', 'name'], ['b.quantity_on_hand', 'stock_quantity'], ['b.quantity_reserved', 'stock_reserved'], ['b.last_movement_at', 'stock_updated_at'])
                ->from(['core_inventory_stock_balances', 'b'])
                ->join(['core_commerce_products', 'p'], 'inner')->on('b.product_id', '=', 'p.id')
                ->join(['core_inventory_warehouses', 'w'], 'left')->on('b.warehouse_id', '=', 'w.id')
                ->where('p.active', '=', 1)
                ->order_by('p.name', 'asc')
                ->order_by('w.name', 'asc')
                ->limit(500)
                ->execute()
                ->as_array();
        }

        return \DB::select(['p.id', 'product_id'], [\DB::expr('0'), 'warehouse_id'], [\DB::expr("''"), 'warehouse_name'], ['p.sku', 'sku'], ['p.name', 'name'], ['p.stock_quantity', 'stock_quantity'], ['p.stock_reserved', 'stock_reserved'], ['p.stock_updated_at', 'stock_updated_at'])
            ->from(['core_commerce_products', 'p'])
            ->where('p.active', '=', 1)
            ->order_by('p.name', 'asc')
            ->limit(300)
            ->execute()
            ->as_array();
    }

    protected function movements()
    {
        return \DB::select(['m.id', 'id'], ['m.movement_type', 'movement_type'], ['m.quantity', 'quantity'], ['m.unit_cost', 'unit_cost'], ['m.related_module', 'related_module'], ['m.related_entity_type', 'related_entity_type'], ['m.related_entity_id', 'related_entity_id'], ['m.notes', 'notes'], ['m.created_by', 'created_by'], ['m.created_at', 'created_at'], ['p.sku', 'sku'], ['p.name', 'product_name'], ['w.name', 'warehouse_name'])
            ->from(['core_inventory_movements', 'm'])
            ->join(['core_commerce_products', 'p'], 'left')->on('m.product_id', '=', 'p.id')
            ->join(['core_inventory_warehouses', 'w'], 'left')->on('m.warehouse_id', '=', 'w.id')
            ->order_by('m.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function deliveries()
    {
        if (!\DBUtil::table_exists('core_sales_deliveries')) {
            return [];
        }

        return \DB::select(['d.id', 'id'], ['d.folio', 'folio'], ['d.status', 'status'], ['d.delivery_date', 'delivery_date'], ['d.total', 'total'], ['d.notes', 'notes'], ['d.billing_invoice_id', 'billing_invoice_id'], ['o.folio', 'order_folio'], ['p.name', 'party_name'], ['w.name', 'warehouse_name'])
            ->from(['core_sales_deliveries', 'd'])
            ->join(['core_sales_orders', 'o'], 'left')->on('d.order_id', '=', 'o.id')
            ->join(['core_parties', 'p'], 'left')->on('d.party_id', '=', 'p.id')
            ->join(['core_inventory_warehouses', 'w'], 'left')->on('d.warehouse_id', '=', 'w.id')
            ->order_by('d.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function audit()
    {
        if (\DBUtil::table_exists('core_inventory_stock_balances')) {
            return \DB::select(
                    ['b.product_id', 'product_id'],
                    ['b.warehouse_id', 'warehouse_id'],
                    ['w.name', 'warehouse_name'],
                    ['p.sku', 'sku'],
                    ['p.name', 'name'],
                    ['b.quantity_on_hand', 'stock_quantity'],
                    [\DB::expr('COALESCE(SUM(m.quantity), 0)'), 'movement_balance'],
                    [\DB::expr('(b.quantity_on_hand - COALESCE(SUM(m.quantity), 0))'), 'difference']
                )
                ->from(['core_inventory_stock_balances', 'b'])
                ->join(['core_commerce_products', 'p'], 'inner')->on('b.product_id', '=', 'p.id')
                ->join(['core_inventory_warehouses', 'w'], 'left')->on('b.warehouse_id', '=', 'w.id')
                ->join(['core_inventory_movements', 'm'], 'left')
                    ->on('b.product_id', '=', 'm.product_id')
                    ->on('b.warehouse_id', '=', 'm.warehouse_id')
                ->where('p.active', '=', 1)
                ->group_by('b.product_id')
                ->group_by('b.warehouse_id')
                ->group_by('w.name')
                ->group_by('p.sku')
                ->group_by('p.name')
                ->group_by('b.quantity_on_hand')
                ->order_by(\DB::expr('ABS(b.quantity_on_hand - COALESCE(SUM(m.quantity), 0))'), 'desc')
                ->limit(300)
                ->execute()
                ->as_array();
        }

        return \DB::select(
                ['p.id', 'product_id'],
                ['p.sku', 'sku'],
                ['p.name', 'name'],
                ['p.stock_quantity', 'stock_quantity'],
                ['p.stock_reserved', 'stock_reserved'],
                [\DB::expr('COALESCE(SUM(m.quantity), 0)'), 'movement_balance'],
                [\DB::expr('(p.stock_quantity - COALESCE(SUM(m.quantity), 0))'), 'difference']
            )
            ->from(['core_commerce_products', 'p'])
            ->join(['core_inventory_movements', 'm'], 'left')->on('p.id', '=', 'm.product_id')
            ->where('p.active', '=', 1)
            ->group_by('p.id')
            ->group_by('p.sku')
            ->group_by('p.name')
            ->group_by('p.stock_quantity')
            ->group_by('p.stock_reserved')
            ->order_by(\DB::expr('ABS(p.stock_quantity - COALESCE(SUM(m.quantity), 0))'), 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function stats()
    {
        $pending_deliveries = \DBUtil::table_exists('core_sales_deliveries')
            ? \DB::select()->from('core_sales_deliveries')->where('status', '!=', 'cancelled')->where('billing_invoice_id', '=', 0)->execute()->count()
            : 0;

        return [
            'products' => (int) \DB::select()->from('core_commerce_products')->where('active', '=', 1)->execute()->count(),
            'warehouses' => (int) \DB::select()->from('core_inventory_warehouses')->where('active', '=', 1)->execute()->count(),
            'movements' => (int) \DB::count_records('core_inventory_movements'),
            'pending_deliveries' => (int) $pending_deliveries,
        ];
    }

    protected function movement_type($type)
    {
        $allowed = ['adjustment_in', 'adjustment_out', 'purchase_in', 'sale_out', 'damage_out', 'transfer'];
        return in_array($type, $allowed, true) ? $type : 'adjustment_in';
    }

    protected function movement_items(array $payload)
    {
        $raw_items = (array) \Arr::get($payload, 'items', []);
        if (empty($raw_items) && (int) \Arr::get($payload, 'product_id', 0) > 0) {
            $raw_items[] = [
                'product_id' => (int) \Arr::get($payload, 'product_id', 0),
                'quantity' => (float) \Arr::get($payload, 'quantity', 0),
            ];
        }

        $items = [];
        foreach ($raw_items as $raw) {
            $raw = (array) $raw;
            $product_id = (int) \Arr::get($raw, 'product_id', 0);
            $quantity = max(0, (float) \Arr::get($raw, 'quantity', 0));
            if ($product_id < 1 || $quantity <= 0 || !Model_Core_Commerce_Product::find($product_id)) {
                continue;
            }
            $items[] = ['product_id' => $product_id, 'quantity' => $quantity];
        }

        return $items;
    }

    protected function insert_movement($warehouse_id, $product_id, $type, $quantity, $module, $entity_type, $entity_id, $notes)
    {
        Model_Core_Inventory_Movement::forge([
            'warehouse_id' => (int) $warehouse_id,
            'product_id' => (int) $product_id,
            'movement_type' => $type,
            'quantity' => (float) $quantity,
            'unit_cost' => 0,
            'related_module' => $module,
            'related_entity_type' => $entity_type,
            'related_entity_id' => (int) $entity_id,
            'notes' => $notes,
            'created_by' => $this->user_id,
        ])->save();
    }

    protected function adjust_balance($warehouse_id, $product_id, $quantity)
    {
        if (!\DBUtil::table_exists('core_inventory_stock_balances')) {
            return;
        }

        $now = time();
        $row = \DB::select('id', 'quantity_on_hand')
            ->from('core_inventory_stock_balances')
            ->where('warehouse_id', '=', (int) $warehouse_id)
            ->where('product_id', '=', (int) $product_id)
            ->execute()
            ->current();

        if ($row) {
            \DB::update('core_inventory_stock_balances')
                ->set([
                    'quantity_on_hand' => \DB::expr('GREATEST(0, quantity_on_hand + '.(float) $quantity.')'),
                    'last_movement_at' => $now,
                    'updated_at' => $now,
                ])
                ->where('id', '=', (int) $row['id'])
                ->execute();
            return;
        }

        \DB::insert('core_inventory_stock_balances')->set([
            'warehouse_id' => (int) $warehouse_id,
            'product_id' => (int) $product_id,
            'quantity_on_hand' => max(0, (float) $quantity),
            'quantity_reserved' => 0,
            'last_movement_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
    }

    protected function refresh_product_stock($product_id)
    {
        if (!\DBUtil::table_exists('core_inventory_stock_balances')) {
            return;
        }

        $row = \DB::select([\DB::expr('COALESCE(SUM(quantity_on_hand), 0)'), 'stock'], [\DB::expr('COALESCE(SUM(quantity_reserved), 0)'), 'reserved'])
            ->from('core_inventory_stock_balances')
            ->where('product_id', '=', (int) $product_id)
            ->execute()
            ->current();

        \DB::update('core_commerce_products')
            ->set([
                'stock_quantity' => (float) $row['stock'],
                'stock_reserved' => (float) $row['reserved'],
                'stock_updated_at' => time(),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $product_id)
            ->execute();
    }
}
