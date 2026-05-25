<?php

namespace Fuel\Migrations;

class Create_budget_control_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_budget_plans')) {
            \DBUtil::create_table('core_budget_plans', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'fiscal_year_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'cost_center_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'total_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'approved_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'approved_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_budget_plans', 'code', 'idx_core_budget_plans_code', 'unique');
            \DBUtil::create_index('core_budget_plans', ['fiscal_year_id', 'status'], 'idx_core_budget_plans_year');
        }

        if (!\DBUtil::table_exists('core_budget_lines')) {
            \DBUtil::create_table('core_budget_lines', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'plan_id' => ['type' => 'int', 'constraint' => 11],
                'account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'cost_center_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'period_start' => ['type' => 'varchar', 'constraint' => 10],
                'period_end' => ['type' => 'varchar', 'constraint' => 10],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'warning_threshold' => ['type' => 'decimal', 'constraint' => '5,2', 'default' => 80],
                'block_threshold' => ['type' => 'decimal', 'constraint' => '5,2', 'default' => 100],
                'notes' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_budget_lines', ['plan_id', 'active'], 'idx_core_budget_lines_plan');
            \DBUtil::create_index('core_budget_lines', ['account_id', 'cost_center_id', 'period_start'], 'idx_core_budget_lines_scope');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_budget_lines');
        \DBUtil::drop_table('core_budget_plans');
    }
}
