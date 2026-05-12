<?php

class Model_Core_Setting extends \Orm\Model
{
    protected static $_table_name = 'core_settings';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'setting_group',
        'setting_key',
        'value',
        'value_type',
        'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_UpdatedAt' => [
            'events' => ['before_save'],
            'property' => 'updated_at',
            'mysql_timestamp' => false,
        ],
    ];
}
