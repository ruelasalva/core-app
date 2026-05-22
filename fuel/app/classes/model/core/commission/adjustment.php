<?php

class Model_Core_Commission_Adjustment extends \Orm\Model
{
    protected static $_table_name = 'core_commission_adjustments';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'entry_id', 'settlement_id', 'seller_id', 'adjustment_type',
        'amount', 'reason', 'created_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
