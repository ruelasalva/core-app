<?php

class Model_Core_Cart_Item extends \Orm\Model
{
    protected static $_table_name = 'core_cart_items';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'cart_id', 'product_id', 'sku', 'name', 'currency_code',
        'unit_price', 'quantity', 'line_total', 'price_list_id', 'created_at', 'updated_at',
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
