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
        $this->template->content = $this->portal_view('dashboard', 'portales/dashboard/index', [
            'portal_code' => $this->portal_code,
            'portal_label' => 'Socios',
        ]);
    }

}
