<?php

/**
 * CONTROLADOR ADMIN_INTEGRATIONS
 *
 * Administra proveedores, conexiones, webhooks y eventos de integraciones externas.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Integrations extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE INTEGRACIONES
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('integrations.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE INTEGRACIONES
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Integraciones';
        $this->template->content = View::forge('admin/integrations/index');
    }

    /**
     * DATA
     *
     * ENTREGA PROVEEDORES, CONEXIONES, WEBHOOKS Y EVENTOS EN JSON
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
                'providers' => $this->get_providers(),
                'connections' => $this->get_connections(),
                'webhooks' => $this->get_webhooks(),
                'events' => $this->get_events(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando integraciones: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar integraciones.'], 500);
        }
    }

    /**
     * SAVE PROVIDER
     *
     * CREA O ACTUALIZA PROVEEDOR DE INTEGRACION
     *
     * @access  public
     * @return  Response
     */
    public function action_save_provider()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('integrations.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN DATOS BASE
            $name = trim((string) \Arr::get($val, 'name', ''));
            $code = $this->codeify(\Arr::get($val, 'code', $name));
            if ($name === '' || $code === '') {
                return $this->json_response(['error' => 'Codigo y nombre son obligatorios.'], 422);
            }

            # SE PREPARAN DATOS PERMITIDOS
            $data = [
                'code' => $code,
                'name' => $name,
                'category' => $this->codeify(\Arr::get($val, 'category', 'general')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'website_url' => trim((string) \Arr::get($val, 'website_url', '')),
                'adapter_class' => trim((string) \Arr::get($val, 'adapter_class', '')),
                'requires_install' => $this->bool_value(\Arr::get($val, 'requires_install', false)),
                'install_notes' => trim((string) \Arr::get($val, 'install_notes', '')),
                'config_schema_json' => trim((string) \Arr::get($val, 'config_schema_json', '')),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];

            # SE CREA O ACTUALIZA
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $provider = Model_Core_Integration_Provider::find($id);
                if (!$provider) {
                    return $this->json_response(['error' => 'Proveedor no encontrado.'], 404);
                }
                $old = $provider->to_array();
                $provider->set($data);
            } else {
                $old = [];
                $provider = Model_Core_Integration_Provider::forge($data);
            }
            $provider->save();

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'integrations',
                'action' => $id > 0 ? 'update_provider' : 'create_provider',
                'entity_type' => 'integration_provider',
                'entity_id' => (int) $provider->id,
                'summary' => 'Proveedor de integracion '.$provider->code,
                'old_values' => $old,
                'new_values' => $provider->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'providers' => $this->get_providers(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando proveedor de integracion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el proveedor.'], 400);
        }
    }

    /**
     * SAVE CONNECTION
     *
     * CREA O ACTUALIZA CONEXION A PROVEEDOR EXTERNO
     *
     * @access  public
     * @return  Response
     */
    public function action_save_connection()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('integrations.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN DATOS BASE
            $provider_id = (int) \Arr::get($val, 'provider_id', 0);
            $name = trim((string) \Arr::get($val, 'name', ''));
            $code = $this->codeify(\Arr::get($val, 'code', $name));
            if ($provider_id < 1 || $name === '' || $code === '') {
                return $this->json_response(['error' => 'Proveedor, codigo y nombre son obligatorios.'], 422);
            }
            $provider_code = $this->provider_code($provider_id);

            # SE PREPARAN DATOS PERMITIDOS
            $data = [
                'provider_id' => $provider_id,
                'code' => $code,
                'name' => $name,
                'environment' => $this->codeify(\Arr::get($val, 'environment', 'sandbox')),
                'public_key' => trim((string) \Arr::get($val, 'public_key', '')),
                'public_value' => trim((string) \Arr::get($val, 'public_value', '')),
                'config_json' => trim((string) \Arr::get($val, 'config_json', '')),
                'enabled' => $this->bool_value(\Arr::get($val, 'enabled', false)),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];
            if ($provider_code === 'inegi_denue') {
                $data['public_key'] = '';
                $data['public_value'] = '';
            }

            # SE CREA O ACTUALIZA
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $connection = Model_Core_Integration_Connection::find($id);
                if (!$connection) {
                    return $this->json_response(['error' => 'Conexion no encontrada.'], 404);
                }
                $old = $this->mask_connection($connection->to_array());
                $connection->set($data);
            } else {
                $old = [];
                $connection = Model_Core_Integration_Connection::forge($data);
            }

            # SE CIFRAN SECRETOS SOLO SI SE CAPTURAN VALORES NUEVOS
            $secret_value = trim((string) \Arr::get($val, 'secret_value', ''));
            if ($provider_code === 'inegi_denue' && $secret_value === '') {
                $secret_value = trim((string) \Arr::get($val, 'public_key', ''));
            }
            if ($secret_value !== '') {
                $connection->secret_value = \Crypt::encode($secret_value);
            }

            $webhook_secret = trim((string) \Arr::get($val, 'webhook_secret', ''));
            if ($webhook_secret !== '') {
                $connection->webhook_secret = \Crypt::encode($webhook_secret);
            }

            $connection->save();

            # SE AUDITA SIN EXPONER SECRETOS
            Helper_Core_Audit::log([
                'module' => 'integrations',
                'action' => $id > 0 ? 'update_connection' : 'create_connection',
                'entity_type' => 'integration_connection',
                'entity_id' => (int) $connection->id,
                'summary' => 'Conexion de integracion '.$connection->code,
                'old_values' => $old,
                'new_values' => $this->mask_connection($connection->to_array()),
            ]);

            return $this->json_response(['status' => 'ok', 'connections' => $this->get_connections(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando conexion de integracion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la conexion.'], 400);
        }
    }

    /**
     * GET PROVIDERS
     *
     * FORMATEA PROVEEDORES DE INTEGRACION
     *
     * @access  protected
     * @return  Array
     */
    protected function get_providers()
    {
        $items = [];
        foreach (Model_Core_Integration_Provider::query()->order_by('sort_order', 'asc')->order_by('name', 'asc')->get() as $provider) {
            $items[] = $provider->to_array();
        }
        return $items;
    }

    /**
     * GET CONNECTIONS
     *
     * FORMATEA CONEXIONES SIN EXPONER SECRETOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_connections()
    {
        $items = [];
        foreach (Model_Core_Integration_Connection::query()->order_by('id', 'desc')->get() as $connection) {
            $items[] = $this->mask_connection($connection->to_array());
        }
        return $items;
    }

    /**
     * GET WEBHOOKS
     *
     * FORMATEA WEBHOOKS CONFIGURADOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_webhooks()
    {
        $items = [];
        foreach (Model_Core_Integration_Webhook::query()->order_by('id', 'desc')->limit(100)->get() as $webhook) {
            $items[] = $webhook->to_array();
        }
        return $items;
    }

    /**
     * GET EVENTS
     *
     * FORMATEA EVENTOS RECIENTES DE INTEGRACION
     *
     * @access  protected
     * @return  Array
     */
    protected function get_events()
    {
        $items = [];
        foreach (Model_Core_Integration_Event::query()->order_by('id', 'desc')->limit(80)->get() as $event) {
            $row = $event->to_array();
            $row['created_at'] = $event->created_at ? date('d/m/Y H:i', $event->created_at) : '';
            $items[] = $row;
        }
        return $items;
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES DEL MODULO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        return [
            'providers' => (int) \DB::count_records('core_integration_providers'),
            'connections' => (int) \DB::count_records('core_integration_connections'),
            'enabled_connections' => (int) \DB::select()->from('core_integration_connections')->where('enabled', '=', 1)->execute()->count(),
            'events' => (int) \DB::count_records('core_integration_events'),
        ];
    }

    /**
     * MASK CONNECTION
     *
     * OCULTA SECRETOS ANTES DE RESPONDER O AUDITAR
     *
     * @access  protected
     * @return  Array
     */
    protected function mask_connection(array $connection)
    {
        $has_secret = !empty($connection['secret_value']) ? 1 : 0;
        $has_webhook_secret = !empty($connection['webhook_secret']) ? 1 : 0;
        $connection['secret_value'] = '';
        $connection['webhook_secret'] = '';
        $connection['has_secret'] = $has_secret;
        $connection['has_webhook_secret'] = $has_webhook_secret;
        return $connection;
    }

    /**
     * PROVIDER CODE
     *
     * OBTIENE CODIGO DEL PROVEEDOR PARA REGLAS DE CREDENCIALES
     *
     * @access  protected
     * @return  String
     */
    protected function provider_code($provider_id)
    {
        $provider = \DB::select('code')
            ->from('core_integration_providers')
            ->where('id', '=', (int) $provider_id)
            ->execute()
            ->current();

        return $provider ? (string) $provider['code'] : '';
    }

    /**
     * BOOL VALUE
     *
     * NORMALIZA BOOLEANOS ENVIADOS POR VUE/JSON EVITANDO QUE "0" O "false" SEAN TRUE
     *
     * @access  protected
     * @return  Int
     */
    protected function bool_value($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'on', 'yes', 'si'], true) ? 1 : 0;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA TABLAS DEL MODULO
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        foreach (['core_integration_providers', 'core_integration_connections', 'core_integration_webhooks', 'core_integration_events'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de integraciones.');
            }
        }
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
