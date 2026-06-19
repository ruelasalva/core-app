<?php

class Model_Core_Communication_MessageAttachment extends \Orm\Model
{
    protected static $_table_name = 'core_communication_message_attachments';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'message_id', 'filename', 'mime_type', 'size_bytes',
        'storage_ref', 'content_hash', 'disposition', 'active',
        'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
        'Orm\Observer_UpdatedAt' => [
            'events' => ['before_save'],
            'property' => 'updated_at',
            'mysql_timestamp' => false,
        ],
    ];
}
