<?php

namespace Fuel\Migrations;

class Create_core_commerce_price_tables
{
    public function up()
    {
        \DBUtil::create_table('core_commerce_price_lists', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 60],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'description' => ['type' => 'text', 'null' => true],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'is_default' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'priority' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_price_lists', 'code', 'idx_core_commerce_price_lists_code', 'unique');

        \DBUtil::create_table('core_commerce_product_prices', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'product_id' => ['type' => 'int', 'constraint' => 11],
            'price_list_id' => ['type' => 'int', 'constraint' => 11],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'price' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
            'min_quantity' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 1],
            'max_quantity' => ['type' => 'decimal', 'constraint' => '18,6', 'null' => true],
            'valid_from' => ['type' => 'date', 'null' => true],
            'valid_until' => ['type' => 'date', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_product_prices', ['product_id', 'price_list_id', 'min_quantity'], 'idx_core_commerce_product_prices_unique', 'unique');
        \DBUtil::create_index('core_commerce_product_prices', 'price_list_id', 'idx_core_commerce_product_prices_list_id');

        \DBUtil::create_table('core_commerce_customer_price_lists', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'customer_id' => ['type' => 'int', 'constraint' => 11],
            'price_list_id' => ['type' => 'int', 'constraint' => 11],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_customer_price_lists', ['customer_id', 'price_list_id'], 'idx_core_commerce_customer_price_lists_unique', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_commerce_customer_price_lists');
        \DBUtil::drop_table('core_commerce_product_prices');
        \DBUtil::drop_table('core_commerce_price_lists');
    }
}
