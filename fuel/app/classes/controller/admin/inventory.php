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
            $product_id = (int) \Arr::get($val, 'product_id', 0);
            $warehouse_id = (int) \Arr::get($val, 'warehouse_id', 0);
            $target_warehouse_id = (int) \Arr::get($val, 'target_warehouse_id', 0);
            $quantity = max(0, (float) \Arr::get($val, 'quantity', 0));
            $notes = trim((string) \Arr::get($val, 'notes', ''));

            if ($product_id < 1 || !Model_Core_Commerce_Product::find($product_id)) {
                return $this->json_response(['error' => 'Producto invalido.'], 422);
            }
            if ($warehouse_id < 1 || !Model_Core_Inventory_Warehouse::find($warehouse_id)) {
                return $this->json_response(['error' => 'Almacen invalido.'], 422);
            }
            if ($quantity <= 0) {
                return $this->json_response(['error' => 'Captura cantidad mayor a cero.'], 422);
            }

            if ($type === 'transfer') {
                if ($target_warehouse_id < 1 || $target_warehouse_id === $warehouse_id || !Model_Core_Inventory_Warehouse::find($target_warehouse_id)) {
                    return $this->json_response(['error' => 'Selecciona almacen destino diferente.'], 422);
                }
                $ref = time();
                $this->insert_movement($warehouse_id, $product_id, 'transfer_out', -$quantity, 'inventory', 'transfer', $ref, $notes);
                $this->insert_movement($target_warehouse_id, $product_id, 'transfer_in', $quantity, 'inventory', 'transfer', $ref, $notes);
            } else {
                $signed = in_array($type, ['adjustment_out', 'sale_out', 'damage_out'], true) ? -$quantity : $quantity;
                $this->insert_movement($warehouse_id, $product_id, $type, $signed, 'inventory', 'manual', 0, $notes);
                $this->update_product_stock($product_id, $signed);
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
        return \DB::select(['p.id', 'product_id'], ['p.sku', 'sku'], ['p.name', 'name'], ['p.stock_quantity', 'stock_quantity'], ['p.stock_reserved', 'stock_reserved'], ['p.stock_updated_at', 'stock_updated_at'])
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

    protected function update_product_stock($product_id, $quantity)
    {
        \DB::update('core_commerce_products')
            ->set([
                'stock_quantity' => \DB::expr('GREATEST(0, stock_quantity + '.(float) $quantity.')'),
                'stock_updated_at' => time(),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $product_id)
            ->execute();
    }
}
