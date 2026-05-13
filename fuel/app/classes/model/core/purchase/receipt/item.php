<?php

class Model_Core_Purchase_Receipt_Item extends \Orm\Model
{
    protected static $_table_name = 'core_purchase_receipt_items';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'receipt_id', 'invoice_id', 'amount', 'notes', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
