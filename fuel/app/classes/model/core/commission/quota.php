<?php

class Model_Core_Commission_Quota extends \Orm\Model
{
    protected static $_table_name = 'core_commission_quotas';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'seller_id', 'plan_id', 'period_code', 'date_from', 'date_to',
        'target_amount', 'target_quantity', 'bonus_percent', 'bonus_amount',
        'status', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
