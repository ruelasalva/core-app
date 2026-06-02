<?php

class Model_Core_Supplier_Import_Run extends \Orm\Model
{
    protected static $_table_name = 'core_supplier_import_runs';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id',
        'party_id',
        'provider_code',
        'connection_id',
        'import_type',
        'source_name',
        'source_url',
        'file_path',
        'dry_run',
        'status',
        'rows_count',
        'created_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'started_at',
        'finished_at',
        'executed_by',
        'summary_json',
        'active',
        'created_at',
        'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
