<?php

class Model_Core_Budget_Plan extends \Orm\Model
{
    protected static $_table_name = 'core_budget_plans';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'code', 'name', 'fiscal_year_id', 'department_id', 'cost_center_id',
        'currency_code', 'status', 'total_amount', 'notes', 'created_by',
        'approved_by', 'approved_at', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
