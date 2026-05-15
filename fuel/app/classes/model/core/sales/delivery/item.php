<?php

class Model_Core_Sales_Delivery_Item extends \Orm\Model
{
    protected static $_table_name = 'core_sales_delivery_items';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'delivery_id', 'order_item_id', 'product_id', 'sku', 'name', 'quantity', 'unit_price',
        'line_total', 'sort_order', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
