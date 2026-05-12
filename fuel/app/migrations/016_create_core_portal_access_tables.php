<?php

namespace Fuel\Migrations;

class Create_core_portal_access_tables
{
    public function up()
    {
        \DBUtil::create_table('core_portal_profiles', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 60],
            'backend_code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'login_route' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'dashboard_route' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'requires_party' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'allowed_party_types' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_portal_profiles', 'code', 'idx_core_portal_profiles_code', 'unique');
        \DBUtil::create_index('core_portal_profiles', 'backend_code', 'idx_core_portal_profiles_backend');

        \DBUtil::create_table('core_party_user_links', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11],
            'party_id' => ['type' => 'int', 'constraint' => 11],
            'portal_code' => ['type' => 'varchar', 'constraint' => 60],
            'role_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'viewer'],
            'scope_json' => ['type' => 'text', 'null' => true],
            'can_manage_users' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_party_user_links', ['user_id', 'party_id', 'portal_code'], 'idx_core_party_user_links_unique', 'unique');
        \DBUtil::create_index('core_party_user_links', ['portal_code', 'active'], 'idx_core_party_user_links_portal');

        \DBUtil::create_table('core_party_branding', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'party_id' => ['type' => 'int', 'constraint' => 11],
            'portal_code' => ['type' => 'varchar', 'constraint' => 60],
            'display_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'logo_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'primary_color' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#0d6efd'],
            'secondary_color' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#343a40'],
            'quote_footer' => ['type' => 'text', 'null' => true],
            'custom_css' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_party_branding', ['party_id', 'portal_code'], 'idx_core_party_branding_unique', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_party_branding');
        \DBUtil::drop_table('core_party_user_links');
        \DBUtil::drop_table('core_portal_profiles');
    }
}
