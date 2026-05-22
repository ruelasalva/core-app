<?php

class Model_Core_Bank_Statement_Import extends \Orm\Model
{
    protected static $_table_name = 'core_bank_statement_imports';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'bank_account_id', 'source_format', 'original_name', 'file_path',
        'period_start', 'period_end', 'rows_count', 'imported_count', 'duplicate_count',
        'status', 'notes', 'created_by', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
