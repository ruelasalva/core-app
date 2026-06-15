<?php

class Model_Core_Workspace_Quickaction extends \Orm\Model
{
    protected static $_table_name = 'core_workspace_quick_actions';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'title', 'icon', 'route', 'permission_key', 'category',
        'color', 'execution_type', 'requires_confirmation', 'opens_modal',
        'keywords', 'active', 'sort_order', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

