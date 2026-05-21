<?php

class Model_Core_Hr_Payroll_Item extends \Orm\Model
{
    protected static $_table_name = 'core_hr_payroll_items';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'run_id', 'employee_id', 'cfdi_id', 'fiscal_document_id', 'payment_id',
        'days_paid', 'perception_total', 'deduction_total', 'net_total', 'sat_status',
        'payment_status', 'notes', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
