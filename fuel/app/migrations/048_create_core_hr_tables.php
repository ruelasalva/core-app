<?php

namespace Fuel\Migrations;

class Create_core_hr_tables
{
    public function up()
    {
        if (\DBUtil::table_exists('core_employees')) {
            $fields = [];
            $this->add_field($fields, 'party_id', ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'user_id']);
            $this->add_field($fields, 'rfc', ['type' => 'varchar', 'constraint' => 13, 'default' => '', 'after' => 'email']);
            $this->add_field($fields, 'curp', ['type' => 'varchar', 'constraint' => 18, 'default' => '', 'after' => 'rfc']);
            $this->add_field($fields, 'nss', ['type' => 'varchar', 'constraint' => 20, 'default' => '', 'after' => 'curp']);
            $this->add_field($fields, 'hire_date', ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'position']);
            $this->add_field($fields, 'termination_date', ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'hire_date']);
            $this->add_field($fields, 'payroll_status', ['type' => 'varchar', 'constraint' => 30, 'default' => 'active', 'after' => 'termination_date']);
            $this->add_field($fields, 'salary_daily', ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0, 'after' => 'payroll_status']);
            $this->add_field($fields, 'salary_integrated', ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0, 'after' => 'salary_daily']);
            $this->add_field($fields, 'payment_frequency', ['type' => 'varchar', 'constraint' => 30, 'default' => 'quincenal', 'after' => 'salary_integrated']);
            $this->add_field($fields, 'bank_account_id', ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'payment_frequency']);
            $this->add_field($fields, 'sat_regime_code', ['type' => 'varchar', 'constraint' => 10, 'default' => '02', 'after' => 'bank_account_id']);
            $this->add_field($fields, 'contract_type', ['type' => 'varchar', 'constraint' => 30, 'default' => 'indefinido', 'after' => 'sat_regime_code']);
            $this->add_field($fields, 'work_shift', ['type' => 'varchar', 'constraint' => 30, 'default' => 'diurna', 'after' => 'contract_type']);
            $this->add_field($fields, 'risk_class', ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'work_shift']);
            if (!empty($fields)) {
                \DBUtil::add_fields('core_employees', $fields);
            }
        }

        if (!\DBUtil::table_exists('core_hr_payroll_periods')) {
            \DBUtil::create_table('core_hr_payroll_periods', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 140],
                'period_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'quincenal'],
                'date_from' => ['type' => 'varchar', 'constraint' => 10],
                'date_to' => ['type' => 'varchar', 'constraint' => 10],
                'payment_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
                'notes' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_hr_payroll_periods', 'code', 'idx_core_hr_periods_code', 'unique');
        }

        if (!\DBUtil::table_exists('core_hr_payroll_runs')) {
            \DBUtil::create_table('core_hr_payroll_runs', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'period_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'run_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'ordinary'],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'perception_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'deduction_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'net_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'payment_batch_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'accounting_entry_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_hr_payroll_runs', 'folio', 'idx_core_hr_runs_folio', 'unique');
            \DBUtil::create_index('core_hr_payroll_runs', ['period_id', 'status'], 'idx_core_hr_runs_period_status');
        }

        if (!\DBUtil::table_exists('core_hr_payroll_items')) {
            \DBUtil::create_table('core_hr_payroll_items', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'run_id' => ['type' => 'int', 'constraint' => 11],
                'employee_id' => ['type' => 'int', 'constraint' => 11],
                'cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'fiscal_document_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'payment_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'days_paid' => ['type' => 'decimal', 'constraint' => '8,2', 'default' => 0],
                'perception_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'deduction_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'net_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'sat_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'payment_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_hr_payroll_items', ['run_id', 'employee_id'], 'idx_core_hr_items_run_employee');
            \DBUtil::create_index('core_hr_payroll_items', 'cfdi_id', 'idx_core_hr_items_cfdi');
        }

        $this->seed_permission();
        $this->seed_help();
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_hr_payroll_items')) {
            \DBUtil::drop_table('core_hr_payroll_items');
        }
        if (\DBUtil::table_exists('core_hr_payroll_runs')) {
            \DBUtil::drop_table('core_hr_payroll_runs');
        }
        if (\DBUtil::table_exists('core_hr_payroll_periods')) {
            \DBUtil::drop_table('core_hr_payroll_periods');
        }
        if (\DBUtil::table_exists('core_employees')) {
            $fields = ['party_id', 'rfc', 'curp', 'nss', 'hire_date', 'termination_date', 'payroll_status', 'salary_daily', 'salary_integrated', 'payment_frequency', 'bank_account_id', 'sat_regime_code', 'contract_type', 'work_shift', 'risk_class'];
            foreach ($fields as $field) {
                if (\DBUtil::field_exists('core_employees', [$field])) {
                    \DBUtil::drop_fields('core_employees', [$field]);
                }
            }
        }
    }

    protected function add_field(array &$fields, $name, array $definition)
    {
        if (!\DBUtil::field_exists('core_employees', [$name])) {
            $fields[$name] = $definition;
        }
    }

    protected function seed_permission()
    {
        if (!\DBUtil::table_exists('users_permissions')) {
            return;
        }
        if (\DB::select('id')->from('users_permissions')->where('area', '=', 'hr')->where('permission', '=', 'access')->execute()->current()) {
            return;
        }
        \DB::insert('users_permissions')->set([
            'area' => 'hr',
            'permission' => 'access',
            'description' => 'Gestion de recursos humanos, empleados y nomina',
            'actions' => serialize(['view', 'create', 'edit', 'delete', 'import', 'export', 'authorize']),
            'user_id' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function seed_help()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            return;
        }
        if (\DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'rh_nomina')->execute()->current()) {
            return;
        }
        \DB::insert('core_knowledge_articles')->set([
            'code' => 'rh_nomina',
            'title' => 'Recursos Humanos y nomina',
            'category' => 'Operacion',
            'summary' => 'Base para empleados, periodos de nomina, timbrado CFDI y pagos bancarios.',
            'content' => '<h3>Objetivo</h3><p>RH administra empleados que pueden o no tener usuario del sistema. La nomina se prepara por periodo y queda lista para relacionarse con CFDI de nomina, bancos, pagos y contabilidad.</p><h4>Flujo recomendado</h4><ol><li>Captura empleado con RFC, CURP, NSS, departamento, sucursal, salario y forma de pago.</li><li>Crea periodo de nomina: semanal, quincenal o mensual.</li><li>Crea corrida de nomina y agrega empleados con percepciones, deducciones y neto.</li><li>Cuando se timbre, relaciona el item con CFDI/fiscal document.</li><li>Cuando se pague, relaciona con bancos/pagos para dejar evidencia de flujo.</li></ol><h4>Relacion ERP</h4><p>RH no sustituye SAT, Bancos ni Contabilidad: prepara la operacion y guarda las llaves para conectar nomina timbrada, pago y poliza.</p>',
            'sort_order' => 60,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}
