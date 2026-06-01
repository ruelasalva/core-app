<?php

namespace Fuel\Migrations;

class Create_core_fiscal_events
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_fiscal_events')) {
            \DBUtil::create_table('core_fiscal_events', [
                'id' => ['type' => 'bigint', 'constraint' => 20, 'auto_increment' => true],
                'company_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'taxpayer_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'fiscal_period' => ['type' => 'char', 'constraint' => 7, 'default' => ''],
                'event_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'event_status' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'success'],
                'source_module' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'fiscal'],
                'source_entity_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'source_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'summary' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'details_json' => ['type' => 'text', 'null' => true],
                'executed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'executed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_fiscal_events', ['taxpayer_rfc', 'fiscal_period'], 'idx_core_fiscal_events_rfc_period');
            \DBUtil::create_index('core_fiscal_events', ['event_type', 'event_status'], 'idx_core_fiscal_events_type_status');
            \DBUtil::create_index('core_fiscal_events', ['source_module', 'source_entity_type', 'source_entity_id'], 'idx_core_fiscal_events_source');
            \DBUtil::create_index('core_fiscal_events', ['executed_at'], 'idx_core_fiscal_events_executed_at');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_fiscal_events');
    }
}
