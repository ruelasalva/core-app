<?php

class Model_Core_Email_Queue extends \Orm\Model
{
    protected static $_table_name = 'core_email_queue';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'event_code',
        'template_code',
        'email_role',
        'to_email',
        'to_name',
        'subject',
        'body',
        'status',
        'attempts',
        'max_attempts',
        'last_error',
        'scheduled_at',
        'sent_at',
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
