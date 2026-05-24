<?php

namespace Fuel\Migrations;

class Create_treasury_cashflow_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_treasury_cashflow_items')) {
            \DBUtil::create_table('core_treasury_cashflow_items', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'flow_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'inflow'],
                'source_module' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'manual'],
                'source_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'source_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'bank_account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'planned_date' => ['type' => 'varchar', 'constraint' => 10],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'probability' => ['type' => 'decimal', 'constraint' => '5,2', 'default' => 100],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'planned'],
                'reference' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_treasury_cashflow_items', 'folio', 'idx_core_treasury_cashflow_folio', 'unique');
            \DBUtil::create_index('core_treasury_cashflow_items', ['planned_date', 'flow_type', 'status'], 'idx_core_treasury_cashflow_date');
            \DBUtil::create_index('core_treasury_cashflow_items', ['source_entity_type', 'source_entity_id'], 'idx_core_treasury_cashflow_source');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_treasury_cashflow_items');
    }
}
