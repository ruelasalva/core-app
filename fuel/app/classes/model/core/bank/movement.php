<?php

class Model_Core_Bank_Movement extends \Orm\Model
{
    protected static $_table_name = 'core_bank_movements';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'bank_account_id', 'movement_date', 'movement_type', 'amount', 'currency_code',
        'reference', 'description', 'source', 'payment_id', 'reconciled', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
