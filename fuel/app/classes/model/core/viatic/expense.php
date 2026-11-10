<?php

class Model_Core_Viatic_Expense extends \Orm\Model
{
    protected static $_table_name = 'core_viatic_expenses';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'trip_id', 'expense_date', 'concept', 'vendor_name', 'document_folio',
        'payment_method', 'account_id', 'currency_code', 'exchange_rate', 'subtotal',
        'tax_total', 'total', 'notes', 'active', 'created_by', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}

