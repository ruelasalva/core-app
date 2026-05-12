<?php

class Model_Core_Catalog_Fiscal_Document_Rule extends \Orm\Model
{
    protected static $_table_name = 'core_catalog_fiscal_document_rules';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'document_type_id', 'operation_type_id', 'sat_cfdi_use_code',
        'sat_payment_form_code', 'sat_payment_method_code', 'sat_tax_regime_code',
        'requires_rfc', 'requires_fiscal_address', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
