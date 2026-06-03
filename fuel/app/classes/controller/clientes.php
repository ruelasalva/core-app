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
        $this->template->content = View::forge('clientes/dashboard/index', [
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
                'helpdesk_stats' => $this->portal_helpdesk_stats(),
                'options' => $this->customer_options($party_id),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando portal clientes: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar el portal.'], 500);
        }
    }

    public function action_cfdi()
    {
        $this->template->title = 'Centro CFDI';
        $this->template->content = $this->portal_view('cfdi', 'clientes/cfdi/index', [
            'portal_code' => $this->portal_code,
            'portal_direction' => 'customer',
            'portal_title' => 'Centro CFDI',
        ]);
    }

    public function action_cfdi_data()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            $filters = [
                'date_from' => \Input::get('date_from', ''),
                'date_to' => \Input::get('date_to', ''),
                'uuid' => \Input::get('uuid', ''),
                'serie_folio' => \Input::get('serie_folio', ''),
                'sat_status' => \Input::get('sat_status', ''),
                'voucher_type' => \Input::get('voucher_type', ''),
            ];

            return $this->json_response([
                'items' => $this->customer_cfdi($party_id, $filters),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando CFDI portal clientes: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar CFDI.'], 500);
        }
    }

    /**
     * ACCOUNT
     *
     * Muestra el estado de cuenta del cliente autenticado en el portal.
     *
     * @access  public
     * @return  Void
     */
    public function action_account()
    {
        $this->template->title = 'Estado de cuenta';
        $this->template->content = View::forge('clientes/account/index', [
            'party' => $this->party,
        ]);
    }

    /**
     * ACCOUNT DATA
     *
     * Entrega estado de cuenta en modo solo lectura. No acepta party_id externo.
     *
     * @access  public
     * @return  Response
     */
    public function action_account_data()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            $filters = [
                'date_from' => \Input::get('date_from', ''),
                'date_to' => \Input::get('date_to', ''),
                'status' => \Input::get('status', 'all'),
                'folio' => \Input::get('folio', ''),
                'currency' => \Input::get('currency', ''),
            ];

            return $this->json_response([
                'account' => $this->customer_account($party_id, $filters),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando estado de cuenta portal clientes: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar el estado de cuenta.'], 500);
        }
    }

    /**
     * CFDI XML DOWNLOAD
     *
     * Descarga XML del CFDI visible para el cliente actual.
     *
     * @access  public
     * @return  Response
     */
    public function action_cfdi_xml_download($cfdi_id = 0)
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            $cfdi = $this->customer_cfdi_by_id((int) $cfdi_id, $party_id);
            if (!$cfdi) {
                throw new \HttpNotFoundException;
            }

            $path = $this->resolve_customer_cfdi_file_path((string) $cfdi['xml_path']);
            if ($path === '') {
                throw new \HttpNotFoundException;
            }

            return $this->download_customer_cfdi_file($path, $this->cfdi_filename($cfdi, 'xml'), 'application/xml');
        } catch (\HttpNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error descargando XML CFDI portal clientes: '.$e->getMessage());
            throw new \HttpNotFoundException;
        }
    }

    /**
     * CFDI PDF DOWNLOAD
     *
     * Descarga PDF de la factura ligada al CFDI visible para el cliente actual.
     *
     * @access  public
     * @return  Response
     */
    public function action_cfdi_pdf_download($cfdi_id = 0)
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            $cfdi = $this->customer_cfdi_by_id((int) $cfdi_id, $party_id);
            if (!$cfdi) {
                throw new \HttpNotFoundException;
            }

            $invoice = $this->customer_invoice_for_cfdi($cfdi, $party_id);
            if (!$invoice || trim((string) \Arr::get($invoice, 'pdf_path', '')) === '') {
                return $this->json_response([
                    'success' => false,
                    'message' => 'El PDF de este CFDI no esta disponible.',
                    'errors' => ['PDF no disponible.'],
                ], 404);
            }

            $path = $this->resolve_customer_cfdi_file_path((string) $invoice['pdf_path']);
            if ($path === '') {
                return $this->json_response([
                    'success' => false,
                    'message' => 'El archivo PDF no fue encontrado.',
                    'errors' => ['PDF no encontrado.'],
                ], 404);
            }

            return $this->download_customer_cfdi_file($path, $this->cfdi_filename($cfdi, 'pdf'), 'application/pdf');
        } catch (\HttpNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error descargando PDF CFDI portal clientes: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo descargar el PDF.',
                'errors' => ['Error controlado de descarga.'],
            ], 404);
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
