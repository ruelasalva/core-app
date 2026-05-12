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
        return (bool) Model_Core_Party_User_Link::query()
            ->where('user_id', $user_id)
            ->where('portal_code', $portal_code)
            ->where('active', 1)
            ->get_one();
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
}
