<?php

class Model_Core_Sales_Delivery extends \Orm\Model
{
    protected static $_table_name = 'core_sales_deliveries';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'order_id', 'billing_invoice_id', 'party_id', 'warehouse_id', 'status',
        'delivery_date', 'currency_code', 'total', 'notes', 'created_by', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
