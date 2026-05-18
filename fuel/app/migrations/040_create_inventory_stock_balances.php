<?php

namespace Fuel\Migrations;

class Create_inventory_stock_balances
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_inventory_stock_balances')) {
            \DBUtil::create_table('core_inventory_stock_balances', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'warehouse_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'quantity_on_hand' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'quantity_reserved' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'last_movement_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_inventory_stock_balances', ['warehouse_id', 'product_id'], 'idx_inventory_balance_unique', 'unique');
            \DBUtil::create_index('core_inventory_stock_balances', 'product_id', 'idx_inventory_balance_product');
        }

        $warehouse_id = $this->default_warehouse_id();
        if ($warehouse_id > 0) {
            $now = time();
            $rows = \DB::select('id', 'stock_quantity', 'stock_reserved')
                ->from('core_commerce_products')
                ->where('active', '=', 1)
                ->execute();

            foreach ($rows as $row) {
                $exists = \DB::select('id')
                    ->from('core_inventory_stock_balances')
                    ->where('warehouse_id', '=', $warehouse_id)
                    ->where('product_id', '=', (int) $row['id'])
                    ->execute()
                    ->current();
                if ($exists) {
                    continue;
                }
                \DB::insert('core_inventory_stock_balances')->set([
                    'warehouse_id' => $warehouse_id,
                    'product_id' => (int) $row['id'],
                    'quantity_on_hand' => (float) $row['stock_quantity'],
                    'quantity_reserved' => (float) $row['stock_reserved'],
                    'last_movement_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_inventory_stock_balances');
    }

    protected function default_warehouse_id()
    {
        if (!\DBUtil::table_exists('core_inventory_warehouses')) {
            return 0;
        }

        $row = \DB::select('id')
            ->from('core_inventory_warehouses')
            ->where('is_default', '=', 1)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if ($row) {
            return (int) $row['id'];
        }

        $row = \DB::select('id')
            ->from('core_inventory_warehouses')
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }
}
