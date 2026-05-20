<?php

class Model_Core_Billing_Recurring_Profile extends \Orm\Model
{
    protected static $_table_name = 'core_billing_recurring_profiles';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'name', 'party_id', 'invoice_type', 'frequency', 'start_date', 'end_date',
        'next_run_date', 'last_run_at', 'auto_stamp', 'pac_connection_id', 'pac_series_id',
        'pac_receptor_uid', 'currency_code', 'exchange_rate', 'payment_term_id', 'sat_cfdi_use_code',
        'sat_payment_form_code', 'sat_payment_method_code', 'notes', 'status', 'created_by',
        'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
