<?php

class Model_Core_Software_Library_Version extends \Orm\Model
{
    protected static $_table_name = 'core_software_library_versions';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'software_id', 'version', 'release_date', 'changelog',
        'is_latest', 'status', 'created_by', 'updated_by', 'active',
        'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
