<?php

class Model_Core_Sat_Payment_Detail extends \Orm\Model
{
    protected static $_table_name = 'core_sat_payment_details';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'payment_cfdi_id', 'invoice_cfdi_id', 'invoice_uuid', 'series', 'folio',
        'currency', 'equivalence', 'partiality_number', 'previous_balance',
        'paid_amount', 'remaining_balance', 'tax_object', 'created_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
    ];
}
