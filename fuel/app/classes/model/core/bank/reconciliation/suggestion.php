<?php

class Model_Core_Bank_Reconciliation_Suggestion extends \Orm\Model
{
    protected static $_table_name = 'core_bank_reconciliation_suggestions';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'movement_id', 'suggested_entity_type', 'suggested_entity_id',
        'payment_type', 'party_id', 'amount', 'currency_code', 'score',
        'reasons_json', 'status', 'applied_by', 'applied_at', 'created_by',
        'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
