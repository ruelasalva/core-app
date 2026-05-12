<?php

class Model_Core_Party_Branding extends \Orm\Model
{
    protected static $_table_name = 'core_party_branding';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'party_id', 'portal_code', 'display_name', 'logo_path', 'primary_color',
        'secondary_color', 'quote_footer', 'custom_css', 'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
