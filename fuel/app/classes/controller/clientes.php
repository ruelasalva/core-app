<?php

class Controller_Clientes extends Controller_Portalbase
{
    protected $portal_code = 'clientes';

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
        $this->template->title = 'Clientes';
        $this->template->content = View::forge('portal/dashboard', ['portal_label' => 'Clientes']);
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

    protected function customer_cfdi($party_id)
    {
        if (!\DBUtil::table_exists('core_sat_cfdi')) {
            return [];
        }

        $items = [];
        $rows = \DB::select('id', 'uuid', 'voucher_type', 'serie', 'folio', 'issued_at', 'currency', 'subtotal', 'tax_transferred_total', 'tax_withheld_total', 'total', 'sat_status', 'sales_status', 'has_payment_complement', 'has_waybill')
            ->from('core_sat_cfdi')
            ->where('customer_party_id', '=', (int) $party_id)
            ->where('portal_visible_customer', '=', 1)
            ->order_by('issued_at', 'desc')
            ->limit(200)
            ->execute();

        foreach ($rows as $row) {
            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y', strtotime($row['issued_at'])) : '';
            $items[] = $row;
        }
        return $items;
    }
}
