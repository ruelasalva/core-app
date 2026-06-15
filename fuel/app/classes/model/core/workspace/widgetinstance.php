<?php

class Model_Core_Workspace_Widgetinstance extends \Orm\Model
{
    protected static $_table_name = 'core_workspace_widget_instances';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'layout_id', 'widget_code', 'catalog_id', 'x', 'y', 'w', 'h',
        'title_override', 'collapsed', 'favorite', 'refresh_time',
        'priority_override', 'mobile_hidden', 'mobile_order', 'last_error',
        'last_loaded_at', 'settings_json', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

