<?php

class Model_Core_Commerce_Product_Price extends \Orm\Model
{
    protected static $_table_name = 'core_commerce_product_prices';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'product_id', 'price_list_id', 'currency_code', 'price', 'min_quantity', 'max_quantity',
        'valid_from', 'valid_until', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
