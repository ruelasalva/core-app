<?php

class Model_Core_Catalog_Exchange_Rate extends \Orm\Model
{
    protected static $_table_name = 'core_catalog_exchange_rates';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'currency_code', 'rate_date', 'rate', 'source', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
