<?php

namespace Fuel\Migrations;

class Create_core_party_tables
{
    public function up()
    {
        \DBUtil::create_table('core_parties', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'party_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'customer'],
            'code' => ['type' => 'varchar', 'constraint' => 60],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'legal_name' => ['type' => 'varchar', 'constraint' => 220, 'default' => ''],
            'rfc' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
            'email' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
            'phone' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'price_list_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'payment_term_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sat_cfdi_use_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'sat_tax_regime_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'fiscal_operation_type_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'shipping_method_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'credit_limit' => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0],
            'credit_days' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'notes' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_parties', 'code', 'idx_core_parties_code', 'unique');
        \DBUtil::create_index('core_parties', ['party_type', 'active'], 'idx_core_parties_type_active');

        \DBUtil::create_table('core_party_addresses', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'party_id' => ['type' => 'int', 'constraint' => 11],
            'address_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'shipping'],
            'name' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
            'street' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'exterior_number' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
            'interior_number' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
            'neighborhood' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'city' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'state' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'country_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MX'],
            'postal_code' => ['type' => 'varchar', 'constraint' => 12, 'default' => ''],
            'is_default' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_party_addresses', 'party_id', 'idx_core_party_addresses_party_id');

        \DBUtil::create_table('core_party_contacts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'party_id' => ['type' => 'int', 'constraint' => 11],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'position' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'email' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
            'phone' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'receives_notifications' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_party_contacts', 'party_id', 'idx_core_party_contacts_party_id');
    }

    public function down()
    {
        \DBUtil::drop_table('core_party_contacts');
        \DBUtil::drop_table('core_party_addresses');
        \DBUtil::drop_table('core_parties');
    }
}
