<?php

class Model_Core_Commerce_Category extends \Orm\Model
{
    protected static $_table_name = 'core_commerce_categories';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'name', 'slug', 'description', 'image_path', 'show_in_home', 'sort_order', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
