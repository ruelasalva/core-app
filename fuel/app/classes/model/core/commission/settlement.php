<?php

class Model_Core_Commission_Settlement extends \Orm\Model
{
    protected static $_table_name = 'core_commission_settlements';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'folio', 'seller_id', 'date_from', 'date_to', 'currency_code',
        'subtotal', 'adjustment_total', 'total', 'status', 'payment_id', 'notes',
        'created_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
