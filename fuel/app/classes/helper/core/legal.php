<?php

/**
 * HELPER CORE_LEGAL
 *
 * Centraliza documentos legales, consentimientos y preferencias de cookies.
 *
 * CONVENCION DE FLAGS:
 * - Cookies: 1 = aceptado, 0 = rechazado
 * - Consentimientos: 1 = aceptado, 0 = rechazado
 */
class Helper_Core_Legal
{
    /**
     * GET COOKIE TOKEN
     *
     * OBTIENE O CREA EL TOKEN PARA VISITANTES ANONIMOS
     *
     * @access  public
     * @return  String
     */
    public static function get_cookie_token()
    {
        # SE BUSCA EL TOKEN EXISTENTE
        $token = \Cookie::get('core_cookie_token');

        # SI NO EXISTE, SE GENERA UNO NUEVO
        if (!$token) {
            $token = sha1(uniqid(mt_rand(), true));
            \Cookie::set('core_cookie_token', $token, 60 * 60 * 24 * 365);
        }

        return $token;
    }

    /**
     * GET COOKIE PREFERENCES
     *
     * OBTIENE LAS PREFERENCIAS DE COOKIES DEL USUARIO O VISITANTE
     *
     * @access  public
     * @return  Model_Core_Web_Cookie_Preference|null
     */
    public static function get_cookie_preferences($user_id = null)
    {
        # SI HAY USUARIO, SE BUSCA POR USER_ID
        if ($user_id) {
            return Model_Core_Web_Cookie_Preference::query()
                ->where('user_id', (int) $user_id)
                ->get_one();
        }

        # SI NO HAY USUARIO, SE BUSCA POR TOKEN ANONIMO
        return Model_Core_Web_Cookie_Preference::query()
            ->where('token', self::get_cookie_token())
            ->get_one();
    }

    /**
     * SAVE COOKIE PREFERENCES
     *
     * GUARDA PREFERENCIAS DE COOKIES PARA USUARIO O VISITANTE
     *
     * @access  public
     * @return  Model_Core_Web_Cookie_Preference
     */
    public static function save_cookie_preferences(array $prefs, $user_id = null)
    {
        # SE NORMALIZAN LOS VALORES RECIBIDOS
        $prefs = self::normalize_cookie_preferences($prefs);

        # SE BUSCA EL REGISTRO EXISTENTE
        $model = self::get_cookie_preferences($user_id);

        # SI NO EXISTE, SE CREA UNO NUEVO
        if (!$model) {
            $model = Model_Core_Web_Cookie_Preference::forge();
            $model->accepted_at = time();
        }

        # SE ASIGNA USUARIO O TOKEN ANONIMO
        if ($user_id) {
            $model->user_id = (int) $user_id;
            $model->token = null;
        } else {
            $model->token = self::get_cookie_token();
        }

        # SE ASIGNAN LAS PREFERENCIAS
        $model->necessary = 1;
        $model->analytics = $prefs['analytics'];
        $model->marketing = $prefs['marketing'];
        $model->personalization = $prefs['personalization'];
        $model->ip_address = \Input::real_ip();
        $model->user_agent = \Input::user_agent();
        $model->updated_at = time();
        $model->save();

        return $model;
    }

    /**
     * NORMALIZE COOKIE PREFERENCES
     *
     * NORMALIZA EL PAYLOAD DE COOKIES A 1 = ACEPTADO, 0 = RECHAZADO
     *
     * @access  public
     * @return  Array
     */
    public static function normalize_cookie_preferences(array $prefs)
    {
        # SE REGRESAN SOLO LAS CATEGORIAS EDITABLES
        return [
            'analytics' => !empty($prefs['analytics']) ? 1 : 0,
            'marketing' => !empty($prefs['marketing']) ? 1 : 0,
            'personalization' => !empty($prefs['personalization']) ? 1 : 0,
        ];
    }

    /**
     * MIGRATE ANONYMOUS TO USER
     *
     * MIGRA LAS PREFERENCIAS ANONIMAS AL USUARIO AUTENTICADO
     *
     * @access  public
     * @return  Bool
     */
    public static function migrate_anonymous_to_user($user_id)
    {
        # VALIDAR USUARIO
        if (!$user_id) {
            return false;
        }

        # BUSCAR REGISTRO ANONIMO ACTUAL
        $anon = Model_Core_Web_Cookie_Preference::query()
            ->where('token', self::get_cookie_token())
            ->get_one();

        if (!$anon) {
            return false;
        }

        # GUARDAR PREFERENCIAS EN EL USUARIO
        self::save_cookie_preferences([
            'analytics' => (int) $anon->analytics,
            'marketing' => (int) $anon->marketing,
            'personalization' => (int) $anon->personalization,
        ], $user_id);

        # ELIMINAR REGISTRO ANONIMO PARA EVITAR DUPLICIDAD
        $anon->delete();

        return true;
    }

    /**
     * HAS COOKIE CATEGORY
     *
     * VERIFICA SI UNA CATEGORIA DE COOKIES ESTA ACEPTADA
     *
     * @access  public
     * @return  Bool
     */
    public static function has_cookie_category($category, $user_id = null)
    {
        # LAS NECESARIAS SIEMPRE ESTAN PERMITIDAS
        if ($category === 'necessary') {
            return true;
        }

        # SE OBTIENEN LAS PREFERENCIAS
        $prefs = self::get_cookie_preferences($user_id);
        if (!$prefs || !isset($prefs->$category)) {
            return false;
        }

        return (int) $prefs->$category === 1;
    }

    /**
     * RENDER COOKIE BANNER
     *
     * GENERA UN BANNER BASICO DE COOKIES PARA FRONTENDS FUTUROS
     *
     * @access  public
     * @return  String
     */
    public static function render_cookie_banner($user_id = null)
    {
        # SI YA HAY PREFERENCIAS, NO SE MUESTRA EL BANNER
        if (self::get_cookie_preferences($user_id)) {
            return '';
        }

        # SE PREPARA LA URL DEL ENDPOINT
        $endpoint = \Uri::create('legal/cookies/accept');

        # SE REGRESA HTML AUTOCONTENIDO PARA FRONTEND
        return <<<HTML
<div id="core-cookie-banner" style="position:fixed;left:0;right:0;bottom:0;z-index:9999;background:#1f2937;color:#fff;padding:16px;">
    <div style="max-width:1100px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
        <div>
            <strong>Preferencias de cookies</strong><br>
            <span>Usamos cookies necesarias y, con tu permiso, cookies de analitica y marketing.</span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" data-core-cookies="necessary" style="padding:8px 12px;">Solo necesarias</button>
            <button type="button" data-core-cookies="all" style="padding:8px 12px;">Aceptar todas</button>
        </div>
    </div>
</div>
<script>
(function() {
    var banner = document.getElementById('core-cookie-banner');
    if (!banner) return;

    function saveCookies(mode) {
        var payload = mode === 'all'
            ? { analytics: 1, marketing: 1, personalization: 1 }
            : { analytics: 0, marketing: 0, personalization: 0 };

        fetch('{$endpoint}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function() {
            banner.parentNode.removeChild(banner);
        });
    }

    banner.querySelectorAll('[data-core-cookies]').forEach(function(button) {
        button.addEventListener('click', function() {
            saveCookies(button.getAttribute('data-core-cookies'));
        });
    });
})();
</script>
HTML;
    }

    /**
     * REGISTER CONSENT
     *
     * REGISTRA LA ACEPTACION O RECHAZO DE UN DOCUMENTO LEGAL
     *
     * @access  public
     * @return  Model_Core_User_Consent
     */
    public static function register_consent($user_id, $document_id, $accepted = true, array $extra = [], $channel = 'web')
    {
        # SE OBTIENE EL DOCUMENTO LEGAL
        $document = Model_Core_Legal_Document::find((int) $document_id);

        # SE CREA EL CONSENTIMIENTO
        $consent = Model_Core_User_Consent::forge([
            'user_id' => (int) $user_id,
            'document_id' => (int) $document_id,
            'version' => $document ? $document->version : '1.0',
            'accepted' => $accepted ? 1 : 0,
            'channel' => $channel,
            'extra_json' => $extra ? json_encode($extra) : null,
            'ip_address' => \Input::real_ip(),
            'user_agent' => \Input::user_agent(),
            'accepted_at' => time(),
        ]);
        $consent->save();

        return $consent;
    }

    /**
     * HAS ACCEPTED
     *
     * VERIFICA SI UN USUARIO ACEPTO LA VERSION ACTIVA DE UN DOCUMENTO
     *
     * @access  public
     * @return  Bool
     */
    public static function has_accepted($user_id, $shortcode)
    {
        # SE BUSCA EL DOCUMENTO ACTIVO
        $document = Model_Core_Legal_Document::active_by_shortcode($shortcode);
        if (!$document) {
            return false;
        }

        # SE BUSCA EL CONSENTIMIENTO VIGENTE
        $consent = Model_Core_User_Consent::query()
            ->where('user_id', (int) $user_id)
            ->where('document_id', (int) $document->id)
            ->where('version', $document->version)
            ->where('accepted', 1)
            ->get_one();

        return (bool) $consent;
    }
}
