<?php

class Model_Core_Software_Library_AccessRule extends \Orm\Model
{
    protected static $_table_name = 'core_software_library_access_rules';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'software_id', 'asset_id', 'scope_type', 'scope_id',
        'portal_code', 'party_type', 'permission', 'valid_from',
        'valid_until', 'notes', 'created_by', 'active',
        'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
