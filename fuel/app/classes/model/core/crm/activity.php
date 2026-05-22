<?php

class Model_Core_Crm_Activity extends \Orm\Model
{
    protected static $_table_name = 'core_crm_activities';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'party_id', 'prospect_id', 'opportunity_id', 'ticket_id', 'activity_type', 'subject', 'description',
        'status', 'priority', 'assigned_user_id', 'due_at', 'completed_at', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
