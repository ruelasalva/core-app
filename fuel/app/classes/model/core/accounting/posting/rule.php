<?php

class Model_Core_Accounting_Posting_Rule extends \Orm\Model
{
    protected static $_table_name = 'core_accounting_posting_rules';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'rule_code', 'name', 'source_module', 'source_event', 'debit_account_id',
        'credit_account_id', 'amount_source', 'requires_party', 'auto_post', 'priority',
        'notes', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
