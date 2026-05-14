<?php

namespace Fuel\Migrations;

class Add_offline_sales_support
{
    public function up()
    {
        \DBUtil::add_fields('core_sales_quotes', [
            'offline_uuid' => ['type' => 'varchar', 'constraint' => 64, 'default' => '', 'after' => 'source'],
            'synced_from_offline' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'offline_uuid'],
            'offline_synced_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'synced_from_offline'],
        ]);
        \DBUtil::create_index('core_sales_quotes', 'offline_uuid', 'idx_core_sales_quotes_offline_uuid');

        \DBUtil::create_table('core_offline_sync_logs', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'offline_uuid' => ['type' => 'varchar', 'constraint' => 64],
            'module' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
            'entity_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'synced'],
            'device_label' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'payload_hash' => ['type' => 'varchar', 'constraint' => 64, 'default' => ''],
            'message' => ['type' => 'text', 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8mb4');
        \DBUtil::create_index('core_offline_sync_logs', 'offline_uuid', 'idx_core_offline_logs_uuid');
        \DBUtil::create_index('core_offline_sync_logs', ['module', 'entity_type'], 'idx_core_offline_logs_module_entity');
    }

    public function down()
    {
        \DBUtil::drop_table('core_offline_sync_logs');
        \DBUtil::drop_fields('core_sales_quotes', [
            'offline_uuid',
            'synced_from_offline',
            'offline_synced_at',
        ]);
    }
}
