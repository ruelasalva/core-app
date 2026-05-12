<?php

class Model_Core_Catalog_Carrier_Service extends \Orm\Model
{
    protected static $_table_name = 'core_catalog_carrier_services';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'carrier_id', 'shipping_method_id', 'code', 'name', 'estimated_days', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
