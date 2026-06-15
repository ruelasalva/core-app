<?php

namespace Fuel\Tasks;

/**
 * TAREA SEEDWORKSPACE
 *
 * Siembra catalogo y acciones seguras del Workspace sin tocar dashboards antiguos.
 */
class Seedworkspace
{
    protected $created = 0;
    protected $updated = 0;
    protected $warnings = [];

    public function run()
    {
        try {
            $this->assert_schema();
            $this->seed_permission();
            $this->seed_widgets();
            $this->seed_quick_actions();
            $this->seed_layout();
            $this->print_summary();

            \Log::info('Seedworkspace ejecutado. creados='.$this->created.' actualizados='.$this->updated.' warnings='.count($this->warnings));
        } catch (\Exception $e) {
            \Log::error('Seedworkspace: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach ([
            'core_workspace_widget_catalog',
            'core_workspace_layouts',
            'core_workspace_widget_instances',
            'core_workspace_quick_actions',
            'core_workspace_user_preferences',
        ] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta migraciones antes de seedworkspace.');
            }
        }
    }

    protected function seed_permission()
    {
        if (!\DBUtil::table_exists('users_permissions')) {
            $this->warnings[] = 'No existe users_permissions; no se creo workspace.access.';
            return;
        }

        $row = $this->permission_row('workspace');
        $actions = ['view', 'edit', 'admin'];
        $data = [
            'area' => 'workspace',
            'permission' => 'access',
            'description' => 'Acceso al Workspace operativo',
            'actions' => serialize($this->auth_permission_actions($actions)),
            'user_id' => 0,
            'updated_at' => time(),
        ];

        if ($row) {
            \DB::update('users_permissions')->set($data)->where('id', '=', (int) $row['id'])->execute();
            $this->updated++;
        } else {
            $data['created_at'] = time();
            \DB::insert('users_permissions')->set($data)->execute();
            $this->created++;
        }

        $this->assign_permission_to_group_100();
    }

    protected function seed_widgets()
    {
        $manifests = (new \Service_Core_Workspace_WidgetRegistry())->manifests();
        foreach ($manifests as $code => $manifest) {
            $this->upsert('core_workspace_widget_catalog', 'code', $code, [
                'code' => $code,
                'title' => $manifest['title'],
                'description' => $manifest['description'],
                'category' => $manifest['category'],
                'type' => $manifest['type'],
                'icon' => $manifest['icon'],
                'color' => $manifest['color'],
                'permission_key' => $manifest['permission_key'],
                'endpoint' => 'admin/workspace/widget/'.$code,
                'refresh_time' => (int) $manifest['refresh_time'],
                'default_w' => 4,
                'default_h' => 2,
                'min_w' => 2,
                'min_h' => 1,
                'max_w' => 12,
                'max_h' => 6,
                'priority' => 10,
                'criticality' => 'low',
                'first_screen' => 1,
                'lazy_load' => 0,
                'refresh_policy' => 'manual',
                'status' => $manifest['status'],
                'manifest_version' => (int) $manifest['version'],
                'capabilities_json' => json_encode(['refresh', 'collapse', 'favorite']),
                'tags_json' => json_encode(['workspace', 'system']),
                'dependencies_json' => json_encode($manifest['dependencies']),
                'settings_json' => json_encode($manifest['settings_schema']),
                'active' => 1,
                'updated_at' => time(),
            ]);
        }
    }

    protected function seed_quick_actions()
    {
        $actions = [
            ['new_quote', 'Nueva cotización', 'bi bi-receipt', 'admin/sales?view=quotes', 'sales.access[create]', 'commercial', 'primary', 10],
            ['new_customer', 'Nuevo cliente', 'bi bi-person-plus', 'admin/parties?section=customers', 'customers.access[edit]', 'commercial', 'success', 20],
            ['products', 'Productos', 'bi bi-box-seam', 'admin/commerce', 'commerce.access[view]', 'commercial', 'info', 30],
            ['purchases', 'Compras', 'bi bi-cart-check', 'admin/purchases', 'purchases.access[view]', 'operation', 'warning', 40],
            ['tickets', 'Tickets', 'bi bi-life-preserver', 'admin/helpdesk', 'helpdesk.access[view]', 'support', 'danger', 50],
            ['sat', 'SAT', 'bi bi-receipt', 'admin/sat', 'sat.access[view]', 'fiscal', 'secondary', 60],
            ['inventory', 'Inventario', 'bi bi-boxes', 'admin/inventory', 'inventory.access[view]', 'operation', 'dark', 70],
        ];

        foreach ($actions as $action) {
            $this->upsert('core_workspace_quick_actions', 'code', $action[0], [
                'code' => $action[0],
                'title' => $action[1],
                'icon' => $action[2],
                'route' => $action[3],
                'permission_key' => $action[4],
                'category' => $action[5],
                'color' => $action[6],
                'execution_type' => 'route',
                'requires_confirmation' => 0,
                'opens_modal' => 0,
                'keywords' => $action[1],
                'active' => 1,
                'sort_order' => $action[7],
                'updated_at' => time(),
            ]);
        }
    }

    protected function seed_layout()
    {
        $layout_id = $this->upsert('core_workspace_layouts', 'name', 'Workspace generico', [
            'scope_type' => 'template',
            'scope_id' => 0,
            'name' => 'Workspace generico',
            'is_default' => 1,
            'layout_version' => 1,
            'schema_version' => 1,
            'profile_code' => 'generic',
            'preset_code' => 'generic',
            'filters_json' => null,
            'mobile_settings_json' => json_encode(['mode' => 'single_column']),
            'layout_snapshot_json' => null,
            'active' => 1,
            'updated_at' => time(),
        ], true);

        if ($layout_id < 1) {
            return;
        }

        $instances = [
            ['welcome', 0, 0, 4, 2],
            ['quick_links', 4, 0, 4, 2],
            ['notifications_placeholder', 8, 0, 4, 2],
        ];

        foreach ($instances as $instance) {
            $exists = \DB::select('id')
                ->from('core_workspace_widget_instances')
                ->where('layout_id', '=', $layout_id)
                ->where('widget_code', '=', $instance[0])
                ->execute()
                ->current();

            $data = [
                'layout_id' => $layout_id,
                'widget_code' => $instance[0],
                'x' => $instance[1],
                'y' => $instance[2],
                'w' => $instance[3],
                'h' => $instance[4],
                'active' => 1,
                'updated_at' => time(),
            ];

            if ($exists) {
                \DB::update('core_workspace_widget_instances')->set($data)->where('id', '=', (int) $exists['id'])->execute();
                $this->updated++;
            } else {
                $data['created_at'] = time();
                \DB::insert('core_workspace_widget_instances')->set($data)->execute();
                $this->created++;
            }
        }
    }

    protected function upsert($table, $key, $value, array $data, $return_id = false)
    {
        $row = \DB::select('id')->from($table)->where($key, '=', $value)->execute()->current();
        if ($row) {
            \DB::update($table)->set($data)->where('id', '=', (int) $row['id'])->execute();
            $this->updated++;
            return $return_id ? (int) $row['id'] : 0;
        }

        $data[$key] = $value;
        $data['created_at'] = isset($data['created_at']) ? $data['created_at'] : time();
        list($id) = \DB::insert($table)->set($data)->execute();
        $this->created++;
        return $return_id ? (int) $id : 0;
    }

    protected function permission_row($area)
    {
        return \DB::select('id', 'actions')
            ->from('users_permissions')
            ->where('area', '=', $area)
            ->where('permission', '=', 'access')
            ->execute()
            ->current();
    }

    protected function assign_permission_to_group_100()
    {
        if (!\DBUtil::table_exists('users_group_permissions')) {
            return;
        }

        $permission = $this->permission_row('workspace');
        if (!$permission) {
            return;
        }

        $exists = \DB::select('id')
            ->from('users_group_permissions')
            ->where('group_id', '=', 100)
            ->where('perms_id', '=', (int) $permission['id'])
            ->execute()
            ->current();

        $data = [
            'group_id' => 100,
            'perms_id' => (int) $permission['id'],
            'actions' => serialize($this->auth_permission_actions(['view', 'edit', 'admin'])),
        ];

        if (\DBUtil::field_exists('users_group_permissions', ['updated_at'])) {
            $data['updated_at'] = time();
        }

        if ($exists) {
            \DB::update('users_group_permissions')->set($data)->where('id', '=', (int) $exists['id'])->execute();
            return;
        }

        if (\DBUtil::field_exists('users_group_permissions', ['created_at'])) {
            $data['created_at'] = time();
        }
        \DB::insert('users_group_permissions')->set($data)->execute();
    }

    protected function auth_permission_actions(array $actions)
    {
        $map = [];
        foreach ($actions as $action) {
            $action = trim((string) $action);
            if ($action !== '') {
                $map[$action] = $action;
            }
        }

        return $map;
    }

    protected function print_summary()
    {
        \Cli::write('Seedworkspace terminado.');
        \Cli::write('Creados: '.$this->created);
        \Cli::write('Actualizados: '.$this->updated);
        if (!empty($this->warnings)) {
            \Cli::write('Advertencias: '.count($this->warnings));
            foreach ($this->warnings as $warning) {
                \Cli::write(' - '.$warning);
            }
        }
    }
}
