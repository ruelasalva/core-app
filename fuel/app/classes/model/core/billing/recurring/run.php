<?php

class Model_Core_Billing_Recurring_Run extends \Orm\Model
{
    protected static $_table_name = 'core_billing_recurring_runs';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'profile_id', 'invoice_id', 'run_date', 'status', 'message', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
