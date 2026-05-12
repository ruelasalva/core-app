<?php

class Model_Core_Web_Integration extends \Orm\Model
{
    protected static $_table_name = 'core_web_integrations';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'code',
        'name',
        'provider',
        'integration_type',
        'environment',
        'public_key',
        'public_value',
        'secret_value',
        'settings_json',
        'enabled',
        'load_in_frontend',
        'load_in_admin',
        'requires_consent',
        'consent_category',
        'sort_order',
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
            ->order_by('sort_order', 'asc')
            ->order_by('name', 'asc')
            ->get();
    }
}
