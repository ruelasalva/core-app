<?php

class Model_Core_Branch extends \Orm\Model
{
    protected static $_table_name = 'core_branches';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'company_id',
        'code',
        'name',
        'city',
        'state',
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

    protected static $_belongs_to = [
        'company' => [
            'key_from' => 'company_id',
            'model_to' => 'Model_Core_Company',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
