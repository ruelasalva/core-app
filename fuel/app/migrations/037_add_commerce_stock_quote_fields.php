<?php

namespace Fuel\Migrations;

class Add_commerce_stock_quote_fields
{
    public function up()
    {
        if (!\DBUtil::field_exists('core_commerce_products', ['stock_quantity'])) {
            \DBUtil::add_fields('core_commerce_products', [
                'stock_quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0, 'after' => 'tax_code'],
                'stock_reserved' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0, 'after' => 'stock_quantity'],
                'stock_updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'stock_reserved'],
            ]);
            \DBUtil::create_index('core_commerce_products', ['brand_id', 'category_id', 'published', 'active'], 'idx_core_commerce_products_quote_filters');
        }
    }

    public function down()
    {
        if (\DBUtil::field_exists('core_commerce_products', ['stock_quantity'])) {
            \DBUtil::drop_fields('core_commerce_products', [
                'stock_quantity',
                'stock_reserved',
                'stock_updated_at',
            ]);
        }
    }
}
