<?php
namespace Fuel\Tasks;

/**
 * TAREA SEEDBUSINESSSUITEPERMISSIONS
 *
 * Crea/repara el permiso read-only de Administracion Comercial.
 *
 * Uso:
 * FUEL_ENV=development php oil refine seedbusinesssuitepermissions
 */
class Seedbusinesssuitepermissions
{
    const COMMERCIAL_MANAGER_GROUP = 65;
    const COMMERCIAL_MANAGER_NAME = 'Gerente Comercial';

    protected $created = [];
    protected $updated = [];
    protected $assigned = [];
    protected $warnings = [];

    public function run()
    {
        try {
            $this->assert_schema();
            $permission_id = $this->ensure_permission();
            $this->assign_commercial_manager($permission_id);
            $this->print_summary();
            \Log::info('Seedbusinesssuitepermissions ejecutado.');
        } catch (\Exception $e) {
            \Log::error('Seedbusinesssuitepermissions: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach (['users_permissions', 'users_groups', 'users_group_permissions'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta migraciones de ORMAuth antes de sembrar permisos.');
            }
        }
    }

    protected function ensure_permission()
    {
        $row = \DB::select('id', 'actions')
            ->from('users_permissions')
            ->where('area', '=', 'business')
            ->where('permission', '=', 'access')
            ->execute()
            ->current();

        $actions = $this->auth_actions(['view']);
        if ($row) {
            $current = $this->actions_from_row($row);
            if (!in_array('view', $current, true)) {
                $current[] = 'view';
                \DB::update('users_permissions')
                    ->set([
                        'actions' => serialize($this->auth_actions($current)),
                        'updated_at' => time(),
                    ])
                    ->where('id', '=', (int) $row['id'])
                    ->execute();
                $this->updated[] = 'Permiso business.access reparado con accion view.';
            } else {
                $this->updated[] = 'Permiso business.access ya existia.';
            }
            return (int) $row['id'];
        }

        list($id) = \DB::insert('users_permissions')->set([
            'area' => 'business',
            'permission' => 'access',
            'description' => 'Acceso read-only a Administracion Comercial',
            'actions' => serialize($actions),
            'user_id' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        $this->created[] = 'Permiso business.access [view]';
        return (int) $id;
    }

    protected function assign_commercial_manager($permission_id)
    {
        $group = \DB::select('id', 'name')
            ->from('users_groups')
            ->where('id', '=', self::COMMERCIAL_MANAGER_GROUP)
            ->execute()
            ->current();

        if (!$group) {
            $this->warnings[] = 'Grupo 65 Gerente Comercial no existe; no se asigno business.access.';
            return;
        }
        if ((string) $group['name'] !== self::COMMERCIAL_MANAGER_NAME) {
            $this->warnings[] = 'Grupo 65 existe con otro nombre; no se asigno business.access para evitar afectar permisos.';
            return;
        }

        $existing = \DB::select('id')
            ->from('users_group_permissions')
            ->where('group_id', '=', self::COMMERCIAL_MANAGER_GROUP)
            ->where('perms_id', '=', (int) $permission_id)
            ->execute()
            ->current();

        $data = [
            'group_id' => self::COMMERCIAL_MANAGER_GROUP,
            'perms_id' => (int) $permission_id,
            'actions' => serialize(['view']),
        ];

        if ($existing) {
            \DB::update('users_group_permissions')
                ->set(['actions' => $data['actions']])
                ->where('id', '=', (int) $existing['id'])
                ->execute();
            $this->updated[] = 'business.access[view] actualizado para Gerente Comercial.';
            return;
        }

        \DB::insert('users_group_permissions')->set($data)->execute();
        $this->assigned[] = 'business.access[view] asignado a Gerente Comercial.';
    }

    protected function actions_from_row(array $row)
    {
        $actions = !empty($row['actions']) ? @unserialize($row['actions']) : [];
        if (!is_array($actions)) {
            return [];
        }

        return array_values($actions);
    }

    protected function auth_actions(array $actions)
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
        \Cli::write('Seed Administracion Comercial permisos terminado.');
        \Cli::write('Creados: '.count($this->created));
        foreach ($this->created as $message) {
            \Cli::write(' - '.$message);
        }
        \Cli::write('Actualizados/existentes: '.count($this->updated));
        foreach ($this->updated as $message) {
            \Cli::write(' - '.$message);
        }
        \Cli::write('Asignados: '.count($this->assigned));
        foreach ($this->assigned as $message) {
            \Cli::write(' - '.$message);
        }
        if (!empty($this->warnings)) {
            \Cli::write('Advertencias: '.count($this->warnings));
            foreach ($this->warnings as $message) {
                \Cli::write(' - '.$message);
            }
        }
        \Cli::write('No se crearon usuarios. No se asignaron permisos SAT, fiscal, contabilidad ni finanzas.');
    }
}
