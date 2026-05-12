<?php

class Model_Core_Bank_Reconciliation extends \Orm\Model
{
    protected static $_table_name = 'core_bank_reconciliations';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'bank_account_id', 'period_start', 'period_end', 'opening_balance', 'closing_balance',
        'status', 'notes', 'created_by', 'closed_by', 'closed_at', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
