<?php

class Model_Core_Communication_Conversation extends \Orm\Model
{
    protected static $_table_name = 'core_communication_conversations';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'channel_code', 'subject', 'normalized_subject', 'direction',
        'status', 'priority', 'owner_user_id', 'assigned_user_id', 'assigned_group_id',
        'related_entity_type', 'related_entity_id', 'related_party_id',
        'last_message_at', 'message_count', 'unread_count', 'active',
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
