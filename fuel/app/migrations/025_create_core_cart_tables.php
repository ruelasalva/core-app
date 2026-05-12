<?php

namespace Fuel\Migrations;

class Create_core_cart_tables
{
    public function up()
    {
        \DBUtil::create_table('core_cart_carts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'token' => ['type' => 'varchar', 'constraint' => 80],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'portal_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'frontend'],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
            'currency_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'MXN'],
            'items_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'expires_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'converted_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_cart_carts', 'token', 'idx_core_cart_carts_token');
        \DBUtil::create_index('core_cart_carts', ['user_id', 'status'], 'idx_core_cart_carts_user_status');
        \DBUtil::create_index('core_cart_carts', ['party_id', 'status'], 'idx_core_cart_carts_party_status');

        \DBUtil::create_table('core_cart_items', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'cart_id' => ['type' => 'int', 'constraint' => 11],
            'product_id' => ['type' => 'int', 'constraint' => 11],
            'sku' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'currency_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'MXN'],
            'unit_price' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 1],
            'line_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'price_list_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_cart_items', ['cart_id', 'product_id'], 'idx_core_cart_items_product', 'unique');
        \DBUtil::create_index('core_cart_items', 'product_id', 'idx_core_cart_items_product_id');
    }

    public function down()
    {
        \DBUtil::drop_table('core_cart_items');
        \DBUtil::drop_table('core_cart_carts');
    }
}
