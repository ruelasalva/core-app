<?php

class Model_Core_Software_Library_Item extends \Orm\Model
{
    protected static $_table_name = 'core_software_library_items';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'name', 'description', 'category_id', 'vendor',
        'software_type', 'platform', 'architecture', 'license_mode',
        'support_notes', 'status', 'visibility', 'created_by',
        'updated_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
