<?php

class Model_Core_Ap_Supplier_Status extends \Orm\Model
{
    protected static $_table_name = 'core_ap_supplier_statuses';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'party_id', 'payment_status', 'payment_priority', 'credit_limit', 'credit_days',
        'current_balance', 'overdue_balance', 'next_payment_date', 'reviewed_by',
        'last_review_at', 'notes', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
