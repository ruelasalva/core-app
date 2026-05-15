<?php

namespace Fuel\Migrations;

class Create_sales_delivery_inventory_flow
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_inventory_warehouses')) {
            \DBUtil::create_table('core_inventory_warehouses', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'branch_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'is_default' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_inventory_warehouses', 'code', 'idx_core_inventory_warehouses_code', 'unique');
        }

        if (!\DBUtil::table_exists('core_inventory_movements')) {
            \DBUtil::create_table('core_inventory_movements', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'warehouse_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'movement_type' => ['type' => 'varchar', 'constraint' => 30],
                'quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'unit_cost' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 0],
                'related_module' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'related_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'related_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_inventory_movements', ['product_id', 'warehouse_id'], 'idx_core_inventory_movements_product');
            \DBUtil::create_index('core_inventory_movements', ['related_module', 'related_entity_id'], 'idx_core_inventory_movements_related');
        }

        if (!\DBUtil::table_exists('core_sales_orders')) {
            \DBUtil::create_table('core_sales_orders', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'source_quote_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
                'order_date' => ['type' => 'varchar', 'constraint' => 10],
                'currency_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'MXN'],
                'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'discount_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'tax_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'delivered_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'billed_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sales_orders', 'folio', 'idx_core_sales_orders_folio', 'unique');
            \DBUtil::create_index('core_sales_orders', ['party_id', 'status'], 'idx_core_sales_orders_party_status');
            \DBUtil::create_index('core_sales_orders', 'source_quote_id', 'idx_core_sales_orders_quote');
        }

        if (!\DBUtil::table_exists('core_sales_order_items')) {
            \DBUtil::create_table('core_sales_order_items', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'order_id' => ['type' => 'int', 'constraint' => 11],
                'quote_item_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'sku' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'name' => ['type' => 'varchar', 'constraint' => 180],
                'currency_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'MXN'],
                'unit_price' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 1],
                'delivered_quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'billed_quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'line_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sales_order_items', ['order_id', 'sort_order'], 'idx_core_sales_order_items_order');
        }

        if (!\DBUtil::table_exists('core_sales_deliveries')) {
            \DBUtil::create_table('core_sales_deliveries', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'order_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'billing_invoice_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'warehouse_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'delivered'],
                'delivery_date' => ['type' => 'varchar', 'constraint' => 10],
                'currency_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'MXN'],
                'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sales_deliveries', 'folio', 'idx_core_sales_deliveries_folio', 'unique');
            \DBUtil::create_index('core_sales_deliveries', ['order_id', 'status'], 'idx_core_sales_deliveries_order');
            \DBUtil::create_index('core_sales_deliveries', 'billing_invoice_id', 'idx_core_sales_deliveries_invoice');
        }

        if (!\DBUtil::table_exists('core_sales_delivery_items')) {
            \DBUtil::create_table('core_sales_delivery_items', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'delivery_id' => ['type' => 'int', 'constraint' => 11],
                'order_item_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'sku' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'name' => ['type' => 'varchar', 'constraint' => 180],
                'quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 1],
                'unit_price' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'line_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sales_delivery_items', ['delivery_id', 'sort_order'], 'idx_core_sales_delivery_items_delivery');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_sales_delivery_items');
        \DBUtil::drop_table('core_sales_deliveries');
        \DBUtil::drop_table('core_sales_order_items');
        \DBUtil::drop_table('core_sales_orders');
        \DBUtil::drop_table('core_inventory_movements');
        \DBUtil::drop_table('core_inventory_warehouses');
    }
}
