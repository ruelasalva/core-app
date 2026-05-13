<?php

class Model_Core_Sat_Cfdi_Detail extends \Orm\Model
{
    protected static $_table_name = 'core_sat_cfdi_details';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'cfdi_id', 'line_type', 'line_number', 'product_service_code',
        'identification_number', 'unit_code', 'unit_name', 'description', 'tax_object',
        'quantity', 'unit_value', 'discount', 'amount', 'vat_amount', 'vat_rate',
        'vat_base', 'ieps_amount', 'ieps_rate', 'ieps_base', 'retention_amount',
        'ret_vat_amount', 'ret_vat_rate', 'ret_vat_base', 'ret_isr_amount',
        'ret_isr_rate', 'ret_isr_base', 'related_uuid', 'relation_type',
        'payment_uuid', 'payment_series', 'payment_folio', 'payment_currency',
        'payment_equivalence', 'payment_method', 'payment_partiality',
        'payment_previous_balance', 'payment_amount', 'payment_remaining_balance',
        'metadata_json', 'created_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
    ];
}
