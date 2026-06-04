<?php

namespace Fuel\Migrations;

class Create_core_contract_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_contract_types')) {
            \DBUtil::create_table('core_contract_types', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 60],
                'name' => ['type' => 'varchar', 'constraint' => 120],
                'party_scope' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'any'],
                'default_portal_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'admin'],
                'requires_party' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'requires_end_date' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'requires_approval' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_contract_types', ['code'], 'uidx_core_contract_types_code', 'unique');
            \DBUtil::create_index('core_contract_types', ['party_scope', 'active'], 'idx_core_contract_types_scope_active');
        }

        if (!\DBUtil::table_exists('core_contracts')) {
            \DBUtil::create_table('core_contracts', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'company_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'contract_number' => ['type' => 'varchar', 'constraint' => 40],
                'contract_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'service_agreement'],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'portal_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'admin'],
                'title' => ['type' => 'varchar', 'constraint' => 180],
                'description' => ['type' => 'text', 'null' => true],
                'start_date' => ['type' => 'date', 'null' => true],
                'end_date' => ['type' => 'date', 'null' => true],
                'renewal_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'none'],
                'status' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'draft'],
                'responsible_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'contract_value' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'renewal_value' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'renewal_currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'billing_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'none'],
                'response_hours' => ['type' => 'decimal', 'constraint' => '10,2', 'default' => 0],
                'resolution_hours' => ['type' => 'decimal', 'constraint' => '10,2', 'default' => 0],
                'visibility' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'internal'],
                'approval_status' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'not_required'],
                'approved_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'approved_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'signed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_contracts', ['contract_number'], 'uidx_core_contracts_number', 'unique');
            \DBUtil::create_index('core_contracts', ['company_id'], 'idx_core_contracts_company');
            \DBUtil::create_index('core_contracts', ['party_id', 'portal_code', 'active'], 'idx_core_contracts_party_portal');
            \DBUtil::create_index('core_contracts', ['contract_type'], 'idx_core_contracts_type');
            \DBUtil::create_index('core_contracts', ['status', 'end_date'], 'idx_core_contracts_status_end');
            \DBUtil::create_index('core_contracts', ['responsible_user_id'], 'idx_core_contracts_responsible');
        }

        if (!\DBUtil::table_exists('core_contract_relations')) {
            \DBUtil::create_table('core_contract_relations', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'contract_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'related_module' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'related_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'related_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'relation_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'reference'],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_contract_relations', ['contract_id'], 'idx_core_contract_relations_contract');
            \DBUtil::create_index('core_contract_relations', ['related_module', 'related_entity_type', 'related_entity_id'], 'idx_core_contract_relations_entity');
            \DBUtil::create_index('core_contract_relations', ['contract_id', 'relation_type'], 'idx_core_contract_relations_type');
        }

        if (!\DBUtil::table_exists('core_contract_events')) {
            \DBUtil::create_table('core_contract_events', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'contract_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'event_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'note'],
                'old_status' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
                'new_status' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
                'message' => ['type' => 'text', 'null' => true],
                'payload_json' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_contract_events', ['contract_id'], 'idx_core_contract_events_contract');
            \DBUtil::create_index('core_contract_events', ['event_type'], 'idx_core_contract_events_type');
            \DBUtil::create_index('core_contract_events', ['created_at'], 'idx_core_contract_events_created');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_contract_events')) {
            \DBUtil::drop_table('core_contract_events');
        }

        if (\DBUtil::table_exists('core_contract_relations')) {
            \DBUtil::drop_table('core_contract_relations');
        }

        if (\DBUtil::table_exists('core_contracts')) {
            \DBUtil::drop_table('core_contracts');
        }

        if (\DBUtil::table_exists('core_contract_types')) {
            \DBUtil::drop_table('core_contract_types');
        }
    }
}
