<?php

namespace Fuel\Migrations;

class Create_core_configuration_tables
{
    public function up()
    {
        \DBUtil::create_table('core_companies', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'legal_name' => ['type' => 'varchar', 'constraint' => 180, 'null' => true],
            'rfc' => ['type' => 'varchar', 'constraint' => 13, 'null' => true],
            'postal_code' => ['type' => 'varchar', 'constraint' => 10, 'null' => true],
            'tax_regime_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'contact_email' => ['type' => 'varchar', 'constraint' => 180, 'null' => true],
            'contact_phone' => ['type' => 'varchar', 'constraint' => 40, 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_table('core_branches', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'company_id' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'city' => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
            'state' => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_branches', 'code', 'idx_core_branches_code', 'unique');

        \DBUtil::create_table('core_departments', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'constraint' => 120],
            'slug' => ['type' => 'varchar', 'constraint' => 140],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_departments', 'slug', 'idx_core_departments_slug', 'unique');

        \DBUtil::create_table('core_employees', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'department_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'branch_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'employee_number' => ['type' => 'varchar', 'constraint' => 40, 'null' => true],
            'full_name' => ['type' => 'varchar', 'constraint' => 180],
            'email' => ['type' => 'varchar', 'constraint' => 180, 'null' => true],
            'position' => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_employees', 'user_id', 'idx_core_employees_user_id');
        \DBUtil::create_index('core_employees', 'department_id', 'idx_core_employees_department_id');

        \DBUtil::create_table('core_backends', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 120],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'base_route' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_backends', 'code', 'idx_core_backends_code', 'unique');

        \DBUtil::create_table('core_settings', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'setting_group' => ['type' => 'varchar', 'constraint' => 80],
            'setting_key' => ['type' => 'varchar', 'constraint' => 120],
            'value' => ['type' => 'text', 'null' => true],
            'value_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'string'],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_settings', ['setting_group', 'setting_key'], 'idx_core_settings_group_key', 'unique');

        \DBUtil::create_table('core_email_settings', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'mailer' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'smtp'],
            'host' => ['type' => 'varchar', 'constraint' => 180, 'null' => true],
            'port' => ['type' => 'int', 'constraint' => 6, 'null' => true],
            'username' => ['type' => 'varchar', 'constraint' => 180, 'null' => true],
            'password' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'encryption' => ['type' => 'varchar', 'constraint' => 20, 'null' => true],
            'from_email' => ['type' => 'varchar', 'constraint' => 180, 'null' => true],
            'from_name' => ['type' => 'varchar', 'constraint' => 180, 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_table('core_notification_events', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 100],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_notification_events', 'code', 'idx_core_notification_events_code', 'unique');

        \DBUtil::create_table('core_system_logs', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'backend' => ['type' => 'varchar', 'constraint' => 80, 'null' => true],
            'module' => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
            'action' => ['type' => 'varchar', 'constraint' => 120],
            'level' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'info'],
            'message' => ['type' => 'text', 'null' => true],
            'context' => ['type' => 'text', 'null' => true],
            'ip' => ['type' => 'varchar', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_system_logs', 'user_id', 'idx_core_system_logs_user_id');
        \DBUtil::create_index('core_system_logs', 'created_at', 'idx_core_system_logs_created_at');
    }

    public function down()
    {
        \DBUtil::drop_table('core_system_logs');
        \DBUtil::drop_table('core_notification_events');
        \DBUtil::drop_table('core_email_settings');
        \DBUtil::drop_table('core_settings');
        \DBUtil::drop_table('core_backends');
        \DBUtil::drop_table('core_employees');
        \DBUtil::drop_table('core_departments');
        \DBUtil::drop_table('core_branches');
        \DBUtil::drop_table('core_companies');
    }
}
