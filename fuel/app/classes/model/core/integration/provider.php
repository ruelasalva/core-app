<?php

class Model_Core_Integration_Provider extends \Orm\Model
{
    protected static $_table_name = 'core_integration_providers';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'category', 'description', 'website_url', 'adapter_class',
        'requires_install', 'install_notes', 'config_schema_json', 'sort_order', 'active',
        'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
