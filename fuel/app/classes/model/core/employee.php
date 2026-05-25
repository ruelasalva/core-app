<?php

class Model_Core_Employee extends \Orm\Model
{
    protected static $_table_name = 'core_employees';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'user_id',
        'party_id',
        'department_id',
        'branch_id',
        'employee_number',
        'full_name',
        'email',
        'rfc',
        'curp',
        'nss',
        'position',
        'hire_date',
        'termination_date',
        'payroll_status',
        'compensation_type',
        'salary_daily',
        'salary_integrated',
        'payment_frequency',
        'bank_account_id',
        'sat_regime_code',
        'contract_type',
        'work_shift',
        'risk_class',
        'active',
        'created_at',
        'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
        'Orm\Observer_UpdatedAt' => [
            'events' => ['before_save'],
            'property' => 'updated_at',
            'mysql_timestamp' => false,
        ],
    ];

    protected static $_belongs_to = [
        'department' => [
            'key_from' => 'department_id',
            'model_to' => 'Model_Core_Department',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
        'branch' => [
            'key_from' => 'branch_id',
            'model_to' => 'Model_Core_Branch',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
