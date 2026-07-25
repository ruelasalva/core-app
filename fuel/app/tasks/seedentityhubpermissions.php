<?php
namespace Fuel\Tasks;

class Seedentityhubpermissions
{
    protected $created = array();
    protected $updated = array();
    protected $assigned = array();
    protected $warnings = array();

    public function run()
    {
        try {
            $this->assert_schema();
            $permission_id = $this->ensure_permission();
            $this->assign_to_commercial_group($permission_id);
            $this->print_summary();
        } catch (\Exception $e) {
            \Log::error('Seedentityhubpermissions: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach (array('users_permissions', 'users_group_permissions') as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta migraciones ORMAuth antes de sembrar permisos.');
            }
        }
    }

    protected function ensure_permission()
    {
        $row = \DB::select('id', 'actions')
            ->from('users_permissions')
            ->where('area', '=', 'entityhub')
            ->where('permission', '=', 'access')
            ->execute()
            ->current();

        $actions = $this->auth_actions(array('view'));

        if (!$row) {
            list($id) = \DB::insert('users_permissions')->set(array(
                'area' => 'entityhub',
                'permission' => 'access',
                'description' => 'Acceso de lectura al Hub de Entidades',
                'actions' => serialize($actions),
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ))->execute();

            $this->created[] = 'entityhub.access[view]';
            return (int) $id;
        }

        $current = !empty($row['actions']) ? @unserialize($row['actions']) : array();
        $current = is_array($current) ? $current : array();
        if (!isset($current['view']) && !in_array('view', $current, true)) {
            $current['view'] = 'view';
            \DB::update('users_permissions')
                ->set(array('actions' => serialize($current), 'updated_at' => time()))
                ->where('id', '=', (int) $row['id'])
                ->execute();
            $this->updated[] = 'entityhub.access reparado con accion view.';
        } else {
            $this->updated[] = 'entityhub.access ya existia.';
        }

        return (int) $row['id'];
    }

    protected function assign_to_commercial_group($permission_id)
    {
        if (!\DBUtil::table_exists('users_groups')) {
            $this->warnings[] = 'No existe users_groups; no se asigno permiso a grupos.';
            return;
        }

        $group = \DB::select('id', 'name')
            ->from('users_groups')
            ->where('id', '=', 65)
            ->execute()
            ->current();

        if (!$group || (string) $group['name'] !== 'Gerente Comercial') {
            $this->warnings[] = 'Grupo 65 Gerente Comercial no encontrado; permiso creado sin asignacion comercial.';
            return;
        }

        $existing = \DB::select('id')
            ->from('users_group_permissions')
            ->where('group_id', '=', 65)
            ->where('perms_id', '=', $permission_id)
            ->execute()
            ->current();

        $actions = serialize(array('view'));
        if ($existing) {
            \DB::update('users_group_permissions')
                ->set(array('actions' => $actions))
                ->where('id', '=', (int) $existing['id'])
                ->execute();
            $this->updated[] = 'entityhub.access asignado previamente al grupo 65.';
            return;
        }

        \DB::insert('users_group_permissions')->set(array(
            'group_id' => 65,
            'perms_id' => $permission_id,
            'actions' => $actions,
        ))->execute();
        $this->assigned[] = 'entityhub.access[view] -> grupo 65 Gerente Comercial';
    }

    protected function auth_actions(array $actions)
    {
        $map = array();
        foreach ($actions as $action) {
            $map[$action] = $action;
        }
        return $map;
    }

    protected function print_summary()
    {
        \Cli::write('Seed Hub de Entidades permisos');
        foreach ($this->created as $item) {
            \Cli::write('[CREADO] '.$item);
        }
        foreach ($this->updated as $item) {
            \Cli::write('[OK] '.$item);
        }
        foreach ($this->assigned as $item) {
            \Cli::write('[ASIGNADO] '.$item);
        }
        foreach ($this->warnings as $item) {
            \Cli::write('[ADVERTENCIA] '.$item);
        }
    }
}
