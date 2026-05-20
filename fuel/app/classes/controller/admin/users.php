<?php

/**
 * CONTROLADOR ADMIN_USERS
 *
 * Administra usuarios OrmAuth y permisos especiales por usuario.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Users extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE USUARIOS
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('user.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA LA PANTALLA DE GESTION DE USUARIOS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Gestion de Usuarios';
        $this->template->content = View::forge('admin/users/index');
    }

    /**
     * GROUPS
     *
     * ENTREGA LOS GRUPOS ORM AUTH EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_groups()
    {
        try {
            # SE CONSULTAN LOS GRUPOS
            $groups = \DB::select('id', 'name')
                ->from('users_groups')
                ->execute()
                ->as_array();

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response($groups);
        } catch (\Exception $e) {
            \Log::error('Error en API Users Groups: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar los grupos.'], 500);
        }
    }

    /**
     * LIST
     *
     * ENTREGA LOS USUARIOS EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_list()
    {
        try {
            # SE CONSULTAN USUARIOS CON METADATA ORM AUTH
            $users = \Auth\Model\Auth_User::query()
                ->related('metadata')
                ->get();

            # SE INICIALIZA EL ARREGLO DE RESPUESTA
            $data = [];

            # SE FORMATEA CADA USUARIO
            foreach ($users as $u) {
                $data[] = [
                    'id'        => (int) $u->id,
                    'username'  => $u->username,
                    'email'     => $u->email,
                    'group_id'  => (int) $u->group_id,
                    'full_name' => isset($u->full_name) ? $u->full_name : '',
                ];
            }

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response($data);
        } catch (\Exception $e) {
            \Log::error('Error en API Users List: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar los usuarios.'], 500);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN USUARIO ORM AUTH
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        # SE OBTIENE EL PAYLOAD JSON
        $val = (array) \Input::json();
        $val['full_name'] = isset($val['full_name']) ? $val['full_name'] : '';

        # SE DETERMINA SI ES EDICION O ALTA
        $editing = !empty($val['id']);

        # VALIDAR PERMISO SEGUN ACCION
        $this->require_access($editing ? 'user.access[edit]' : 'user.access[create]');

        try {
            # SE VALIDAN LOS DATOS RECIBIDOS
            $this->validate_user_payload($val, !$editing);

            # SI ES EDICION, SE ACTUALIZA EL USUARIO EXISTENTE
            if ($editing) {
                $user = \Auth\Model\Auth_User::find((int) $val['id']);
                if (!$user) {
                    return $this->json_response(['error' => 'Usuario no encontrado.'], 404);
                }

                \Auth::update_user([
                    'email'     => $val['email'],
                    'group'     => (int) $val['group_id'],
                    'full_name' => trim((string) $val['full_name']),
                ], $user->username);

                return $this->json_response(['status' => 'updated']);
            }

            # SI ES ALTA, SE CREA EL USUARIO NUEVO
            $user_id = \Auth::create_user(
                trim((string) $val['username']),
                (string) $val['password'],
                trim((string) $val['email']),
                (int) $val['group_id'],
                ['full_name' => trim((string) $val['full_name'])]
            );

            # SE REGRESA EL ID CREADO
            return $this->json_response(['status' => 'created', 'id' => $user_id]);
        } catch (\InvalidArgumentException $e) {
            return $this->json_response(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            \Log::error('Error guardando usuario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el usuario.'], 400);
        }
    }

    /**
     * GET SPECIAL PERMS
     *
     * ENTREGA LOS PERMISOS ESPECIALES ASIGNADOS A UN USUARIO
     *
     * @access  public
     * @return  Response
     */
    public function action_get_special_perms($id)
    {
        # VALIDAR PERMISO PARA VER PERMISOS
        $this->require_access('permissions.access[view]');

        try {
            # SE OBTIENEN LOS PERMISOS DIRECTOS DEL USUARIO
            $assigned = \DB::select('perms_id', 'actions')
                ->from('users_user_permissions')
                ->where('user_id', '=', (int) $id)
                ->execute()
                ->as_array();

            # SE FORMATEAN LOS PERMISOS POR ACCION
            $formatted = [];
            foreach ($assigned as $a) {
                $actions = @unserialize($a['actions']);
                $action_name = is_array($actions) ? reset($actions) : '';
                $formatted[] = $a['perms_id'].($action_name ? '_'.$action_name : '');
            }

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response(['assigned' => $formatted]);
        } catch (\Exception $e) {
            \Log::error('Error cargando permisos especiales: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar los permisos.'], 500);
        }
    }

    /**
     * SAVE SPECIAL PERMS
     *
     * GUARDA PERMISOS ESPECIALES A NIVEL USUARIO
     *
     * @access  public
     * @return  Response
     */
    public function post_save_special_perms()
    {
        # VALIDAR PERMISO PARA EDITAR PERMISOS
        $this->require_access('permissions.access[edit]');

        # SE OBTIENE EL PAYLOAD JSON
        $val = (array) \Input::json();

        # SE INICIALIZAN VARIABLES
        $user_id = isset($val['user_id']) ? (int) $val['user_id'] : 0;
        $perms = isset($val['perms']) && is_array($val['perms']) ? $val['perms'] : [];

        # VALIDACIONES MINIMAS
        if ($user_id < 1) {
            return $this->json_response(['error' => 'Usuario invalido.'], 422);
        }

        try {
            # SE LIMPIAN LOS PERMISOS ESPECIALES ACTUALES
            \DB::delete('users_user_permissions')->where('user_id', '=', $user_id)->execute();

            # SE INSERTAN LOS NUEVOS PERMISOS
            foreach ($perms as $p_id) {
                $parts = explode('_', (string) $p_id, 2);
                $real_id = (int) $parts[0];
                $action = isset($parts[1]) ? $parts[1] : '';

                if ($real_id < 1) {
                    continue;
                }

                \DB::insert('users_user_permissions')->set([
                    'user_id'  => $user_id,
                    'perms_id' => $real_id,
                    'actions'  => serialize((array) $action),
                ])->execute();
            }

            # SE REGRESA RESPUESTA EXITOSA
            return $this->json_response(['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Error guardando permisos especiales: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron guardar los permisos.'], 400);
        }
    }

    public function action_dashboards()
    {
        $this->require_access('user.access[view]');

        try {
            return $this->json_response([
                'dashboards' => $this->dashboard_options(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando dashboards de usuario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar dashboards.'], 500);
        }
    }

    public function action_user_dashboards($id)
    {
        $this->require_access('user.access[view]');

        try {
            $assigned = [];
            if (\DBUtil::table_exists('core_dashboard_user_assignments')) {
                $rows = \DB::select('dashboard_id')
                    ->from('core_dashboard_user_assignments')
                    ->where('user_id', '=', (int) $id)
                    ->where('active', '=', 1)
                    ->execute();
                foreach ($rows as $row) {
                    $assigned[] = (int) $row['dashboard_id'];
                }
            }
            return $this->json_response(['assigned' => $assigned]);
        } catch (\Exception $e) {
            \Log::error('Error cargando dashboards asignados: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar dashboards asignados.'], 500);
        }
    }

    public function post_save_dashboards()
    {
        $this->require_access('user.access[edit]');
        $val = (array) \Input::json();
        $user_id = (int) \Arr::get($val, 'user_id', 0);
        $dashboard_ids = array_map('intval', (array) \Arr::get($val, 'dashboard_ids', []));

        if ($user_id < 1) {
            return $this->json_response(['error' => 'Usuario invalido.'], 422);
        }

        try {
            \DB::delete('core_dashboard_user_assignments')->where('user_id', '=', $user_id)->execute();
            $now = time();
            $first = true;
            foreach (array_unique($dashboard_ids) as $dashboard_id) {
                if ($dashboard_id < 1) {
                    continue;
                }
                \DB::insert('core_dashboard_user_assignments')->set([
                    'dashboard_id' => $dashboard_id,
                    'user_id' => $user_id,
                    'is_default' => $first ? 1 : 0,
                    'active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
                $first = false;
            }
            return $this->json_response(['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Error guardando dashboards asignados: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron guardar dashboards.'], 400);
        }
    }

    /**
     * VALIDATE USER PAYLOAD
     *
     * VALIDA LOS CAMPOS MINIMOS PARA CREAR O EDITAR USUARIOS
     *
     * @access  protected
     * @return  Void
     */
    protected function validate_user_payload(array $val, $require_password)
    {
        # VALIDAR USUARIO
        if (empty($val['username'])) {
            throw new \InvalidArgumentException('El usuario es obligatorio.');
        }

        # VALIDAR EMAIL
        if (empty($val['email']) || !filter_var($val['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El email no es valido.');
        }

        # VALIDAR GRUPO
        if (empty($val['group_id']) || (int) $val['group_id'] < 1) {
            throw new \InvalidArgumentException('Selecciona un grupo valido.');
        }

        # VALIDAR PASSWORD EN ALTA
        if ($require_password && (empty($val['password']) || strlen((string) $val['password']) < 10)) {
            throw new \InvalidArgumentException('La contrasena debe tener al menos 10 caracteres.');
        }
    }

    protected function dashboard_options()
    {
        if (!\DBUtil::table_exists('core_dashboards')) {
            return [];
        }
        return \DB::select('id', 'code', 'name', 'description', 'dashboard_type')
            ->from('core_dashboards')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->execute()
            ->as_array();
    }
}
