<?php

/**
 * CONTROLADOR ADMIN_PORTALS
 *
 * Administra la base multiportal: perfiles, vinculos usuario-tercero y branding externo.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Portals extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE PORTALES
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('portals.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL BASE DE ACCESOS MULTIPORTAL
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE OBTIENE LA SECCION INICIAL SOLICITADA
        $initial_section = $this->current_section();
        $definitions = $this->get_definitions();

        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Portales - '.$definitions[$initial_section]['title'];
        $this->template->content = View::forge('admin/portals/index', [
            'initial_section' => $initial_section,
            'section_help' => $this->get_section_help(),
        ]);
    }

    /**
     * DATA
     *
     * ENTREGA DEFINICIONES, OPCIONES Y REGISTROS MULTIPORTAL EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'definitions' => $this->get_definitions(),
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando portales: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar portales.'], 500);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN REGISTRO MULTIPORTAL
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('portals.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $section = trim((string) \Arr::get($val, 'section', ''));
            $definitions = $this->get_definitions();
            if (!isset($definitions[$section])) {
                return $this->json_response(['error' => 'Seccion invalida.'], 422);
            }

            # SE PREPARAN DATOS
            $definition = $definitions[$section];
            $data = [];
            foreach ($definition['fields'] as $field) {
                $name = $field['name'];
                $type = \Arr::get($field, 'type', 'text');
                $value = \Arr::get($val, $name, \Arr::get($field, 'default', ''));

                if ($type === 'checkbox') {
                    $value = (int) (bool) $value;
                } elseif ($type === 'number') {
                    $value = (float) $value;
                } elseif ($type === 'integer') {
                    $value = (int) $value;
                } elseif ($type === 'color') {
                    $value = preg_match('/^#[0-9a-fA-F]{6}$/', trim((string) $value)) ? trim((string) $value) : \Arr::get($field, 'default', '#000000');
                } else {
                    $value = trim((string) $value);
                }

                $data[$name] = $value;
            }

            # VALIDACIONES MINIMAS
            foreach (\Arr::get($definition, 'required', []) as $required) {
                if (!isset($data[$required]) || $data[$required] === '') {
                    return $this->json_response(['error' => 'El campo '.$required.' es obligatorio.'], 422);
                }
            }

            # SE NORMALIZAN CAMPOS SENSIBLES
            foreach (['code', 'backend_code', 'portal_code', 'role_code'] as $key) {
                if (isset($data[$key])) {
                    $data[$key] = $this->codeify($data[$key]);
                }
            }

            if (isset($data['scope_json'])) {
                $data['scope_json'] = $this->normalize_json($data['scope_json']);
            }

            if (isset($data['custom_css'])) {
                $data['custom_css'] = $this->sanitize_css($data['custom_css']);
            }

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $class = $definition['model'];
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $item = $class::find($id);
                if (!$item) {
                    return $this->json_response(['error' => 'Registro no encontrado.'], 404);
                }
                $item->set($data);
            } else {
                $item = $class::forge($data);
            }

            # SE GUARDA EL REGISTRO
            $item->save();

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->json_response([
                'status' => 'ok',
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando portales: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el registro.'], 400);
        }
    }

    /**
     * GET DEFINITIONS
     *
     * DEFINE SECCIONES, MODELOS Y CAMPOS ADMINISTRABLES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_definitions()
    {
        return [
            'profiles' => [
                'title' => 'Perfiles de portal',
                'model' => 'Model_Core_Portal_Profile',
                'table' => 'core_portal_profiles',
                'required' => ['code', 'backend_code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo portal', 'type' => 'text', 'default' => ''],
                    ['name' => 'backend_code', 'label' => 'Backend', 'type' => 'select', 'options' => 'backends', 'default' => 'clientes'],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'description', 'label' => 'Descripcion', 'type' => 'text', 'default' => ''],
                    ['name' => 'login_route', 'label' => 'Ruta login', 'type' => 'text', 'default' => ''],
                    ['name' => 'dashboard_route', 'label' => 'Ruta dashboard', 'type' => 'text', 'default' => ''],
                    ['name' => 'requires_party', 'label' => 'Requiere tercero', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'allowed_party_types', 'label' => 'Tipos tercero permitidos', 'type' => 'text', 'default' => 'customer'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'user_links' => [
                'title' => 'Usuarios por portal',
                'model' => 'Model_Core_Party_User_Link',
                'table' => 'core_party_user_links',
                'required' => ['user_id', 'party_id', 'portal_code', 'role_code'],
                'fields' => [
                    ['name' => 'user_id', 'label' => 'Usuario', 'type' => 'select', 'options' => 'users', 'default' => 0],
                    ['name' => 'party_id', 'label' => 'Tercero', 'type' => 'select', 'options' => 'parties', 'default' => 0],
                    ['name' => 'portal_code', 'label' => 'Portal', 'type' => 'select', 'options' => 'portals', 'default' => 'clientes'],
                    ['name' => 'role_code', 'label' => 'Rol portal', 'type' => 'select_static', 'options' => [
                        ['value' => 'owner', 'label' => 'Responsable'],
                        ['value' => 'admin', 'label' => 'Administrador'],
                        ['value' => 'seller', 'label' => 'Vendedor'],
                        ['value' => 'billing', 'label' => 'Facturacion'],
                        ['value' => 'viewer', 'label' => 'Consulta'],
                    ], 'default' => 'viewer'],
                    ['name' => 'scope_json', 'label' => 'Scopes JSON', 'type' => 'textarea', 'default' => '{}'],
                    ['name' => 'can_manage_users', 'label' => 'Administra usuarios', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'branding' => [
                'title' => 'Branding por portal',
                'model' => 'Model_Core_Party_Branding',
                'table' => 'core_party_branding',
                'required' => ['party_id', 'portal_code'],
                'fields' => [
                    ['name' => 'party_id', 'label' => 'Tercero', 'type' => 'select', 'options' => 'parties', 'default' => 0],
                    ['name' => 'portal_code', 'label' => 'Portal', 'type' => 'select', 'options' => 'portals', 'default' => 'revendedores'],
                    ['name' => 'display_name', 'label' => 'Nombre visible', 'type' => 'text', 'default' => ''],
                    ['name' => 'logo_path', 'label' => 'Logo', 'type' => 'text', 'default' => ''],
                    ['name' => 'primary_color', 'label' => 'Color primario', 'type' => 'color', 'default' => '#0d6efd'],
                    ['name' => 'secondary_color', 'label' => 'Color secundario', 'type' => 'color', 'default' => '#343a40'],
                    ['name' => 'quote_footer', 'label' => 'Pie cotizacion', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'custom_css', 'label' => 'CSS controlado', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
        ];
    }

    /**
     * CURRENT SECTION
     *
     * NORMALIZA LA SECCION SOLICITADA POR URL
     *
     * @access  protected
     * @return  String
     */
    protected function current_section()
    {
        # SE VALIDA CONTRA DEFINICIONES PERMITIDAS
        $section = trim((string) \Input::get('section', 'user_links'));
        return isset($this->get_definitions()[$section]) ? $section : 'user_links';
    }

    /**
     * GET SECTION HELP
     *
     * EXPLICA EL USO OPERATIVO DE CADA SECCION DEL MODULO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_section_help()
    {
        return [
            'profiles' => 'Define que portales existen: clientes, socios, proveedores, revendedores u otros futuros.',
            'user_links' => 'Aqui se relaciona el usuario con un tercero y un portal. Sin este vinculo el usuario no puede entrar al portal externo.',
            'branding' => 'Configura la marca visible por tercero y portal, especialmente para revendedores que cotizan con su logo y colores.',
        ];
    }

    /**
     * GET ALL ITEMS
     *
     * OBTIENE REGISTROS DE TODAS LAS SECCIONES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_all_items()
    {
        $items = [];
        foreach ($this->get_definitions() as $key => $definition) {
            $class = $definition['model'];
            $items[$key] = [];
            foreach ($class::query()->order_by('id', 'desc')->get() as $row) {
                $items[$key][] = $row->to_array();
            }
        }

        return $items;
    }

    /**
     * GET OPTIONS
     *
     * OBTIENE OPCIONES PARA SELECTS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        return [
            'backends' => $this->select_options('core_backends', 'code', 'name'),
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'portals' => $this->select_options('core_portal_profiles', 'code', 'name'),
            'users' => $this->user_options(),
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASE MULTIPORTAL
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        return [
            'profiles' => (int) \DB::count_records('core_portal_profiles'),
            'user_links' => (int) \DB::count_records('core_party_user_links'),
            'branding' => (int) \DB::count_records('core_party_branding'),
        ];
    }

    /**
     * SELECT OPTIONS
     *
     * FORMATEA OPCIONES DE TABLA SIMPLE
     *
     * @access  protected
     * @return  Array
     */
    protected function select_options($table, $value_field, $label_field)
    {
        $rows = \DB::select($value_field, $label_field)
            ->from($table)
            ->where('active', '=', 1)
            ->order_by($label_field, 'asc')
            ->execute();

        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }

        return $options;
    }

    /**
     * USER OPTIONS
     *
     * FORMATEA USUARIOS ORM AUTH PARA SELECTS
     *
     * @access  protected
     * @return  Array
     */
    protected function user_options()
    {
        $rows = \DB::select('id', 'username', 'email')
            ->from('users')
            ->order_by('username', 'asc')
            ->execute();

        $options = [];
        foreach ($rows as $row) {
            $label = $row['username'].($row['email'] ? ' - '.$row['email'] : '');
            $options[] = ['value' => (string) $row['id'], 'label' => $label];
        }

        return $options;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS NECESARIAS EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        foreach ($this->get_definitions() as $definition) {
            if (!\DBUtil::table_exists($definition['table'])) {
                throw new \RuntimeException('Falta ejecutar migraciones de portales.');
            }
        }
    }

    /**
     * NORMALIZE JSON
     *
     * VALIDA JSON DE SCOPES O REGRESA OBJETO VACIO
     *
     * @access  protected
     * @return  String
     */
    protected function normalize_json($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '{}';
        }

        json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $value : '{}';
    }

    /**
     * SANITIZE CSS
     *
     * LIMPIA CSS CONTROLADO PARA BRANDING EXTERNO
     *
     * @access  protected
     * @return  String
     */
    protected function sanitize_css($value)
    {
        $value = preg_replace('/expression\s*\(|javascript\s*:|@import/i', '', (string) $value);
        return trim($value);
    }

    /**
     * CODEIFY
     *
     * NORMALIZA CODIGOS INTERNOS
     *
     * @access  protected
     * @return  String
     */
    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
