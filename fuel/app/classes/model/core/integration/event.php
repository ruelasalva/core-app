<?php

class Model_Core_Integration_Event extends \Orm\Model
{
    protected static $_table_name = 'core_integration_events';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'provider_code', 'connection_id', 'event_type', 'external_id', 'direction',
        'status', 'payload_json', 'response_json', 'error_message', 'received_at',
        'processed_at', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
