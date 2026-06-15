<?php

/**
 * CONTROLADOR AUTH
 *
 * Maneja inicio y cierre de sesion con OrmAuth.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Auth extends Controller
{
    /**
     * LOGIN
     *
     * MUESTRA Y PROCESA EL FORMULARIO DE ACCESO
     *
     * @access  public
     * @return  View
     */
    public function action_login()
    {
        # SI YA HAY SESION ACTIVA, SE REDIRECCIONA AL ADMIN
        if (\Auth::check()) {
            if ($this->password_policy()->must_change($this->current_user_id())) {
                $this->set_password_change_context('admin', 'admin', 'logout');
                \Response::redirect('auth/force_password_change');
            }
            \Response::redirect('admin');
        }

        # SE INICIALIZAN LAS VARIABLES
        $data = [];

        # SI SE ENVIA EL FORMULARIO
        if (\Input::method() == 'POST') {
            $username = \Input::post('username');
            $password = \Input::post('password');

            # ORM AUTH VALIDA CONTRA LA TABLA USERS
            if (\Auth::login($username, $password)) {
                # SE MIGRAN PREFERENCIAS DE COOKIES ANONIMAS AL USUARIO
                $user_id_data = \Auth::get_user_id();
                $user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
                \Helper_Core_Legal::migrate_anonymous_to_user($user_id);

                # SE REGISTRA EL LOGIN EXITOSO
                \Log::info("LOGIN EXITOSO: Usuario {$username} desde " . \Input::ip());
                if ($this->password_policy()->must_change($user_id)) {
                    \Log::warning('Password change requerido al login. user_id='.$user_id.' timestamp='.time());
                    $this->set_password_change_context('admin', 'admin', 'logout');
                    \Response::redirect('auth/force_password_change');
                }
                \Response::redirect('admin');
            } else {
                # SE REGISTRA EL INTENTO FALLIDO
                \Log::warning("FALLO DE LOGIN: Intento con usuario {$username}");
                $data['error'] = 'Credenciales incorrectas.';
            }
        }

        # SE CARGA LA VISTA DE LOGIN
        return \View::forge('auth/login', $data);
    }

    /**
     * FORCE PASSWORD CHANGE
     *
     * Obliga al usuario autenticado a cambiar contrasena antes de entrar al admin.
     *
     * @access  public
     * @return  View|Void
     */
    public function action_force_password_change()
    {
        if (!\Auth::check()) {
            \Response::redirect('login');
        }

        $user_id = $this->current_user_id();
        $redirect_route = $this->password_change_redirect_route();
        $logout_route = $this->password_change_logout_route();

        if (!$this->password_policy()->must_change($user_id)) {
            $this->clear_password_change_context();
            \Response::redirect($redirect_route);
        }

        $data = [
            'error' => null,
            'logout_url' => \Uri::create($logout_route),
        ];

        if (\Input::method() === 'POST') {
            $password = (string) \Input::post('password', '');
            $confirm = (string) \Input::post('password_confirm', '');

            if ($password !== $confirm) {
                $data['error'] = 'La confirmacion no coincide con la nueva contrasena.';
            } else {
                try {
                    $this->password_policy()->change_forced_password($user_id, $password);
                    \Log::warning('Password change forzado completado. user_id='.$user_id.' timestamp='.time());
                    $this->clear_password_change_context();
                    \Response::redirect($redirect_route);
                } catch (\InvalidArgumentException $e) {
                    $data['error'] = $e->getMessage();
                } catch (\Exception $e) {
                    \Log::error('Error en cambio forzado de password user_id='.$user_id.': '.$e->getMessage());
                    $data['error'] = 'No se pudo cambiar la contrasena.';
                }
            }
        }

        return \View::forge('auth/force_password_change', $data);
    }

    /**
     * LOGOUT
     *
     * CIERRA LA SESION ACTUAL
     *
     * @access  public
     * @return  Void
     */
    public function action_logout()
    {
        # SE CIERRA SESION Y SE REDIRECCIONA AL LOGIN
        $this->clear_password_change_context();
        \Auth::logout();
        \Response::redirect('login');
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

    protected function set_password_change_context($context, $redirect_route, $logout_route, $portal_code = '')
    {
        \Session::set('password_change_required', 1);
        \Session::set('password_change_context', (string) $context);
        \Session::set('password_change_redirect', $this->safe_internal_route($redirect_route, 'admin'));
        \Session::set('password_change_logout', $this->safe_internal_route($logout_route, 'logout'));
        \Session::set('password_change_portal_code', (string) $portal_code);
    }

    protected function clear_password_change_context()
    {
        \Session::delete('password_change_required');
        \Session::delete('password_change_context');
        \Session::delete('password_change_redirect');
        \Session::delete('password_change_logout');
        \Session::delete('password_change_portal_code');
    }

    protected function password_change_redirect_route()
    {
        return $this->safe_internal_route(\Session::get('password_change_redirect', 'admin'), 'admin');
    }

    protected function password_change_logout_route()
    {
        return $this->safe_internal_route(\Session::get('password_change_logout', 'logout'), 'logout');
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
