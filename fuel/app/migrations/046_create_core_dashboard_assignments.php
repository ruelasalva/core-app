<?php

namespace Fuel\Migrations;

class Create_core_dashboard_assignments
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_dashboards')) {
            \DBUtil::create_table('core_dashboards', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 60],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'dashboard_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'generic'],
                'config_json' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_dashboards', 'code', 'idx_core_dashboards_code', 'unique');
        }

        if (\DBUtil::table_exists('core_commerce_products') && !\DBUtil::field_exists('core_commerce_products', ['stock_min'])) {
            \DBUtil::add_fields('core_commerce_products', [
                'stock_min' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0, 'after' => 'stock_reserved'],
            ]);
        }

        if (!\DBUtil::table_exists('core_dashboard_user_assignments')) {
            \DBUtil::create_table('core_dashboard_user_assignments', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'dashboard_id' => ['type' => 'int', 'constraint' => 11],
                'user_id' => ['type' => 'int', 'constraint' => 11],
                'is_default' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_dashboard_user_assignments', ['user_id', 'dashboard_id'], 'idx_core_dashboard_user_unique', 'unique');
        }

        $this->seed_dashboards();
        $this->seed_help();
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_commerce_products') && \DBUtil::field_exists('core_commerce_products', ['stock_min'])) {
            \DBUtil::drop_fields('core_commerce_products', ['stock_min']);
        }
        \DBUtil::drop_table('core_dashboard_user_assignments');
        \DBUtil::drop_table('core_dashboards');
    }

    protected function seed_dashboards()
    {
        $now = time();
        $items = [
            ['generic', 'Dashboard generico', 'Panel base sin informacion sensible.', 'generic'],
            ['executive_commercial', 'Dashboard ejecutivo comercial', 'Ventas, inventario, cobranza, credito y tendencias.', 'executive_commercial'],
        ];

        foreach ($items as $item) {
            $exists = \DB::select('id')->from('core_dashboards')->where('code', '=', $item[0])->execute()->current();
            $data = [
                'name' => $item[1],
                'description' => $item[2],
                'dashboard_type' => $item[3],
                'active' => 1,
                'updated_at' => $now,
            ];
            if ($exists) {
                \DB::update('core_dashboards')->set($data)->where('id', '=', (int) $exists['id'])->execute();
                continue;
            }
            $data['code'] = $item[0];
            $data['config_json'] = null;
            $data['created_at'] = $now;
            \DB::insert('core_dashboards')->set($data)->execute();
        }
    }

    protected function seed_help()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            return;
        }
        if (\DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'dashboards_asignables')->execute()->current()) {
            return;
        }

        \DB::insert('core_knowledge_articles')->set([
            'code' => 'dashboards_asignables',
            'title' => 'Dashboards asignables',
            'category' => 'Administracion',
            'summary' => 'Tableros genericos y ejecutivos asignados por usuario.',
            'content' => '<h3>Objetivo</h3><p>El sistema permite que cada usuario tenga dashboards segun su rol. El tablero generico no expone informacion sensible; el tablero ejecutivo concentra ventas, inventario, cobranza y tendencias.</p><h4>Asignacion</h4><ol><li>Entra a <strong>Admin &gt; Usuarios</strong>.</li><li>Abre <strong>Dashboards</strong> en el usuario.</li><li>Selecciona los tableros que puede ver y guarda.</li></ol><h4>Dashboard ejecutivo comercial</h4><ul><li>Ventas recientes por zona, producto y canal.</li><li>Inventario con alertas por bajo stock o existencia negativa.</li><li>Cobranza con saldos pendientes, vencidos y dias de credito.</li><li>Tendencias mensuales para apoyar decisiones.</li></ul>',
            'sort_order' => 57,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}
