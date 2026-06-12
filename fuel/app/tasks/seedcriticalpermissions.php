<?php

namespace Fuel\Tasks;

/**
 * TAREA SEEDCRITICALPERMISSIONS
 *
 * Siembra permisos granulares para modulos criticos sin asignarlos a usuarios o grupos.
 *
 * Uso:
 * php oil refine seedcriticalpermissions
 */
class Seedcriticalpermissions
{
    protected $created = [];
    protected $updated = [];
    protected $unchanged = [];
    protected $warnings = [];

    protected $permission_definitions = [
        'sat' => [
            'description' => 'Acceso granular a SAT, descargas, validaciones, catalogos y credenciales',
            'actions' => ['download', 'validate', 'catalog_sync', 'credentials'],
        ],
        'cfdi' => [
            'description' => 'Acceso granular a CFDI, clasificacion, relaciones, conversiones y auditoria',
            'actions' => ['view', 'classify', 'link', 'convert_purchase', 'convert_sale', 'audit'],
        ],
        'billing' => [
            'description' => 'Acceso granular a facturacion CFDI, timbrado, cancelacion, REP y notas',
            'actions' => ['create', 'stamp', 'cancel', 'rep', 'credit_note'],
        ],
        'fiscal' => [
            'description' => 'Acceso granular a fiscal, libro, DIOT, IVA, cierre y exportacion',
            'actions' => ['view', 'ledger', 'diot', 'iva', 'closing', 'export'],
        ],
        'accounting' => [
            'description' => 'Acceso granular a contabilidad, catalogo, posteo, periodos, reportes y exportacion',
            'actions' => ['chart', 'post', 'periods', 'reports', 'export'],
        ],
    ];

    /**
     * RUN
     *
     * Ejecuta el seed idempotente.
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->assert_schema();
            $this->seed_permissions();
            $this->print_summary();

            \Log::info('Seedcriticalpermissions ejecutado. creados='.count($this->created).' actualizados='.count($this->updated).' sin_cambios='.count($this->unchanged).' advertencias='.count($this->warnings));
        } catch (\Exception $e) {
            \Log::error('Seedcriticalpermissions: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    /**
     * ASSERT SCHEMA
     *
     * Valida tablas ORMAuth necesarias.
     *
     * @return void
     */
    protected function assert_schema()
    {
        foreach (['users_permissions', 'users_group_permissions'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta migraciones de ORMAuth antes de sembrar permisos criticos.');
            }
        }
    }

    /**
     * SEED PERMISSIONS
     *
     * Crea o actualiza acciones granulares sin asignar a grupos.
     *
     * @return void
     */
    protected function seed_permissions()
    {
        foreach ($this->permission_definitions as $area => $definition) {
            $row = $this->permission_row($area);

            if (!$row) {
                \DB::insert('users_permissions')->set([
                    'area' => $area,
                    'permission' => 'access',
                    'description' => $definition['description'],
                    'actions' => serialize($this->auth_permission_actions($definition['actions'])),
                    'user_id' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ])->execute();

                $this->created[] = $area.'.access ['.implode(',', $definition['actions']).']';
                continue;
            }

            $current = $this->actions_from_row($row);
            $merged = $this->merge_actions($current, $definition['actions']);

            $current_sorted = $current;
            sort($current_sorted);
            $merged_sorted = $merged;
            sort($merged_sorted);

            if ($current_sorted === $merged_sorted) {
                $this->unchanged[] = $area.'.access ya tenia acciones requeridas.';
                continue;
            }

            \DB::update('users_permissions')
                ->set([
                    'description' => $definition['description'],
                    'actions' => serialize($this->auth_permission_actions($merged)),
                    'updated_at' => time(),
                ])
                ->where('id', '=', (int) $row['id'])
                ->execute();

            $added = array_values(array_diff($definition['actions'], $current));
            $this->updated[] = $area.'.access actualizado. Agregadas: '.implode(',', $added).'.';
        }
    }

    /**
     * PERMISSION ROW
     *
     * Obtiene una fila de users_permissions por area.
     *
     * @param string $area
     * @return array|null
     */
    protected function permission_row($area)
    {
        return \DB::select('id', 'area', 'permission', 'description', 'actions')
            ->from('users_permissions')
            ->where('area', '=', $area)
            ->where('permission', '=', 'access')
            ->execute()
            ->current();
    }

    /**
     * ACTIONS FROM ROW
     *
     * Decodifica acciones serializadas de ORMAuth.
     *
     * @param array $row
     * @return array
     */
    protected function actions_from_row(array $row)
    {
        $actions = !empty($row['actions']) ? @unserialize($row['actions']) : [];
        return is_array($actions) ? array_values($actions) : [];
    }

    /**
     * MERGE ACTIONS
     *
     * Conserva acciones existentes y agrega las granulares solicitadas.
     *
     * @param array $current
     * @param array $required
     * @return array
     */
    protected function merge_actions(array $current, array $required)
    {
        $merged = array_values(array_unique(array_merge($current, $required)));
        sort($merged);
        return $merged;
    }

    /**
     * AUTH PERMISSION ACTIONS
     *
     * ORMAuth cruza acciones por llave; users_permissions.actions debe ser mapa.
     *
     * @param array $actions
     * @return array
     */
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

    /**
     * PRINT SUMMARY
     *
     * Muestra resultado de la ejecucion.
     *
     * @return void
     */
    protected function print_summary()
    {
        \Cli::write('Seed de permisos criticos terminado.');
        \Cli::write('');

        \Cli::write('Permisos creados: '.count($this->created));
        foreach ($this->created as $message) {
            \Cli::write(' - '.$message);
        }

        \Cli::write('Permisos actualizados: '.count($this->updated));
        foreach ($this->updated as $message) {
            \Cli::write(' - '.$message);
        }

        \Cli::write('Sin cambios: '.count($this->unchanged));
        foreach ($this->unchanged as $message) {
            \Cli::write(' - '.$message);
        }

        if (!empty($this->warnings)) {
            \Cli::write('Advertencias: '.count($this->warnings));
            foreach ($this->warnings as $message) {
                \Cli::write(' - '.$message);
            }
        }

        \Cli::write('');
        \Cli::write('No se asignaron permisos a usuarios ni grupos.');
        \Cli::write('No se modifico logica SAT, CFDI, fiscal, contable ni de facturacion.');
    }
}
