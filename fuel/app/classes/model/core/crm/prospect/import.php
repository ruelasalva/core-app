<?php

class Model_Core_Crm_Prospect_Import extends \Orm\Model
{
    protected static $_table_name = 'core_crm_prospect_imports';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'source_id', 'connection_id', 'folio', 'query_type', 'query_json',
        'results_count', 'imported_count', 'skipped_count', 'status', 'error_message',
        'created_by', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
