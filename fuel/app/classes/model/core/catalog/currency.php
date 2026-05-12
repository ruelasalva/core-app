<?php

class Model_Core_Catalog_Currency extends \Orm\Model
{
    protected static $_table_name = 'core_catalog_currencies';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'symbol', 'decimals', 'is_base', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
