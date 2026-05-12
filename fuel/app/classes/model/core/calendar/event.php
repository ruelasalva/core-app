<?php

class Model_Core_Calendar_Event extends \Orm\Model
{
    protected static $_table_name = 'core_calendar_events';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'title', 'description', 'event_type', 'resource_id', 'assigned_user_id', 'organizer_user_id',
        'related_entity_type', 'related_entity_id', 'start_at', 'end_at', 'all_day', 'status', 'visibility',
        'color', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
