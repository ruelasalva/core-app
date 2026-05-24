<?php

class Model_Core_Treasury_Cashflow_Item extends \Orm\Model
{
    protected static $_table_name = 'core_treasury_cashflow_items';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'flow_type', 'source_module', 'source_entity_type', 'source_entity_id',
        'party_id', 'bank_account_id', 'planned_date', 'currency_code', 'amount', 'probability',
        'status', 'reference', 'notes', 'created_by', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
