<?php

class Model_Core_Sat_Sync_Request extends \Orm\Model
{
    protected static $_table_name = 'core_sat_sync_requests';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'request_type',
        'date_from',
        'date_to',
        'status',
        'sat_request_id',
        'attempts',
        'processed_count',
        'error_message',
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
