<?php
namespace Fuel\Tasks;

/**
 * TAREA REPAIRAUTHPERMISSIONACTIONS
 *
 * Repara el formato de users_permissions.actions para que ORMAuth pueda
 * resolver acciones granulares con Auth::has_access('area.access[action]').
 *
 * Uso:
 * php oil refine repairauthpermissionactions
 */
class Repairauthpermissionactions
{
    protected $summary = [
        'scanned' => 0,
        'repaired' => 0,
        'skipped' => 0,
        'already_ok' => 0,
        'invalid' => 0,
        'cache_users_cleared' => 0,
        'cache_roles_cleared' => 0,
    ];

    /**
     * RUN
     *
     * Ejecuta reparacion idempotente del catalogo maestro de permisos.
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->assert_schema();
            $this->repair_permissions();
            $this->clear_permission_cache();
            $this->print_summary();

            \Log::info('Repairauthpermissionactions ejecutado. resumen='.json_encode($this->summary));
        } catch (\Exception $e) {
            \Log::error('Repairauthpermissionactions: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    /**
     * ASSERT SCHEMA
     *
     * Valida tablas ORMAuth requeridas.
     *
     * @return void
     */
    protected function assert_schema()
    {
        foreach (['users_permissions', 'users'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. No se puede reparar acciones ORMAuth.');
            }
        }
    }

    /**
     * REPAIR PERMISSIONS
     *
     * Convierte acciones numericas a mapa asociativo en users_permissions.
     *
     * @return void
     */
    protected function repair_permissions()
    {
        $rows = \DB::select('id', 'area', 'permission', 'actions')
            ->from('users_permissions')
            ->order_by('area', 'asc')
            ->order_by('permission', 'asc')
            ->execute();

        foreach ($rows as $row) {
            $this->summary['scanned']++;

            $raw = isset($row['actions']) ? (string) $row['actions'] : '';
            if (trim($raw) === '') {
                $this->summary['skipped']++;
                continue;
            }

            $actions = @unserialize($raw);
            if (!is_array($actions)) {
                $this->summary['invalid']++;
                \Log::warning('Repairauthpermissionactions: acciones invalidas en users_permissions id='.(int) $row['id'].' area='.$row['area'].'.'.$row['permission']);
                continue;
            }

            if (empty($actions)) {
                $this->summary['skipped']++;
                continue;
            }

            if ($this->is_action_map($actions)) {
                $this->summary['already_ok']++;
                continue;
            }

            $mapped = $this->to_action_map($actions);
            if (empty($mapped)) {
                $this->summary['invalid']++;
                continue;
            }

            \DB::update('users_permissions')
                ->set([
                    'actions' => serialize($mapped),
                    'updated_at' => time(),
                ])
                ->where('id', '=', (int) $row['id'])
                ->execute();

            $this->summary['repaired']++;
        }
    }

    /**
     * IS ACTION MAP
     *
     * ORMAuth espera que el catalogo maestro tenga acciones como llaves.
     *
     * @param array $actions
     * @return bool
     */
    protected function is_action_map(array $actions)
    {
        foreach ($actions as $key => $value) {
            if (!is_string($key) || $key === '' || (string) $key !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * TO ACTION MAP
     *
     * Normaliza acciones a ['view' => 'view'].
     *
     * @param array $actions
     * @return array
     */
    protected function to_action_map(array $actions)
    {
        $map = [];

        foreach ($actions as $key => $value) {
            $action = is_string($key) && !is_numeric($key) ? $key : $value;
            $action = trim((string) $action);
            if ($action === '') {
                continue;
            }

            $map[$action] = $action;
        }

        ksort($map);
        return $map;
    }

    /**
     * CLEAR PERMISSION CACHE
     *
     * Limpia cache ORMAuth de permisos por usuario y roles.
     *
     * @return void
     */
    protected function clear_permission_cache()
    {
        foreach (\DB::select('id')->from('users')->execute() as $user) {
            try {
                \Cache::delete('auth.permissions.user_'.(int) $user['id']);
                $this->summary['cache_users_cleared']++;
            } catch (\Exception $e) {
                // La cache puede no existir.
            }
        }

        try {
            \Cache::delete('auth.roles');
            $this->summary['cache_roles_cleared'] = 1;
        } catch (\Exception $e) {
            // La cache puede no existir.
        }
    }

    /**
     * PRINT SUMMARY
     *
     * Muestra resultado de la reparacion.
     *
     * @return void
     */
    protected function print_summary()
    {
        \Cli::write('Repair Auth Permission Actions terminado.');
        \Cli::write('');
        \Cli::write('scanned: '.$this->summary['scanned']);
        \Cli::write('repaired: '.$this->summary['repaired']);
        \Cli::write('skipped: '.$this->summary['skipped']);
        \Cli::write('already_ok: '.$this->summary['already_ok']);
        \Cli::write('invalid: '.$this->summary['invalid']);
        \Cli::write('cache_users_cleared: '.$this->summary['cache_users_cleared']);
        \Cli::write('cache_roles_cleared: '.$this->summary['cache_roles_cleared']);
        \Cli::write('');
        \Cli::write('No se modificaron usuarios, grupos ni asignaciones de permisos por grupo.');
    }
}
