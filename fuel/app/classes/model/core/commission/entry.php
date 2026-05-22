<?php

class Model_Core_Commission_Entry extends \Orm\Model
{
    protected static $_table_name = 'core_commission_entries';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'seller_id', 'plan_id', 'rule_id', 'quota_id', 'trigger_event',
        'source_module', 'source_entity_type', 'source_entity_id', 'source_item_id',
        'party_id', 'product_id', 'currency_code', 'base_amount', 'commission_percent',
        'commission_amount', 'status', 'earned_at', 'settlement_id', 'notes',
        'created_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
