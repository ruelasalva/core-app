<?php

class Model_Core_Document_Link extends \Orm\Model
{
    protected static $_table_name = 'core_document_links';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'document_id', 'entity_type', 'entity_id', 'relation_type', 'notes',
        'created_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
