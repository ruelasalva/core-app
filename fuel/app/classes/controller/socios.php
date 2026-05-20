<?php

/**
 * CONTROLADOR SOCIOS
 *
 * Entrada principal del portal de socios.
 *
 * @package  app
 * @extends  Controller_Socios_Helpdesk
 */
class Controller_Socios extends Controller_Socios_Helpdesk
{
    /**
     * INDEX
     *
     * MUESTRA DASHBOARD DEL PORTAL DE SOCIOS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Socios';
        $this->template->content = View::forge('socios/dashboard/index', ['portal_label' => 'Socios']);
    }

}
