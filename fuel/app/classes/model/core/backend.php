<?php

class Model_Core_Backend extends \Orm\Model
{
    protected static $_table_name = 'core_backends';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'code',
        'name',
        'description',
        'base_route',
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
            ->order_by('id', 'asc')
            ->get();
    }
}
