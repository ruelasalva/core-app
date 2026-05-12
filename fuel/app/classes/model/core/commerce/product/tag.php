<?php

class Model_Core_Commerce_Product_Tag extends \Orm\Model
{
    protected static $_table_name = 'core_commerce_product_tags';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'product_id', 'tag_id', 'created_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
    ];
}
