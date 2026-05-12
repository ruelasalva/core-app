<?php

/**
 * CONTROLADOR ADMIN_PERMISSIONS
 *
 * Administra grupos y permisos OrmAuth.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Permissions extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE PERMISOS
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('permissions.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA LA PANTALLA DE GRUPOS Y PERMISOS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Configuracion de Roles y Permisos';
        $this->template->content = View::forge('admin/permissions/index');
    }

    /**
     * DATA
     *
     * ENTREGA GRUPOS, PERMISOS Y RELACIONES EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE OBTIENEN LOS GRUPOS ORM AUTH
            $groups = \DB::select('id', 'name')
                ->from('users_groups')
                ->order_by('id', 'asc')
                ->execute()
                ->as_array();

            # SE OBTIENEN LOS PERMISOS BASE
            $raw_permissions = \DB::select('*')
                ->from('users_permissions')
                ->order_by('area', 'asc')
                ->order_by('permission', 'asc')
                ->execute()
                ->as_array();

            # SE FORMATEAN LOS PERMISOS POR ACCION
            $permissions = [];
            foreach ($raw_permissions as $p) {
                $actions = !empty($p['actions']) ? @unserialize($p['actions']) : [];

                if (is_array($actions) && !empty($actions)) {
                    foreach ($actions as $action) {
                        $permissions[] = [
                            'id'          => $p['id'].'_'.$action,
                            'real_id'     => (int) $p['id'],
                            'area'        => $p['area'],
                            'permission'  => $p['permission'].'['.$action.']',
                            'description' => $p['description'].' ['.$action.']',
                        ];
                    }
                    continue;
                }

                $permissions[] = [
                    'id'          => (string) $p['id'],
                    'real_id'     => (int) $p['id'],
                    'area'        => $p['area'],
                    'permission'  => $p['permission'],
                    'description' => $p['description'],
                ];
            }

            # SE OBTIENEN LAS RELACIONES GRUPO-PERMISO
            $raw_relations = \DB::select('group_id', 'perms_id', 'actions')
                ->from('users_group_permissions')
                ->execute()
                ->as_array();

            # SE FORMATEAN LAS RELACIONES POR ACCION
            $relations = [];
            foreach ($raw_relations as $relation) {
                $actions = !empty($relation['actions']) ? @unserialize($relation['actions']) : [];

                if (is_array($actions) && !empty($actions)) {
                    foreach ($actions as $action) {
                        $relations[] = [
                            'group_id' => (int) $relation['group_id'],
                            'perm_id'  => $relation['perms_id'].'_'.$action,
                        ];
                    }
                    continue;
                }

                $relations[] = [
                    'group_id' => (int) $relation['group_id'],
                    'perm_id'  => (string) $relation['perms_id'],
                ];
            }

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response([
                'groups'      => $groups,
                'permissions' => $permissions,
                'relations'   => $relations,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando permisos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar los permisos.'], 500);
        }
    }

    /**
     * SYNC
     *
     * SINCRONIZA LOS PERMISOS ASIGNADOS A UN GRUPO
     *
     * @access  public
     * @return  Response
     */
    public function post_sync()
    {
        # SE OBTIENE EL PAYLOAD JSON
        $val = (array) \Input::json();

        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('permissions.access[edit]');

        # SE INICIALIZAN VARIABLES
        $group_id = isset($val['group_id']) ? (int) $val['group_id'] : 0;
        $perm_ids = isset($val['perms']) && is_array($val['perms']) ? $val['perms'] : [];

        # VALIDACIONES MINIMAS
        if ($group_id < 1) {
            return $this->json_response(['error' => 'Grupo invalido.'], 422);
        }

        try {
            # SE AGRUPAN ACCIONES POR PERMISO REAL
            $grouped_permissions = [];
            foreach ($perm_ids as $p_id) {
                $parts = explode('_', (string) $p_id, 2);
                $real_id = (int) $parts[0];

                if ($real_id < 1) {
                    continue;
                }

                if (!isset($grouped_permissions[$real_id])) {
                    $grouped_permissions[$real_id] = [];
                }

                if (isset($parts[1]) && $parts[1] !== '') {
                    $grouped_permissions[$real_id][] = $parts[1];
                }
            }

            # SE LIMPIAN LOS PERMISOS ACTUALES DEL GRUPO
            \DB::delete('users_group_permissions')->where('group_id', '=', $group_id)->execute();

            # SE INSERTAN LOS PERMISOS NUEVOS
            foreach ($grouped_permissions as $real_id => $actions) {
                \DB::insert('users_group_permissions')->set([
                    'group_id' => $group_id,
                    'perms_id' => $real_id,
                    'actions'  => serialize(array_values(array_unique($actions))),
                ])->execute();
            }

            # SE LIMPIA CACHE DE PERMISOS
            $this->clear_group_permission_cache($group_id);

            # SE REGRESA RESPUESTA EXITOSA
            return $this->json_response(['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Error sincronizando permisos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron guardar los permisos.'], 400);
        }
    }

    /**
     * CLEAR GROUP PERMISSION CACHE
     *
     * LIMPIA EL CACHE DE PERMISOS DE LOS USUARIOS DEL GRUPO
     *
     * @access  protected
     * @return  Void
     */
    protected function clear_group_permission_cache($group_id)
    {
        # SE OBTIENEN LOS USUARIOS DEL GRUPO
        $users = \DB::select('id')
            ->from('users')
            ->where('group_id', '=', (int) $group_id)
            ->execute()
            ->as_array();

        # SE LIMPIA EL CACHE USUARIO POR USUARIO
        foreach ($users as $user) {
            try {
                \Cache::delete('auth.permissions.user_'.(int) $user['id']);
            } catch (\Exception $e) {
                # EL CACHE PUEDE NO EXISTIR TODAVIA
            }
        }
    }
}
