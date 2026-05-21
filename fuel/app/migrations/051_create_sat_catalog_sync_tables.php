<?php

namespace Fuel\Migrations;

class Create_sat_catalog_sync_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_sat_catalog_sync_sources')) {
            \DBUtil::create_table('core_sat_catalog_sync_sources', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'catalog_key' => ['type' => 'varchar', 'constraint' => 80],
                'source_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => 'SAT'],
                'source_url' => ['type' => 'text', 'null' => true],
                'source_format' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'auto'],
                'sheet_name' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'code_column' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'code'],
                'name_column' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'name'],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'last_synced_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'last_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'last_message' => ['type' => 'text', 'null' => true],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sat_catalog_sync_sources', 'catalog_key', 'idx_core_sat_catalog_sync_catalog', 'unique');
        }

        if (!\DBUtil::table_exists('core_sat_catalog_sync_logs')) {
            \DBUtil::create_table('core_sat_catalog_sync_logs', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'source_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'catalog_key' => ['type' => 'varchar', 'constraint' => 80],
                'source_url' => ['type' => 'text', 'null' => true],
                'download_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'inserted_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'skipped_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'message' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sat_catalog_sync_logs', ['catalog_key', 'created_at'], 'idx_core_sat_catalog_sync_logs_catalog');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_sat_catalog_sync_logs')) {
            \DBUtil::drop_table('core_sat_catalog_sync_logs');
        }
        if (\DBUtil::table_exists('core_sat_catalog_sync_sources')) {
            \DBUtil::drop_table('core_sat_catalog_sync_sources');
        }
    }
}
