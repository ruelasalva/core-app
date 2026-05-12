<?php

class Model_Core_Notification_Event extends \Orm\Model
{
    protected static $_table_name = 'core_notification_events';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'code',
        'name',
        'description',
        'title_template',
        'message_template',
        'url_template',
        'icon',
        'priority',
        'notify_internal',
        'notify_email',
        'email_role',
        'email_template_code',
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

    public static function list_for_admin()
    {
        return static::query()
            ->order_by('code', 'asc')
            ->get();
    }

    public static function active_by_code($code)
    {
        return static::query()
            ->where('code', trim((string) $code))
            ->where('active', 1)
            ->get_one();
    }
}
