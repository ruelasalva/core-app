<?php

class Model_Core_Viatic_Advance extends \Orm\Model
{
    protected static $_table_name = 'core_viatic_advances';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'trip_id', 'bank_account_id', 'currency_code', 'amount', 'requested_at',
        'paid_at', 'status', 'reference', 'notes', 'active', 'created_by', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

