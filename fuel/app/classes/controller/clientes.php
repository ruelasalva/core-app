<?php

/**
 * CONTROLADOR CLIENTES
 *
 * Entrada principal del portal de clientes.
 *
 * @package  app
 * @extends  Controller_Clientes_Cotizaciones
 */
class Controller_Clientes extends Controller_Clientes_Cotizaciones
{
    /**
     * INDEX
     *
     * MUESTRA DASHBOARD DEL PORTAL DE CLIENTES
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Portal clientes';
        $this->template->content = View::forge('portal/clientes', [
            'party' => $this->party,
        ]);
    }

    public function action_data()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            return $this->json_response([
                'stats' => $this->customer_stats($party_id),
                'account' => $this->customer_account($party_id),
                'cfdi' => $this->customer_cfdi($party_id),
                'quotes' => $this->customer_quotes($party_id),
                'orders' => $this->customer_orders($party_id),
                'options' => $this->customer_options($party_id),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando portal clientes: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar el portal.'], 500);
        }
    }

    public function action_cfdi()
    {
        $this->template->title = 'CFDI emitidos';
        $this->template->content = View::forge('portal/cfdi', [
            'portal_code' => $this->portal_code,
            'portal_direction' => 'customer',
            'portal_title' => 'CFDI de cliente',
        ]);
    }

    public function action_cfdi_data()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            return $this->json_response([
                'items' => $this->customer_cfdi($party_id),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando CFDI portal clientes: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar CFDI.'], 500);
        }
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
