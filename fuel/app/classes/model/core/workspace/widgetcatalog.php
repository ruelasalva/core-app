<?php

class Model_Core_Workspace_Widgetcatalog extends \Orm\Model
{
    protected static $_table_name = 'core_workspace_widget_catalog';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'title', 'description', 'category', 'type', 'icon', 'color',
        'permission_key', 'endpoint', 'refresh_time', 'default_w', 'default_h',
        'min_w', 'min_h', 'max_w', 'max_h', 'priority', 'criticality',
        'first_screen', 'lazy_load', 'refresh_policy', 'feature_flag', 'status',
        'manifest_version', 'capabilities_json', 'tags_json', 'dependencies_json',
        'settings_json', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

