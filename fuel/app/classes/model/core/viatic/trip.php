<?php

class Model_Core_Viatic_Trip extends \Orm\Model
{
    protected static $_table_name = 'core_viatic_trips';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'seller_id', 'employee_id', 'user_id', 'department_id',
        'title', 'destination', 'travel_reason', 'start_date', 'end_date', 'status',
        'currency_code', 'requested_amount', 'advance_total', 'expense_total', 'balance_total',
        'settlement_account_id', 'accounting_entry_id', 'notes', 'active', 'created_by',
        'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

