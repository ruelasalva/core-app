<?php

/**
 * CONTROLADOR ADMIN_SAT
 *
 * Administra configuracion, credenciales y tablero base SAT.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Sat extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA SAT
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('sat.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL PRINCIPAL SAT
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'SAT';
        $this->template->content = View::forge('admin/sat/index');
    }

    /**
     * CATALOGS
     *
     * MUESTRA LA PANTALLA DE CATALOGOS SAT
     *
     * @access  public
     * @return  Void
     */
    public function action_catalogs()
    {
        # SE CARGA LA VISTA DE CATALOGOS FISCALES
        $this->template->title = 'Catalogos SAT';
        $this->template->content = View::forge('admin/sat/catalogs');
    }

    /**
     * DATA
     *
     * ENTREGA CONFIGURACION, CREDENCIALES Y ESTADISTICAS SAT EN JSON
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
                'config' => $this->get_config(),
                'credentials' => $this->get_credentials(),
                'stats' => $this->get_stats(),
                'requests' => $this->get_recent_requests(),
                'cfdi_alerts' => $this->get_cfdi_alerts(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar SAT.'], 500);
        }
    }

    /**
     * CATALOGS DATA
     *
     * ENTREGA DEFINICIONES Y REGISTROS DE CATALOGOS SAT EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_catalogs_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_catalog_schema_ready();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'definitions' => $this->get_catalog_definitions(),
                'items' => $this->get_catalog_items(),
                'stats' => $this->get_catalog_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando catalogos SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar los catalogos SAT.'], 500);
        }
    }

    /**
     * SAVE CATALOG
     *
     * CREA O ACTUALIZA UN REGISTRO DE CATALOGO SAT
     *
     * @access  public
     * @return  Response
     */
    public function post_save_catalog()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('sat.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_catalog_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $catalog = trim((string) \Arr::get($val, 'catalog', ''));
            $definitions = $this->get_catalog_definitions();

            # VALIDAR CATALOGO
            if (!isset($definitions[$catalog])) {
                return $this->json_response(['error' => 'Catalogo SAT invalido.'], 422);
            }

            # SE PREPARAN DATOS
            $definition = $definitions[$catalog];
            $data = [];
            foreach ($definition['fields'] as $field) {
                $name = $field['name'];
                $type = \Arr::get($field, 'type', 'text');
                $value = \Arr::get($val, $name, \Arr::get($field, 'default', ''));

                if ($type === 'checkbox') {
                    $value = (int) (bool) $value;
                } elseif ($type === 'number') {
                    $value = (float) $value;
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

            if (isset($data['code'])) {
                $data['code'] = strtoupper(trim($data['code']));
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
                'items' => $this->get_catalog_items(),
                'stats' => $this->get_catalog_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando catalogo SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el registro SAT.'], 400);
        }
    }

    /**
     * SAVE CONFIG
     *
     * GUARDA LA CONFIGURACION GENERAL SAT
     *
     * @access  public
     * @return  Response
     */
    public function post_save_config()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('sat.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE BUSCA O CREA CONFIGURACION
            $config = Model_Core_Sat_Config::get_current();

            # SE ASIGNAN DATOS
            $config->set([
                'mode' => trim((string) \Arr::get($val, 'mode', 'test')) === 'production' ? 'production' : 'test',
                'enabled' => (int) (bool) \Arr::get($val, 'enabled', false),
                'storage_path' => trim((string) \Arr::get($val, 'storage_path', 'fuel/app/storage/sat')),
            ]);
            $config->save();

            # SE REGRESA CONFIGURACION ACTUALIZADA
            return $this->json_response(['status' => 'ok', 'config' => $this->get_config()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando SAT config: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la configuracion SAT.'], 400);
        }
    }

    /**
     * SAVE CREDENTIAL
     *
     * CREA O ACTUALIZA UNA CREDENCIAL SAT
     *
     * @access  public
     * @return  Response
     */
    public function post_save_credential()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('sat.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $type = trim((string) \Arr::get($val, 'credential_type', 'fiel'));
            $rfc = strtoupper(trim((string) \Arr::get($val, 'rfc', '')));

            # VALIDACIONES MINIMAS
            if (!in_array($type, ['fiel', 'csd'])) {
                return $this->json_response(['error' => 'Tipo de credencial invalido.'], 422);
            }

            if ($rfc === '') {
                return $this->json_response(['error' => 'RFC es obligatorio.'], 422);
            }

            # SE PREPARAN DATOS
            $data = [
                'credential_type' => $type,
                'rfc' => $rfc,
                'cer_path' => trim((string) \Arr::get($val, 'cer_path', '')),
                'key_path' => trim((string) \Arr::get($val, 'key_path', '')),
                'valid_from' => trim((string) \Arr::get($val, 'valid_from', '')) ?: null,
                'valid_until' => trim((string) \Arr::get($val, 'valid_until', '')) ?: null,
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $credential = Model_Core_Sat_Credential::find($id);
                if (!$credential) {
                    return $this->json_response(['error' => 'Credencial no encontrada.'], 404);
                }
                $credential->set($data);
            } else {
                $credential = Model_Core_Sat_Credential::forge($data);
            }

            # SE CIFRA PASSWORD SI SE CAPTURO UNO NUEVO
            $password = trim((string) \Arr::get($val, 'password', ''));
            if ($password !== '') {
                $credential->password_encrypted = \Crypt::encode($password);
            }

            # SE GUARDA CREDENCIAL
            $credential->save();

            # SE REGRESA LISTADO ACTUALIZADO
            return $this->json_response(['status' => 'ok', 'credentials' => $this->get_credentials()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando credencial SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la credencial SAT.'], 400);
        }
    }

    /**
     * SAVE REQUEST
     *
     * REGISTRA UNA SOLICITUD SAT PENDIENTE PARA XML O METADATA
     *
     * @access  public
     * @return  Response
     */
    public function action_save_request()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('sat.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE NORMALIZAN CAMPOS
            $download_type = trim((string) \Arr::get($val, 'download_type', 'xml')) === 'metadata' ? 'metadata' : 'xml';
            $direction = trim((string) \Arr::get($val, 'direction', 'received')) === 'issued' ? 'issued' : 'received';
            $date_from = trim((string) \Arr::get($val, 'date_from', date('Y-m-d')));
            $date_to = trim((string) \Arr::get($val, 'date_to', date('Y-m-d')));

            # SE CREA SOLICITUD LOCAL; EL TASK FUTURO HARA LA LLAMADA REAL AL SAT
            $request = Model_Core_Sat_Sync_Request::forge([
                'request_type' => $direction.'_'.$download_type,
                'download_type' => $download_type,
                'direction' => $direction,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'status' => 'pending',
                'sat_request_id' => '',
                'attempts' => 0,
                'package_count' => 0,
                'downloaded_count' => 0,
                'processed_count' => 0,
                'missing_count' => 0,
                'cancelled_count' => 0,
                'error_message' => '',
            ]);
            $request->save();

            # SE AUDITA LA SOLICITUD
            Helper_Core_Audit::log([
                'module' => 'sat',
                'action' => 'create_sync_request',
                'entity_type' => 'sat_sync_request',
                'entity_id' => (int) $request->id,
                'summary' => 'Solicitud SAT '.$request->request_type.' '.$date_from.' a '.$date_to,
                'new_values' => $request->to_array(),
            ]);

            # SE REGRESA LISTADO ACTUALIZADO
            return $this->json_response([
                'status' => 'ok',
                'requests' => $this->get_recent_requests(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creando solicitud SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear la solicitud SAT.'], 400);
        }
    }

    /**
     * GET CONFIG
     *
     * FORMATEA CONFIGURACION SAT PARA LA VISTA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_config()
    {
        # SE OBTIENE CONFIGURACION ACTUAL
        $config = Model_Core_Sat_Config::get_current();

        # SE REGRESA LA CONFIGURACION NORMALIZADA
        return [
            'id' => (int) $config->id,
            'mode' => (string) $config->mode,
            'enabled' => (int) $config->enabled,
            'storage_path' => (string) $config->storage_path,
            'last_sync_at' => $config->last_sync_at ? date('d/m/Y H:i', $config->last_sync_at) : 'Nunca',
        ];
    }

    /**
     * GET CREDENTIALS
     *
     * FORMATEA CREDENCIALES SAT PARA LA VISTA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_credentials()
    {
        # SE INICIALIZA RESPUESTA
        $items = [];

        # SE RECORREN CREDENCIALES
        foreach (Model_Core_Sat_Credential::list_for_admin() as $credential) {
            $items[] = [
                'id' => (int) $credential->id,
                'credential_type' => (string) $credential->credential_type,
                'rfc' => (string) $credential->rfc,
                'cer_path' => (string) $credential->cer_path,
                'key_path' => (string) $credential->key_path,
                'password' => '',
                'has_password' => $credential->password_encrypted !== '' ? 1 : 0,
                'valid_from' => (string) $credential->valid_from,
                'valid_until' => (string) $credential->valid_until,
                'notes' => (string) $credential->notes,
                'active' => (int) $credential->active,
            ];
        }

        return $items;
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASICOS SAT
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE REGRESAN CONTADORES AGREGADOS
        return [
            'cfdi' => (int) \DB::count_records('core_sat_cfdi'),
            'requests' => (int) \DB::count_records('core_sat_sync_requests'),
            'packages' => (int) \DB::count_records('core_sat_packages'),
            'credentials' => (int) \DB::count_records('core_sat_credentials'),
            'missing_xml' => $this->field_exists('core_sat_cfdi', 'missing_xml') ? (int) \DB::select()->from('core_sat_cfdi')->where('missing_xml', '=', 1)->execute()->count() : 0,
            'cancelled' => (int) \DB::select()->from('core_sat_cfdi')->where('sat_status', '=', 'cancelado')->execute()->count(),
            'unvalidated' => $this->field_exists('core_sat_cfdi', 'last_validated_at') ? (int) \DB::select()->from('core_sat_cfdi')->where('last_validated_at', '=', 0)->execute()->count() : 0,
        ];
    }

    /**
     * GET CFDI ALERTS
     *
     * OBTIENE CFDI QUE REQUIEREN REVISION FISCAL
     *
     * @access  protected
     * @return  Array
     */
    protected function get_cfdi_alerts()
    {
        # SI AUN NO EXISTE LA MIGRACION NUEVA SE REGRESA VACIO
        if (!$this->field_exists('core_sat_cfdi', 'missing_xml')) {
            return [];
        }

        # SE CONSULTAN ALERTAS PRINCIPALES
        $rows = \DB::select('id', 'uuid', 'direction', 'emitter_rfc', 'receiver_rfc', 'total', 'sat_status', 'origin', 'xml_path', 'missing_xml', 'last_validated_at')
            ->from('core_sat_cfdi')
            ->where_open()
                ->where('missing_xml', '=', 1)
                ->or_where('sat_status', '=', 'cancelado')
                ->or_where('last_validated_at', '=', 0)
            ->where_close()
            ->order_by('id', 'desc')
            ->limit(20)
            ->execute();

        # SE FORMATEA RESPUESTA
        $items = [];
        foreach ($rows as $row) {
            $row['last_validated_at'] = $row['last_validated_at'] ? date('d/m/Y H:i', $row['last_validated_at']) : 'Sin validar';
            $items[] = $row;
        }

        return $items;
    }

    /**
     * GET RECENT REQUESTS
     *
     * OBTIENE LAS ULTIMAS SOLICITUDES SAT
     *
     * @access  protected
     * @return  Array
     */
    protected function get_recent_requests()
    {
        # SE CONSULTAN ULTIMAS SOLICITUDES
        $requests = Model_Core_Sat_Sync_Request::query()
            ->order_by('created_at', 'desc')
            ->limit(10)
            ->get();

        # SE FORMATEA RESPUESTA
        $items = [];
        foreach ($requests as $request) {
            $items[] = [
                'id' => (int) $request->id,
                'request_type' => (string) $request->request_type,
                'download_type' => isset($request->download_type) ? (string) $request->download_type : 'xml',
                'direction' => isset($request->direction) ? (string) $request->direction : '',
                'date_from' => (string) $request->date_from,
                'date_to' => (string) $request->date_to,
                'status' => (string) $request->status,
                'package_count' => isset($request->package_count) ? (int) $request->package_count : 0,
                'downloaded_count' => isset($request->downloaded_count) ? (int) $request->downloaded_count : 0,
                'processed_count' => (int) $request->processed_count,
                'missing_count' => isset($request->missing_count) ? (int) $request->missing_count : 0,
                'cancelled_count' => isset($request->cancelled_count) ? (int) $request->cancelled_count : 0,
                'created_at' => $request->created_at ? date('d/m/Y H:i', $request->created_at) : '',
            ];
        }

        return $items;
    }

    /**
     * GET CATALOG DEFINITIONS
     *
     * DEFINE LOS CATALOGOS SAT ADMINISTRABLES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_catalog_definitions()
    {
        # SE DEFINEN CATALOGOS FISCALES BASE
        return [
            'payment_forms' => [
                'title' => 'Formas de pago',
                'model' => 'Model_Core_Sat_Catalog_Payment_Form',
                'table' => 'core_sat_payment_forms',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'banked', 'label' => 'Bancarizada', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'payment_methods' => [
                'title' => 'Metodos de pago',
                'model' => 'Model_Core_Sat_Catalog_Payment_Method',
                'table' => 'core_sat_payment_methods',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'description', 'label' => 'Descripcion', 'type' => 'text', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'cfdi_uses' => [
                'title' => 'Uso CFDI',
                'model' => 'Model_Core_Sat_Catalog_Cfdi_Use',
                'table' => 'core_sat_cfdi_uses',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'applies_person', 'label' => 'Persona fisica', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'applies_company', 'label' => 'Persona moral', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'tax_regimes' => [
                'title' => 'Regimen fiscal',
                'model' => 'Model_Core_Sat_Catalog_Tax_Regime',
                'table' => 'core_sat_tax_regimes',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'applies_person', 'label' => 'Persona fisica', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'applies_company', 'label' => 'Persona moral', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'unit_keys' => [
                'title' => 'Claves unidad',
                'model' => 'Model_Core_Sat_Catalog_Unit_Key',
                'table' => 'core_sat_unit_keys',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'symbol', 'label' => 'Simbolo', 'type' => 'text', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'taxes' => [
                'title' => 'Impuestos SAT',
                'model' => 'Model_Core_Sat_Catalog_Tax',
                'table' => 'core_sat_taxes',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'tax_type', 'label' => 'Tipo', 'type' => 'text', 'default' => 'traslado'],
                    ['name' => 'factor_type', 'label' => 'Factor', 'type' => 'text', 'default' => 'Tasa'],
                    ['name' => 'default_rate', 'label' => 'Tasa default', 'type' => 'number', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
        ];
    }

    /**
     * GET CATALOG ITEMS
     *
     * OBTIENE REGISTROS DE CATALOGOS SAT
     *
     * @access  protected
     * @return  Array
     */
    protected function get_catalog_items()
    {
        # SE INICIALIZA RESPUESTA
        $items = [];

        # SE RECORREN CATALOGOS
        foreach ($this->get_catalog_definitions() as $key => $definition) {
            $class = $definition['model'];
            $items[$key] = [];

            foreach ($class::query()->order_by('id', 'desc')->get() as $row) {
                $items[$key][] = $row->to_array();
            }
        }

        return $items;
    }

    /**
     * GET CATALOG STATS
     *
     * OBTIENE CONTADORES DE CATALOGOS SAT
     *
     * @access  protected
     * @return  Array
     */
    protected function get_catalog_stats()
    {
        # SE INICIALIZAN CONTADORES
        $stats = [];

        # SE RECORREN TABLAS
        foreach ($this->get_catalog_definitions() as $key => $definition) {
            $stats[$key] = (int) \DB::count_records($definition['table']);
        }

        return $stats;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS SAT EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach (['core_sat_config', 'core_sat_credentials', 'core_sat_sync_requests', 'core_sat_packages', 'core_sat_cfdi'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones SAT.');
            }
        }
    }

    protected function field_exists($table, $field)
    {
        return \DBUtil::field_exists($table, [$field]);
    }

    /**
     * ASSERT CATALOG SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS DE CATALOGOS SAT EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_catalog_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach ($this->get_catalog_definitions() as $definition) {
            if (!\DBUtil::table_exists($definition['table'])) {
                throw new \RuntimeException('Falta ejecutar migraciones de catalogos SAT.');
            }
        }
    }
}
