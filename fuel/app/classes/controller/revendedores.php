<?php

/**
 * CONTROLADOR REVENDEDORES
 *
 * Entrada principal del portal de revendedores.
 *
 * @package  app
 * @extends  Controller_Revendedores_Clientes
 */
class Controller_Revendedores extends Controller_Revendedores_Clientes
{
    /**
     * INDEX
     *
     * MUESTRA DASHBOARD DEL PORTAL DE REVENDEDORES
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Revendedores';
        $this->template->content = $this->portal_view('dashboard', 'portales/dashboard/index', ['portal_label' => 'Revendedores']);
    }

    /**
     * HELPDESK
     *
     * DELEGA PANEL DE TICKETS AL CONTROLADOR BASE DE PORTALES
     *
     * @access  public
     * @return  Void
     */
    public function action_helpdesk()
    {
        return parent::action_helpdesk();
    }

    /**
     * HELPDESK DATA
     *
     * DELEGA LECTURA DE TICKETS AL CONTROLADOR BASE DE PORTALES
     *
     * @access  public
     * @return  Response
     */
    public function action_helpdesk_data()
    {
        return parent::action_helpdesk_data();
    }

    /**
     * HELPDESK CREATE
     *
     * DELEGA CREACION DE TICKETS AL CONTROLADOR BASE DE PORTALES
     *
     * @access  public
     * @return  Response
     */
    public function post_helpdesk_create()
    {
        return parent::post_helpdesk_create();
    }

    /**
     * HELPDESK REPLY
     *
     * DELEGA RESPUESTAS DE TICKETS AL CONTROLADOR BASE DE PORTALES
     *
     * @access  public
     * @return  Response
     */
    public function post_helpdesk_reply()
    {
        return parent::post_helpdesk_reply();
    }

    /**
     * HELPDESK UPLOAD
     *
     * DELEGA CARGA DE ADJUNTOS AL CONTROLADOR BASE DE PORTALES
     *
     * @access  public
     * @return  Response
     */
    public function post_helpdesk_upload()
    {
        return parent::post_helpdesk_upload();
    }

}
