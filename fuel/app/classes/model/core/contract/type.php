<?php

class Model_Core_Contract_Type extends \Orm\Model
{
    protected static $_table_name = 'core_contract_types';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'party_scope', 'default_portal_code', 'requires_party',
        'requires_end_date', 'requires_approval', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
