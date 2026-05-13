<?php

class Model_Core_Purchase_Order_Item extends \Orm\Model
{
    protected static $_table_name = 'core_purchase_order_items';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'order_id', 'product_id', 'sku', 'description', 'quantity', 'unit_code',
        'unit_price', 'discount_amount', 'tax_rate', 'tax_amount', 'retention_amount',
        'line_total', 'received_quantity', 'invoiced_quantity', 'sort_order', 'active',
        'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
