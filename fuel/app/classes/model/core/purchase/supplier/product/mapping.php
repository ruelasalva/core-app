<?php

/**
 * MODELO CORE_PURCHASE_SUPPLIER_PRODUCT_MAPPING
 *
 * Equivalencias reutilizables entre conceptos de proveedor y productos internos.
 *
 * @package  app
 * @extends  Orm\Model
 */
class Model_Core_Purchase_Supplier_Product_Mapping extends \Orm\Model
{
    protected static $_table_name = 'core_purchase_supplier_product_mappings';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id',
        'party_id',
        'supplier_rfc',
        'supplier_sku',
        'supplier_description',
        'supplier_description_hash',
        'sat_product_service_code',
        'sat_unit_code',
        'product_id',
        'internal_sku',
        'internal_name',
        'unit_code',
        'conversion_factor',
        'last_unit_cost',
        'last_seen_at',
        'active',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
