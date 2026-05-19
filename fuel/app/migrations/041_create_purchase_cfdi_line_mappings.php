<?php

namespace Fuel\Migrations;

class Create_purchase_cfdi_line_mappings
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_purchase_cfdi_line_mappings')) {
            \DBUtil::create_table('core_purchase_cfdi_line_mappings', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'cfdi_detail_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'purchase_order_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'purchase_order_item_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'purchase_invoice_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'line_class' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'internal_purchase'],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'warehouse_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'inventory_movement_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'supplier_sku' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'supplier_description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'internal_sku' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'internal_name' => ['type' => 'varchar', 'constraint' => 200, 'default' => ''],
                'quantity' => ['type' => 'decimal', 'constraint' => '14,4', 'default' => 0],
                'unit_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
                'unit_cost' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'mapped'],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_purchase_cfdi_line_mappings', ['cfdi_id', 'cfdi_detail_id'], 'idx_purchase_cfdi_line');
            \DBUtil::create_index('core_purchase_cfdi_line_mappings', ['purchase_order_id', 'purchase_order_item_id'], 'idx_purchase_cfdi_order_line');
            \DBUtil::create_index('core_purchase_cfdi_line_mappings', ['product_id', 'warehouse_id'], 'idx_purchase_cfdi_product');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_purchase_cfdi_line_mappings');
    }
}
