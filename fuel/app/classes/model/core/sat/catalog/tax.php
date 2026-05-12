<?php

class Model_Core_Sat_Catalog_Tax extends \Orm\Model
{
    protected static $_table_name = 'core_sat_taxes';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'code', 'name', 'tax_type', 'factor_type', 'default_rate', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
