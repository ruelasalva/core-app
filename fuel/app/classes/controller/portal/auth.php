<?php

/**
 * CONTROLADOR PORTAL_AUTH
 *
 * Maneja inicio y cierre de sesion para portales externos.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Portal_Auth extends Controller
{
    /**
     * LOGIN
     *
     * AUTENTICA USUARIOS EXTERNOS Y VALIDA VINCULO ACTIVO CON EL PORTAL
     *
     * @access  public
     * @return  View|Void
     */
    public function action_login($portal_code = '')
    {
        # SE NORMALIZA Y VALIDA EL PORTAL
        $portal_code = $this->codeify($portal_code);
        $profile = $this->get_profile($portal_code);
        if (!$profile) {
            throw new \HttpNotFoundException;
        }

        # SI YA HAY SESION ACTIVA, SE VALIDA ACCESO AL PORTAL
        if (\Auth::check()) {
            if ($this->has_portal_access($portal_code)) {
                if ($this->password_policy()->must_change($this->current_user_id())) {
                    $this->set_password_change_context($portal_code, $profile->dashboard_route ?: $portal_code);
                    \Response::redirect('auth/force_password_change');
                }
                \Response::redirect($profile->dashboard_route ?: $portal_code);
            }

            \Auth::logout();
        }

        # SE INICIALIZAN VARIABLES DE VISTA
        $data = [
            'portal' => $profile,
            'action' => $portal_code.'/login',
            'error' => null,
        ];

        # SI SE ENVIA EL FORMULARIO
        if (\Input::method() === 'POST') {
            $username = trim((string) \Input::post('username', ''));
            $password = (string) \Input::post('password', '');

            if (\Auth::login($username, $password) && $this->has_portal_access($portal_code)) {
                # SE MIGRAN PREFERENCIAS LEGALES AL USUARIO
                $user_id_data = \Auth::get_user_id();
                $user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
                \Helper_Core_Legal::migrate_anonymous_to_user($user_id);

                # SE REGISTRA LOGIN EXITOSO
                \Log::info('LOGIN PORTAL: Usuario '.$username.' portal '.$portal_code.' desde '.\Input::ip());
                if ($this->password_policy()->must_change($user_id)) {
                    \Log::warning('Password change requerido en login portal. user_id='.$user_id.' portal='.$portal_code.' timestamp='.time());
                    $this->set_password_change_context($portal_code, $profile->dashboard_route ?: $portal_code);
                    \Response::redirect('auth/force_password_change');
                }
                \Response::redirect($profile->dashboard_route ?: $portal_code);
            }

            # SI NO TIENE ACCESO, SE CIERRA SESION PARA EVITAR QUEDAR EN CONTEXTO INCORRECTO
            \Auth::logout();
            \Log::warning('FALLO LOGIN PORTAL: Usuario '.$username.' portal '.$portal_code);
            $data['error'] = 'Credenciales incorrectas o usuario sin acceso a este portal.';
        }

        # SE CARGA VISTA DE LOGIN EXTERNO
        return \View::forge('auth/portal_login', $data);
    }

    /**
     * LOGOUT
     *
     * CIERRA SESION Y REGRESA AL LOGIN DEL PORTAL
     *
     * @access  public
     * @return  Void
     */
    public function action_logout($portal_code = '')
    {
        # SE CIERRA SESION
        $portal_code = $this->codeify($portal_code);
        $this->clear_password_change_context();
        \Auth::logout();

        # SE REDIRECCIONA AL LOGIN DEL PORTAL O AL LOGIN GENERAL
        if ($portal_code !== '' && $this->get_profile($portal_code)) {
            \Response::redirect($portal_code.'/login');
        }

        \Response::redirect('login');
    }

    /**
     * HAS PORTAL ACCESS
     *
     * VALIDA SI EL USUARIO ACTUAL TIENE VINCULO ACTIVO AL PORTAL
     *
     * @access  protected
     * @return  Bool
     */
    protected function has_portal_access($portal_code)
    {
        # SE OBTIENE USUARIO ACTUAL
        $user_id_data = \Auth::get_user_id();
        $user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
        if ($user_id < 1) {
            return false;
        }

        # SE BUSCA VINCULO ACTIVO
        $link = Model_Core_Party_User_Link::query()
            ->where('user_id', $user_id)
            ->where('portal_code', $portal_code)
            ->where('active', 1)
            ->get_one();

        if (!$link) {
            return false;
        }

        $party = Model_Core_Party::find((int) $link->party_id);
        if (!$party || (int) $party->active !== 1) {
            \Log::warning('LOGIN PORTAL BLOQUEADO: tercero inactivo o inexistente para usuario '.$user_id.' portal '.$portal_code);
            return false;
        }

        if (!$this->party_type_allowed($portal_code, (string) $party->party_type)) {
            \Log::warning('LOGIN PORTAL BLOQUEADO: tipo de tercero '.$party->party_type.' no permitido para portal '.$portal_code.' usuario '.$user_id);
            return false;
        }

        return true;
    }

    /**
     * GET PROFILE
     *
     * OBTIENE EL PERFIL DE PORTAL ACTIVO
     *
     * @access  protected
     * @return  Model_Core_Portal_Profile|null
     */
    protected function get_profile($portal_code)
    {
        if ($portal_code === '') {
            return null;
        }

        return Model_Core_Portal_Profile::query()
            ->where('code', $portal_code)
            ->where('active', 1)
            ->get_one();
    }

    /**
     * PARTY TYPE ALLOWED
     *
     * VALIDA EL TIPO DE TERCERO CONTRA EL PERFIL ACTIVO DEL PORTAL.
     *
     * @access  protected
     * @return  Bool
     */
    protected function party_type_allowed($portal_code, $party_type)
    {
        $profile = $this->get_profile($portal_code);
        if (!$profile) {
            return false;
        }

        $allowed = trim((string) $profile->allowed_party_types);
        if ($allowed === '') {
            return true;
        }

        $types = array_filter(array_map('trim', explode(',', $allowed)));
        return in_array((string) $party_type, $types, true);
    }

    /**
     * CODEIFY
     *
     * NORMALIZA CODIGOS DE PORTAL
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

    protected function current_user_id()
    {
        $user_id_data = \Auth::get_user_id();
        return isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
    }

    protected function password_policy()
    {
        return new \Service_Core_Auth_PasswordPolicy();
    }

    protected function set_password_change_context($portal_code, $redirect_route)
    {
        $portal_code = $this->codeify($portal_code);
        \Session::set('password_change_required', 1);
        \Session::set('password_change_context', 'portal');
        \Session::set('password_change_redirect', $this->safe_internal_route($redirect_route, $portal_code));
        \Session::set('password_change_logout', $portal_code.'/logout');
        \Session::set('password_change_portal_code', $portal_code);
    }

    protected function clear_password_change_context()
    {
        \Session::delete('password_change_required');
        \Session::delete('password_change_context');
        \Session::delete('password_change_redirect');
        \Session::delete('password_change_logout');
        \Session::delete('password_change_portal_code');
    }

    protected function safe_internal_route($route, $fallback)
    {
        $route = trim((string) $route);
        if ($route === '' || preg_match('#^(https?:)?//#i', $route) || strpos($route, '\\') !== false) {
            return $fallback;
        }

        return ltrim($route, '/');
    }
}
