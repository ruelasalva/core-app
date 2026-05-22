<?php

class Model_Core_Commission_Rule extends \Orm\Model
{
    protected static $_table_name = 'core_commission_rules';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'plan_id', 'code', 'name', 'rule_scope', 'seller_id', 'party_id', 'product_id',
        'brand_id', 'category_id', 'subcategory_id', 'trigger_event', 'calculation_base',
        'value_type', 'value', 'min_quantity', 'min_amount', 'priority', 'stackable',
        'valid_from', 'valid_until', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
