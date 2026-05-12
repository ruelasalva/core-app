<?php

class Model_Core_Helpdesk_Ticket extends \Orm\Model
{
    protected static $_table_name = 'core_helpdesk_tickets';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'source', 'portal_code', 'party_id', 'requester_user_id', 'assigned_user_id',
        'department_id', 'category_id', 'status_id', 'priority', 'subject', 'description',
        'last_message_at', 'closed_at', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
