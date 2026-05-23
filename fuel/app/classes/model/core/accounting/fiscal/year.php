<?php

class Model_Core_Accounting_Fiscal_Year extends \Orm\Model
{
    protected static $_table_name = 'core_accounting_fiscal_years';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'code', 'name', 'start_date', 'end_date', 'status', 'locked',
        'closed_by', 'closed_at', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
