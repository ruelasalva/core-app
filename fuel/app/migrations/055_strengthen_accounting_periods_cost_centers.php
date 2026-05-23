<?php

namespace Fuel\Migrations;

class Strengthen_accounting_periods_cost_centers
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_accounting_fiscal_years')) {
            \DBUtil::create_table('core_accounting_fiscal_years', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 20],
                'name' => ['type' => 'varchar', 'constraint' => 120],
                'start_date' => ['type' => 'varchar', 'constraint' => 10],
                'end_date' => ['type' => 'varchar', 'constraint' => 10],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
                'locked' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'closed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'closed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_accounting_fiscal_years', 'code', 'idx_core_accounting_years_code', 'unique');
        }

        if (!\DBUtil::table_exists('core_accounting_periods')) {
            \DBUtil::create_table('core_accounting_periods', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'fiscal_year_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'period_key' => ['type' => 'varchar', 'constraint' => 7],
                'name' => ['type' => 'varchar', 'constraint' => 80],
                'start_date' => ['type' => 'varchar', 'constraint' => 10],
                'end_date' => ['type' => 'varchar', 'constraint' => 10],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
                'allow_manual_entries' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'allow_operational_posting' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'locked' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'closed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'closed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_accounting_periods', 'period_key', 'idx_core_accounting_periods_key', 'unique');
            \DBUtil::create_index('core_accounting_periods', ['status', 'locked'], 'idx_core_accounting_periods_status');
        }

        if (!\DBUtil::table_exists('core_accounting_cost_centers')) {
            \DBUtil::create_table('core_accounting_cost_centers', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 140],
                'center_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'department'],
                'parent_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'branch_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'manager_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'budget_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_accounting_cost_centers', 'code', 'idx_core_accounting_cost_centers_code', 'unique');
            \DBUtil::create_index('core_accounting_cost_centers', ['center_type', 'active'], 'idx_core_accounting_cost_centers_type');
        }

        if (\DBUtil::table_exists('core_accounting_journal_entries') && !\DBUtil::field_exists('core_accounting_journal_entries', ['period_id'])) {
            \DBUtil::add_fields('core_accounting_journal_entries', [
                'period_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'period'],
                'locked' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'posted_at'],
            ]);
        }

        if (\DBUtil::table_exists('core_accounting_journal_lines') && !\DBUtil::field_exists('core_accounting_journal_lines', ['cost_center_id'])) {
            \DBUtil::add_fields('core_accounting_journal_lines', [
                'cost_center_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'department_id'],
            ]);
            \DBUtil::create_index('core_accounting_journal_lines', 'cost_center_id', 'idx_core_accounting_lines_cost_center');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_accounting_journal_lines') && \DBUtil::field_exists('core_accounting_journal_lines', ['cost_center_id'])) {
            \DBUtil::drop_fields('core_accounting_journal_lines', ['cost_center_id']);
        }
        if (\DBUtil::table_exists('core_accounting_journal_entries') && \DBUtil::field_exists('core_accounting_journal_entries', ['period_id'])) {
            \DBUtil::drop_fields('core_accounting_journal_entries', ['period_id', 'locked']);
        }
        \DBUtil::drop_table('core_accounting_cost_centers');
        \DBUtil::drop_table('core_accounting_periods');
        \DBUtil::drop_table('core_accounting_fiscal_years');
    }
}
