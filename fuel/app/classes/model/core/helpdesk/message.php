<?php

class Model_Core_Helpdesk_Message extends \Orm\Model
{
    protected static $_table_name = 'core_helpdesk_messages';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'ticket_id', 'user_id', 'author_type', 'message', 'is_internal', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
