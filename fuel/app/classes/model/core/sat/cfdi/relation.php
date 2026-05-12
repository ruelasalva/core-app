<?php

class Model_Core_Sat_Cfdi_Relation extends \Orm\Model
{
    protected static $_table_name = 'core_sat_cfdi_relations';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'cfdi_id',
        'related_uuid',
        'relation_type',
        'related_cfdi_id',
        'exists_in_system',
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
