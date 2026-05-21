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
                'integrations' => $this->get_integration_status(),
                'stats' => $this->get_stats(),
                'requests' => $this->get_recent_requests(),
                'packages' => $this->get_recent_packages(),
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
                'cer_original_name' => trim((string) \Arr::get($val, 'cer_original_name', '')),
                'key_path' => trim((string) \Arr::get($val, 'key_path', '')),
                'key_original_name' => trim((string) \Arr::get($val, 'key_original_name', '')),
                'password_encrypted' => '',
                'certificate_serial' => trim((string) \Arr::get($val, 'certificate_serial', '')),
                'certificate_subject' => trim((string) \Arr::get($val, 'certificate_subject', '')),
                'certificate_issuer' => trim((string) \Arr::get($val, 'certificate_issuer', '')),
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
                unset($data['password_encrypted']);
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

    public function post_upload_credential_file()
    {
        $this->require_access('sat.access[edit]');

        try {
            $this->assert_schema_ready();
            $id = (int) \Input::post('credential_id', 0);
            $file_type = trim((string) \Input::post('file_type', ''));
            if (!in_array($file_type, ['cer', 'key'], true)) {
                return $this->json_response(['error' => 'Tipo de archivo invalido.'], 422);
            }

            $credential = Model_Core_Sat_Credential::find($id);
            if (!$credential) {
                return $this->json_response(['error' => 'Guarda primero la credencial.'], 404);
            }

            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona un archivo valido.'], 422);
            }

            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if ($extension !== $file_type) {
                return $this->json_response(['error' => 'El archivo debe ser .'.$file_type.'.'], 422);
            }
            if ((int) \Arr::get($file, 'size', 0) > 2097152) {
                return $this->json_response(['error' => 'El archivo no puede superar 2 MB.'], 422);
            }

            $relative_dir = 'storage/sat/credentials/'.strtolower($credential->rfc).'/'.$credential->credential_type;
            $absolute_dir = APPPATH.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0750, true);
            }

            $filename = $file_type.'_'.date('Ymd_His').'_'.\Str::random('alnum', 8).'.'.$extension;
            $target = $absolute_dir.DS.$filename;
            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->json_response(['error' => 'No se pudo guardar el archivo.'], 400);
            }

            if ($file_type === 'cer') {
                $credential->cer_path = str_replace('\\', '/', 'fuel/app/'.$relative_dir.'/'.$filename);
                $credential->cer_original_name = (string) \Arr::get($file, 'name', '');
                $this->apply_certificate_metadata($credential, $target);
            } else {
                $credential->key_path = str_replace('\\', '/', 'fuel/app/'.$relative_dir.'/'.$filename);
                $credential->key_original_name = (string) \Arr::get($file, 'name', '');
            }
            $credential->save();

            Helper_Core_Audit::log([
                'module' => 'sat',
                'action' => 'upload_credential_file',
                'entity_type' => 'sat_credential',
                'entity_id' => (int) $credential->id,
                'summary' => 'Archivo '.$file_type.' cargado para '.$credential->credential_type.' '.$credential->rfc,
                'new_values' => [
                    'credential_type' => $credential->credential_type,
                    'rfc' => $credential->rfc,
                    'file_type' => $file_type,
                    'original_name' => (string) \Arr::get($file, 'name', ''),
                ],
            ]);

            return $this->json_response(['status' => 'ok', 'credentials' => $this->get_credentials()]);
        } catch (\Exception $e) {
            \Log::error('Error cargando archivo credencial SAT: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_upload_credential_file()
    {
        return $this->post_upload_credential_file();
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

            # SE CREA SOLICITUD LOCAL; EL TASK FUTURO HARA LA LLAMADA REAL AL SAT
            $service = new Service_Core_Sat_Sync();
            $service->create_request($val);

            # SE REGRESA LISTADO ACTUALIZADO
            return $this->json_response([
                'status' => 'ok',
                'requests' => $this->get_recent_requests(),
                'packages' => $this->get_recent_packages(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creando solicitud SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear la solicitud SAT.'], 400);
        }
    }

    /**
     * SUBMIT REQUESTS
     *
     * ENVIA SOLICITUDES PENDIENTES AL SAT
     *
     * @access  public
     * @return  Response
     */
    public function action_submit_requests()
    {
        $this->require_access('sat.access[edit]');

        try {
            $this->assert_schema_ready();
            $payload = (array) \Input::json();
            $result = (new Service_Core_Sat_Sync())->submit_pending(5, (array) \Arr::get($payload, 'request_ids', []));
            return $this->json_response($this->operation_payload($result));
        } catch (\Exception $e) {
            \Log::error('Error enviando solicitudes SAT: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * VERIFY REQUESTS
     *
     * CONSULTA AL SAT SI LAS SOLICITUDES YA GENERARON PAQUETES
     *
     * @access  public
     * @return  Response
     */
    public function action_verify_requests()
    {
        $this->require_access('sat.access[edit]');

        try {
            $this->assert_schema_ready();
            $payload = (array) \Input::json();
            $result = (new Service_Core_Sat_Sync())->verify_requests(10, (array) \Arr::get($payload, 'request_ids', []));
            return $this->json_response($this->operation_payload($result));
        } catch (\Exception $e) {
            \Log::error('Error verificando solicitudes SAT: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * DOWNLOAD PACKAGES
     *
     * DESCARGA Y PROCESA PAQUETES DISPONIBLES
     *
     * @access  public
     * @return  Response
     */
    public function action_download_packages()
    {
        $this->require_access('sat.access[edit]');

        try {
            $this->assert_schema_ready();
            $payload = (array) \Input::json();
            $result = (new Service_Core_Sat_Sync())->download_packages(
                5,
                (array) \Arr::get($payload, 'package_ids', []),
                (array) \Arr::get($payload, 'request_ids', [])
            );
            return $this->json_response($this->operation_payload($result));
        } catch (\Exception $e) {
            \Log::error('Error descargando paquetes SAT: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
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
                'cer_original_name' => (string) $credential->cer_original_name,
                'key_path' => (string) $credential->key_path,
                'key_original_name' => (string) $credential->key_original_name,
                'password' => '',
                'has_password' => $credential->password_encrypted !== '' ? 1 : 0,
                'certificate_serial' => (string) $credential->certificate_serial,
                'certificate_subject' => (string) $credential->certificate_subject,
                'certificate_issuer' => (string) $credential->certificate_issuer,
                'valid_from' => (string) $credential->valid_from,
                'valid_until' => (string) $credential->valid_until,
                'days_remaining' => $this->days_remaining($credential->valid_until),
                'validity_status' => $this->validity_status($credential->valid_until),
                'notes' => (string) $credential->notes,
                'active' => (int) $credential->active,
            ];
        }

        return $items;
    }

    protected function get_integration_status()
    {
        $items = [
            'sat_download' => ['provider' => 'sat', 'enabled' => 0, 'connection' => null],
            'pac_billing' => ['provider' => 'factura_com', 'enabled' => 0, 'connection' => null],
        ];

        foreach ($items as $key => $item) {
            $provider = \DB::select('id', 'code', 'name', 'active')
                ->from('core_integration_providers')
                ->where('code', '=', $item['provider'])
                ->execute()
                ->current();
            if (!$provider) {
                continue;
            }
            $connection = \DB::select('id', 'code', 'name', 'environment', 'enabled', 'active')
                ->from('core_integration_connections')
                ->where('provider_id', '=', (int) $provider['id'])
                ->where('active', '=', 1)
                ->order_by('enabled', 'desc')
                ->order_by('id', 'desc')
                ->execute()
                ->current();

            $items[$key] = [
                'provider' => $provider,
                'enabled' => $connection && (int) $connection['enabled'] === 1 && (int) $provider['active'] === 1 ? 1 : 0,
                'connection' => $connection ?: null,
            ];
        }

        return $items;
    }

    protected function days_remaining($valid_until)
    {
        $time = strtotime((string) $valid_until);
        if (!$time) {
            return null;
        }
        return (int) floor(($time - strtotime(date('Y-m-d'))) / 86400);
    }

    protected function validity_status($valid_until)
    {
        $days = $this->days_remaining($valid_until);
        if ($days === null) {
            return 'unknown';
        }
        if ($days < 0) {
            return 'expired';
        }
        if ($days <= 30) {
            return 'warning';
        }
        return 'valid';
    }

    protected function apply_certificate_metadata(Model_Core_Sat_Credential $credential, $path)
    {
        $parsed = $this->parse_certificate($path);
        if (!$parsed) {
            return;
        }

        $credential->certificate_serial = (string) \Arr::get($parsed, 'serialNumberHex', \Arr::get($parsed, 'serialNumber', ''));
        $credential->certificate_subject = json_encode(\Arr::get($parsed, 'subject', []));
        $credential->certificate_issuer = json_encode(\Arr::get($parsed, 'issuer', []));
        if (!empty($parsed['validFrom_time_t'])) {
            $credential->valid_from = date('Y-m-d', (int) $parsed['validFrom_time_t']);
        }
        if (!empty($parsed['validTo_time_t'])) {
            $credential->valid_until = date('Y-m-d', (int) $parsed['validTo_time_t']);
        }
    }

    protected function parse_certificate($path)
    {
        if (!function_exists('openssl_x509_parse')) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false || $content === '') {
            return null;
        }

        $pem = $content;
        if (strpos($content, 'BEGIN CERTIFICATE') === false) {
            $pem = "-----BEGIN CERTIFICATE-----\n".chunk_split(base64_encode($content), 64, "\n")."-----END CERTIFICATE-----\n";
        }

        $resource = @openssl_x509_read($pem);
        if (!$resource) {
            return null;
        }

        $parsed = @openssl_x509_parse($resource);
        return is_array($parsed) ? $parsed : null;
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
                'sat_request_id' => (string) $request->sat_request_id,
                'error_message' => (string) $request->error_message,
                'created_at' => $request->created_at ? date('d/m/Y H:i', $request->created_at) : '',
            ];
        }

        return $items;
    }

    /**
     * GET RECENT PACKAGES
     *
     * OBTIENE LOS ULTIMOS PAQUETES SAT
     *
     * @access  protected
     * @return  Array
     */
    protected function get_recent_packages()
    {
        $packages = Model_Core_Sat_Package::query()
            ->order_by('created_at', 'desc')
            ->limit(10)
            ->get();

        $items = [];
        foreach ($packages as $package) {
            $items[] = [
                'id' => (int) $package->id,
                'sync_request_id' => (int) $package->sync_request_id,
                'package_id' => (string) $package->package_id,
                'package_type' => (string) $package->package_type,
                'xml_count' => (int) $package->xml_count,
                'status' => (string) $package->status,
                'path' => (string) $package->path,
                'sha256_hash' => (string) $package->sha256_hash,
                'created_at' => $package->created_at ? date('d/m/Y H:i', $package->created_at) : '',
            ];
        }

        return $items;
    }

    protected function operation_payload(array $result)
    {
        return [
            'status' => 'ok',
            'result' => $result,
            'requests' => $this->get_recent_requests(),
            'packages' => $this->get_recent_packages(),
            'stats' => $this->get_stats(),
            'cfdi_alerts' => $this->get_cfdi_alerts(),
        ];
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
            'product_service_keys' => [
                'title' => 'Claves producto/servicio',
                'model' => 'Model_Core_Sat_Catalog_Product_Service_Key',
                'table' => 'core_sat_product_service_keys',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'object_tax_codes' => [
                'title' => 'Objeto de impuesto',
                'model' => 'Model_Core_Sat_Catalog_Object_Tax_Code',
                'table' => 'core_sat_object_tax_codes',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
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
