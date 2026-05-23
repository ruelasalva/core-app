<?php

class Model_Core_Accounting_Cost_Center extends \Orm\Model
{
    protected static $_table_name = 'core_accounting_cost_centers';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'code', 'name', 'center_type', 'parent_id', 'department_id', 'branch_id',
        'manager_user_id', 'budget_amount', 'currency_code', 'notes', 'active',
        'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
