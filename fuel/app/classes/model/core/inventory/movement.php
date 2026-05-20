<?php

class Model_Core_Inventory_Movement extends \Orm\Model
{
    protected static $_table_name = 'core_inventory_movements';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'warehouse_id', 'product_id', 'movement_type', 'quantity', 'unit_cost',
        'related_module', 'related_entity_type', 'related_entity_id', 'fiscal_document_id',
        'requires_fiscal_transfer', 'notes', 'created_by', 'created_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
    ];
}
