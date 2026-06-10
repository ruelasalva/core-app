<?php

namespace Fuel\Tasks;

/**
 * TAREA REPAIRPRIMARYADMIN
 *
 * Asegura que el usuario admin existente conserve acceso administrativo completo.
 *
 * Uso:
 * php oil refine repairprimaryadmin
 */
class Repairprimaryadmin
{
    protected $summary = [
        'group_checked' => 0,
        'admin_found' => 0,
        'admin_group_updated' => 0,
        'permissions_assigned' => 0,
        'permissions_skipped' => 0,
    ];

    public function run()
    {
        try {
            $this->assert_schema();
            $this->ensure_super_admin_group();
            $admin = $this->admin_user();

            if ($admin) {
                $this->summary['admin_found'] = 1;
                $this->ensure_admin_group($admin);
                $this->assign_all_permissions_to_super_admin();
                $this->clear_admin_permission_cache((int) $admin['id']);
            } else {
                $this->assign_all_permissions_to_super_admin();
            }

            $this->print_summary();
            \Log::info('Repairprimaryadmin ejecutado: '.json_encode($this->summary));
        } catch (\Exception $e) {
            \Log::error('Repairprimaryadmin: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach (['users_groups', 'users', 'users_permissions', 'users_group_permissions'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'.');
            }
        }
    }

    protected function ensure_super_admin_group()
    {
        $now = time();
        $row = \DB::select('id')
            ->from('users_groups')
            ->where('id', '=', 100)
            ->execute()
            ->current();

        if ($row) {
            \DB::update('users_groups')
                ->set([
                    'name' => 'Administrador General',
                    'updated_at' => $now,
                ])
                ->where('id', '=', 100)
                ->execute();
        } else {
            \DB::insert('users_groups')->set([
                'id' => 100,
                'name' => 'Administrador General',
                'user_id' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
        }

        $this->summary['group_checked'] = 1;
    }

    protected function admin_user()
    {
        return \DB::select('id', 'username', 'group_id')
            ->from('users')
            ->where('username', '=', 'admin')
            ->execute()
            ->current();
    }

    protected function ensure_admin_group(array $admin)
    {
        if ((int) $admin['group_id'] === 100) {
            return;
        }

        $data = ['group_id' => 100];
        if ($this->table_has_column('users', 'updated_at')) {
            $data['updated_at'] = time();
        }

        \DB::update('users')
            ->set($data)
            ->where('id', '=', (int) $admin['id'])
            ->execute();

        $this->summary['admin_group_updated'] = 1;
    }

    protected function assign_all_permissions_to_super_admin()
    {
        $permissions = \DB::select('id', 'actions')
            ->from('users_permissions')
            ->execute();

        foreach ($permissions as $permission) {
            $permission_id = (int) $permission['id'];
            $actions = $this->actions_from_permission($permission);

            $existing = \DB::select('id')
                ->from('users_group_permissions')
                ->where('group_id', '=', 100)
                ->where('perms_id', '=', $permission_id)
                ->execute()
                ->current();

            if ($existing) {
                \DB::update('users_group_permissions')
                    ->set(['actions' => serialize($actions)])
                    ->where('id', '=', (int) $existing['id'])
                    ->execute();
                $this->summary['permissions_skipped']++;
                continue;
            }

            \DB::insert('users_group_permissions')->set([
                'group_id' => 100,
                'perms_id' => $permission_id,
                'actions' => serialize($actions),
            ])->execute();

            $this->summary['permissions_assigned']++;
        }
    }

    protected function actions_from_permission(array $permission)
    {
        $actions = !empty($permission['actions']) ? @unserialize($permission['actions']) : [];
        return is_array($actions) ? array_values(array_unique($actions)) : [];
    }

    protected function table_has_column($table, $column)
    {
        try {
            return \DBUtil::field_exists($table, [$column]);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function clear_admin_permission_cache($admin_id)
    {
        try {
            \Cache::delete('auth.permissions.user_'.(int) $admin_id);
        } catch (\Exception $e) {
            // Cache may not exist yet.
        }
    }

    protected function print_summary()
    {
        foreach ($this->summary as $key => $value) {
            \Cli::write($key.': '.$value);
        }
    }
}
