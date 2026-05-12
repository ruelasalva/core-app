<?php

class Model_Core_Integration_Webhook extends \Orm\Model
{
    protected static $_table_name = 'core_integration_webhooks';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'connection_id', 'code', 'name', 'endpoint_route', 'events_json',
        'verify_signature', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
