<?php

class Model_Core_Party_User_Link extends \Orm\Model
{
    protected static $_table_name = 'core_party_user_links';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'user_id', 'party_id', 'portal_code', 'role_code', 'scope_json',
        'can_manage_users', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
