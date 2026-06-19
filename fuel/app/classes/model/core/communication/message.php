<?php

class Model_Core_Communication_Message extends \Orm\Model
{
    protected static $_table_name = 'core_communication_messages';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'conversation_id', 'account_id', 'channel_code', 'direction',
        'message_type', 'external_message_id', 'external_thread_id', 'in_reply_to',
        'references_hash', 'from_email', 'from_name', 'to_json', 'cc_json',
        'bcc_json', 'subject', 'body_text', 'body_html_sanitized', 'snippet',
        'received_at', 'sent_at', 'status', 'provider_code', 'queue_id',
        'related_entity_type', 'related_entity_id', 'related_party_id',
        'raw_headers_json', 'has_attachments', 'attachment_count',
        'content_hash', 'active', 'created_at', 'updated_at',
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
