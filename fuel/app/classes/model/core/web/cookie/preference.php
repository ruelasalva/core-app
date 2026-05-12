<?php

class Model_Core_Web_Cookie_Preference extends \Orm\Model
{
    protected static $_table_name = 'core_web_cookie_preferences';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'user_id',
        'token',
        'necessary',
        'analytics',
        'marketing',
        'personalization',
        'ip_address',
        'user_agent',
        'accepted_at',
        'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'accepted_at',
            'mysql_timestamp' => false,
        ],
        'Orm\Observer_UpdatedAt' => [
            'events' => ['before_save'],
            'property' => 'updated_at',
            'mysql_timestamp' => false,
        ],
    ];
}
