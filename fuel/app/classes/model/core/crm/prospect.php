<?php

class Model_Core_Crm_Prospect extends \Orm\Model
{
    protected static $_table_name = 'core_crm_prospects';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'source_id', 'import_id', 'external_id', 'external_clee', 'name', 'legal_name',
        'activity', 'activity_code', 'size_range', 'phone', 'email', 'website',
        'state', 'municipality', 'locality', 'neighborhood', 'postal_code', 'street',
        'external_number', 'full_address', 'latitude', 'longitude', 'owner_user_id',
        'seller_id', 'status', 'priority', 'next_action_at', 'converted_party_id',
        'converted_at', 'raw_json', 'notes', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
