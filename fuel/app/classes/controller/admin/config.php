<?php

/**
 * CONTROLADOR ADMIN_CONFIG
 *
 * Administra la configuracion general: empresa, departamentos, grupos y backends.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Config extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE CONFIGURACION
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('config.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA LA PANTALLA PRINCIPAL DE CONFIGURACION
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Configuracion';
        $this->template->content = View::forge('admin/config/index');
    }

    /**
     * DATA
     *
     * ENTREGA LA CONFIGURACION BASE EN FORMATO JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response([
                'company'     => $this->get_company(),
                'departments' => $this->get_departments(),
                'backends'    => $this->get_backends(),
                'groups'      => $this->get_groups(),
                'operations'   => $this->get_operations_settings(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando configuracion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar la configuracion.'], 500);
        }
    }

    /**
     * SAVE COMPANY
     *
     * GUARDA LOS DATOS GENERALES Y OPERATIVOS DE LA EMPRESA
     *
     * @access  public
     * @return  Response
     */
    public function post_save_company()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('config.access[edit]');

        # SE OBTIENE EL PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $name = trim((string) \Arr::get($val, 'name', ''));

            # VALIDACIONES MINIMAS
            if ($name === '') {
                return $this->json_response(['error' => 'El nombre comercial es obligatorio.'], 422);
            }

            $email = trim((string) \Arr::get($val, 'contact_email', ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json_response(['error' => 'El correo de contacto no es valido.'], 422);
            }

            # SE BUSCA O CREA LA EMPRESA BASE
            $company = Model_Core_Company::get_current();

            # SE ASIGNAN LOS DATOS AL MODELO
            $company->set([
                'name'                       => $name,
                'legal_name'                 => trim((string) \Arr::get($val, 'legal_name', '')),
                'rfc'                        => strtoupper(trim((string) \Arr::get($val, 'rfc', ''))),
                'postal_code'                => trim((string) \Arr::get($val, 'postal_code', '')),
                'tax_regime_id'              => $this->nullable_int(\Arr::get($val, 'tax_regime_id')),
                'contact_email'              => $email,
                'contact_phone'              => trim((string) \Arr::get($val, 'contact_phone', '')),
                'invoice_receive_days'       => $this->normalize_list(\Arr::get($val, 'invoice_receive_days', '')),
                'invoice_receive_limit_time' => trim((string) \Arr::get($val, 'invoice_receive_limit_time', '')),
                'payment_days'               => $this->normalize_list(\Arr::get($val, 'payment_days', '')),
                'payment_terms_days'         => $this->nullable_int(\Arr::get($val, 'payment_terms_days')),
                'payment_frequency'          => trim((string) \Arr::get($val, 'payment_frequency', '')),
                'payment_days_of_month'      => $this->normalize_list(\Arr::get($val, 'payment_days_of_month', '')),
                'announcement_message'       => trim((string) \Arr::get($val, 'announcement_message', '')),
                'blocked_reception'          => (int) (bool) \Arr::get($val, 'blocked_reception', false),
                'holidays'                   => $this->normalize_list(\Arr::get($val, 'holidays', '')),
                'policy_file'                => trim((string) \Arr::get($val, 'policy_file', '')),
                'active'                     => 1,
            ]);

            # SE GUARDA LA EMPRESA
            $company->save();

            # SE REGRESA LA EMPRESA ACTUALIZADA
            return $this->json_response(['status' => 'ok', 'company' => $this->get_company()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando empresa: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la empresa.'], 400);
        }
    }

    public function action_save_operations()
    {
        $this->require_access('config.access[edit]');
        $val = (array) \Input::json();

        try {
            $this->assert_schema_ready();
            $settings = [
                'allow_negative_inventory_sales' => (int) (bool) \Arr::get($val, 'allow_negative_inventory_sales', false),
            ];

            foreach ($settings as $key => $value) {
                $this->set_setting('operations', $key, (string) $value, 'bool');
            }

            return $this->json_response(['status' => 'ok', 'operations' => $this->get_operations_settings()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando configuracion operativa: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la configuracion operativa.'], 400);
        }
    }

    /**
     * SAVE DEPARTMENT
     *
     * CREA O ACTUALIZA UN DEPARTAMENTO
     *
     * @access  public
     * @return  Response
     */
    public function post_save_department()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('config.access[edit]');

        # SE OBTIENE EL PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $name = trim((string) \Arr::get($val, 'name', ''));

            # VALIDACIONES MINIMAS
            if ($name === '') {
                return $this->json_response(['error' => 'El nombre del departamento es obligatorio.'], 422);
            }

            # SE PREPARAN LOS DATOS DEL MODELO
            $data = [
                'name'        => $name,
                'slug'        => $this->slugify($name),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'active'      => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $department = Model_Core_Department::find($id);
                if (!$department) {
                    return $this->json_response(['error' => 'Departamento no encontrado.'], 404);
                }
                $department->set($data);
            } else {
                $department = Model_Core_Department::forge($data);
            }

            # SE GUARDA EL DEPARTAMENTO
            $department->save();

            # SE REGRESA LA LISTA ACTUALIZADA
            return $this->json_response(['status' => 'ok', 'departments' => $this->get_departments()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando departamento: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el departamento.'], 400);
        }
    }

    /**
     * SAVE BACKEND
     *
     * CREA O ACTUALIZA UN BACKEND OPERATIVO
     *
     * @access  public
     * @return  Response
     */
    public function post_save_backend()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('config.access[edit]');

        # SE OBTIENE EL PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $name = trim((string) \Arr::get($val, 'name', ''));
            $code = trim((string) \Arr::get($val, 'code', ''));

            # VALIDACIONES MINIMAS
            if ($name === '' || $code === '') {
                return $this->json_response(['error' => 'Codigo y nombre del backend son obligatorios.'], 422);
            }

            # SE PREPARAN LOS DATOS DEL MODELO
            $data = [
                'code'        => $this->slugify($code),
                'name'        => $name,
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'base_route'  => trim((string) \Arr::get($val, 'base_route', '')),
                'active'      => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $backend = Model_Core_Backend::find($id);
                if (!$backend) {
                    return $this->json_response(['error' => 'Backend no encontrado.'], 404);
                }
                $backend->set($data);
            } else {
                $backend = Model_Core_Backend::forge($data);
            }

            # SE GUARDA EL BACKEND
            $backend->save();

            # SE REGRESA LA LISTA ACTUALIZADA
            return $this->json_response(['status' => 'ok', 'backends' => $this->get_backends()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando backend: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el backend.'], 400);
        }
    }

    /**
     * GET COMPANY
     *
     * FORMATEA LA EMPRESA BASE PARA LA VISTA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_company()
    {
        # SE OBTIENE LA EMPRESA PRINCIPAL
        $company = Model_Core_Company::get_current();

        # SE REGRESAN LOS DATOS NORMALIZADOS
        return [
            'id'                         => (int) $company->id,
            'name'                       => (string) $company->name,
            'legal_name'                 => (string) $company->legal_name,
            'rfc'                        => (string) $company->rfc,
            'postal_code'                => (string) $company->postal_code,
            'tax_regime_id'              => $company->tax_regime_id ? (int) $company->tax_regime_id : null,
            'contact_email'              => (string) $company->contact_email,
            'contact_phone'              => (string) $company->contact_phone,
            'invoice_receive_days'       => (string) $company->invoice_receive_days,
            'invoice_receive_limit_time' => (string) $company->invoice_receive_limit_time,
            'payment_days'               => (string) $company->payment_days,
            'payment_terms_days'         => $company->payment_terms_days ? (int) $company->payment_terms_days : null,
            'payment_frequency'          => (string) $company->payment_frequency,
            'payment_days_of_month'      => (string) $company->payment_days_of_month,
            'announcement_message'       => (string) $company->announcement_message,
            'blocked_reception'          => (int) $company->blocked_reception,
            'holidays'                   => (string) $company->holidays,
            'policy_file'                => (string) $company->policy_file,
        ];
    }

    /**
     * GET DEPARTMENTS
     *
     * FORMATEA LOS DEPARTAMENTOS PARA LA VISTA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_departments()
    {
        # SE INICIALIZA EL ARREGLO DE RESPUESTA
        $departments = [];

        # SE RECORREN LOS DEPARTAMENTOS
        foreach (Model_Core_Department::list_for_admin() as $department) {
            $departments[] = [
                'id'          => (int) $department->id,
                'name'        => $department->name,
                'slug'        => $department->slug,
                'description' => $department->description,
                'active'      => (int) $department->active,
            ];
        }

        return $departments;
    }

    /**
     * GET BACKENDS
     *
     * FORMATEA LOS BACKENDS PARA LA VISTA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_backends()
    {
        # SE INICIALIZA EL ARREGLO DE RESPUESTA
        $backends = [];

        # SE RECORREN LOS BACKENDS
        foreach (Model_Core_Backend::list_for_admin() as $backend) {
            $backends[] = [
                'id'          => (int) $backend->id,
                'code'        => $backend->code,
                'name'        => $backend->name,
                'description' => $backend->description,
                'base_route'  => $backend->base_route,
                'active'      => (int) $backend->active,
            ];
        }

        return $backends;
    }

    /**
     * GET GROUPS
     *
     * OBTIENE LOS GRUPOS ORM AUTH
     *
     * @access  protected
     * @return  Array
     */
    protected function get_groups()
    {
        # SE CONSULTAN LOS GRUPOS DE ACCESO
        return \DB::select('id', 'name')
            ->from('users_groups')
            ->order_by('id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function get_operations_settings()
    {
        return [
            'allow_negative_inventory_sales' => (int) $this->get_setting('operations', 'allow_negative_inventory_sales', '0'),
        ];
    }

    protected function get_setting($group, $key, $default = '')
    {
        if (!\DBUtil::table_exists('core_settings')) {
            return $default;
        }
        $row = \DB::select('value')
            ->from('core_settings')
            ->where('setting_group', '=', (string) $group)
            ->where('setting_key', '=', (string) $key)
            ->execute()
            ->current();
        return $row ? (string) $row['value'] : $default;
    }

    protected function set_setting($group, $key, $value, $type = 'string')
    {
        $now = time();
        $row = \DB::select('id')
            ->from('core_settings')
            ->where('setting_group', '=', (string) $group)
            ->where('setting_key', '=', (string) $key)
            ->execute()
            ->current();

        if ($row) {
            \DB::update('core_settings')
                ->set(['value' => (string) $value, 'value_type' => (string) $type, 'updated_at' => $now])
                ->where('id', '=', (int) $row['id'])
                ->execute();
            return;
        }

        \DB::insert('core_settings')->set([
            'setting_group' => (string) $group,
            'setting_key' => (string) $key,
            'value' => (string) $value,
            'value_type' => (string) $type,
            'updated_at' => $now,
        ])->execute();
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS Y CAMPOS DE CONFIGURACION EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICAN LAS TABLAS REQUERIDAS
        foreach (['core_companies', 'core_departments', 'core_backends', 'core_settings'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de configuracion.');
            }
        }

        # SE VERIFICAN LOS CAMPOS DE LA MIGRACION 002
        if (!\DBUtil::field_exists('core_companies', ['invoice_receive_days', 'blocked_reception'])) {
            throw new \RuntimeException('Falta ejecutar la migracion 002 de configuracion de empresa.');
        }
    }

    /**
     * NORMALIZE LIST
     *
     * CONVIERTE ARREGLOS O TEXTO EN CADENA NORMALIZADA
     *
     * @access  protected
     * @return  String
     */
    protected function normalize_list($value)
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return trim((string) $value);
    }

    /**
     * NULLABLE INT
     *
     * NORMALIZA VALORES NUMERICOS OPCIONALES
     *
     * @access  protected
     * @return  Int|null
     */
    protected function nullable_int($value)
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * SLUGIFY
     *
     * GENERA CLAVES URL/INTERNAS A PARTIR DE TEXTO
     *
     * @access  protected
     * @return  String
     */
    protected function slugify($value)
    {
        # SE NORMALIZA EL VALOR RECIBIDO
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }
}
