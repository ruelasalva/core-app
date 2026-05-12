<?php

class Model_Core_Sat_Cfdi_Event extends \Orm\Model
{
    protected static $_table_name = 'core_sat_cfdi_events';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'cfdi_id',
        'event_type',
        'payload_json',
        'created_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
    ];
}
