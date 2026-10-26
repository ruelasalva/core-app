<?php

class Model_Core_Software_Library_UploadSession extends \Orm\Model
{
    protected static $_table_name = 'core_software_library_upload_sessions';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'upload_code', 'software_id', 'version_id', 'title',
        'filename', 'mime_type', 'size_bytes', 'chunk_size',
        'total_chunks', 'received_chunks', 'checksum_sha256',
        'status', 'created_by', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
