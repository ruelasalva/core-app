<?php

class Model_Core_Contract_Event extends \Orm\Model
{
    protected static $_table_name = 'core_contract_events';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'contract_id', 'event_type', 'old_status', 'new_status', 'message',
        'payload_json', 'created_by', 'created_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
    ];

    protected static $_belongs_to = [
        'contract' => [
            'key_from' => 'contract_id',
            'model_to' => 'Model_Core_Contract',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
