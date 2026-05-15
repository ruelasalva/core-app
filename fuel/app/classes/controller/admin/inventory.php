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
                'stock' => $this->stock(),
                'movements' => $this->movements(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando inventario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar inventario.'], 500);
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
        return \DB::select(['m.id', 'id'], ['m.movement_type', 'movement_type'], ['m.quantity', 'quantity'], ['m.related_entity_type', 'related_entity_type'], ['m.related_entity_id', 'related_entity_id'], ['m.notes', 'notes'], ['m.created_at', 'created_at'], ['p.sku', 'sku'], ['p.name', 'product_name'], ['w.name', 'warehouse_name'])
            ->from(['core_inventory_movements', 'm'])
            ->join(['core_commerce_products', 'p'], 'left')->on('m.product_id', '=', 'p.id')
            ->join(['core_inventory_warehouses', 'w'], 'left')->on('m.warehouse_id', '=', 'w.id')
            ->order_by('m.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }
}
