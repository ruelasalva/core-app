<?php

class Model_Core_Billing_Invoice_Item extends \Orm\Model
{
    protected static $_table_name = 'core_billing_invoice_items';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'invoice_id', 'product_id', 'sat_product_service_code', 'description', 'quantity', 'unit_code',
        'unit_price', 'discount_amount', 'tax_code', 'tax_rate', 'tax_amount', 'retention_amount',
        'line_total', 'sort_order', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
