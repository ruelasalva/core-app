<?php

class Model_Core_Commerce_Price_List extends \Orm\Model
{
    protected static $_table_name = 'core_commerce_price_lists';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'code', 'name', 'description', 'currency_code', 'is_default', 'priority', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
