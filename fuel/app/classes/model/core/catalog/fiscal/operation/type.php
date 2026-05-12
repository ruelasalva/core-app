<?php

class Model_Core_Catalog_Fiscal_Operation_Type extends \Orm\Model
{
    protected static $_table_name = 'core_catalog_fiscal_operation_types';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'operation_scope', 'requires_cfdi', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
