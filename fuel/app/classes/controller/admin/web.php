<?php

/**
 * CONTROLADOR ADMIN_WEB
 *
 * Administra integraciones web, captcha, pixeles, analytics y preferencias base de cookies.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Web extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * Valida sesion administrativa y permiso de lectura del modulo Web.
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y LA SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('web.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA LA PANTALLA PRINCIPAL DEL MODULO WEB
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Web';
        $this->template->content = View::forge('admin/web/index');
    }

    /**
     * DATA
     *
     * ENTREGA INTEGRACIONES Y ESTADISTICAS DE COOKIES EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA BASE DEL MODULO EXISTA
            $this->assert_schema_ready();

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response([
                'integrations' => $this->get_integrations(),
                'stats' => $this->get_cookie_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando web: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar el modulo web.'], 500);
        }
    }

    /**
     * SAVE INTEGRATION
     *
     * CREA O ACTUALIZA UNA INTEGRACION WEB
     *
     * @access  public
     * @return  Response
     */
    public function post_save_integration()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('web.access[edit]');

        # SE OBTIENE EL PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA BASE DEL MODULO EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $name = trim((string) \Arr::get($val, 'name', ''));
            $code = trim((string) \Arr::get($val, 'code', ''));

            # VALIDACIONES MINIMAS
            if ($name === '' || $code === '') {
                return $this->json_response(['error' => 'Codigo y nombre son obligatorios.'], 422);
            }

            # SE PREPARAN LOS DATOS DEL MODELO
            $data = [
                'code' => $this->slugify($code),
                'name' => $name,
                'provider' => trim((string) \Arr::get($val, 'provider', '')),
                'integration_type' => trim((string) \Arr::get($val, 'integration_type', 'script')),
                'environment' => trim((string) \Arr::get($val, 'environment', 'production')),
                'public_key' => trim((string) \Arr::get($val, 'public_key', '')),
                'public_value' => trim((string) \Arr::get($val, 'public_value', '')),
                'settings_json' => trim((string) \Arr::get($val, 'settings_json', '')),
                'enabled' => (int) (bool) \Arr::get($val, 'enabled', false),
                'load_in_frontend' => (int) (bool) \Arr::get($val, 'load_in_frontend', true),
                'load_in_admin' => (int) (bool) \Arr::get($val, 'load_in_admin', false),
                'requires_consent' => (int) (bool) \Arr::get($val, 'requires_consent', true),
                'consent_category' => trim((string) \Arr::get($val, 'consent_category', 'analytics')),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
            ];

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $integration = Model_Core_Web_Integration::find($id);
                if (!$integration) {
                    return $this->json_response(['error' => 'Integracion no encontrada.'], 404);
                }
                $integration->set($data);
            } else {
                $integration = Model_Core_Web_Integration::forge($data);
            }

            # SE CIFRA EL VALOR SECRETO SOLO SI SE CAPTURO UNO NUEVO
            $secret_value = trim((string) \Arr::get($val, 'secret_value', ''));
            if ($secret_value !== '') {
                $integration->secret_value = \Crypt::encode($secret_value);
            }

            # SE GUARDA LA INTEGRACION
            $integration->save();

            # SE REGRESA LA LISTA ACTUALIZADA
            return $this->json_response(['status' => 'ok', 'integrations' => $this->get_integrations()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando integracion web: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la integracion.'], 400);
        }
    }

    /**
     * GET INTEGRATIONS
     *
     * FORMATEA LAS INTEGRACIONES PARA LA VISTA ADMINISTRATIVA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_integrations()
    {
        # SE INICIALIZA EL ARREGLO DE RESPUESTA
        $items = [];

        # SE RECORREN LAS INTEGRACIONES ACTIVAS E INACTIVAS
        foreach (Model_Core_Web_Integration::list_for_admin() as $integration) {
            $items[] = [
                'id' => (int) $integration->id,
                'code' => (string) $integration->code,
                'name' => (string) $integration->name,
                'provider' => (string) $integration->provider,
                'integration_type' => (string) $integration->integration_type,
                'environment' => (string) $integration->environment,
                'public_key' => (string) $integration->public_key,
                'public_value' => (string) $integration->public_value,
                'secret_value' => '',
                'has_secret' => $integration->secret_value !== '' ? 1 : 0,
                'settings_json' => (string) $integration->settings_json,
                'enabled' => (int) $integration->enabled,
                'load_in_frontend' => (int) $integration->load_in_frontend,
                'load_in_admin' => (int) $integration->load_in_admin,
                'requires_consent' => (int) $integration->requires_consent,
                'consent_category' => (string) $integration->consent_category,
                'sort_order' => (int) $integration->sort_order,
            ];
        }

        return $items;
    }

    /**
     * GET COOKIE STATS
     *
     * OBTIENE CONTADORES BASICOS DE CONSENTIMIENTO DE COOKIES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_cookie_stats()
    {
        # SE REGRESAN CONTADORES AGREGADOS
        return [
            'total' => (int) \DB::count_records('core_web_cookie_preferences'),
            'analytics' => (int) \DB::select()->from('core_web_cookie_preferences')->where('analytics', '=', 1)->execute()->count(),
            'marketing' => (int) \DB::select()->from('core_web_cookie_preferences')->where('marketing', '=', 1)->execute()->count(),
            'personalization' => (int) \DB::select()->from('core_web_cookie_preferences')->where('personalization', '=', 1)->execute()->count(),
        ];
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS DEL MODULO WEB EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach (['core_web_integrations', 'core_web_cookie_preferences'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones web.');
            }
        }
    }

    /**
     * SLUGIFY
     *
     * NORMALIZA CODIGOS INTERNOS PARA INTEGRACIONES
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
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
