<?php

class Model_Core_Accounting_Account extends \Orm\Model
{
    protected static $_table_name = 'core_accounting_accounts';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'code', 'name', 'account_type', 'parent_id', 'level', 'nature', 'currency_code',
        'sat_group_code', 'requires_party', 'requires_cost_center', 'is_postable', 'active',
        'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
