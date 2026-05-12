<?php

class Model_Core_Sat_Cfdi extends \Orm\Model
{
    protected static $_table_name = 'core_sat_cfdi';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'uuid',
        'direction',
        'version',
        'serie',
        'folio',
        'emitter_rfc',
        'emitter_name',
        'receiver_rfc',
        'receiver_name',
        'issued_at',
        'stamped_at',
        'total',
        'subtotal',
        'currency',
        'exchange_rate',
        'voucher_type',
        'payment_method',
        'payment_form',
        'cfdi_use',
        'sat_status',
        'origin',
        'processed',
        'accounted',
        'xml_path',
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
