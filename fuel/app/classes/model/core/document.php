<?php

class Model_Core_Document extends \Orm\Model
{
    protected static $_table_name = 'core_documents';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'document_type', 'title', 'description', 'file_path', 'original_name',
        'mime_type', 'file_extension', 'file_size', 'checksum', 'visibility',
        'is_evidence', 'uploaded_by', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
