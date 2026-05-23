<?php

class Model_Core_Ar_Customer_Status extends \Orm\Model
{
    protected static $_table_name = 'core_ar_customer_statuses';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'party_id', 'credit_status', 'credit_limit', 'credit_days',
        'current_balance', 'overdue_balance', 'last_review_at', 'reviewed_by',
        'notes', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
