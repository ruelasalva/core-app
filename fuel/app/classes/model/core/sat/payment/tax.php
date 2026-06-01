<?php

class Model_Core_Sat_Payment_Tax extends \Orm\Model
{
    protected static $_table_name = 'core_sat_payment_taxes';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'source_hash',
        'payment_cfdi_id',
        'payment_detail_id',
        'invoice_cfdi_id',
        'payment_uuid',
        'invoice_uuid',
        'tax_scope',
        'tax_code',
        'tax_type',
        'tax_factor_type',
        'tax_rate',
        'base_amount',
        'tax_amount',
        'currency',
        'exchange_rate',
        'payment_date',
        'active',
        'created_at',
        'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
        'Orm\Observer_UpdatedAt' => [
            'events' => ['before_save'],
            'property' => 'updated_at',
            'mysql_timestamp' => false,
        ],
    ];
}
