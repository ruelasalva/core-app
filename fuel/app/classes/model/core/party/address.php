<?php

class Model_Core_Party_Address extends \Orm\Model
{
    protected static $_table_name = 'core_party_addresses';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'party_id', 'address_type', 'name', 'street', 'exterior_number', 'interior_number',
        'neighborhood', 'city', 'state', 'country_code', 'postal_code', 'is_default',
        'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
