<?php

class Model_Core_Audit_Log extends \Orm\Model
{
    protected static $_table_name = 'core_audit_logs';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'user_id', 'portal_code', 'backend', 'module', 'action', 'entity_type',
        'entity_id', 'summary', 'old_values_json', 'new_values_json', 'metadata_json',
        'ip', 'user_agent', 'created_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
    ];
}
