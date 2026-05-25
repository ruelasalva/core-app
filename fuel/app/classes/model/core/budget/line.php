<?php

class Model_Core_Budget_Line extends \Orm\Model
{
    protected static $_table_name = 'core_budget_lines';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'plan_id', 'account_id', 'department_id', 'cost_center_id',
        'period_start', 'period_end', 'currency_code', 'amount',
        'warning_threshold', 'block_threshold', 'notes',
        'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
