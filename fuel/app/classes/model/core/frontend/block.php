<?php

class Model_Core_Frontend_Block extends \Orm\Model
{
    protected static $_table_name = 'core_frontend_blocks';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'code', 'name', 'block_type', 'content', 'settings_json', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
