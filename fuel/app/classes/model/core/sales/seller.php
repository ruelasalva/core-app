<?php

class Model_Core_Sales_Seller extends \Orm\Model
{
    protected static $_table_name = 'core_sales_sellers';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'seller_type', 'employee_id', 'party_id', 'user_id',
        'default_commission_plan_id', 'base_commission_percent', 'quota_commission_percent',
        'payment_commission_percent', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
