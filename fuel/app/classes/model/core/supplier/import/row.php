<?php

class Model_Core_Supplier_Import_Row extends \Orm\Model
{
    protected static $_table_name = 'core_supplier_import_rows';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id',
        'import_run_id',
        'party_id',
        'provider_code',
        'source_hash',
        'external_id',
        'source_url',
        'supplier_sku',
        'supplier_model',
        'supplier_name',
        'supplier_brand',
        'supplier_category',
        'supplier_description',
        'supplier_compatibility',
        'supplier_currency',
        'supplier_cost',
        'supplier_price',
        'supplier_stock',
        'selling_price',
        'image_url',
        'local_image_path',
        'product_id',
        'mapping_id',
        'row_status',
        'error_message',
        'raw_json',
        'active',
        'created_at',
        'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
