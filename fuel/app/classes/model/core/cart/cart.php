<?php

class Model_Core_Cart_Cart extends \Orm\Model
{
    protected static $_table_name = 'core_cart_carts';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'token', 'user_id', 'party_id', 'portal_code', 'status', 'currency_code',
        'items_count', 'subtotal', 'total', 'expires_at', 'converted_at', 'created_at', 'updated_at',
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
