<?php

namespace Fuel\Migrations;

class Create_core_supplier_import_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_supplier_import_runs')) {
            \DBUtil::create_table('core_supplier_import_runs', [
                'id' => ['type' => 'bigint', 'constraint' => 20, 'auto_increment' => true],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'connection_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'import_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'manual'],
                'source_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'source_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'file_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'dry_run' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'rows_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'skipped_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'error_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'started_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'finished_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'executed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'summary_json' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_supplier_import_runs', ['party_id', 'created_at'], 'idx_supplier_import_runs_party_created');
            \DBUtil::create_index('core_supplier_import_runs', ['provider_code', 'status'], 'idx_supplier_import_runs_provider_status');
            \DBUtil::create_index('core_supplier_import_runs', ['status', 'created_at'], 'idx_supplier_import_runs_status_created');
        }

        if (!\DBUtil::table_exists('core_supplier_import_rows')) {
            \DBUtil::create_table('core_supplier_import_rows', [
                'id' => ['type' => 'bigint', 'constraint' => 20, 'auto_increment' => true],
                'import_run_id' => ['type' => 'bigint', 'constraint' => 20, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'source_hash' => ['type' => 'char', 'constraint' => 64],
                'external_id' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
                'source_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'supplier_sku' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'supplier_model' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
                'supplier_name' => ['type' => 'varchar', 'constraint' => 220, 'default' => ''],
                'supplier_brand' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
                'supplier_category' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'supplier_description' => ['type' => 'text', 'null' => true],
                'supplier_compatibility' => ['type' => 'text', 'null' => true],
                'supplier_currency' => ['type' => 'varchar', 'constraint' => 5, 'default' => 'MXN'],
                'supplier_cost' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'supplier_price' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'supplier_stock' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'selling_price' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'image_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'local_image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'mapping_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'row_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'error_message' => ['type' => 'text', 'null' => true],
                'raw_json' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_supplier_import_rows', ['source_hash'], 'uidx_supplier_import_rows_source_hash', 'unique');
            \DBUtil::create_index('core_supplier_import_rows', ['import_run_id'], 'idx_supplier_import_rows_run');
            \DBUtil::create_index('core_supplier_import_rows', ['party_id', 'supplier_sku'], 'idx_supplier_import_rows_party_sku');
            \DBUtil::create_index('core_supplier_import_rows', ['provider_code', 'external_id'], 'idx_supplier_import_rows_provider_external');
            \DBUtil::create_index('core_supplier_import_rows', ['product_id'], 'idx_supplier_import_rows_product');
            \DBUtil::create_index('core_supplier_import_rows', ['row_status'], 'idx_supplier_import_rows_status');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_supplier_import_rows')) {
            \DBUtil::drop_table('core_supplier_import_rows');
        }

        if (\DBUtil::table_exists('core_supplier_import_runs')) {
            \DBUtil::drop_table('core_supplier_import_runs');
        }
    }
}
