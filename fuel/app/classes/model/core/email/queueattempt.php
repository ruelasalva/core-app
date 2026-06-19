<?php

class Model_Core_Email_QueueAttempt extends \Orm\Model
{
    protected static $_table_name = 'core_email_queue_attempts';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'queue_id',
        'attempt_number',
        'transport',
        'provider_code',
        'status',
        'response_code',
        'response_message',
        'attempted_at',
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
