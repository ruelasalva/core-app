<?php

class Model_Core_Accounting_Journal_Entry extends \Orm\Model
{
    protected static $_table_name = 'core_accounting_journal_entries';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'entry_type', 'entry_date', 'period', 'status', 'source_module',
        'source_entity_type', 'source_entity_id', 'currency_code', 'exchange_rate',
        'total_debit', 'total_credit', 'description', 'created_by', 'posted_by', 'posted_at',
        'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
