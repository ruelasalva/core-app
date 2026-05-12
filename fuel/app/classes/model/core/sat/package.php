<?php

class Model_Core_Sat_Package extends \Orm\Model
{
    protected static $_table_name = 'core_sat_packages';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'sync_request_id',
        'package_id',
        'xml_count',
        'path',
        'sha256_hash',
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
