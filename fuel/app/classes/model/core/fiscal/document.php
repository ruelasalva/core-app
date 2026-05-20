<?php

class Model_Core_Fiscal_Document extends \Orm\Model
{
    protected static $_table_name = 'core_fiscal_documents';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'folio', 'document_type', 'cfdi_version', 'voucher_type', 'party_id',
        'source_module', 'source_entity_type', 'source_entity_id', 'source_folio',
        'fiscal_mode', 'pac_provider_code', 'pac_connection_id', 'pac_series_id',
        'pac_uid', 'uuid', 'related_uuid', 'sat_status', 'workflow_status',
        'issue_date', 'currency_code', 'total', 'payload_json', 'response_json',
        'xml_path', 'pdf_path', 'notes', 'created_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
