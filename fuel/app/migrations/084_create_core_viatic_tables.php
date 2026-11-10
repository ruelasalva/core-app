<?php

namespace Fuel\Migrations;

class Create_core_viatic_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_viatic_trips')) {
            \DBUtil::create_table('core_viatic_trips', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'employee_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'title' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'destination' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'travel_reason' => ['type' => 'text', 'null' => true],
                'start_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'end_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'requested_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'advance_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'expense_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'balance_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'settlement_account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'accounting_entry_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_viatic_trips', 'folio', 'idx_core_viatic_trips_folio', 'unique');
            \DBUtil::create_index('core_viatic_trips', ['user_id', 'status', 'active'], 'idx_core_viatic_trips_user_status');
            \DBUtil::create_index('core_viatic_trips', ['seller_id', 'employee_id'], 'idx_core_viatic_trips_seller_employee');
        }

        if (!\DBUtil::table_exists('core_viatic_advances')) {
            \DBUtil::create_table('core_viatic_advances', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'trip_id' => ['type' => 'int', 'constraint' => 11],
                'bank_account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'requested_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'paid_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'reference' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_viatic_advances', ['trip_id', 'status', 'active'], 'idx_core_viatic_advances_trip_status');
        }

        if (!\DBUtil::table_exists('core_viatic_expenses')) {
            \DBUtil::create_table('core_viatic_expenses', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'trip_id' => ['type' => 'int', 'constraint' => 11],
                'expense_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'concept' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'vendor_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'document_folio' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'payment_method' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'cash'],
                'account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'exchange_rate' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
                'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'tax_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_viatic_expenses', ['trip_id', 'expense_date', 'active'], 'idx_core_viatic_expenses_trip_date');
            \DBUtil::create_index('core_viatic_expenses', ['account_id', 'currency_code'], 'idx_core_viatic_expenses_account_currency');
        }

        if (!\DBUtil::table_exists('core_viatic_settlements')) {
            \DBUtil::create_table('core_viatic_settlements', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'trip_id' => ['type' => 'int', 'constraint' => 11],
                'advance_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'expense_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'balance_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'balance_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'equalized'],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
                'settled_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_viatic_settlements', ['trip_id', 'status', 'active'], 'idx_core_viatic_settlements_trip_status');
        }

        $this->seed_permission();
        $this->seed_help();
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_viatic_settlements')) {
            \DBUtil::drop_table('core_viatic_settlements');
        }
        if (\DBUtil::table_exists('core_viatic_expenses')) {
            \DBUtil::drop_table('core_viatic_expenses');
        }
        if (\DBUtil::table_exists('core_viatic_advances')) {
            \DBUtil::drop_table('core_viatic_advances');
        }
        if (\DBUtil::table_exists('core_viatic_trips')) {
            \DBUtil::drop_table('core_viatic_trips');
        }
    }

    protected function seed_permission()
    {
        if (!\DBUtil::table_exists('users_permissions')) {
            return;
        }

        $row = \DB::select('id', 'actions')
            ->from('users_permissions')
            ->where('area', '=', 'viatics')
            ->where('permission', '=', 'access')
            ->execute()
            ->current();

        $actions = [
            'view' => 'view',
            'create' => 'create',
            'edit' => 'edit',
            'delete' => 'delete',
            'post' => 'post',
        ];

        if (!$row) {
            \DB::insert('users_permissions')->set([
                'area' => 'viatics',
                'permission' => 'access',
                'description' => 'Acceso al modulo de viaticos',
                'actions' => serialize($actions),
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
            return;
        }

        $current = @unserialize($row['actions']);
        $current = is_array($current) ? $current : [];
        \DB::update('users_permissions')
            ->set([
                'actions' => serialize(array_merge($current, $actions)),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $row['id'])
            ->execute();
    }

    protected function seed_help()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            return;
        }

        if (\DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'viaticos_operacion')->execute()->current()) {
            return;
        }

        \DB::insert('core_knowledge_articles')->set([
            'code' => 'viaticos_operacion',
            'title' => 'Viaticos de viaje',
            'category' => 'Finanzas',
            'summary' => 'Gestion guiada de viajes, anticipos, gastos y cierre con borrador contable.',
            'content' => '<h3>Flujo</h3><ol><li>Crear viaje</li><li>Registrar anticipo</li><li>Capturar gastos</li><li>Revisar saldo</li><li>Generar borrador contable</li></ol><p>El modulo no publica polizas automaticamente: deja el asiento en borrador para revision contable.</p>',
            'sort_order' => 70,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}

