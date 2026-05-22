<?php

class Model_Core_Sales_Quote extends \Orm\Model
{
    protected static $_table_name = 'core_sales_quotes';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'folio', 'source', 'offline_uuid', 'synced_from_offline', 'offline_synced_at',
        'cart_id', 'user_id', 'party_id', 'seller_id', 'status', 'currency_code',
        'subtotal', 'discount_total', 'tax_total', 'total', 'customer_notes', 'internal_notes',
        'expires_at', 'created_at', 'updated_at',
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
