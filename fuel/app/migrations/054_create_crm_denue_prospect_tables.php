<?php

namespace Fuel\Migrations;

class Create_crm_denue_prospect_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_crm_external_sources')) {
            \DBUtil::create_table('core_crm_external_sources', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 60],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'description' => ['type' => 'text', 'null' => true],
                'website_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_external_sources', 'code', 'idx_core_crm_external_sources_code', 'unique');
        }

        if (!\DBUtil::table_exists('core_crm_prospect_imports')) {
            \DBUtil::create_table('core_crm_prospect_imports', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'source_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'connection_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'query_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'denue_api'],
                'query_json' => ['type' => 'text', 'null' => true],
                'results_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'imported_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'skipped_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'completed'],
                'error_message' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_prospect_imports', 'folio', 'idx_core_crm_prospect_imports_folio', 'unique');
            \DBUtil::create_index('core_crm_prospect_imports', ['source_id', 'created_at'], 'idx_core_crm_prospect_imports_source');
        }

        if (!\DBUtil::table_exists('core_crm_prospects')) {
            \DBUtil::create_table('core_crm_prospects', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'source_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'import_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'external_id' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'external_clee' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'name' => ['type' => 'varchar', 'constraint' => 180],
                'legal_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'activity' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'activity_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
                'size_range' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'phone' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'email' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'website' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'state' => ['type' => 'varchar', 'constraint' => 100, 'default' => ''],
                'municipality' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'locality' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'neighborhood' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'postal_code' => ['type' => 'varchar', 'constraint' => 12, 'default' => ''],
                'street' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'external_number' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
                'full_address' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'latitude' => ['type' => 'decimal', 'constraint' => '12,8', 'default' => 0],
                'longitude' => ['type' => 'decimal', 'constraint' => '12,8', 'default' => 0],
                'owner_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'new'],
                'priority' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'normal'],
                'next_action_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'converted_party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'converted_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'raw_json' => ['type' => 'text', 'null' => true],
                'notes' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_prospects', ['source_id', 'external_id'], 'idx_core_crm_prospects_source_external');
            \DBUtil::create_index('core_crm_prospects', ['status', 'owner_user_id'], 'idx_core_crm_prospects_status_owner');
            \DBUtil::create_index('core_crm_prospects', ['state', 'municipality'], 'idx_core_crm_prospects_geo');
        }

        if (\DBUtil::table_exists('core_crm_opportunities') && !\DBUtil::field_exists('core_crm_opportunities', ['prospect_id'])) {
            \DBUtil::add_fields('core_crm_opportunities', [
                'prospect_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'party_id'],
            ]);
            \DBUtil::create_index('core_crm_opportunities', 'prospect_id', 'idx_core_crm_opportunities_prospect');
        }

        if (\DBUtil::table_exists('core_crm_activities') && !\DBUtil::field_exists('core_crm_activities', ['prospect_id'])) {
            \DBUtil::add_fields('core_crm_activities', [
                'prospect_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'party_id'],
            ]);
            \DBUtil::create_index('core_crm_activities', ['prospect_id', 'status'], 'idx_core_crm_activities_prospect_status');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_crm_activities') && \DBUtil::field_exists('core_crm_activities', ['prospect_id'])) {
            \DBUtil::drop_fields('core_crm_activities', ['prospect_id']);
        }
        if (\DBUtil::table_exists('core_crm_opportunities') && \DBUtil::field_exists('core_crm_opportunities', ['prospect_id'])) {
            \DBUtil::drop_fields('core_crm_opportunities', ['prospect_id']);
        }
        if (\DBUtil::table_exists('core_crm_prospects')) {
            \DBUtil::drop_table('core_crm_prospects');
        }
        if (\DBUtil::table_exists('core_crm_prospect_imports')) {
            \DBUtil::drop_table('core_crm_prospect_imports');
        }
        if (\DBUtil::table_exists('core_crm_external_sources')) {
            \DBUtil::drop_table('core_crm_external_sources');
        }
    }
}
