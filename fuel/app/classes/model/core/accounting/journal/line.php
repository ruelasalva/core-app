<?php

class Model_Core_Accounting_Journal_Line extends \Orm\Model
{
    protected static $_table_name = 'core_accounting_journal_lines';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'entry_id', 'account_id', 'party_id', 'department_id', 'cost_center',
        'description', 'debit', 'credit', 'currency_code', 'exchange_rate', 'sort_order',
        'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
