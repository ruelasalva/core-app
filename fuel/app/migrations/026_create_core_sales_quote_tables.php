<?php

namespace Fuel\Migrations;

class Create_core_sales_quote_tables
{
    public function up()
    {
        \DBUtil::create_table('core_sales_quotes', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 40],
            'source' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'frontend_cart'],
            'cart_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'requested'],
            'currency_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'MXN'],
            'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'discount_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'tax_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'customer_notes' => ['type' => 'text', 'null' => true],
            'internal_notes' => ['type' => 'text', 'null' => true],
            'expires_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sales_quotes', 'folio', 'idx_core_sales_quotes_folio', 'unique');
        \DBUtil::create_index('core_sales_quotes', ['party_id', 'status'], 'idx_core_sales_quotes_party_status');
        \DBUtil::create_index('core_sales_quotes', ['cart_id'], 'idx_core_sales_quotes_cart');

        \DBUtil::create_table('core_sales_quote_items', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'quote_id' => ['type' => 'int', 'constraint' => 11],
            'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sku' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'currency_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'MXN'],
            'unit_price' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 1],
            'line_subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'line_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sales_quote_items', ['quote_id', 'sort_order'], 'idx_core_sales_quote_items_quote');
        \DBUtil::create_index('core_sales_quote_items', 'product_id', 'idx_core_sales_quote_items_product');
    }

    public function down()
    {
        \DBUtil::drop_table('core_sales_quote_items');
        \DBUtil::drop_table('core_sales_quotes');
    }
}
