<?php

class Model_Core_Catalog_Payment_Term extends \Orm\Model
{
    protected static $_table_name = 'core_catalog_payment_terms';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'days', 'requires_credit', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
