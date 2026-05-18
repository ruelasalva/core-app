<?php

class Model_Core_Inventory_Stock_Balance extends \Orm\Model
{
    protected static $_table_name = 'core_inventory_stock_balances';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'warehouse_id', 'product_id', 'quantity_on_hand', 'quantity_reserved',
        'last_movement_at', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
