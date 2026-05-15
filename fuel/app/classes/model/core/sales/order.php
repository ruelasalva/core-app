<?php

class Model_Core_Sales_Order extends \Orm\Model
{
    protected static $_table_name = 'core_sales_orders';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'source_quote_id', 'party_id', 'status', 'order_date', 'currency_code',
        'subtotal', 'discount_total', 'tax_total', 'total', 'delivered_total', 'billed_total',
        'notes', 'created_by', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
