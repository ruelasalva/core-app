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
     * CONVERSION
     *
     * PANTALLA COMERCIAL PARA CONFIGURAR WIDGETS PUBLICOS DE CONVERSION.
     *
     * @access  public
     * @return  Void
     */
    public function action_conversion()
    {
        $this->template->title = 'Conversión web';
        $this->template->content = View::forge('admin/web/conversion');
    }

    /**
     * CONVERSION DATA
     *
     * ENTREGA CONFIGURACION DE CONVERSION SIN EXPONER SECRETOS.
     *
     * @access  public
     * @return  Response
     */
    public function action_conversion_data()
    {
        try {
            $this->assert_schema_ready();

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => [
                    'settings' => $this->get_conversion_settings_for_admin(),
                    'crm_available' => \DBUtil::table_exists('core_crm_prospects') ? 1 : 0,
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando conversion web: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar la configuración de conversión web.',
                'data' => [],
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * SAVE CONVERSION
     *
     * GUARDA CONFIGURACION COMERCIAL EN CORE_WEB_INTEGRATIONS.
     *
     * @access  public
     * @return  Response
     */
    public function post_save_conversion()
    {
        $this->require_access('web.access[edit]');

        try {
            $this->assert_schema_ready();
            $payload = (array) \Input::json();
            $settings = (array) \Arr::get($payload, 'settings', $payload);

            $this->save_conversion_whatsapp((array) \Arr::get($settings, 'whatsapp', []));
            $this->save_conversion_messenger((array) \Arr::get($settings, 'messenger', []));
            $this->save_conversion_general($settings);

            \Log::info('Configuración de conversión web actualizada.');

            return $this->json_response([
                'success' => true,
                'message' => 'Configuración de conversión web guardada.',
                'data' => [
                    'settings' => $this->get_conversion_settings_for_admin(),
                    'crm_available' => \DBUtil::table_exists('core_crm_prospects') ? 1 : 0,
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando conversion web: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar la configuración de conversión web.',
                'data' => [],
                'errors' => [$e->getMessage()],
            ], 400);
        }
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
                'enabled' => $this->bool_value(\Arr::get($val, 'enabled', false)),
                'load_in_frontend' => $this->bool_value(\Arr::get($val, 'load_in_frontend', true)),
                'load_in_admin' => $this->bool_value(\Arr::get($val, 'load_in_admin', false)),
                'requires_consent' => $this->bool_value(\Arr::get($val, 'requires_consent', true)),
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
     * GET CONVERSION SETTINGS FOR ADMIN
     *
     * FORMATEA CONFIGURACION COMERCIAL SIN SECRET_VALUE.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_conversion_settings_for_admin()
    {
        $whatsapp = $this->integration_by_code('whatsapp_click_chat');
        $messenger = $this->integration_by_code('meta_messenger');
        $general = $this->integration_by_code('web_conversion_settings');

        $whatsapp_settings = $whatsapp ? $this->settings_array($whatsapp->settings_json) : [];
        $messenger_settings = $messenger ? $this->settings_array($messenger->settings_json) : [];
        $general_settings = $general ? $this->settings_array($general->settings_json) : [];

        return [
            'whatsapp' => [
                'enabled' => $whatsapp ? (int) $whatsapp->enabled : 0,
                'phone' => $whatsapp ? (string) ($whatsapp->public_key ?: $whatsapp->public_value) : '',
                'message' => (string) \Arr::get($whatsapp_settings, 'message', 'Hola, quiero información.'),
                'label' => (string) \Arr::get($whatsapp_settings, 'label', 'WhatsApp'),
                'position' => (string) \Arr::get($whatsapp_settings, 'side', 'right'),
                'show_mobile' => (int) \Arr::get($whatsapp_settings, 'show_mobile', 1),
                'show_desktop' => (int) \Arr::get($whatsapp_settings, 'show_desktop', 1),
            ],
            'messenger' => [
                'enabled' => $messenger ? (int) $messenger->enabled : 0,
                'page_id' => $messenger ? (string) $messenger->public_key : '',
                'requires_consent' => 1,
                'label' => (string) \Arr::get($messenger_settings, 'label', 'Messenger / Facebook'),
                'help_text' => (string) \Arr::get($messenger_settings, 'help_text', 'Se carga solo con consentimiento de marketing.'),
            ],
            'mobile_cta' => $this->array_with_defaults((array) \Arr::get($general_settings, 'mobile_cta', []), [
                'enabled' => 1,
                'show_whatsapp' => 1,
                'show_catalog' => 1,
                'show_contact' => 1,
                'whatsapp_label' => 'WhatsApp',
                'catalog_label' => 'Catálogo',
                'contact_label' => 'Contacto',
            ]),
            'product_inquiry' => $this->array_with_defaults((array) \Arr::get($general_settings, 'product_inquiry', []), [
                'enabled' => 1,
                'label' => 'Consultar producto',
                'destination' => 'whatsapp',
            ]),
            'trust_badges' => $this->conversion_badges((array) \Arr::get($general_settings, 'trust_badges', [])),
            'leads' => $this->array_with_defaults((array) \Arr::get($general_settings, 'leads', []), [
                'crm_enabled' => \DBUtil::table_exists('core_crm_prospects') ? 1 : 0,
                'default_source' => 'web/contact',
                'notification_email' => '',
            ]),
        ];
    }

    protected function save_conversion_whatsapp(array $data)
    {
        $phone = preg_replace('/\D+/', '', (string) \Arr::get($data, 'phone', ''));
        $enabled = $this->bool_value(\Arr::get($data, 'enabled', false));
        if ($enabled && $phone === '') {
            throw new \InvalidArgumentException('Captura el teléfono de WhatsApp o desactiva el widget.');
        }

        $side = \Arr::get($data, 'position', 'right') === 'left' ? 'left' : 'right';
        $settings = [
            'message' => trim((string) \Arr::get($data, 'message', 'Hola, quiero información.')),
            'label' => trim((string) \Arr::get($data, 'label', 'WhatsApp')),
            'side' => $side,
            'show_mobile' => $this->bool_value(\Arr::get($data, 'show_mobile', true)),
            'show_desktop' => $this->bool_value(\Arr::get($data, 'show_desktop', true)),
            'bottom' => 24,
        ];

        $integration = $this->get_or_create_integration('whatsapp_click_chat', 'WhatsApp', 'whatsapp', 'contact');
        $integration->public_key = $phone;
        $integration->public_value = '';
        $integration->settings_json = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $integration->enabled = $enabled;
        $integration->load_in_frontend = 1;
        $integration->load_in_admin = 0;
        $integration->requires_consent = 0;
        $integration->consent_category = 'necessary';
        $integration->sort_order = 10;
        $integration->save();
    }

    protected function save_conversion_messenger(array $data)
    {
        $page_id = preg_replace('/[^0-9]/', '', (string) \Arr::get($data, 'page_id', ''));
        $enabled = $this->bool_value(\Arr::get($data, 'enabled', false));
        if ($enabled && $page_id === '') {
            throw new \InvalidArgumentException('Captura el Page ID de Messenger o desactiva el widget.');
        }

        $settings = [
            'label' => trim((string) \Arr::get($data, 'label', 'Messenger / Facebook')),
            'help_text' => trim((string) \Arr::get($data, 'help_text', 'Se carga solo con consentimiento de marketing.')),
            'locale' => 'es_LA',
            'version' => 'v20.0',
            'attribution' => 'biz_inbox',
        ];

        $integration = $this->get_or_create_integration('meta_messenger', 'Messenger / Facebook', 'meta', 'messenger');
        $integration->public_key = $page_id;
        $integration->public_value = '';
        $integration->settings_json = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $integration->enabled = $enabled;
        $integration->load_in_frontend = 1;
        $integration->load_in_admin = 0;
        $integration->requires_consent = 1;
        $integration->consent_category = 'marketing';
        $integration->sort_order = 20;
        $integration->save();
    }

    protected function save_conversion_general(array $settings)
    {
        $payload = [
            'mobile_cta' => $this->sanitize_mobile_cta((array) \Arr::get($settings, 'mobile_cta', [])),
            'product_inquiry' => $this->sanitize_product_inquiry((array) \Arr::get($settings, 'product_inquiry', [])),
            'trust_badges' => $this->conversion_badges((array) \Arr::get($settings, 'trust_badges', [])),
            'leads' => $this->sanitize_leads((array) \Arr::get($settings, 'leads', [])),
        ];

        $integration = $this->get_or_create_integration('web_conversion_settings', 'Configuración de conversión web', 'core_app', 'contact');
        $integration->public_key = '';
        $integration->public_value = '';
        $integration->settings_json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $integration->enabled = 1;
        $integration->load_in_frontend = 0;
        $integration->load_in_admin = 0;
        $integration->requires_consent = 0;
        $integration->consent_category = 'necessary';
        $integration->sort_order = 30;
        $integration->save();
    }

    protected function sanitize_mobile_cta(array $data)
    {
        return [
            'enabled' => $this->bool_value(\Arr::get($data, 'enabled', true)),
            'show_whatsapp' => $this->bool_value(\Arr::get($data, 'show_whatsapp', true)),
            'show_catalog' => $this->bool_value(\Arr::get($data, 'show_catalog', true)),
            'show_contact' => $this->bool_value(\Arr::get($data, 'show_contact', true)),
            'whatsapp_label' => $this->short_text(\Arr::get($data, 'whatsapp_label', 'WhatsApp'), 40),
            'catalog_label' => $this->short_text(\Arr::get($data, 'catalog_label', 'Catálogo'), 40),
            'contact_label' => $this->short_text(\Arr::get($data, 'contact_label', 'Contacto'), 40),
        ];
    }

    protected function sanitize_product_inquiry(array $data)
    {
        $destination = trim((string) \Arr::get($data, 'destination', 'whatsapp'));
        if (!in_array($destination, ['whatsapp', 'contact', 'crm'], true)) {
            $destination = 'whatsapp';
        }

        return [
            'enabled' => $this->bool_value(\Arr::get($data, 'enabled', true)),
            'label' => $this->short_text(\Arr::get($data, 'label', 'Consultar producto'), 80),
            'destination' => $destination,
        ];
    }

    protected function sanitize_leads(array $data)
    {
        return [
            'crm_enabled' => $this->bool_value(\Arr::get($data, 'crm_enabled', false)) && \DBUtil::table_exists('core_crm_prospects') ? 1 : 0,
            'default_source' => $this->short_text(\Arr::get($data, 'default_source', 'web/contact'), 80),
            'notification_email' => filter_var((string) \Arr::get($data, 'notification_email', ''), FILTER_VALIDATE_EMAIL)
                ? trim((string) \Arr::get($data, 'notification_email', ''))
                : '',
        ];
    }

    protected function conversion_badges(array $items)
    {
        $defaults = [
            ['icon' => 'bi bi-person-check', 'label' => 'Atención personalizada'],
            ['icon' => 'bi bi-receipt', 'label' => 'Facturación disponible'],
            ['icon' => 'bi bi-truck', 'label' => 'Envío o entrega'],
            ['icon' => 'bi bi-headset', 'label' => 'Soporte técnico'],
        ];

        $source = !empty($items) ? $items : $defaults;
        $badges = [];
        for ($i = 0; $i < 4; $i++) {
            $item = isset($source[$i]) && is_array($source[$i]) ? $source[$i] : $defaults[$i];
            $badges[] = [
                'icon' => $this->short_text(\Arr::get($item, 'icon', $defaults[$i]['icon']), 80),
                'label' => $this->short_text(\Arr::get($item, 'label', $defaults[$i]['label']), 80),
            ];
        }

        return $badges;
    }

    protected function get_or_create_integration($code, $name, $provider, $type)
    {
        $integration = $this->integration_by_code($code);
        if ($integration) {
            return $integration;
        }

        return Model_Core_Web_Integration::forge([
            'code' => $code,
            'name' => $name,
            'provider' => $provider,
            'integration_type' => $type,
            'environment' => 'production',
            'public_key' => '',
            'public_value' => '',
            'secret_value' => '',
            'settings_json' => '',
            'enabled' => 0,
            'load_in_frontend' => 1,
            'load_in_admin' => 0,
            'requires_consent' => 0,
            'consent_category' => 'necessary',
            'sort_order' => 0,
        ]);
    }

    protected function integration_by_code($code)
    {
        return Model_Core_Web_Integration::query()
            ->where('code', (string) $code)
            ->get_one();
    }

    protected function settings_array($json)
    {
        $settings = json_decode((string) $json, true);
        return is_array($settings) ? $settings : [];
    }

    protected function array_with_defaults(array $data, array $defaults)
    {
        return array_merge($defaults, $data);
    }

    protected function short_text($value, $max)
    {
        $value = trim((string) $value);
        return function_exists('mb_substr') ? mb_substr($value, 0, (int) $max) : substr($value, 0, (int) $max);
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
}
