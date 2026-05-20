<?php

class Model_Core_Crm_Opportunity extends \Orm\Model
{
    protected static $_table_name = 'core_crm_opportunities';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'folio', 'party_id', 'owner_user_id', 'department_id', 'source', 'stage', 'title',
        'description', 'estimated_amount', 'probability', 'expected_close_at', 'next_action_at',
        'lost_reason', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
