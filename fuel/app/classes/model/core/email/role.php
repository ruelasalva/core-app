<?php

class Model_Core_Email_Role extends \Orm\Model
{
    protected static $_table_name = 'core_email_roles';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'code',
        'name',
        'from_email',
        'from_name',
        'reply_to_email',
        'reply_to_name',
        'to_emails',
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
