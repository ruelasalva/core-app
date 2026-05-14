<?php

class Model_Core_Purchase_Order extends \Orm\Model
{
    protected static $_table_name = 'core_purchase_orders';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'source', 'portal_code', 'party_id', 'department_id',
        'requested_by', 'requested_at', 'authorized_by', 'authorized_at',
        'rejected_by', 'rejected_at', 'order_date', 'expected_date',
        'payment_term_id', 'currency_code', 'exchange_rate', 'subtotal', 'tax_total',
        'retention_total', 'total', 'invoiced_total', 'balance_total', 'status',
        'approval_status', 'approval_required', 'approval_rule_id', 'notes',
        'internal_notes', 'approval_notes', 'external_reference', 'created_by', 'active',
        'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
