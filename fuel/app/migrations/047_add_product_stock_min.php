<?php

namespace Fuel\Migrations;

class Add_product_stock_min
{
    public function up()
    {
        if (\DBUtil::table_exists('core_commerce_products') && !\DBUtil::field_exists('core_commerce_products', ['stock_min'])) {
            \DBUtil::add_fields('core_commerce_products', [
                'stock_min' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0, 'after' => 'stock_reserved'],
            ]);
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_commerce_products') && \DBUtil::field_exists('core_commerce_products', ['stock_min'])) {
            \DBUtil::drop_fields('core_commerce_products', ['stock_min']);
        }
    }
}
