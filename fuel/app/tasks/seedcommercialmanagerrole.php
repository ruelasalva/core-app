<?php
namespace Fuel\Tasks;

/**
 * TAREA SEEDCOMMERCIALMANAGERROLE
 *
 * Prepara el grupo Gerente Comercial y sus permisos ORMAuth sin crear usuarios.
 *
 * Uso:
 * php oil refine seedcommercialmanagerrole
 */
class Seedcommercialmanagerrole
{
    const GROUP_ID = 65;
    const GROUP_NAME = 'Gerente Comercial';

    protected $created = [];
    protected $updated = [];
    protected $assigned = [];
    protected $warnings = [];

    protected $permission_definitions = [
        'web_conversion' => [
            'description' => 'Acceso a configuracion de conversion web',
            'actions' => ['view', 'edit'],
        ],
        'supplierimport' => [
            'description' => 'Acceso a importacion de proveedores',
            'actions' => ['view', 'edit'],
        ],
        'customers' => [
            'description' => 'Acceso comercial a clientes',
            'actions' => ['view', 'edit'],
        ],
    ];

    protected $assignments = [
        'admin_dashboard' => ['view'],
        'frontend' => ['view', 'edit'],
        'web_conversion' => ['view', 'edit'],
        'commerce' => ['view', 'edit'],
        'supplierimport' => ['view', 'edit'],
        'crm' => ['view', 'create', 'edit', 'import'],
        'sales' => ['view', 'create', 'edit'],
        'customers' => ['view', 'edit'],
        'commissions' => ['view'],
        'help' => ['view'],
    ];

    /**
     * RUN
     *
     * Crea/repara permisos comerciales y asigna el rol al grupo 65.
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->assert_schema();
            $this->ensure_group();
            $this->ensure_missing_permissions();
            $this->repair_crm_permission_actions();
            $this->assign_group_permissions();
            $this->print_summary();

            \Log::info('Seedcommercialmanagerrole ejecutado. creados='.count($this->created).' actualizados='.count($this->updated).' asignados='.count($this->assigned).' advertencias='.count($this->warnings));
        } catch (\Exception $e) {
            \Log::error('Seedcommercialmanagerrole: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach (['users_groups', 'users_permissions', 'users_group_permissions'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta migraciones de ORMAuth antes de sembrar el rol comercial.');
            }
        }
    }

    protected function ensure_group()
    {
        $row = \DB::select('id', 'name')
            ->from('users_groups')
            ->where('id', '=', self::GROUP_ID)
            ->execute()
            ->current();

        if ($row) {
            if ((string) $row['name'] !== self::GROUP_NAME) {
                throw new \RuntimeException('El grupo '.self::GROUP_ID.' ya existe con nombre "'.$row['name'].'". No se asignaron permisos para evitar afectar un grupo existente.');
            }

            $this->updated[] = 'Grupo '.self::GROUP_ID.' - '.self::GROUP_NAME.' ya existia.';
            return;
        }

        \DB::insert('users_groups')->set([
            'id' => self::GROUP_ID,
            'name' => self::GROUP_NAME,
            'user_id' => 0,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        $this->created[] = 'Grupo '.self::GROUP_ID.' - '.self::GROUP_NAME;
    }

    protected function ensure_missing_permissions()
    {
        foreach ($this->permission_definitions as $area => $definition) {
            $permission = $this->permission_row($area);
            if ($permission) {
                $this->updated[] = 'Permiso '.$area.'.access ya existia.';
                continue;
            }

            \DB::insert('users_permissions')->set([
                'area' => $area,
                'permission' => 'access',
                'description' => $definition['description'],
                'actions' => serialize($this->auth_permission_actions($definition['actions'])),
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();

            $this->created[] = 'Permiso '.$area.'.access ['.implode(',', $definition['actions']).']';
        }
    }

    protected function repair_crm_permission_actions()
    {
        $permission = $this->permission_row('crm');
        $required = ['view', 'create', 'edit', 'import', 'export'];

        if (!$permission) {
            \DB::insert('users_permissions')->set([
                'area' => 'crm',
                'permission' => 'access',
                'description' => 'Acceso al modulo CRM comercial',
                'actions' => serialize($this->auth_permission_actions($required)),
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();

            $this->created[] = 'Permiso crm.access ['.implode(',', $required).']';
            return;
        }

        $current = $this->actions_from_row($permission);
        $merged = array_values(array_unique(array_merge($current, $required)));
        sort($current);
        $sorted_merged = $merged;
        sort($sorted_merged);

        if ($current === $sorted_merged) {
            $this->updated[] = 'Permiso crm.access ya tenia acciones requeridas.';
            return;
        }

        \DB::update('users_permissions')
            ->set([
                'actions' => serialize($this->auth_permission_actions($merged)),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $permission['id'])
            ->execute();

        $this->updated[] = 'Permiso crm.access reparado con acciones '.implode(',', $required).'.';
    }

    protected function assign_group_permissions()
    {
        $permission_ids = [];
        foreach ($this->assignments as $area => $actions) {
            $permission = $this->permission_row($area);
            if (!$permission) {
                $this->warnings[] = 'No se encontro '.$area.'.access; no se asigno al grupo.';
                continue;
            }

            $permission_ids[(int) $permission['id']] = [
                'area' => $area,
                'actions' => $actions,
            ];
        }

        \DB::delete('users_group_permissions')
            ->where('group_id', '=', self::GROUP_ID)
            ->execute();

        foreach ($permission_ids as $permission_id => $definition) {
            \DB::insert('users_group_permissions')->set([
                'group_id' => self::GROUP_ID,
                'perms_id' => $permission_id,
                'actions' => serialize($definition['actions']),
            ])->execute();

            $this->assigned[] = $definition['area'].'.access ['.implode(',', $definition['actions']).']';
        }
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
        \Cli::write('Seed Gerente Comercial terminado.');
        \Cli::write('');

        \Cli::write('Creados: '.count($this->created));
        foreach ($this->created as $message) {
            \Cli::write(' - '.$message);
        }

        \Cli::write('Existentes/actualizados: '.count($this->updated));
        foreach ($this->updated as $message) {
            \Cli::write(' - '.$message);
        }

        \Cli::write('Permisos asignados al grupo '.self::GROUP_ID.': '.count($this->assigned));
        foreach ($this->assigned as $message) {
            \Cli::write(' - '.$message);
        }

        if (!empty($this->warnings)) {
            \Cli::write('Advertencias: '.count($this->warnings));
            foreach ($this->warnings as $message) {
                \Cli::write(' - '.$message);
            }
        }

        \Cli::write('');
        \Cli::write('No se crearon usuarios. No se asignaron permisos SAT, fiscal, contables, financieros, de configuracion, usuarios, permisos ni integraciones tecnicas.');
    }
}
