<?php

/**
 * CONTROLADOR PROVEEDORES
 *
 * Entrada principal del portal de proveedores.
 *
 * @package  app
 * @extends  Controller_Proveedores_Compras
 */
class Controller_Proveedores extends Controller_Proveedores_Compras
{
    public function before()
    {
        $action = \Request::active() ? \Request::active()->action : '';
        if (in_array($action, ['registro', 'registro_submit'], true)) {
            $this->auto_render = false;
            return;
        }
        parent::before();
    }

    public function action_registro()
    {
        return \Response::forge(View::forge('portal/supplier_register', [
            'action' => Uri::create('proveedores/registro_submit'),
            'error' => '',
            'success' => '',
            'values' => [],
            'sat_tax_regimes' => Helper_Core_Sat_Catalog::options('core_sat_tax_regimes'),
        ]));
    }

    public function post_registro_submit()
    {
        return $this->action_registro_submit();
    }

    public function action_registro_submit()
    {
        $values = [
            'name' => trim((string) \Input::post('name', '')),
            'legal_name' => trim((string) \Input::post('legal_name', '')),
            'rfc' => strtoupper(trim((string) \Input::post('rfc', ''))),
            'email' => trim((string) \Input::post('email', '')),
            'phone' => trim((string) \Input::post('phone', '')),
            'sat_tax_regime_code' => trim((string) \Input::post('sat_tax_regime_code', '')),
            'business_line' => trim((string) \Input::post('business_line', '')),
            'notes' => trim((string) \Input::post('notes', '')),
        ];

        try {
            if ($values['name'] === '' || $values['legal_name'] === '' || $values['rfc'] === '' || $values['email'] === '') {
                throw new \RuntimeException('Nombre, razon social, RFC y correo son obligatorios.');
            }
            if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Captura un correo valido.');
            }
            if (\DB::select('id')->from('core_parties')->where('rfc', '=', $values['rfc'])->execute()->current()) {
                throw new \RuntimeException('Ya existe un socio comercial con ese RFC.');
            }
            if (!\DBUtil::field_exists('core_parties', ['onboarding_status'])) {
                throw new \RuntimeException('El alta de proveedores requiere ejecutar migraciones.');
            }

            $party = Model_Core_Party::forge([
                'party_type' => 'supplier',
                'code' => $this->codeify($values['rfc']),
                'name' => $values['name'],
                'legal_name' => $values['legal_name'],
                'rfc' => $values['rfc'],
                'email' => $values['email'],
                'phone' => $values['phone'],
                'department_id' => 0,
                'sales_user_id' => 0,
                'buyer_user_id' => 0,
                'price_list_id' => 0,
                'payment_term_id' => 0,
                'sat_cfdi_use_code' => 'G03',
                'sat_tax_regime_code' => $values['sat_tax_regime_code'] ?: '601',
                'fiscal_operation_type_id' => 0,
                'shipping_method_id' => 0,
                'credit_limit' => 0,
                'credit_days' => 0,
                'notes' => 'Solicitud portal proveedor. Giro: '.$values['business_line']."\n".$values['notes'],
                'onboarding_status' => 'pending',
                'onboarding_notes' => 'Solicitud recibida desde portal proveedores.',
                'reviewed_by' => 0,
                'reviewed_at' => 0,
                'active' => 0,
            ]);
            $party->save();

            Helper_Core_Audit::log([
                'module' => 'parties',
                'action' => 'supplier_portal_request',
                'business_event' => 'parties.supplier_portal_request',
                'entity_type' => 'party',
                'entity_id' => (int) $party->id,
                'table_name' => 'core_parties',
                'portal_code' => $this->portal_code,
                'backend' => 'portal',
                'summary' => 'Solicitud de proveedor '.$party->name,
                'new_values' => $party->to_array(),
            ]);

            Helper_Core_Notification::create([
                'event_code' => 'parties.supplier_portal_request',
                'notification_type' => 'parties',
                'title' => 'Nueva solicitud de proveedor',
                'message' => $party->name.' solicito alta como proveedor.',
                'url' => \Uri::create('admin/parties'),
                'icon' => 'bi bi-building-add',
                'priority' => 2,
                'created_by' => 0,
            ], $this->admin_user_ids());

            return \Response::forge(View::forge('portal/supplier_register', [
                'action' => Uri::create('proveedores/registro_submit'),
                'error' => '',
                'success' => 'Solicitud recibida. Nuestro equipo revisara tu informacion y activara el portal cuando sea aprobada.',
                'values' => [],
                'sat_tax_regimes' => Helper_Core_Sat_Catalog::options('core_sat_tax_regimes'),
            ]));
        } catch (\Exception $e) {
            return \Response::forge(View::forge('portal/supplier_register', [
                'action' => Uri::create('proveedores/registro_submit'),
                'error' => $e->getMessage(),
                'success' => '',
                'values' => $values,
                'sat_tax_regimes' => Helper_Core_Sat_Catalog::options('core_sat_tax_regimes'),
            ]), 400);
        }
    }

    /**
     * INDEX
     *
     * MUESTRA DASHBOARD DEL PORTAL DE PROVEEDORES
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Proveedores';
        $this->template->content = $this->portal_view('dashboard', 'portales/dashboard/index', [
            'portal_code' => $this->portal_code,
            'portal_label' => 'Proveedores',
        ]);
    }

    public function action_cfdi()
    {
        $this->template->title = 'CFDI recibidos';
        $this->template->content = $this->portal_view('cfdi', 'portales/cfdi/index', [
            'portal_code' => $this->portal_code,
            'portal_direction' => 'supplier',
            'portal_title' => 'CFDI de proveedor',
        ]);
    }

    public function action_cfdi_data()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            return $this->json_response([
                'items' => $this->supplier_cfdi($party_id),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando CFDI portal proveedores: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar CFDI.'], 500);
        }
    }

    /**
     * CONTRACTS
     *
     * Muestra contratos visibles para el proveedor autenticado en modo solo lectura.
     *
     * @access  public
     * @return  Void
     */
    public function action_contracts()
    {
        $this->template->title = 'Mis Contratos';
        $this->template->content = View::forge('proveedores/contracts/index', [
            'party' => $this->party,
        ]);
    }

    /**
     * CONTRACTS DATA
     *
     * Entrega contratos, documentos y eventos visibles para el proveedor actual.
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
                    'contracts' => $this->supplier_contracts($party_id),
                    'documents' => $this->supplier_contract_documents($party_id),
                    'events' => $this->supplier_contract_events($party_id),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando contratos portal proveedores: '.$e->getMessage());
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
     * Descarga documentos de contratos solo si pertenecen al proveedor actual y
     * el contrato esta visible en portal.
     *
     * @access  public
     * @return  Response
     */
    public function action_contracts_document_download($document_id = 0)
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            $document = $this->supplier_contract_document_by_id((int) $document_id, $party_id);
            if (!$document) {
                \Log::warning('Portal proveedores: intento de descarga de documento de contrato no autorizado document_id='.(int) $document_id.' party_id='.$party_id);
                throw new \HttpNotFoundException;
            }

            return $this->download_portal_document($document);
        } catch (\HttpNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error descargando documento contrato portal proveedores: '.$e->getMessage());
            throw new \HttpNotFoundException;
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

    protected function supplier_cfdi($party_id)
    {
        if (!\DBUtil::table_exists('core_sat_cfdi')) {
            return [];
        }

        $items = [];
        $rows = \DB::select('id', 'uuid', 'voucher_type', 'serie', 'folio', 'issued_at', 'currency', 'subtotal', 'tax_transferred_total', 'tax_withheld_total', 'total', 'sat_status', 'purchase_status', 'has_payment_complement', 'has_waybill')
            ->from('core_sat_cfdi')
            ->where('supplier_party_id', '=', (int) $party_id)
            ->where('portal_visible_supplier', '=', 1)
            ->order_by('issued_at', 'desc')
            ->limit(200)
            ->execute();

        foreach ($rows as $row) {
            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y', strtotime($row['issued_at'])) : '';
            $items[] = $row;
        }
        return $items;
    }

    /**
     * SUPPLIER CONTRACTS
     *
     * Obtiene contratos del tercero proveedor con visibilidad publica para portal.
     *
     * @access  protected
     * @return  Array
     */
    protected function supplier_contracts($party_id)
    {
        if (!$this->supplier_contracts_schema_ready()) {
            \Log::warning('Portal proveedores: tablas de contratos no disponibles para party_id='.(int) $party_id);
            return [];
        }

        $type_labels = $this->supplier_contract_type_labels();
        $manager = new \Service_Core_Contracts_Manager();
        $contracts = [];

        $rows = \Model_Core_Contract::query()
            ->where('party_id', '=', (int) $party_id)
            ->where('visibility', '=', 'portal')
            ->where('active', '=', 1)
            ->where_open()
                ->where('portal_code', '=', 'proveedores')
                ->or_where('portal_code', '=', '')
                ->or_where('portal_code', 'IS', \DB::expr('NULL'))
            ->where_close()
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
                'start_label' => $this->supplier_contract_date_label((string) $contract->start_date),
                'end_date' => (string) $contract->end_date,
                'end_label' => $this->supplier_contract_date_label((string) $contract->end_date),
                'status' => (string) $contract->status,
                'status_label' => $this->supplier_contract_status_label((string) $contract->status),
                'expiration_status' => $expiration,
                'expiration_label' => $this->supplier_contract_expiration_label($expiration),
                'expiration_days' => $days,
                'expiration_days_label' => $this->supplier_contract_expiration_days_label($days, $expiration),
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
     * SUPPLIER CONTRACT DOCUMENTS
     *
     * Lista documentos vinculados a contratos visibles del proveedor sin exponer rutas.
     *
     * @access  protected
     * @return  Array
     */
    protected function supplier_contract_documents($party_id)
    {
        if (!$this->supplier_contracts_documents_schema_ready()) {
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
            ->where_open()
                ->where('c.portal_code', '=', 'proveedores')
                ->or_where('c.portal_code', '=', '')
                ->or_where('c.portal_code', 'IS', \DB::expr('NULL'))
            ->where_close()
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
                'relation_label' => $this->supplier_contract_document_relation_label((string) $row['relation_type']),
                'title' => (string) $row['title'],
                'original_name' => (string) $row['original_name'],
                'file_extension' => (string) $row['file_extension'],
                'file_size' => (int) $row['file_size'],
                'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', (int) $row['created_at']) : '',
                'download_url' => \Uri::create('proveedores/contracts_document_download/'.(int) $row['document_id']),
            ];
        }

        return $documents;
    }

    /**
     * SUPPLIER CONTRACT EVENTS
     *
     * Lista eventos historicos de contratos visibles para el proveedor.
     *
     * @access  protected
     * @return  Array
     */
    protected function supplier_contract_events($party_id)
    {
        if (!$this->supplier_contracts_schema_ready()) {
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
            ->where_open()
                ->where('c.portal_code', '=', 'proveedores')
                ->or_where('c.portal_code', '=', '')
                ->or_where('c.portal_code', 'IS', \DB::expr('NULL'))
            ->where_close()
            ->order_by('e.id', 'desc')
            ->limit(500)
            ->execute();

        $events = [];
        foreach ($rows as $row) {
            $events[] = [
                'id' => (int) $row['id'],
                'contract_id' => (int) $row['contract_id'],
                'event_type' => (string) $row['event_type'],
                'event_label' => $this->supplier_contract_event_label((string) $row['event_type']),
                'old_status' => (string) $row['old_status'],
                'old_status_label' => $this->supplier_contract_status_label((string) $row['old_status']),
                'new_status' => (string) $row['new_status'],
                'new_status_label' => $this->supplier_contract_status_label((string) $row['new_status']),
                'message' => (string) $row['message'],
                'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', (int) $row['created_at']) : '',
            ];
        }

        return $events;
    }

    /**
     * SUPPLIER CONTRACT DOCUMENT BY ID
     *
     * Busca un documento descargable asegurando contrato visible y propio.
     *
     * @access  protected
     * @return  Array|null
     */
    protected function supplier_contract_document_by_id($document_id, $party_id)
    {
        if ($document_id < 1 || !$this->supplier_contracts_documents_schema_ready()) {
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
            ->where_open()
                ->where('c.portal_code', '=', 'proveedores')
                ->or_where('c.portal_code', '=', '')
                ->or_where('c.portal_code', 'IS', \DB::expr('NULL'))
            ->where_close()
            ->where('l.entity_type', '=', 'contract')
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->execute()
            ->current();
    }

    protected function supplier_contract_type_labels()
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

    protected function supplier_contracts_schema_ready()
    {
        return \DBUtil::table_exists('core_contracts')
            && \DBUtil::table_exists('core_contract_types')
            && \DBUtil::table_exists('core_contract_events');
    }

    protected function supplier_contracts_documents_schema_ready()
    {
        return $this->supplier_contracts_schema_ready()
            && \DBUtil::table_exists('core_documents')
            && \DBUtil::table_exists('core_document_links');
    }

    protected function supplier_contract_date_label($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return '-';
        }

        $time = strtotime($date);
        return $time ? date('d/m/Y', $time) : $date;
    }

    protected function supplier_contract_status_label($status)
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

    protected function supplier_contract_expiration_label($status)
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

    protected function supplier_contract_expiration_days_label($days, $status)
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

    protected function supplier_contract_document_relation_label($relation_type)
    {
        $labels = [
            'main_contract' => 'Contrato principal',
            'annex' => 'Anexo',
            'evidence' => 'Evidencia',
            'signed_document' => 'Documento firmado',
        ];

        return \Arr::get($labels, (string) $relation_type, (string) $relation_type);
    }

    protected function supplier_contract_event_label($event_type)
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

    protected function admin_user_ids()
    {
        $ids = [];
        foreach (\DB::select('id')->from('users')->where('group_id', '>=', 70)->execute() as $row) {
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }
}
