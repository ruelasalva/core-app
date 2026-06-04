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
     * CONTRACTS
     *
     * Muestra contratos visibles para el cliente autenticado en modo solo lectura.
     *
     * @access  public
     * @return  Void
     */
    public function action_contracts()
    {
        $this->template->title = 'Mis Contratos';
        $this->template->content = View::forge('clientes/contracts/index', [
            'party' => $this->party,
        ]);
    }

    /**
     * CONTRACTS DATA
     *
     * Entrega contratos, documentos y eventos visibles para el cliente actual.
     * No acepta party_id externo; la tenencia se toma de core_party_user_links.
     *
     * @access  public
     * @return  Response
     */
    public function action_contracts_data()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;

            return $this->json_response([
                'success' => true,
                'message' => 'Contratos cargados.',
                'data' => [
                    'contracts' => $this->customer_contracts($party_id),
                    'documents' => $this->customer_contract_documents($party_id),
                    'events' => $this->customer_contract_events($party_id),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando contratos portal clientes: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudieron cargar los contratos.',
                'data' => [],
                'errors' => ['Error controlado cargando contratos.'],
            ], 500);
        }
    }

    /**
     * CONTRACTS DOCUMENT DOWNLOAD
     *
     * Descarga documentos de contratos solo si pertenecen al cliente actual y el
     * contrato esta visible en portal.
     *
     * @access  public
     * @return  Response
     */
    public function action_contracts_document_download($document_id = 0)
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            $document = $this->customer_contract_document_by_id((int) $document_id, $party_id);
            if (!$document) {
                \Log::warning('Portal clientes: intento de descarga de documento de contrato no autorizado document_id='.(int) $document_id.' party_id='.$party_id);
                throw new \HttpNotFoundException;
            }

            return $this->download_portal_document($document);
        } catch (\HttpNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error descargando documento contrato portal clientes: '.$e->getMessage());
            throw new \HttpNotFoundException;
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

    /**
     * CUSTOMER CONTRACTS
     *
     * Obtiene contratos del tercero del portal con visibilidad publica para portal.
     *
     * @access  protected
     * @return  Array
     */
    protected function customer_contracts($party_id)
    {
        if (!$this->contracts_schema_ready()) {
            \Log::warning('Portal clientes: tablas de contratos no disponibles para party_id='.(int) $party_id);
            return [];
        }

        $type_labels = $this->customer_contract_type_labels();
        $manager = new \Service_Core_Contracts_Manager();
        $contracts = [];

        $rows = \Model_Core_Contract::query()
            ->where('party_id', '=', (int) $party_id)
            ->where('visibility', '=', 'portal')
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(300)
            ->get();

        foreach ($rows as $contract) {
            $expiration = $manager->calculate_expiration_status($contract);
            $days = $manager->calculate_expiration_days($contract);
            $contracts[] = [
                'id' => (int) $contract->id,
                'contract_number' => (string) $contract->contract_number,
                'contract_type' => (string) $contract->contract_type,
                'contract_type_label' => \Arr::get($type_labels, (string) $contract->contract_type, (string) $contract->contract_type),
                'title' => (string) $contract->title,
                'description' => (string) $contract->description,
                'start_date' => (string) $contract->start_date,
                'start_label' => $this->customer_contract_date_label((string) $contract->start_date),
                'end_date' => (string) $contract->end_date,
                'end_label' => $this->customer_contract_date_label((string) $contract->end_date),
                'status' => (string) $contract->status,
                'status_label' => $this->customer_contract_status_label((string) $contract->status),
                'expiration_status' => $expiration,
                'expiration_label' => $this->customer_contract_expiration_label($expiration),
                'expiration_days' => $days,
                'expiration_days_label' => $this->customer_contract_expiration_days_label($days, $expiration),
                'visibility' => (string) $contract->visibility,
                'visibility_label' => 'Visible en portal',
                'contract_value' => (float) $contract->contract_value,
                'currency_code' => (string) $contract->currency_code,
                'renewal_type' => (string) $contract->renewal_type,
                'billing_type' => (string) $contract->billing_type,
                'response_hours' => (float) $contract->response_hours,
                'resolution_hours' => (float) $contract->resolution_hours,
                'notes' => (string) $contract->notes,
            ];
        }

        return $contracts;
    }

    /**
     * CUSTOMER CONTRACT DOCUMENTS
     *
     * Lista documentos vinculados a contratos visibles del cliente sin exponer rutas.
     *
     * @access  protected
     * @return  Array
     */
    protected function customer_contract_documents($party_id)
    {
        if (!$this->contracts_documents_schema_ready()) {
            return [];
        }

        $rows = \DB::select(
                ['c.id', 'contract_id'],
                ['l.id', 'link_id'],
                ['l.relation_type', 'relation_type'],
                ['d.id', 'document_id'],
                ['d.document_type', 'document_type'],
                ['d.title', 'title'],
                ['d.original_name', 'original_name'],
                ['d.file_extension', 'file_extension'],
                ['d.file_size', 'file_size'],
                ['d.created_at', 'created_at']
            )
            ->from(['core_contracts', 'c'])
            ->join(['core_document_links', 'l'], 'inner')->on('l.entity_id', '=', 'c.id')
            ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
            ->where('c.party_id', '=', (int) $party_id)
            ->where('c.visibility', '=', 'portal')
            ->where('c.active', '=', 1)
            ->where('l.entity_type', '=', 'contract')
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->order_by('l.id', 'desc')
            ->limit(500)
            ->execute();

        $documents = [];
        foreach ($rows as $row) {
            $documents[] = [
                'contract_id' => (int) $row['contract_id'],
                'link_id' => (int) $row['link_id'],
                'document_id' => (int) $row['document_id'],
                'document_type' => (string) $row['document_type'],
                'relation_type' => (string) $row['relation_type'],
                'relation_label' => $this->customer_contract_document_relation_label((string) $row['relation_type']),
                'title' => (string) $row['title'],
                'original_name' => (string) $row['original_name'],
                'file_extension' => (string) $row['file_extension'],
                'file_size' => (int) $row['file_size'],
                'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', (int) $row['created_at']) : '',
                'download_url' => \Uri::create('clientes/contracts_document_download/'.(int) $row['document_id']),
            ];
        }

        return $documents;
    }

    /**
     * CUSTOMER CONTRACT EVENTS
     *
     * Lista eventos historicos de contratos visibles para el cliente.
     *
     * @access  protected
     * @return  Array
     */
    protected function customer_contract_events($party_id)
    {
        if (!$this->contracts_schema_ready()) {
            return [];
        }

        $rows = \DB::select(
                ['e.id', 'id'],
                ['e.contract_id', 'contract_id'],
                ['e.event_type', 'event_type'],
                ['e.old_status', 'old_status'],
                ['e.new_status', 'new_status'],
                ['e.message', 'message'],
                ['e.created_at', 'created_at']
            )
            ->from(['core_contract_events', 'e'])
            ->join(['core_contracts', 'c'], 'inner')->on('c.id', '=', 'e.contract_id')
            ->where('c.party_id', '=', (int) $party_id)
            ->where('c.visibility', '=', 'portal')
            ->where('c.active', '=', 1)
            ->order_by('e.id', 'desc')
            ->limit(500)
            ->execute();

        $events = [];
        foreach ($rows as $row) {
            $events[] = [
                'id' => (int) $row['id'],
                'contract_id' => (int) $row['contract_id'],
                'event_type' => (string) $row['event_type'],
                'event_label' => $this->customer_contract_event_label((string) $row['event_type']),
                'old_status' => (string) $row['old_status'],
                'old_status_label' => $this->customer_contract_status_label((string) $row['old_status']),
                'new_status' => (string) $row['new_status'],
                'new_status_label' => $this->customer_contract_status_label((string) $row['new_status']),
                'message' => (string) $row['message'],
                'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', (int) $row['created_at']) : '',
            ];
        }

        return $events;
    }

    /**
     * CUSTOMER CONTRACT DOCUMENT BY ID
     *
     * Busca un documento descargable asegurando contrato visible y propio.
     *
     * @access  protected
     * @return  Array|null
     */
    protected function customer_contract_document_by_id($document_id, $party_id)
    {
        if ($document_id < 1 || !$this->contracts_documents_schema_ready()) {
            return null;
        }

        return \DB::select(['d.id', 'id'], ['d.file_path', 'file_path'], ['d.original_name', 'original_name'], ['d.mime_type', 'mime_type'])
            ->from(['core_contracts', 'c'])
            ->join(['core_document_links', 'l'], 'inner')->on('l.entity_id', '=', 'c.id')
            ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
            ->where('d.id', '=', (int) $document_id)
            ->where('c.party_id', '=', (int) $party_id)
            ->where('c.visibility', '=', 'portal')
            ->where('c.active', '=', 1)
            ->where('l.entity_type', '=', 'contract')
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->execute()
            ->current();
    }

    protected function customer_contract_type_labels()
    {
        if (!\DBUtil::table_exists('core_contract_types')) {
            return [];
        }

        $rows = \Model_Core_Contract_Type::query()
            ->where('active', '=', 1)
            ->get();

        $labels = [];
        foreach ($rows as $type) {
            $labels[(string) $type->code] = (string) $type->name;
        }

        return $labels;
    }

    protected function contracts_schema_ready()
    {
        return \DBUtil::table_exists('core_contracts')
            && \DBUtil::table_exists('core_contract_types')
            && \DBUtil::table_exists('core_contract_events');
    }

    protected function contracts_documents_schema_ready()
    {
        return $this->contracts_schema_ready()
            && \DBUtil::table_exists('core_documents')
            && \DBUtil::table_exists('core_document_links');
    }

    protected function customer_contract_date_label($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return '-';
        }

        $time = strtotime($date);
        return $time ? date('d/m/Y', $time) : $date;
    }

    protected function customer_contract_status_label($status)
    {
        $labels = [
            'draft' => 'Borrador',
            'pending_signature' => 'Pendiente de firma',
            'active' => 'Activo',
            'renewal_pending' => 'Renovacion pendiente',
            'expired' => 'Vencido',
            'terminated' => 'Terminado',
            'cancelled' => 'Cancelado',
            'archived' => 'Archivado',
        ];

        return \Arr::get($labels, (string) $status, (string) $status);
    }

    protected function customer_contract_expiration_label($status)
    {
        if ($status === 'no_end_date') {
            return 'Sin vencimiento';
        }
        if ($status === 'expired') {
            return 'Vencido';
        }
        if (in_array($status, ['expiring_90', 'expiring_60', 'expiring_30'], true)) {
            return 'Por vencer';
        }
        if ($status === 'inactive') {
            return 'No vigente';
        }

        return 'Vigente';
    }

    protected function customer_contract_expiration_days_label($days, $status)
    {
        if ($status === 'no_end_date') {
            return 'Sin fecha fin';
        }
        if ($days === null) {
            return '-';
        }
        if ((int) $days < 0) {
            return abs((int) $days).' dias vencido';
        }

        return (int) $days.' dias restantes';
    }

    protected function customer_contract_document_relation_label($relation_type)
    {
        $labels = [
            'main_contract' => 'Contrato principal',
            'annex' => 'Anexo',
            'evidence' => 'Evidencia',
            'signed_document' => 'Documento firmado',
        ];

        return \Arr::get($labels, (string) $relation_type, (string) $relation_type);
    }

    protected function customer_contract_event_label($event_type)
    {
        $labels = [
            'created' => 'Contrato creado',
            'updated' => 'Contrato actualizado',
            'status_changed' => 'Cambio de estado',
            'document_uploaded' => 'Documento cargado',
            'document_linked' => 'Documento vinculado',
            'document_unlinked' => 'Documento desvinculado',
            'relation_added' => 'Relacion agregada',
            'relation_removed' => 'Relacion removida',
        ];

        return \Arr::get($labels, (string) $event_type, (string) $event_type);
    }

}
