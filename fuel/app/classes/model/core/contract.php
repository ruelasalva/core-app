<?php

class Model_Core_Contract extends \Orm\Model
{
    protected static $_table_name = 'core_contracts';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'company_id', 'contract_number', 'contract_type', 'party_id', 'portal_code',
        'title', 'description', 'start_date', 'end_date', 'renewal_type', 'status',
        'responsible_user_id', 'contract_value', 'currency_code', 'renewal_value',
        'renewal_currency_code', 'billing_type', 'response_hours', 'resolution_hours',
        'visibility', 'approval_status', 'approved_by', 'approved_at', 'signed_at',
        'notes', 'created_by', 'updated_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];

    protected static $_belongs_to = [
        'party' => [
            'key_from' => 'party_id',
            'model_to' => 'Model_Core_Party',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
        'company' => [
            'key_from' => 'company_id',
            'model_to' => 'Model_Core_Company',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];

    protected static $_has_many = [
        'relations' => [
            'key_from' => 'id',
            'model_to' => 'Model_Core_Contract_Relation',
            'key_to' => 'contract_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
        'events' => [
            'key_from' => 'id',
            'model_to' => 'Model_Core_Contract_Event',
            'key_to' => 'contract_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
