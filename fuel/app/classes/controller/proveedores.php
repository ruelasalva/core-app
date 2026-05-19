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
            ]));
        } catch (\Exception $e) {
            return \Response::forge(View::forge('portal/supplier_register', [
                'action' => Uri::create('proveedores/registro_submit'),
                'error' => $e->getMessage(),
                'success' => '',
                'values' => $values,
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
        $this->template->content = View::forge('portal/dashboard', ['portal_label' => 'Proveedores']);
    }

    public function action_cfdi()
    {
        $this->template->title = 'CFDI recibidos';
        $this->template->content = View::forge('portal/cfdi', [
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

    protected function admin_user_ids()
    {
        $ids = [];
        foreach (\DB::select('id')->from('users')->where('group_id', '>=', 70)->execute() as $row) {
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }
}
