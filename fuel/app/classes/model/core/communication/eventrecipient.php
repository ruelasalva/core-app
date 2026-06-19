<?php

class Model_Core_Communication_EventRecipient extends \Orm\Model
{
    protected static $_table_name = 'core_communication_event_recipients';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'event_code',
        'channel_code',
        'recipient_type',
        'recipient_value',
        'mode',
        'active',
        'created_at',
        'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
        'Orm\Observer_UpdatedAt' => [
            'events' => ['before_save'],
            'property' => 'updated_at',
            'mysql_timestamp' => false,
        ],
    ];
}
