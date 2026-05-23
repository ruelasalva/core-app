<?php

/**
 * HELPER CORE_WEB
 *
 * Centraliza integraciones publicas del frontend: analytics, pixeles, tags y captcha.
 *
 * @package  app
 */
class Helper_Core_Web
{
    /**
     * FRONTEND HEAD
     *
     * GENERA SCRIPTS PUBLICOS PARA EL HEAD SEGUN CONFIGURACION Y CONSENTIMIENTO.
     *
     * @access  public
     * @return  String
     */
    public static function frontend_head()
    {
        # SE RENDERIZAN INTEGRACIONES DE CARGA TEMPRANA
        return self::render_integrations(['analytics', 'tag_manager'], 'head');
    }

    /**
     * FRONTEND BODY END
     *
     * GENERA SCRIPTS PUBLICOS PARA EL CIERRE DEL BODY SEGUN CONFIGURACION Y CONSENTIMIENTO.
     *
     * @access  public
     * @return  String
     */
    public static function frontend_body_end()
    {
        # SE RENDERIZAN NOSCRIPT, PIXELES Y SCRIPTS GENERICOS
        return self::render_integrations(['tag_manager', 'pixel', 'script', 'contact', 'messenger'], 'body');
    }

    /**
     * CAPTCHA ENABLED
     *
     * INDICA SI RECAPTCHA ESTA CONFIGURADO PARA FORMULARIOS PUBLICOS.
     *
     * @access  public
     * @return  Bool
     */
    public static function captcha_enabled()
    {
        # SE REQUIERE LLAVE PUBLICA Y SECRETA PARA ACTIVAR CAPTCHA
        $captcha = self::integration('google_recaptcha');
        return $captcha && trim((string) $captcha->public_key) !== '' && trim((string) $captcha->secret_value) !== '';
    }

    /**
     * CAPTCHA SITE KEY
     *
     * OBTIENE LA LLAVE PUBLICA DE RECAPTCHA.
     *
     * @access  public
     * @return  String
     */
    public static function captcha_site_key()
    {
        # SE REGRESA LLAVE PUBLICA SI EXISTE
        $captcha = self::integration('google_recaptcha');
        return $captcha ? (string) $captcha->public_key : '';
    }

    /**
     * RENDER CAPTCHA
     *
     * GENERA EL WIDGET DE RECAPTCHA SOLO SI ESTA CONFIGURADO.
     *
     * @access  public
     * @return  String
     */
    public static function render_captcha()
    {
        # SI NO ESTA CONFIGURADO, NO SE MUESTRA NADA
        if (!self::captcha_enabled()) {
            return '';
        }

        $site_key = e(self::captcha_site_key());

        return '<div class="g-recaptcha" data-sitekey="'.$site_key.'"></div>';
    }

    /**
     * CAPTCHA SCRIPT
     *
     * GENERA EL SCRIPT DE RECAPTCHA SOLO SI ESTA CONFIGURADO.
     *
     * @access  public
     * @return  String
     */
    public static function captcha_script()
    {
        # SI NO ESTA CONFIGURADO, NO SE CARGA SCRIPT EXTERNO
        if (!self::captcha_enabled()) {
            return '';
        }

        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }

    /**
     * GOOGLE MAPS EMBED URL
     *
     * OBTIENE URL SEGURA PARA MOSTRAR MAPA DE CONTACTO.
     *
     * @access  public
     * @return  String
     */
    public static function google_maps_embed_url()
    {
        # SE BUSCA INTEGRACION ACTIVA
        $maps = self::integration('google_maps');
        if (!$maps) {
            return '';
        }

        # PUBLIC_VALUE PUEDE CONTENER URL EMBED COMPLETA
        $url = trim((string) $maps->public_value);

        # SETTINGS_JSON PUEDE CONTENER embed_url PARA EVITAR PEGARLO EN VISTAS
        $settings = json_decode((string) $maps->settings_json, true);
        if (is_array($settings) && !empty($settings['embed_url'])) {
            $url = trim((string) $settings['embed_url']);
        }

        # SOLO SE PERMITEN IFRAMES HTTPS DE GOOGLE MAPS
        if ($url === '' || !preg_match('#^https://www\.google\.com/maps/embed#i', $url)) {
            return '';
        }

        return $url;
    }

    /**
     * VERIFY CAPTCHA
     *
     * VALIDA EL TOKEN DE RECAPTCHA SOLO CUANDO ESTA CONFIGURADO.
     *
     * @access  public
     * @return  Bool
     */
    public static function verify_captcha($token)
    {
        # SI CAPTCHA NO ESTA ACTIVO, NO BLOQUEA FORMULARIOS
        if (!self::captcha_enabled()) {
            return true;
        }

        # TOKEN OBLIGATORIO CUANDO ESTA ACTIVO
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        # SE DECODIFICA SECRETO
        $captcha = self::integration('google_recaptcha');
        try {
            $secret = \Crypt::decode((string) $captcha->secret_value);
        } catch (\Exception $e) {
            \Log::error('No se pudo decodificar secreto reCAPTCHA: '.$e->getMessage());
            return false;
        }

        # SE VALIDA CONTRA GOOGLE
        $payload = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => \Input::real_ip(),
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);

        $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($response === false) {
            \Log::warning('No se pudo verificar reCAPTCHA con Google.');
            return false;
        }

        $json = json_decode($response, true);
        return is_array($json) && !empty($json['success']);
    }

    /**
     * INTEGRATION
     *
     * OBTIENE UNA INTEGRACION WEB ACTIVA POR CODIGO.
     *
     * @access  public
     * @return  Model_Core_Web_Integration|null
     */
    public static function integration($code)
    {
        # SE VALIDA TABLA ANTES DE CONSULTAR
        if (!\DBUtil::table_exists('core_web_integrations')) {
            return null;
        }

        return Model_Core_Web_Integration::query()
            ->where('code', (string) $code)
            ->where('enabled', 1)
            ->get_one();
    }

    /**
     * RENDER INTEGRATIONS
     *
     * RENDERIZA INTEGRACIONES GENERICAS DEL FRONTEND.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_integrations(array $types, $position)
    {
        # SE VALIDA TABLA ANTES DE CONSULTAR
        if (!\DBUtil::table_exists('core_web_integrations')) {
            return '';
        }

        $html = [];
        $items = Model_Core_Web_Integration::query()
            ->where('enabled', 1)
            ->where('load_in_frontend', 1)
            ->where('integration_type', 'in', $types)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();

        foreach ($items as $item) {
            if (!self::consent_allows($item)) {
                continue;
            }

            $rendered = self::render_integration($item, $position);
            if ($rendered !== '') {
                $html[] = $rendered;
            }
        }

        return implode("\n", $html);
    }

    /**
     * CONSENT ALLOWS
     *
     * VERIFICA SI LA INTEGRACION PUEDE CARGARSE SEGUN COOKIES.
     *
     * @access  protected
     * @return  Bool
     */
    protected static function consent_allows(Model_Core_Web_Integration $item)
    {
        # SI NO REQUIERE CONSENTIMIENTO, SE PERMITE
        if ((int) $item->requires_consent === 0) {
            return true;
        }

        # SE USA LEGAL COMO FUENTE DE PREFERENCIAS
        if (!class_exists('Helper_Core_Legal')) {
            return false;
        }

        return Helper_Core_Legal::has_cookie_category((string) $item->consent_category);
    }

    /**
     * RENDER INTEGRATION
     *
     * GENERA HTML SEGURO PARA INTEGRACIONES SOPORTADAS.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_integration(Model_Core_Web_Integration $item, $position)
    {
        # SE RESUELVE POR TIPO/CODIGO
        switch ((string) $item->code) {
            case 'google_analytics':
                return self::render_google_analytics($item);
            case 'google_tag_manager':
                return self::render_google_tag_manager($item, $position);
            case 'meta_pixel':
                return self::render_meta_pixel($item);
            case 'whatsapp_click_chat':
                return self::render_whatsapp_click_chat($item);
            case 'meta_messenger':
                return self::render_meta_messenger($item);
            default:
                return self::render_custom_script($item);
        }
    }

    /**
     * RENDER GOOGLE ANALYTICS
     *
     * GENERA SCRIPT GA4 A PARTIR DE LA LLAVE PUBLICA.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_google_analytics(Model_Core_Web_Integration $item)
    {
        # SE REQUIERE ID GA4
        $id = trim((string) ($item->public_key ?: $item->public_value));
        if ($id === '') {
            return '';
        }

        $id = e($id);
        return '<script async src="https://www.googletagmanager.com/gtag/js?id='.$id.'"></script>'."\n"
            .'<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","'.$id.'");</script>';
    }

    /**
     * RENDER GOOGLE TAG MANAGER
     *
     * GENERA TAG MANAGER EN HEAD O NOSCRIPT EN BODY.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_google_tag_manager(Model_Core_Web_Integration $item, $position)
    {
        # SE REQUIERE ID GTM
        $id = trim((string) ($item->public_key ?: $item->public_value));
        if ($id === '') {
            return '';
        }

        $id = e($id);
        if ($position === 'body') {
            return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$id.'" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
        }

        return '<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";j.async=true;j.src="https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);})(window,document,"script","dataLayer","'.$id.'");</script>';
    }

    /**
     * RENDER META PIXEL
     *
     * GENERA META PIXEL A PARTIR DEL ID PUBLICO.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_meta_pixel(Model_Core_Web_Integration $item)
    {
        # SE REQUIERE ID PIXEL
        $id = trim((string) ($item->public_key ?: $item->public_value));
        if ($id === '') {
            return '';
        }

        $id = e($id);
        return '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init","'.$id.'");fbq("track","PageView");</script>'
            .'<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='.$id.'&ev=PageView&noscript=1"></noscript>';
    }

    /**
     * RENDER WHATSAPP CLICK CHAT
     *
     * GENERA BOTON FLOTANTE OFICIAL WA.ME SIN API NI COOKIES DE TERCEROS.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_whatsapp_click_chat(Model_Core_Web_Integration $item)
    {
        # SE REQUIERE NUMERO EN FORMATO INTERNACIONAL SIN SIGNOS
        $phone = preg_replace('/\D+/', '', (string) ($item->public_key ?: $item->public_value));
        if ($phone === '') {
            return '';
        }

        # SETTINGS_JSON PERMITE PERSONALIZAR TEXTO Y POSICION SIN TOCAR TEMPLATE
        $settings = json_decode((string) $item->settings_json, true);
        $settings = is_array($settings) ? $settings : [];
        $label = trim((string) \Arr::get($settings, 'label', 'WhatsApp'));
        $message = trim((string) \Arr::get($settings, 'message', 'Hola, quiero informacion.'));
        $side = \Arr::get($settings, 'side', 'right') === 'left' ? 'left' : 'right';
        $bottom = max(16, min(160, (int) \Arr::get($settings, 'bottom', 24)));
        $url = 'https://wa.me/'.$phone;
        if ($message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return '<style>.core-contact-float{position:fixed;'.$side.':22px;bottom:'.$bottom.'px;z-index:9990;display:inline-flex;align-items:center;gap:9px;padding:11px 15px;border-radius:999px;background:#25d366;color:#102014;font-weight:800;box-shadow:0 16px 34px rgba(15,23,42,.18);border:1px solid rgba(0,0,0,.08)}.core-contact-float:hover{color:#102014;filter:brightness(.98);transform:translateY(-1px)}.core-contact-float i{font-size:1.25rem}@media(max-width:560px){.core-contact-float{'.$side.':16px;bottom:18px;padding:12px}.core-contact-float span{display:none}}</style>'
            .'<a class="core-contact-float" href="'.e($url).'" target="_blank" rel="noopener noreferrer" aria-label="'.e($label).'"><i class="bi bi-whatsapp"></i><span>'.e($label).'</span></a>';
    }

    /**
     * RENDER META MESSENGER
     *
     * GENERA CUSTOMER CHAT PLUGIN DE META SOLO CON CONSENTIMIENTO DE MARKETING.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_meta_messenger(Model_Core_Web_Integration $item)
    {
        # SE REQUIERE PAGE ID DE FACEBOOK
        $page_id = preg_replace('/[^0-9]/', '', (string) $item->public_key);
        if ($page_id === '') {
            return '';
        }

        # SETTINGS_JSON CONTROLA LOCALES Y ATRIBUCION
        $settings = json_decode((string) $item->settings_json, true);
        $settings = is_array($settings) ? $settings : [];
        $locale = preg_replace('/[^a-zA-Z_]/', '', (string) \Arr::get($settings, 'locale', 'es_LA'));
        $attribution = trim((string) \Arr::get($settings, 'attribution', 'biz_inbox'));
        $version = preg_replace('/[^v0-9.]/', '', (string) \Arr::get($settings, 'version', 'v20.0'));

        return '<div id="fb-root"></div><div id="fb-customer-chat" class="fb-customerchat" attribution="'.e($attribution).'" page_id="'.e($page_id).'"></div>'
            .'<script>window.fbAsyncInit=function(){FB.init({xfbml:true,version:"'.e($version).'"});};(function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(d.getElementById(id)){return;}js=d.createElement(s);js.id=id;js.src="https://connect.facebook.net/'.e($locale).'/sdk/xfbml.customerchat.js";fjs.parentNode.insertBefore(js,fjs);}(document,"script","facebook-jssdk"));</script>';
    }

    /**
     * RENDER CUSTOM SCRIPT
     *
     * GENERA SCRIPT CUSTOM CONTROLADO DESDE SETTINGS_JSON.
     *
     * @access  protected
     * @return  String
     */
    protected static function render_custom_script(Model_Core_Web_Integration $item)
    {
        # SOLO SE PERMITE SCRIPT URL EXTERNO CAPTURADO COMO PUBLIC_VALUE
        $url = trim((string) $item->public_value);
        if ($url === '' || !preg_match('/^https:\/\//i', $url)) {
            return '';
        }

        return '<script src="'.e($url).'" async></script>';
    }
}
