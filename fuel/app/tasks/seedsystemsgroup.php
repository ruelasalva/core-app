<?php

namespace Fuel\Tasks;

/**
 * TAREA SEEDSYSTEMSGROUP
 *
 * Crea el grupo Sistemas y permisos mínimos para ruteo de notificaciones.
 *
 * Uso:
 * php oil refine seedsystemsgroup
 * php oil refine seedsystemsgroup --add-user=admin --force-primary-group=1
 */
class Seedsystemsgroup
{
    const GROUP_NAME = 'Sistemas';
    const PREFERRED_GROUP_ID = 80;

    protected $summary = [
        'group_created' => 0,
        'group_existing' => 0,
        'group_id' => 0,
        'permissions_created' => 0,
        'permissions_updated' => 0,
        'permissions_assigned' => 0,
        'permissions_existing' => 0,
        'users_assigned' => 0,
        'users_skipped' => 0,
        'warnings' => [],
    ];

    protected $permission_definitions = [
        'helpdesk' => [
            'description' => 'Acceso mínimo a mesa de ayuda para notificaciones de sistemas',
            'actions' => ['view'],
        ],
        'communications' => [
            'description' => 'Acceso mínimo al centro de comunicaciones',
            'actions' => ['view'],
        ],
    ];

    public function run()
    {
        try {
            $this->assert_schema();

            $group_id = $this->ensure_group();
            $this->ensure_permissions();
            $this->assign_permissions($group_id);
            $this->maybe_assign_user($group_id);
            $this->print_summary();

            \Log::info('Seedsystemsgroup ejecutado. group_id='.$group_id.' usuarios_asignados='.$this->summary['users_assigned']);
        } catch (\Exception $e) {
            \Log::error('Seedsystemsgroup: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach (['users_groups', 'users', 'users_permissions', 'users_group_permissions'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta migraciones ORMAuth antes de sembrar Sistemas.');
            }
        }
    }

    protected function ensure_group()
    {
        $row = $this->group_by_name(self::GROUP_NAME);
        if ($row) {
            $this->summary['group_existing'] = 1;
            $this->summary['group_id'] = (int) $row['id'];
            return (int) $row['id'];
        }

        $group_id = $this->available_group_id();
        \DB::insert('users_groups')->set([
            'id' => $group_id,
            'name' => self::GROUP_NAME,
            'user_id' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        $this->summary['group_created'] = 1;
        $this->summary['group_id'] = $group_id;
        return $group_id;
    }

    protected function group_by_name($name)
    {
        return \DB::select('id', 'name')
            ->from('users_groups')
            ->where('name', '=', $name)
            ->execute()
            ->current();
    }

    protected function available_group_id()
    {
        $preferred = self::PREFERRED_GROUP_ID;
        $row = \DB::select('id')->from('users_groups')->where('id', '=', $preferred)->execute()->current();
        if (!$row) {
            return $preferred;
        }

        $max = \DB::select([\DB::expr('MAX(id)'), 'max_id'])->from('users_groups')->execute()->current();
        $next = max($preferred + 1, (int) \Arr::get($max, 'max_id', $preferred) + 1);
        while (\DB::select('id')->from('users_groups')->where('id', '=', $next)->execute()->current()) {
            $next++;
        }

        return $next;
    }

    protected function ensure_permissions()
    {
        foreach ($this->permission_definitions as $area => $definition) {
            $row = $this->permission_row($area);
            $actions = $this->auth_permission_actions($definition['actions']);

            if (!$row) {
                \DB::insert('users_permissions')->set([
                    'area' => $area,
                    'permission' => 'access',
                    'description' => $definition['description'],
                    'actions' => serialize($actions),
                    'user_id' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ])->execute();
                $this->summary['permissions_created']++;
                continue;
            }

            $current = $this->actions_from_row($row);
            $merged = array_merge($current, $actions);
            if ($merged === $current) {
                continue;
            }

            \DB::update('users_permissions')
                ->set([
                    'actions' => serialize($merged),
                    'updated_at' => time(),
                ])
                ->where('id', '=', (int) $row['id'])
                ->execute();
            $this->summary['permissions_updated']++;
        }
    }

    protected function assign_permissions($group_id)
    {
        foreach ($this->permission_definitions as $area => $definition) {
            $permission = $this->permission_row($area);
            if (!$permission) {
                $this->summary['warnings'][] = 'No se encontro '.$area.'.access para asignar al grupo.';
                continue;
            }

            $existing = \DB::select('id')
                ->from('users_group_permissions')
                ->where('group_id', '=', (int) $group_id)
                ->where('perms_id', '=', (int) $permission['id'])
                ->execute()
                ->current();

            $data = [
                'group_id' => (int) $group_id,
                'perms_id' => (int) $permission['id'],
                'actions' => serialize($this->auth_permission_actions($definition['actions'])),
            ];

            if ($existing) {
                \DB::update('users_group_permissions')
                    ->set($data)
                    ->where('id', '=', (int) $existing['id'])
                    ->execute();
                $this->summary['permissions_existing']++;
                continue;
            }

            \DB::insert('users_group_permissions')->set($data)->execute();
            $this->summary['permissions_assigned']++;
        }
    }

    protected function maybe_assign_user($group_id)
    {
        $target = trim((string) \Cli::option('add-user', ''));
        if ($target === '') {
            $this->summary['warnings'][] = 'No se asignaron usuarios. Usa --add-user=USERNAME_OR_ID con --force-primary-group=1 si deseas mover un usuario al grupo Sistemas.';
            return;
        }

        $user = $this->find_user($target);
        if (!$user) {
            throw new \RuntimeException('Usuario no encontrado para --add-user='.$target);
        }

        $force = (string) \Cli::option('force-primary-group', '0') === '1';
        if (!$force) {
            $this->summary['users_skipped']++;
            $this->summary['warnings'][] = 'Usuario '.$user['username'].' encontrado, pero no se movio porque ORMAuth usa users.group_id unico. Ejecuta con --force-primary-group=1 para confirmar.';
            return;
        }

        if ((int) $user['group_id'] === (int) $group_id) {
            $this->summary['users_skipped']++;
            $this->summary['warnings'][] = 'Usuario '.$user['username'].' ya pertenece a Sistemas.';
            return;
        }

        $data = ['group_id' => (int) $group_id];
        if (\DBUtil::field_exists('users', ['updated_at'])) {
            $data['updated_at'] = time();
        }

        \DB::update('users')->set($data)->where('id', '=', (int) $user['id'])->execute();
        $this->summary['users_assigned']++;
        \Log::warning('Seedsystemsgroup movio usuario a grupo Sistemas user_id='.(int) $user['id'].' previous_group_id='.(int) $user['group_id'].' new_group_id='.(int) $group_id);
    }

    protected function find_user($target)
    {
        $query = \DB::select('id', 'username', 'email', 'group_id')->from('users');
        if (ctype_digit((string) $target)) {
            return $query->where('id', '=', (int) $target)->execute()->current();
        }

        return $query
            ->where_open()
                ->where('username', '=', $target)
                ->or_where('email', '=', $target)
            ->where_close()
            ->execute()
            ->current();
    }

    protected function permission_row($area)
    {
        return \DB::select('id', 'area', 'permission', 'actions')
            ->from('users_permissions')
            ->where('area', '=', $area)
            ->where('permission', '=', 'access')
            ->execute()
            ->current();
    }

    protected function actions_from_row(array $row)
    {
        $actions = !empty($row['actions']) ? @unserialize($row['actions']) : [];
        return is_array($actions) ? $actions : [];
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
        \Cli::write('Seed grupo Sistemas terminado.');
        \Cli::write('group_id: '.$this->summary['group_id']);
        \Cli::write('group_created: '.$this->summary['group_created']);
        \Cli::write('group_existing: '.$this->summary['group_existing']);
        \Cli::write('permissions_created: '.$this->summary['permissions_created']);
        \Cli::write('permissions_updated: '.$this->summary['permissions_updated']);
        \Cli::write('permissions_assigned: '.$this->summary['permissions_assigned']);
        \Cli::write('permissions_existing: '.$this->summary['permissions_existing']);
        \Cli::write('users_assigned: '.$this->summary['users_assigned']);
        \Cli::write('users_skipped: '.$this->summary['users_skipped']);

        foreach ($this->summary['warnings'] as $warning) {
            \Cli::write('[WARN] '.$warning);
        }

        \Cli::write('No se asignaron permisos de super admin.');
        \Cli::write('No se conectaron eventos adicionales.');
    }
}
