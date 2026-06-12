<?php

namespace Fuel\Tasks;

/**
 * TAREA CONTRACTSSEED
 *
 * Siembra tipos base de contratos y permisos ORMAuth del modulo Contratos.
 *
 * Uso:
 * php oil refine contractsseed
 */
class Contractsseed
{
    protected $created = [];
    protected $updated = [];
    protected $warnings = [];

    protected $contract_types = [
        'service_agreement' => ['name' => 'Acuerdo de servicio', 'party_scope' => 'any'],
        'maintenance_contract' => ['name' => 'Contrato de mantenimiento', 'party_scope' => 'customer'],
        'rental_contract' => ['name' => 'Contrato de renta', 'party_scope' => 'customer'],
        'supplier_agreement' => ['name' => 'Contrato proveedor', 'party_scope' => 'supplier'],
        'distribution_agreement' => ['name' => 'Contrato de distribución', 'party_scope' => 'partner'],
        'employment_agreement' => ['name' => 'Contrato laboral', 'party_scope' => 'employee'],
        'confidentiality_agreement' => ['name' => 'Acuerdo de confidencialidad', 'party_scope' => 'any'],
    ];

    protected $permission_actions = [
        'view',
        'create',
        'edit',
        'status',
        'upload_document',
        'link',
        'view_sensitive',
    ];

    /**
     * RUN
     *
     * Ejecuta el seed idempotente. No asigna permisos a grupos ni usuarios.
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->assert_schema();
            $this->seed_contract_types();
            $this->seed_permissions();
            $this->print_summary();

            \Log::info('Contractsseed ejecutado. creados='.count($this->created).' actualizados='.count($this->updated).' advertencias='.count($this->warnings));
        } catch (\Exception $e) {
            \Log::error('Contractsseed: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach (['core_contract_types', 'users_permissions'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta migraciones antes de sembrar contratos.');
            }
        }
    }

    protected function seed_contract_types()
    {
        foreach ($this->contract_types as $code => $definition) {
            $row = \DB::select('id', 'name', 'party_scope')
                ->from('core_contract_types')
                ->where('code', '=', $code)
                ->execute()
                ->current();

            $data = [
                'code' => $code,
                'name' => $definition['name'],
                'party_scope' => $definition['party_scope'],
                'default_portal_code' => 'admin',
                'requires_party' => 1,
                'requires_end_date' => 0,
                'requires_approval' => 0,
                'active' => 1,
                'updated_at' => time(),
            ];

            if ($row) {
                \DB::update('core_contract_types')
                    ->set($data)
                    ->where('id', '=', (int) $row['id'])
                    ->execute();

                $this->updated[] = 'Tipo '.$code.' actualizado.';
                continue;
            }

            $data['created_at'] = time();
            \DB::insert('core_contract_types')->set($data)->execute();
            $this->created[] = 'Tipo '.$code.' creado.';
        }
    }

    protected function seed_permissions()
    {
        $row = $this->permission_row('contracts');
        if (!$row) {
            \DB::insert('users_permissions')->set([
                'area' => 'contracts',
                'permission' => 'access',
                'description' => 'Gestión de contratos',
                'actions' => serialize($this->auth_permission_actions($this->permission_actions)),
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();

            $this->created[] = 'Permiso contracts.access creado ['.implode(',', $this->permission_actions).'].';
            return;
        }

        $current = $this->actions_from_row($row);
        $merged = array_values(array_unique(array_merge($current, $this->permission_actions)));
        sort($merged);
        $current_sorted = $current;
        sort($current_sorted);

        if ($current_sorted === $merged) {
            $this->updated[] = 'Permiso contracts.access ya tenia acciones requeridas.';
            return;
        }

        \DB::update('users_permissions')
            ->set([
                'description' => 'Gestión de contratos',
                'actions' => serialize($this->auth_permission_actions($merged)),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $row['id'])
            ->execute();

        $this->updated[] = 'Permiso contracts.access actualizado ['.implode(',', $merged).'].';
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
        return is_array($actions) ? array_values($actions) : [];
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
        \Cli::write('Seed de contratos terminado.');
        \Cli::write('');

        \Cli::write('Creados: '.count($this->created));
        foreach ($this->created as $message) {
            \Cli::write(' - '.$message);
        }

        \Cli::write('Existentes/actualizados: '.count($this->updated));
        foreach ($this->updated as $message) {
            \Cli::write(' - '.$message);
        }

        if (!empty($this->warnings)) {
            \Cli::write('Advertencias: '.count($this->warnings));
            foreach ($this->warnings as $message) {
                \Cli::write(' - '.$message);
            }
        }

        \Cli::write('');
        \Cli::write('No se asignaron permisos a usuarios ni grupos. Asignalos desde Grupos y Permisos.');
    }
}
