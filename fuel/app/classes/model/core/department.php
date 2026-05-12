<?php

class Model_Core_Department extends \Orm\Model
{
    protected static $_table_name = 'core_departments';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'name',
        'slug',
        'description',
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

    protected static $_has_many = [
        'employees' => [
            'key_from' => 'id',
            'model_to' => 'Model_Core_Employee',
            'key_to' => 'department_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];

    public static function list_for_admin()
    {
        return static::query()
            ->order_by('name', 'asc')
            ->get();
    }
}
