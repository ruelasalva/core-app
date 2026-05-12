<?php

/**
 * CONTROLADOR ADMIN_DASHBOARD
 *
 * Muestra el panel principal del administrador.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Dashboard extends Controller_Adminbase
{
    /**
     * INDEX
     *
     * MUESTRA EL DASHBOARD ADMINISTRATIVO
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # REGISTRO DE ACTIVIDAD
        \Log::info('El administrador '.\Auth::get_screen_name().' entro al Dashboard.');

        # SE INICIALIZAN LAS VARIABLES PARA LA VISTA
        $data = [
            'title' => 'Panel de Control Principal',
            'modules' => [
                'config' => $this->is_super_admin || \Auth::has_access('config.access[view]'),
                'web' => $this->is_super_admin || \Auth::has_access('web.access[view]'),
            ],
        ];

        # SE CARGA LA VISTA
        $this->template->title = 'Dashboard';
        $this->template->content = \View::forge('admin/dashboard', $data);
    }
}
