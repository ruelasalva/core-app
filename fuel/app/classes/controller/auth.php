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
        \Auth::logout();
        \Response::redirect('login');
    }
}
