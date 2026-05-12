<?php

class Model_Core_Commerce_Tag extends \Orm\Model
{
    protected static $_table_name = 'core_commerce_tags';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'name', 'slug', 'tag_type', 'color', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
