<?php

class Model_Core_Portal_Profile extends \Orm\Model
{
    protected static $_table_name = 'core_portal_profiles';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'code', 'backend_code', 'name', 'description', 'login_route', 'dashboard_route',
        'requires_party', 'allowed_party_types', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
