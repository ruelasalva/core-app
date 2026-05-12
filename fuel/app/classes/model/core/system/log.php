<?php

class Model_Core_System_Log extends \Orm\Model
{
    protected static $_table_name = 'core_system_logs';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'user_id',
        'backend',
        'module',
        'action',
        'level',
        'message',
        'context',
        'ip',
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
