<?php

class Model_Core_Integration_Connection extends \Orm\Model
{
    protected static $_table_name = 'core_integration_connections';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'provider_id', 'code', 'name', 'environment', 'public_key', 'public_value',
        'secret_value', 'webhook_secret', 'config_json', 'enabled', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
