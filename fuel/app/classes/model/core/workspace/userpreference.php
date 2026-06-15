<?php

class Model_Core_Workspace_Userpreference extends \Orm\Model
{
    protected static $_table_name = 'core_workspace_user_preferences';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'user_id', 'default_layout_id', 'compact_mode', 'active_profile_code',
        'active_preset_code', 'favorite_widgets_json', 'favorite_actions_json',
        'hidden_widgets_json', 'filters_json', 'recent_commands_json',
        'recent_searches_json', 'mobile_preferences_json', 'settings_json',
        'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

