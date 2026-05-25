<?php

namespace Fuel\Migrations;

class Add_employee_compensation_type
{
    public function up()
    {
        if (\DBUtil::table_exists('core_employees') && !\DBUtil::field_exists('core_employees', ['compensation_type'])) {
            \DBUtil::add_fields('core_employees', [
                'compensation_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'salary', 'after' => 'payroll_status'],
            ]);
            \DBUtil::create_index('core_employees', ['compensation_type', 'active'], 'idx_core_employees_compensation');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_employees') && \DBUtil::field_exists('core_employees', ['compensation_type'])) {
            \DBUtil::drop_fields('core_employees', ['compensation_type']);
        }
    }
}
