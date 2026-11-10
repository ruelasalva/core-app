<?php

class Model_Core_Viatic_Settlement extends \Orm\Model
{
    protected static $_table_name = 'core_viatic_settlements';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'trip_id', 'advance_total', 'expense_total', 'balance_total', 'balance_type',
        'status', 'settled_at', 'notes', 'active', 'created_by', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

