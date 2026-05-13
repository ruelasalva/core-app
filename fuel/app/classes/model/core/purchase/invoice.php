<?php

class Model_Core_Purchase_Invoice extends \Orm\Model
{
    protected static $_table_name = 'core_purchase_invoices';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'party_id', 'order_id', 'billing_invoice_id', 'cfdi_id', 'uuid',
        'invoice_date', 'due_date', 'currency_code', 'subtotal', 'tax_total',
        'retention_total', 'total', 'balance_due', 'status', 'validation_status',
        'sat_status', 'message', 'created_by', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
