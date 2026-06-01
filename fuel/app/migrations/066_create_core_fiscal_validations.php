<?php

namespace Fuel\Migrations;

class Create_core_fiscal_validations
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_fiscal_validations')) {
            \DBUtil::create_table('core_fiscal_validations', [
                'id' => ['type' => 'bigint', 'constraint' => 20, 'auto_increment' => true],
                'company_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'taxpayer_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'fiscal_period' => ['type' => 'char', 'constraint' => 7, 'default' => ''],
                'validation_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'ledger_integrity'],
                'status' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'ok'],
                'warnings_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'errors_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'summary_json' => ['type' => 'text', 'null' => true],
                'executed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'executed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_fiscal_validations', ['taxpayer_rfc', 'fiscal_period'], 'idx_core_fiscal_validations_rfc_period');
            \DBUtil::create_index('core_fiscal_validations', ['validation_type', 'status'], 'idx_core_fiscal_validations_type_status');
            \DBUtil::create_index('core_fiscal_validations', ['executed_at'], 'idx_core_fiscal_validations_executed_at');
            \DBUtil::create_index('core_fiscal_validations', ['active'], 'idx_core_fiscal_validations_active');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_fiscal_validations');
    }
}
