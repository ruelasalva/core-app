<?php

class Model_Core_Catalog_Bank_Account extends \Orm\Model
{
    protected static $_table_name = 'core_catalog_bank_accounts';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'bank_id', 'name', 'account_number', 'clabe', 'currency_code', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
