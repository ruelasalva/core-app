<?php

class Model_Core_Frontend_Menu_Item extends \Orm\Model
{
    protected static $_table_name = 'core_frontend_menu_items';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'menu_id', 'parent_id', 'label', 'url', 'target_type', 'target_id', 'sort_order', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
