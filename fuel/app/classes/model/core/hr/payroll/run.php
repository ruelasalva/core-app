<?php

class Model_Core_Hr_Payroll_Run extends \Orm\Model
{
    protected static $_table_name = 'core_hr_payroll_runs';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'folio', 'period_id', 'department_id', 'run_type', 'status', 'currency_code',
        'perception_total', 'deduction_total', 'net_total', 'payment_batch_id',
        'accounting_entry_id', 'created_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
