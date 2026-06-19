<?php

class Model_Core_Communication_Provider extends \Orm\Model
{
    protected static $_table_name = 'core_communication_providers';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'code',
        'name',
        'type',
        'transport',
        'host',
        'port',
        'username',
        'password_encrypted',
        'api_key_encrypted',
        'api_base_url',
        'encryption',
        'timeout_seconds',
        'verify_tls',
        'from_email',
        'from_name',
        'reply_to_email',
        'daily_limit',
        'hourly_limit',
        'simulation_mode',
        'active',
        'last_test_at',
        'last_test_status',
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

    public static function active_by_code($code)
    {
        return static::query()
            ->where('code', trim((string) $code))
            ->where('active', 1)
            ->get_one();
    }
}
