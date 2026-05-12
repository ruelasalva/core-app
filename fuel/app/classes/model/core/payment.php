<?php

class Model_Core_Payment extends \Orm\Model
{
    protected static $_table_name = 'core_payments';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'payment_type', 'party_id', 'bank_account_id', 'integration_connection_id',
        'payment_date', 'currency_code', 'exchange_rate', 'amount', 'sat_payment_form_code',
        'reference', 'external_id', 'status', 'notes', 'created_by', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
