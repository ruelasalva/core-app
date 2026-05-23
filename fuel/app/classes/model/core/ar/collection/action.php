<?php

class Model_Core_Ar_Collection_Action extends \Orm\Model
{
    protected static $_table_name = 'core_ar_collection_actions';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'party_id', 'invoice_id', 'action_type', 'status', 'priority',
        'assigned_user_id', 'action_date', 'next_action_date', 'promise_date',
        'promise_amount', 'result', 'notes', 'created_by', 'completed_by',
        'completed_at', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
