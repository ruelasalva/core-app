<?php

namespace Fuel\Migrations;

class Create_core_web_tables
{
    public function up()
    {
        \DBUtil::create_table('core_web_integrations', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'provider' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'integration_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'script'],
            'environment' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'production'],
            'public_key' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'public_value' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'secret_value' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'settings_json' => ['type' => 'text', 'null' => true],
            'enabled' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'load_in_frontend' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'load_in_admin' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'requires_consent' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'consent_category' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'analytics'],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_web_integrations', 'code', 'idx_core_web_integrations_code', 'unique');
        \DBUtil::create_index('core_web_integrations', 'enabled', 'idx_core_web_integrations_enabled');
        \DBUtil::create_index('core_web_integrations', 'consent_category', 'idx_core_web_integrations_consent_category');

        \DBUtil::create_table('core_web_cookie_preferences', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'token' => ['type' => 'varchar', 'constraint' => 80, 'null' => true],
            'necessary' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'analytics' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'marketing' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'personalization' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'ip_address' => ['type' => 'varchar', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'accepted_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_web_cookie_preferences', 'user_id', 'idx_core_web_cookie_preferences_user_id');
        \DBUtil::create_index('core_web_cookie_preferences', 'token', 'idx_core_web_cookie_preferences_token');
    }

    public function down()
    {
        \DBUtil::drop_table('core_web_cookie_preferences');
        \DBUtil::drop_table('core_web_integrations');
    }
}
