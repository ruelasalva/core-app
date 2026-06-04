<?php

class Model_Core_Contract_Relation extends \Orm\Model
{
    protected static $_table_name = 'core_contract_relations';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'contract_id', 'related_module', 'related_entity_type', 'related_entity_id',
        'relation_type', 'notes', 'created_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];

    protected static $_belongs_to = [
        'contract' => [
            'key_from' => 'contract_id',
            'model_to' => 'Model_Core_Contract',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
