<?php
namespace Fuel\Tasks;

class Install
{
    public function run()
    {
        \Package::load('orm');
        \Package::load('auth');

        try {
            // 1. LIMPIEZA RADICAL
            // Desactivamos llaves foráneas para poder limpiar sin errores de relación
            \DB::query('SET FOREIGN_KEY_CHECKS = 0')->execute();
            
            $tables = [
                'users_groups',
                'users',
                'users_permissions',
                'users_metadata',
                'users_group_permissions',
                'users_group_roles',
                'users_role_permissions',
                'users_user_permissions',
            ];
            foreach ($tables as $table) {
                \DB::query("TRUNCATE TABLE `$table`")->execute();
                \DB::query("ALTER TABLE `$table` AUTO_INCREMENT = 1")->execute();
            }
            
            \DB::query('SET FOREIGN_KEY_CHECKS = 1')->execute();
            echo "--- Tablas limpias y secuencias reseteadas --- \n";

            // 2. CREAR GRUPOS CON 'REPLACE'
            // Usamos REPLACE en lugar de INSERT para evitar el error de Duplicate Entry '1'
            $groups = [
                5   => 'Consulta',
                15  => 'Portal Externo',
                25  => 'Operador',
                40  => 'Supervisor',
                50  => 'Gerente',
                60  => 'Administrador de Ventas',
                70  => 'Administrador de Compras',
                80  => 'Administrador de Finanzas',
                90  => 'Administrador de Configuracion',
                100 => 'Administrador General',
            ];

            foreach ($groups as $id => $name) {
                // Query cruda para asegurar que MySQL respete el ID manual
                $sql = "REPLACE INTO `users_groups` (`id`, `name`, `user_id`, `created_at`) 
                        VALUES ($id, '$name', 0, " . time() . ")";
                \DB::query($sql)->execute();
                echo "Grupo forzado: [$id] $name \n";
            }

            // 3. PERMISOS (ORM)
            $actions = ['view', 'create', 'edit', 'delete', 'import', 'export'];
            $core_permissions = [
                'admin_dashboard' => 'Panel de control principal',
                'user'            => 'Gestion de usuarios',
                'permissions'     => 'Gestion de roles y permisos',
                'config'          => 'Configuracion del sistema',
                'web'             => 'Gestion web, integraciones y privacidad',
                'legal'           => 'Gestion legal, consentimientos y cookies',
                'communications'  => 'Gestion de correos, eventos y notificaciones',
                'sat'             => 'Gestion SAT, CFDI y sincronizacion fiscal',
                'catalogs'        => 'Gestion de catalogos base del ERP',
                'commerce'        => 'Gestion comercial, marcas, categorias y productos',
                'frontend'        => 'Gestion de paginas, banners, menus y frontend administrable',
            ];

            foreach ($core_permissions as $area => $desc) {
                $p = \Auth\Model\Auth_Permission::forge([
                    'area'        => $area,
                    'permission'  => 'access',
                    'description' => $desc,
                    'actions'     => $actions,
                    'user_id'     => 0,
                ]);
                $p->save();
            }

            // 4. USUARIO ADMINISTRADOR
            $admin_password = getenv('COREAPP_ADMIN_PASSWORD');
            if (!$admin_password) {
                $admin_password = bin2hex(random_bytes(8));
                echo "\n Password temporal admin: {$admin_password} \n";
            }

            if (strlen($admin_password) < 12) {
                throw new \Exception('COREAPP_ADMIN_PASSWORD debe tener al menos 12 caracteres.');
            }

            \Auth::create_user(
                'admin',
                $admin_password,
                'admin@coreapp.local',
                100, 
                ['full_name' => 'System Root', 'dept_id' => 1]
            );

            echo "\n [SUCCESS] Instalación limpia. IDs verificados. \n";

        } catch (\Exception $e) {
            echo "\n [ERROR] " . $e->getMessage() . "\n";
            \Log::error("Fallo en instalación: " . $e->getMessage());
        }
    }
}
