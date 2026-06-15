<?php

class Model_Core_Workspace_Layout extends \Orm\Model
{
    protected static $_table_name = 'core_workspace_layouts';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'scope_type', 'scope_id', 'name', 'is_default', 'layout_version',
        'schema_version', 'profile_code', 'preset_code', 'filters_json',
        'mobile_settings_json', 'layout_snapshot_json', 'active', 'created_at',
        'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

