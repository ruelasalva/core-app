<?php

class Model_Core_Billing_Invoice_Event extends \Orm\Model
{
    protected static $_table_name = 'core_billing_invoice_events';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'invoice_id', 'event_type', 'summary', 'payload_json', 'created_by', 'created_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
    ];
}
