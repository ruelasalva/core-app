<?php

/**
 * CONTROLADOR ADMIN_BILLING
 *
 * Administra facturacion base y preparacion de CFDI.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Billing extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE FACTURACION
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('billing.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE FACTURACION
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Facturacion CFDI';
        $this->template->content = View::forge('admin/billing/index');
    }

    /**
     * DATA
     *
     * ENTREGA FACTURAS, CONCEPTOS Y OPCIONES
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA ESTRUCTURA
            $this->assert_schema_ready();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'invoices' => $this->get_invoices(),
                'recurring_profiles' => $this->get_recurring_profiles(),
                'recurring_items' => $this->get_recurring_items((int) \Input::get('recurring_profile_id', 0)),
                'recurring_runs' => $this->get_recurring_runs(),
                'fiscal_documents' => $this->get_fiscal_documents(),
                'pending_deliveries' => $this->get_pending_deliveries(),
                'items' => $this->get_items((int) \Input::get('invoice_id', 0)),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando facturacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar facturacion.'], 500);
        }
    }

    /**
     * SAVE INVOICE
     *
     * CREA O ACTUALIZA UNA FACTURA BASE
     *
     * @access  public
     * @return  Response
     */
    public function action_save_invoice()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('billing.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            if ((int) \Arr::get($val, 'party_id', 0) < 1) {
                return $this->json_response(['error' => 'El tercero es obligatorio.'], 422);
            }

            # SE PREPARAN DATOS
            $data = [
                'invoice_type' => $this->codeify(\Arr::get($val, 'invoice_type', 'sale')),
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'cfdi_id' => (int) \Arr::get($val, 'cfdi_id', 0),
                'fiscal_mode' => $this->fiscal_mode(\Arr::get($val, 'fiscal_mode', 'fiscal_required')),
                'requires_waybill' => (int) (bool) \Arr::get($val, 'requires_waybill', false),
                'pac_provider_code' => 'factura_com',
                'pac_connection_id' => (int) \Arr::get($val, 'pac_connection_id', 0),
                'pac_series_id' => trim((string) \Arr::get($val, 'pac_series_id', '')),
                'pac_receptor_uid' => trim((string) \Arr::get($val, 'pac_receptor_uid', '')),
                'source_module' => $this->codeify(\Arr::get($val, 'source_module', 'manual')),
                'source_entity_type' => trim((string) \Arr::get($val, 'source_entity_type', '')),
                'source_entity_id' => (int) \Arr::get($val, 'source_entity_id', 0),
                'issue_date' => trim((string) \Arr::get($val, 'issue_date', date('Y-m-d'))),
                'due_date' => trim((string) \Arr::get($val, 'due_date', '')),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'payment_term_id' => (int) \Arr::get($val, 'payment_term_id', 0),
                'sat_cfdi_use_code' => trim((string) \Arr::get($val, 'sat_cfdi_use_code', 'G03')),
                'sat_payment_form_code' => trim((string) \Arr::get($val, 'sat_payment_form_code', '99')),
                'sat_payment_method_code' => trim((string) \Arr::get($val, 'sat_payment_method_code', 'PPD')),
                'status' => $this->codeify(\Arr::get($val, 'status', 'draft')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE CREA O ACTUALIZA
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $invoice = Model_Core_Billing_Invoice::find($id);
                if (!$invoice) {
                    return $this->json_response(['error' => 'Factura no encontrada.'], 404);
                }
                $old = $invoice->to_array();
                $invoice->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_invoice_folio();
                $data['created_by'] = $this->user_id;
                $invoice = Model_Core_Billing_Invoice::forge($this->invoice_defaults($data));
            }
            $invoice->save();
            $this->recalculate_invoice((int) $invoice->id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'billing',
                'action' => $id > 0 ? 'update_invoice' : 'create_invoice',
                'entity_type' => 'billing_invoice',
                'entity_id' => (int) $invoice->id,
                'summary' => 'Factura '.$invoice->folio.' estado '.$invoice->status,
                'old_values' => $old,
                'new_values' => $invoice->to_array(),
            ]);

            return $this->json_response([
                'status' => 'ok',
                'invoice_id' => (int) $invoice->id,
                'folio' => (string) $invoice->folio,
                'invoices' => $this->get_invoices(),
                'items' => $this->get_items((int) $invoice->id),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando factura: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la factura.'], 400);
        }
    }

    /**
     * SAVE ITEM
     *
     * CREA O ACTUALIZA UN CONCEPTO DE FACTURA
     *
     * @access  public
     * @return  Response
     */
    public function action_save_item()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('billing.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            $invoice_id = (int) \Arr::get($val, 'invoice_id', 0);
            if ($invoice_id < 1 || !Model_Core_Billing_Invoice::find($invoice_id)) {
                return $this->json_response(['error' => 'Factura invalida.'], 422);
            }

            if (trim((string) \Arr::get($val, 'description', '')) === '') {
                return $this->json_response(['error' => 'La descripcion es obligatoria.'], 422);
            }

            # SE CALCULAN IMPORTES
            $quantity = max(0, (float) \Arr::get($val, 'quantity', 1));
            $unit_price = max(0, (float) \Arr::get($val, 'unit_price', 0));
            $discount = max(0, (float) \Arr::get($val, 'discount_amount', 0));
            $tax_rate = max(0, (float) \Arr::get($val, 'tax_rate', 0));
            $base = max(0, ($quantity * $unit_price) - $discount);
            $tax_amount = round($base * $tax_rate, 2);
            $retention = max(0, (float) \Arr::get($val, 'retention_amount', 0));

            # SE PREPARAN DATOS
            $data = [
                'invoice_id' => $invoice_id,
                'product_id' => (int) \Arr::get($val, 'product_id', 0),
                'sat_product_service_code' => trim((string) \Arr::get($val, 'sat_product_service_code', '01010101')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'quantity' => $quantity,
                'unit_code' => trim((string) \Arr::get($val, 'unit_code', 'H87')),
                'sat_object_tax_code' => trim((string) \Arr::get($val, 'sat_object_tax_code', '02')),
                'unit_price' => $unit_price,
                'discount_amount' => $discount,
                'tax_code' => trim((string) \Arr::get($val, 'tax_code', 'iva_16')),
                'tax_factor_type' => trim((string) \Arr::get($val, 'tax_factor_type', 'Tasa')),
                'tax_rate' => $tax_rate,
                'tax_amount' => $tax_amount,
                'retention_amount' => $retention,
                'retention_tax_code' => trim((string) \Arr::get($val, 'retention_tax_code', '')),
                'retention_rate' => (float) \Arr::get($val, 'retention_rate', 0),
                'line_total' => round($base + $tax_amount - $retention, 2),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE CREA O ACTUALIZA
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $item = Model_Core_Billing_Invoice_Item::find($id);
                if (!$item) {
                    return $this->json_response(['error' => 'Concepto no encontrado.'], 404);
                }
                $old = $item->to_array();
                $item->set($data);
            } else {
                $old = [];
                $item = Model_Core_Billing_Invoice_Item::forge($data);
            }
            $item->save();
            $invoice = $this->recalculate_invoice($invoice_id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'billing',
                'action' => $id > 0 ? 'update_invoice_item' : 'create_invoice_item',
                'entity_type' => 'billing_invoice_item',
                'entity_id' => (int) $item->id,
                'summary' => 'Concepto en factura '.$invoice->folio,
                'old_values' => $old,
                'new_values' => $item->to_array(),
            ]);

            return $this->json_response([
                'status' => 'ok',
                'invoices' => $this->get_invoices(),
                'items' => $this->get_items($invoice_id),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando concepto de factura: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el concepto.'], 400);
        }
    }

    public function action_prepare_cfdi()
    {
        $this->require_access('billing.access[view]');

        try {
            $invoice = $this->invoice_from_request();
            $payload = $this->build_cfdi_payload($invoice);
            $fiscal = $this->ensure_fiscal_document_from_invoice($invoice, $payload);
            $invoice->pac_request_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $invoice->fiscal_document_id = (int) $fiscal->id;
            $invoice->status = $invoice->status === 'draft' ? 'ready' : $invoice->status;
            $invoice->save();
            $this->log_invoice_event((int) $invoice->id, 'prepare_cfdi', 'Payload CFDI 4.0 preparado', $payload);

            return $this->json_response([
                'status' => 'ok',
                'payload' => $payload,
                'invoices' => $this->get_invoices(),
                'fiscal_documents' => $this->get_fiscal_documents(),
                'items' => $this->get_items((int) $invoice->id),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error preparando CFDI: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_stamp_invoice()
    {
        $this->require_access('billing.access[edit]');

        try {
            $invoice = $this->invoice_from_request();
            if ($invoice->status === 'stamped') {
                return $this->json_response(['error' => 'La factura ya esta timbrada.'], 422);
            }

            $payload = $this->build_cfdi_payload($invoice);
            $connection = $this->pac_connection($invoice);
            $event_id = $this->log_integration_event('cfdi40.create', $connection, $payload, 'pending');
            $response = (new Helper_Core_Pac_FacturaCom($connection))->stamp($payload);
            $this->finish_integration_event($event_id, $response);

            if (!$response['success']) {
                $invoice->pac_request_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $invoice->pac_response_json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $invoice->save();
                throw new \RuntimeException('Factura.com no timbro el CFDI. Revisa respuesta PAC.');
            }

            $json = $response['json'] ?: [];
            $invoice->pac_connection_id = (int) $connection->id;
            $invoice->pac_request_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $invoice->pac_response_json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $invoice->pac_uid = $this->first_value($json, ['uid', 'UID', 'cfdi_uid', 'CfdiUID', 'id']);
            $invoice->uuid = strtoupper($this->first_value($json, ['uuid', 'UUID', 'folio_fiscal', 'FolioFiscal']));
            $invoice->sat_status = 'vigente';
            $invoice->status = 'stamped';
            $invoice->stamped_at = time();
            $invoice->cfdi_id = $this->upsert_sat_cfdi($invoice);
            $invoice->save();
            $this->sync_fiscal_document_from_invoice($invoice, $payload, $response);

            $this->log_invoice_event((int) $invoice->id, 'stamp', 'Factura timbrada con Factura.com', $response);
            Helper_Core_Audit::log([
                'module' => 'billing',
                'action' => 'stamp_invoice',
                'entity_type' => 'billing_invoice',
                'entity_id' => (int) $invoice->id,
                'summary' => 'Factura timbrada '.$invoice->folio.' UUID '.$invoice->uuid,
                'new_values' => $invoice->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'invoices' => $this->get_invoices(), 'fiscal_documents' => $this->get_fiscal_documents(), 'items' => $this->get_items((int) $invoice->id), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error timbrando factura: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_cancel_invoice()
    {
        $this->require_access('billing.access[edit]');

        try {
            $val = (array) \Input::json();
            $invoice = $this->invoice_from_request($val);
            $uid = (string) ($invoice->pac_uid ?: $invoice->uuid);
            if ($uid === '') {
                return $this->json_response(['error' => 'La factura no tiene UID/UUID para cancelar.'], 422);
            }

            $motive = trim((string) \Arr::get($val, 'cancel_motive', '02')) ?: '02';
            $substitute = trim((string) \Arr::get($val, 'cancel_substitute_uuid', ''));
            $connection = $this->pac_connection($invoice);
            $payload = ['motivo' => $motive, 'folioSustituto' => $substitute];
            $event_id = $this->log_integration_event('cfdi40.cancel', $connection, $payload, 'pending', $uid);
            $response = (new Helper_Core_Pac_FacturaCom($connection))->cancel($uid, $motive, $substitute);
            $this->finish_integration_event($event_id, $response);

            if (!$response['success']) {
                throw new \RuntimeException('Factura.com no cancelo el CFDI. Revisa respuesta PAC.');
            }

            $invoice->status = 'cancelled';
            $invoice->sat_status = 'cancelado';
            $invoice->cancelled_at = time();
            $invoice->cancel_motive = $motive;
            $invoice->cancel_substitute_uuid = $substitute;
            $invoice->pac_response_json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $invoice->save();
            $this->mark_sat_cfdi_cancelled($invoice);
            $this->sync_fiscal_document_from_invoice($invoice, [], $response);
            $this->log_invoice_event((int) $invoice->id, 'cancel', 'Factura cancelada con Factura.com', $response);

            return $this->json_response(['status' => 'ok', 'invoices' => $this->get_invoices(), 'fiscal_documents' => $this->get_fiscal_documents(), 'items' => $this->get_items((int) $invoice->id), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error cancelando factura: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_save_recurring_profile()
    {
        $this->require_access('billing.access[edit]');
        $val = (array) \Input::json();

        try {
            $name = trim((string) \Arr::get($val, 'name', ''));
            $party_id = (int) \Arr::get($val, 'party_id', 0);
            if ($name === '' || $party_id < 1) {
                return $this->json_response(['error' => 'Nombre y cliente son obligatorios.'], 422);
            }

            $start_date = trim((string) \Arr::get($val, 'start_date', date('Y-m-d')));
            $next_run_date = trim((string) \Arr::get($val, 'next_run_date', $start_date));
            $data = [
                'name' => $name,
                'party_id' => $party_id,
                'invoice_type' => $this->codeify(\Arr::get($val, 'invoice_type', 'sale')),
                'frequency' => $this->recurring_frequency(\Arr::get($val, 'frequency', 'monthly')),
                'start_date' => $start_date,
                'end_date' => trim((string) \Arr::get($val, 'end_date', '')),
                'next_run_date' => $next_run_date ?: $start_date,
                'auto_stamp' => (int) (bool) \Arr::get($val, 'auto_stamp', false),
                'pac_connection_id' => (int) \Arr::get($val, 'pac_connection_id', 0),
                'pac_series_id' => trim((string) \Arr::get($val, 'pac_series_id', '')),
                'pac_receptor_uid' => trim((string) \Arr::get($val, 'pac_receptor_uid', '')),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'payment_term_id' => (int) \Arr::get($val, 'payment_term_id', 0),
                'sat_cfdi_use_code' => trim((string) \Arr::get($val, 'sat_cfdi_use_code', 'G03')),
                'sat_payment_form_code' => trim((string) \Arr::get($val, 'sat_payment_form_code', '99')),
                'sat_payment_method_code' => trim((string) \Arr::get($val, 'sat_payment_method_code', 'PPD')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'status' => $this->recurring_status(\Arr::get($val, 'status', 'active')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $profile = Model_Core_Billing_Recurring_Profile::find($id);
                if (!$profile) {
                    return $this->json_response(['error' => 'Perfil recurrente no encontrado.'], 404);
                }
                $old = $profile->to_array();
                $profile->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_recurring_folio();
                $data['created_by'] = $this->user_id;
                $profile = Model_Core_Billing_Recurring_Profile::forge($data);
            }
            $profile->save();

            Helper_Core_Audit::log([
                'module' => 'billing',
                'action' => $id > 0 ? 'update_recurring_profile' : 'create_recurring_profile',
                'business_event' => 'billing.recurring_profile_saved',
                'entity_type' => 'billing_recurring_profile',
                'entity_id' => (int) $profile->id,
                'table_name' => 'core_billing_recurring_profiles',
                'summary' => 'Perfil recurrente '.$profile->folio.' '.$profile->name,
                'old_values' => $old,
                'new_values' => $profile->to_array(),
            ]);

            return $this->recurring_response((int) $profile->id);
        } catch (\Exception $e) {
            \Log::error('Error guardando perfil recurrente: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el perfil recurrente.'], 400);
        }
    }

    public function action_save_recurring_item()
    {
        $this->require_access('billing.access[edit]');
        $val = (array) \Input::json();

        try {
            $profile_id = (int) \Arr::get($val, 'profile_id', 0);
            if ($profile_id < 1 || !Model_Core_Billing_Recurring_Profile::find($profile_id)) {
                return $this->json_response(['error' => 'Perfil recurrente invalido.'], 422);
            }
            if (trim((string) \Arr::get($val, 'description', '')) === '') {
                return $this->json_response(['error' => 'La descripcion del concepto es obligatoria.'], 422);
            }

            $data = [
                'profile_id' => $profile_id,
                'product_id' => (int) \Arr::get($val, 'product_id', 0),
                'sat_product_service_code' => trim((string) \Arr::get($val, 'sat_product_service_code', '01010101')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'quantity' => max(0, (float) \Arr::get($val, 'quantity', 1)),
                'unit_code' => trim((string) \Arr::get($val, 'unit_code', 'E48')),
                'sat_object_tax_code' => trim((string) \Arr::get($val, 'sat_object_tax_code', '02')),
                'unit_price' => max(0, (float) \Arr::get($val, 'unit_price', 0)),
                'discount_amount' => max(0, (float) \Arr::get($val, 'discount_amount', 0)),
                'tax_code' => trim((string) \Arr::get($val, 'tax_code', 'iva_16')),
                'tax_factor_type' => trim((string) \Arr::get($val, 'tax_factor_type', 'Tasa')),
                'tax_rate' => max(0, (float) \Arr::get($val, 'tax_rate', 0)),
                'retention_tax_code' => trim((string) \Arr::get($val, 'retention_tax_code', '')),
                'retention_rate' => max(0, (float) \Arr::get($val, 'retention_rate', 0)),
                'retention_amount' => max(0, (float) \Arr::get($val, 'retention_amount', 0)),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $item = Model_Core_Billing_Recurring_Item::find($id);
                if (!$item) {
                    return $this->json_response(['error' => 'Concepto recurrente no encontrado.'], 404);
                }
                $item->set($data);
            } else {
                $item = Model_Core_Billing_Recurring_Item::forge($data);
            }
            $item->save();

            return $this->recurring_response($profile_id);
        } catch (\Exception $e) {
            \Log::error('Error guardando concepto recurrente: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el concepto recurrente.'], 400);
        }
    }

    public function action_generate_recurring_invoice()
    {
        $this->require_access('billing.access[edit]');
        $val = (array) \Input::json();
        $transaction_started = false;

        try {
            $profile = Model_Core_Billing_Recurring_Profile::find((int) \Arr::get($val, 'id', 0));
            if (!$profile || (int) $profile->active !== 1) {
                return $this->json_response(['error' => 'Perfil recurrente no encontrado o inactivo.'], 404);
            }
            if ($profile->status !== 'active') {
                return $this->json_response(['error' => 'El perfil recurrente no esta activo.'], 422);
            }

            $run_date = trim((string) \Arr::get($val, 'run_date', $profile->next_run_date ?: date('Y-m-d')));
            if ($profile->end_date && $run_date > $profile->end_date) {
                return $this->json_response(['error' => 'La fecha de ejecucion rebasa el fin del contrato.'], 422);
            }

            $items = $this->get_recurring_items((int) $profile->id);
            if (empty($items)) {
                return $this->json_response(['error' => 'Agrega al menos un concepto al perfil recurrente.'], 422);
            }

            \DB::start_transaction();
            $transaction_started = true;
            $invoice = Model_Core_Billing_Invoice::forge($this->invoice_defaults([
                'folio' => $this->next_invoice_folio(),
                'invoice_type' => (string) $profile->invoice_type,
                'party_id' => (int) $profile->party_id,
                'cfdi_id' => 0,
                'pac_provider_code' => 'factura_com',
                'pac_connection_id' => (int) $profile->pac_connection_id,
                'pac_series_id' => (string) $profile->pac_series_id,
                'pac_receptor_uid' => (string) $profile->pac_receptor_uid,
                'source_module' => 'billing_recurring',
                'source_entity_type' => 'billing_recurring_profile',
                'source_entity_id' => (int) $profile->id,
                'issue_date' => $run_date,
                'due_date' => '',
                'currency_code' => (string) $profile->currency_code,
                'exchange_rate' => (float) $profile->exchange_rate,
                'payment_term_id' => (int) $profile->payment_term_id,
                'sat_cfdi_use_code' => (string) $profile->sat_cfdi_use_code,
                'sat_payment_form_code' => (string) $profile->sat_payment_form_code,
                'sat_payment_method_code' => (string) $profile->sat_payment_method_code,
                'status' => 'draft',
                'notes' => 'Factura recurrente '.$profile->folio.' - '.$profile->name,
                'created_by' => $this->user_id,
                'active' => 1,
            ]));
            $invoice->save();

            foreach ($items as $row) {
                $this->create_invoice_item_from_recurring((int) $invoice->id, $row);
            }
            $this->recalculate_invoice((int) $invoice->id);

            Model_Core_Billing_Recurring_Run::forge([
                'profile_id' => (int) $profile->id,
                'invoice_id' => (int) $invoice->id,
                'run_date' => $run_date,
                'status' => 'created',
                'message' => 'Factura '.$invoice->folio.' creada como borrador.',
            ])->save();

            $profile->last_run_at = time();
            $profile->next_run_date = $this->next_recurring_date($run_date, (string) $profile->frequency);
            $profile->save();
            \DB::commit_transaction();

            $this->log_invoice_event((int) $invoice->id, 'recurring_create', 'Factura creada desde perfil recurrente '.$profile->folio, ['profile_id' => (int) $profile->id, 'run_date' => $run_date]);

            return $this->json_response([
                'status' => 'ok',
                'invoice_id' => (int) $invoice->id,
                'folio' => (string) $invoice->folio,
                'invoices' => $this->get_invoices(),
                'recurring_profiles' => $this->get_recurring_profiles(),
                'recurring_runs' => $this->get_recurring_runs(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generando factura recurrente: '.$e->getMessage());
            if ($transaction_started) {
                \DB::rollback_transaction();
            }
            return $this->json_response(['error' => 'No se pudo generar factura recurrente: '.$e->getMessage()], 400);
        }
    }

    public function action_create_from_delivery()
    {
        $this->require_access('billing.access[edit]');
        $transaction_started = false;

        try {
            $val = (array) \Input::json();
            $delivery_id = (int) \Arr::get($val, 'delivery_id', 0);
            \Log::info('Facturacion: inicio crear factura desde entrega id='.$delivery_id.' payload='.json_encode($val));

            $delivery = \DB::select()->from('core_sales_deliveries')->where('id', '=', $delivery_id)->where('active', '=', 1)->execute()->current();
            if (!$delivery) {
                return $this->json_response(['error' => 'Entrega no encontrada.'], 404);
            }
            if ((int) $delivery['billing_invoice_id'] > 0) {
                return $this->json_response(['error' => 'La entrega ya tiene factura.'], 422);
            }

            $party = Model_Core_Party::find((int) $delivery['party_id']);
            if (!$party) {
                return $this->json_response(['error' => 'La entrega no tiene cliente valido. Revisa el pedido de origen.'], 422);
            }

            $delivery_items = \DB::select()->from('core_sales_delivery_items')->where('delivery_id', '=', $delivery_id)->order_by('sort_order', 'asc')->execute()->as_array();
            if (empty($delivery_items)) {
                return $this->json_response(['error' => 'La entrega no tiene partidas para facturar. Revisa Ventas > Entregas.'], 422);
            }

            \DB::start_transaction();
            $transaction_started = true;

            $invoice = Model_Core_Billing_Invoice::forge([
                'folio' => $this->next_invoice_folio(),
                'invoice_type' => 'sale',
                'party_id' => (int) $delivery['party_id'],
                'cfdi_id' => 0,
                'source_module' => 'sales',
                'source_entity_type' => 'sales_delivery',
                'source_entity_id' => $delivery_id,
                'issue_date' => date('Y-m-d'),
                'due_date' => '',
                'currency_code' => (string) $delivery['currency_code'],
                'exchange_rate' => 1,
                'payment_term_id' => $party ? (int) $party->payment_term_id : 0,
                'sat_cfdi_use_code' => $party && $party->sat_cfdi_use_code ? (string) $party->sat_cfdi_use_code : 'G03',
                'sat_payment_form_code' => '99',
                'sat_payment_method_code' => 'PPD',
                'pac_provider_code' => 'factura_com',
                'pac_connection_id' => 0,
                'pac_series_id' => '',
                'pac_receptor_uid' => '',
                'pac_uid' => '',
                'uuid' => '',
                'sat_status' => '',
                'stamped_at' => 0,
                'cancelled_at' => 0,
                'cancel_motive' => '',
                'cancel_substitute_uuid' => '',
                'pac_request_json' => null,
                'pac_response_json' => null,
                'xml_path' => '',
                'pdf_path' => '',
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'retention_total' => 0,
                'total' => 0,
                'balance_due' => 0,
                'status' => 'draft',
                'notes' => 'Factura creada desde entrega '.$delivery['folio'],
                'created_by' => (int) $this->user_id,
                'active' => 1,
            ]);
            $invoice->save();

            $sort = 10;
            $item_count = 0;
            foreach ($delivery_items as $item) {
                $product = $item['product_id'] ? Model_Core_Commerce_Product::find((int) $item['product_id']) : null;
                $tax_rate = $product && isset($product->sat_tax_rate) ? (float) $product->sat_tax_rate : $this->tax_rate($product ? (string) $product->tax_code : 'iva_16');
                $base = (float) $item['quantity'] * (float) $item['unit_price'];
                $tax_amount = round($base * $tax_rate, 2);
                Model_Core_Billing_Invoice_Item::forge([
                    'invoice_id' => (int) $invoice->id,
                    'product_id' => (int) $item['product_id'],
                    'sat_product_service_code' => $product && isset($product->sat_product_service_code) && (string) $product->sat_product_service_code !== '' ? (string) $product->sat_product_service_code : '01010101',
                    'description' => (string) $item['name'],
                    'quantity' => (float) $item['quantity'],
                    'unit_code' => $product && isset($product->sat_unit_code) && (string) $product->sat_unit_code !== '' ? (string) $product->sat_unit_code : 'H87',
                    'sat_object_tax_code' => $product && isset($product->sat_object_tax_code) && (string) $product->sat_object_tax_code !== '' ? (string) $product->sat_object_tax_code : ($tax_rate > 0 ? '02' : '01'),
                    'unit_price' => (float) $item['unit_price'],
                    'discount_amount' => 0,
                    'tax_code' => $product ? (string) $product->tax_code : 'iva_16',
                    'tax_factor_type' => $product && isset($product->sat_tax_factor_type) && (string) $product->sat_tax_factor_type !== '' ? (string) $product->sat_tax_factor_type : 'Tasa',
                    'tax_rate' => $tax_rate,
                    'tax_amount' => $tax_amount,
                    'retention_amount' => 0,
                    'retention_tax_code' => '',
                    'retention_rate' => 0,
                    'line_total' => round($base + $tax_amount, 2),
                    'sort_order' => $sort,
                    'active' => 1,
                ])->save();
                $sort += 10;
                $item_count++;
            }

            $this->recalculate_invoice((int) $invoice->id);
            \DB::update('core_sales_deliveries')->set(['billing_invoice_id' => (int) $invoice->id, 'status' => 'billed', 'updated_at' => time()])->where('id', '=', $delivery_id)->execute();
            $this->refresh_sales_order_billing((int) $delivery['order_id']);

            $this->log_invoice_event((int) $invoice->id, 'create_from_delivery', 'Factura creada desde entrega '.$delivery['folio'], $delivery);
            \Log::info('Facturacion: factura '.$invoice->folio.' creada desde entrega '.$delivery['folio'].' partidas='.$item_count.' invoice_id='.(int) $invoice->id);

            \DB::commit_transaction();
            $transaction_started = false;

            return $this->json_response(['status' => 'ok', 'folio' => $invoice->folio, 'invoice_id' => (int) $invoice->id, 'invoices' => $this->get_invoices(), 'pending_deliveries' => $this->get_pending_deliveries(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            if ($transaction_started) {
                \DB::rollback_transaction();
            }
            \Log::error('Error creando factura desde entrega: '.$e->getMessage().' | payload='.json_encode((array) \Input::json()).' | trace='.$e->getTraceAsString());
            return $this->json_response(['error' => 'No se pudo crear la factura desde entrega: '.$e->getMessage()], 400);
        }
    }

    /**
     * GET INVOICES
     *
     * FORMATEA FACTURAS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_invoices()
    {
        # SE CONSULTAN FACTURAS RECIENTES
        $items = [];
        $rows = Model_Core_Billing_Invoice::query()->order_by('id', 'desc')->limit(200)->get();

        foreach ($rows as $invoice) {
            $row = $invoice->to_array();
            $party = $invoice->party_id ? Model_Core_Party::find((int) $invoice->party_id) : null;
            $row['party_name'] = $party ? (string) $party->name : '';
            $row['created_at'] = $invoice->created_at ? date('d/m/Y H:i', $invoice->created_at) : '';
            $items[] = $row;
        }

        return $items;
    }

    /**
     * GET ITEMS
     *
     * OBTIENE CONCEPTOS DE UNA FACTURA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_items($invoice_id)
    {
        # SE VALIDA FACTURA
        if ($invoice_id < 1) {
            return [];
        }

        # SE CONSULTAN CONCEPTOS
        $items = [];
        $rows = Model_Core_Billing_Invoice_Item::query()
            ->where('invoice_id', '=', $invoice_id)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();

        foreach ($rows as $item) {
            $items[] = $item->to_array();
        }

        return $items;
    }

    protected function get_fiscal_documents()
    {
        if (!\DBUtil::table_exists('core_fiscal_documents')) {
            return [];
        }

        return \DB::select(['f.id', 'id'], ['f.folio', 'folio'], ['f.document_type', 'document_type'], ['f.voucher_type', 'voucher_type'], ['f.party_id', 'party_id'], ['p.name', 'party_name'], ['f.source_module', 'source_module'], ['f.source_entity_type', 'source_entity_type'], ['f.source_entity_id', 'source_entity_id'], ['f.source_folio', 'source_folio'], ['f.fiscal_mode', 'fiscal_mode'], ['f.uuid', 'uuid'], ['f.sat_status', 'sat_status'], ['f.workflow_status', 'workflow_status'], ['f.issue_date', 'issue_date'], ['f.currency_code', 'currency_code'], ['f.total', 'total'], ['f.created_at', 'created_at'])
            ->from(['core_fiscal_documents', 'f'])
            ->join(['core_parties', 'p'], 'left')->on('f.party_id', '=', 'p.id')
            ->where('f.active', '=', 1)
            ->order_by('f.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function get_recurring_profiles()
    {
        if (!\DBUtil::table_exists('core_billing_recurring_profiles')) {
            return [];
        }

        return \DB::select(['r.id', 'id'], ['r.folio', 'folio'], ['r.name', 'name'], ['r.party_id', 'party_id'], ['p.name', 'party_name'], ['r.invoice_type', 'invoice_type'], ['r.frequency', 'frequency'], ['r.start_date', 'start_date'], ['r.end_date', 'end_date'], ['r.next_run_date', 'next_run_date'], ['r.last_run_at', 'last_run_at'], ['r.auto_stamp', 'auto_stamp'], ['r.pac_connection_id', 'pac_connection_id'], ['r.pac_series_id', 'pac_series_id'], ['r.pac_receptor_uid', 'pac_receptor_uid'], ['r.currency_code', 'currency_code'], ['r.exchange_rate', 'exchange_rate'], ['r.payment_term_id', 'payment_term_id'], ['r.sat_cfdi_use_code', 'sat_cfdi_use_code'], ['r.sat_payment_form_code', 'sat_payment_form_code'], ['r.sat_payment_method_code', 'sat_payment_method_code'], ['r.notes', 'notes'], ['r.status', 'status'], ['r.active', 'active'])
            ->from(['core_billing_recurring_profiles', 'r'])
            ->join(['core_parties', 'p'], 'left')->on('r.party_id', '=', 'p.id')
            ->where('r.active', '=', 1)
            ->order_by('r.next_run_date', 'asc')
            ->order_by('r.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function get_recurring_items($profile_id)
    {
        if ($profile_id < 1 || !\DBUtil::table_exists('core_billing_recurring_items')) {
            return [];
        }

        return \DB::select()->from('core_billing_recurring_items')
            ->where('profile_id', '=', (int) $profile_id)
            ->where('active', '=', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->execute()
            ->as_array();
    }

    protected function get_recurring_runs()
    {
        if (!\DBUtil::table_exists('core_billing_recurring_runs')) {
            return [];
        }

        return \DB::select(['rr.id', 'id'], ['rr.profile_id', 'profile_id'], ['rp.folio', 'profile_folio'], ['rp.name', 'profile_name'], ['rr.invoice_id', 'invoice_id'], ['i.folio', 'invoice_folio'], ['rr.run_date', 'run_date'], ['rr.status', 'status'], ['rr.message', 'message'], ['rr.created_at', 'created_at'])
            ->from(['core_billing_recurring_runs', 'rr'])
            ->join(['core_billing_recurring_profiles', 'rp'], 'left')->on('rr.profile_id', '=', 'rp.id')
            ->join(['core_billing_invoices', 'i'], 'left')->on('rr.invoice_id', '=', 'i.id')
            ->order_by('rr.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function get_pending_deliveries()
    {
        if (!\DBUtil::table_exists('core_sales_deliveries')) {
            return [];
        }

        $query = \DB::select(
                ['d.id', 'id'],
                ['d.folio', 'folio'],
                ['d.order_id', 'order_id'],
                ['d.delivery_date', 'delivery_date'],
                ['d.currency_code', 'currency_code'],
                ['d.total', 'total'],
                ['d.status', 'status'],
                ['o.folio', 'order_folio'],
                ['p.name', 'party_name'],
                ['w.name', 'warehouse_name']
            )
            ->from(['core_sales_deliveries', 'd'])
            ->join(['core_sales_orders', 'o'], 'left')->on('d.order_id', '=', 'o.id')
            ->join(['core_parties', 'p'], 'left')->on('d.party_id', '=', 'p.id')
            ->join(['core_inventory_warehouses', 'w'], 'left')->on('d.warehouse_id', '=', 'w.id')
            ->where('d.active', '=', 1)
            ->where('d.billing_invoice_id', '=', 0)
            ->order_by('d.delivery_date', 'asc')
            ->order_by('d.id', 'asc')
            ->limit(200);
        $this->apply_party_scope($query, 'p', 'sales');

        return $query->execute()->as_array();
    }

    /**
     * GET OPTIONS
     *
     * ENTREGA OPCIONES PARA FORMULARIOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        # SE REGRESAN CATALOGOS RELACIONADOS
        return [
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'products' => $this->product_options(),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'payment_terms' => $this->select_options('core_catalog_payment_terms', 'id', 'name'),
            'sat_cfdi_uses' => Helper_Core_Sat_Catalog::options('core_sat_cfdi_uses'),
            'sat_payment_forms' => Helper_Core_Sat_Catalog::options('core_sat_payment_forms'),
            'sat_payment_methods' => Helper_Core_Sat_Catalog::options('core_sat_payment_methods'),
            'sat_tax_regimes' => Helper_Core_Sat_Catalog::options('core_sat_tax_regimes'),
            'units' => Helper_Core_Sat_Catalog::options('core_sat_unit_keys'),
            'sat_product_service_keys' => Helper_Core_Sat_Catalog::options('core_sat_product_service_keys'),
            'sat_object_tax_codes' => Helper_Core_Sat_Catalog::options('core_sat_object_tax_codes'),
            'taxes' => $this->select_options('core_catalog_taxes', 'code', 'name'),
            'retentions' => $this->select_options('core_catalog_retentions', 'code', 'name'),
            'pac_connections' => $this->pac_connection_options(),
        ];
    }

    /**
     * PRODUCT OPTIONS
     *
     * ENTREGA PRODUCTOS CON DATOS UTILES PARA CAPTURAR CONCEPTOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function product_options()
    {
        if (!\DBUtil::table_exists('core_commerce_products')) {
            return [];
        }

        $items = [];
        $select = [
            'id',
            'sku',
            'name',
            'unit_code',
            'currency_code',
            'price',
            'tax_code',
            'product_type',
            'is_internal_service',
            'stock_quantity',
            'stock_reserved',
            'main_image_path',
        ];
        if (\DBUtil::field_exists('core_commerce_products', ['sat_product_service_code'])) {
            $select[] = 'sat_product_service_code';
            $select[] = 'sat_unit_code';
            $select[] = 'sat_object_tax_code';
            $select[] = 'sat_tax_code';
            $select[] = 'sat_tax_factor_type';
            $select[] = 'sat_tax_rate';
        }

        $rows = \DB::select_array($select)
            ->from('core_commerce_products')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->limit(300)
            ->execute();

        foreach ($rows as $row) {
            $stock = (float) $row['stock_quantity'];
            $reserved = (float) $row['stock_reserved'];
            $items[] = [
                'value' => (int) $row['id'],
                'sku' => (string) $row['sku'],
                'label' => trim((string) $row['name'].' '.($row['sku'] ? '('.$row['sku'].')' : '')),
                'name' => (string) $row['name'],
                'unit_code' => (string) $row['unit_code'],
                'sat_product_service_code' => (string) \Arr::get($row, 'sat_product_service_code', '01010101'),
                'sat_unit_code' => (string) \Arr::get($row, 'sat_unit_code', ((string) \Arr::get($row, 'product_type', 'product') === 'service' ? 'E48' : 'H87')),
                'sat_object_tax_code' => (string) \Arr::get($row, 'sat_object_tax_code', '02'),
                'currency_code' => (string) $row['currency_code'],
                'price' => (float) $row['price'],
                'tax_code' => (string) $row['tax_code'],
                'sat_tax_code' => (string) \Arr::get($row, 'sat_tax_code', '002'),
                'sat_tax_factor_type' => (string) \Arr::get($row, 'sat_tax_factor_type', 'Tasa'),
                'product_type' => (string) $row['product_type'],
                'is_internal_service' => (int) $row['is_internal_service'],
                'tax_rate' => (float) \Arr::get($row, 'sat_tax_rate', $this->tax_rate((string) $row['tax_code'])),
                'stock_quantity' => $stock,
                'stock_reserved' => $reserved,
                'available_stock' => max(0, $stock - $reserved),
                'image_url' => $this->media_url((string) $row['main_image_path']),
            ];
        }

        return $items;
    }

    protected function media_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        return \Uri::base(false).ltrim($path, '/');
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES DE FACTURACION
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE REGRESAN CONTADORES AGREGADOS
        return [
            'invoices' => (int) \DB::count_records('core_billing_invoices'),
            'draft' => (int) \DB::select()->from('core_billing_invoices')->where('status', '=', 'draft')->execute()->count(),
            'ready' => (int) \DB::select()->from('core_billing_invoices')->where('status', '=', 'ready')->execute()->count(),
            'stamped' => (int) \DB::select()->from('core_billing_invoices')->where('status', '=', 'stamped')->execute()->count(),
            'cancelled' => (int) \DB::select()->from('core_billing_invoices')->where('status', '=', 'cancelled')->execute()->count(),
            'pending_deliveries' => \DBUtil::table_exists('core_sales_deliveries') ? (int) \DB::select()->from('core_sales_deliveries')->where('active', '=', 1)->where('billing_invoice_id', '=', 0)->execute()->count() : 0,
            'recurring_profiles' => \DBUtil::table_exists('core_billing_recurring_profiles') ? (int) \DB::select()->from('core_billing_recurring_profiles')->where('active', '=', 1)->execute()->count() : 0,
            'recurring_due' => \DBUtil::table_exists('core_billing_recurring_profiles') ? (int) \DB::select()->from('core_billing_recurring_profiles')->where('active', '=', 1)->where('status', '=', 'active')->where('next_run_date', '<=', date('Y-m-d'))->execute()->count() : 0,
            'fiscal_drafts' => \DBUtil::table_exists('core_fiscal_documents') ? (int) \DB::select()->from('core_fiscal_documents')->where('active', '=', 1)->where('workflow_status', '=', 'draft')->execute()->count() : 0,
        ];
    }

    protected function refresh_sales_order_billing($order_id)
    {
        if ($order_id < 1 || !\DBUtil::table_exists('core_sales_deliveries')) {
            return;
        }

        $row = \DB::select([\DB::expr('COALESCE(SUM(total), 0)'), 'billed_total'])
            ->from('core_sales_deliveries')
            ->where('order_id', '=', (int) $order_id)
            ->where('billing_invoice_id', '>', 0)
            ->where('active', '=', 1)
            ->execute()
            ->current();
        $pending_delivery = \DB::select('id')
            ->from('core_sales_deliveries')
            ->where('order_id', '=', (int) $order_id)
            ->where('billing_invoice_id', '=', 0)
            ->where('active', '=', 1)
            ->execute()
            ->current();
        $order = Model_Core_Sales_Order::find((int) $order_id);
        if (!$order) {
            return;
        }
        $billed_total = $row ? (float) $row['billed_total'] : 0;
        $pending_quantity = 0;
        foreach (\DB::select('quantity', 'delivered_quantity')->from('core_sales_order_items')->where('order_id', '=', (int) $order_id)->execute() as $item) {
            $pending_quantity += max(0, (float) $item['quantity'] - (float) $item['delivered_quantity']);
        }
        $status = $pending_quantity <= 0 && !$pending_delivery ? 'billed' : (string) $order->status;
        \DB::update('core_sales_orders')
            ->set(['status' => $status, 'billed_total' => round($billed_total, 2), 'updated_at' => time()])
            ->where('id', '=', (int) $order_id)
            ->execute();
    }

    protected function invoice_from_request(array $val = null)
    {
        $val = $val ?: (array) \Input::json();
        $invoice = Model_Core_Billing_Invoice::find((int) \Arr::get($val, 'id', 0));
        if (!$invoice) {
            throw new \RuntimeException('Factura no encontrada.');
        }
        return $invoice;
    }

    protected function build_cfdi_payload(Model_Core_Billing_Invoice $invoice)
    {
        $party = $invoice->party_id ? Model_Core_Party::find((int) $invoice->party_id) : null;
        if (!$party) {
            throw new \RuntimeException('Selecciona cliente/receptor.');
        }
        if (trim((string) $invoice->pac_receptor_uid) === '') {
            throw new \RuntimeException('Captura UID de cliente Factura.com en la factura.');
        }
        if (trim((string) $invoice->pac_series_id) === '') {
            throw new \RuntimeException('Captura Serie Factura.com en la factura o integracion.');
        }

        $concepts = [];
        foreach (Model_Core_Billing_Invoice_Item::query()->where('invoice_id', '=', (int) $invoice->id)->where('active', '=', 1)->get() as $item) {
            $base = max(0, ((float) $item->quantity * (float) $item->unit_price) - (float) $item->discount_amount);
            $concept = [
                'ClaveProdServ' => (string) $item->sat_product_service_code,
                'NoIdentificacion' => $item->product_id ? (string) $item->product_id : '',
                'Cantidad' => $this->cfdi_number($item->quantity, 6),
                'ClaveUnidad' => (string) $item->unit_code,
                'Unidad' => (string) $item->unit_code,
                'Descripcion' => (string) $item->description,
                'ValorUnitario' => $this->cfdi_number($item->unit_price, 6),
                'Importe' => $this->cfdi_number((float) $item->quantity * (float) $item->unit_price, 6),
                'Descuento' => $this->cfdi_number($item->discount_amount, 6),
                'ObjetoImp' => (string) ($item->sat_object_tax_code ?: '02'),
                'Impuestos' => [
                    'Traslados' => [],
                    'Retenidos' => [],
                    'Locales' => [],
                ],
            ];
            if ((float) $item->tax_amount > 0 || (string) $item->tax_factor_type === 'Exento') {
                $concept['Impuestos']['Traslados'][] = [
                    'Base' => $this->cfdi_number($base, 6),
                    'Impuesto' => $this->sat_tax_code((string) $item->tax_code, '002'),
                    'TipoFactor' => (string) ($item->tax_factor_type ?: 'Tasa'),
                    'TasaOCuota' => $this->cfdi_number($item->tax_rate, 6),
                    'Importe' => $this->cfdi_number($item->tax_amount, 6),
                ];
            }
            if ((float) $item->retention_amount > 0) {
                $concept['Impuestos']['Retenidos'][] = [
                    'Base' => $this->cfdi_number($base, 6),
                    'Impuesto' => $this->sat_retention_code((string) $item->retention_tax_code, '002'),
                    'TipoFactor' => 'Tasa',
                    'TasaOCuota' => $this->cfdi_number($item->retention_rate, 6),
                    'Importe' => $this->cfdi_number($item->retention_amount, 6),
                ];
            }
            $concepts[] = $concept;
        }
        if (empty($concepts)) {
            throw new \RuntimeException('Agrega conceptos antes de timbrar.');
        }

        return [
            'Receptor' => [
                'UID' => (string) $invoice->pac_receptor_uid,
                'RegimenFiscalR' => (string) ($party->sat_tax_regime_code ?: '601'),
            ],
            'TipoDocumento' => $invoice->invoice_type === 'credit_note' ? 'nota_credito' : 'factura',
            'RegimenFiscal' => (string) $this->issuer_tax_regime(),
            'Conceptos' => $concepts,
            'UsoCFDI' => (string) $invoice->sat_cfdi_use_code,
            'Serie' => (int) $invoice->pac_series_id,
            'FormaPago' => (string) $invoice->sat_payment_form_code,
            'MetodoPago' => (string) $invoice->sat_payment_method_code,
            'Moneda' => (string) $invoice->currency_code,
            'TipoCambio' => $invoice->currency_code === 'MXN' ? '' : $this->cfdi_number($invoice->exchange_rate, 6),
            'CondicionesDePago' => (string) $this->payment_term_name((int) $invoice->payment_term_id),
            'NumOrder' => (string) $invoice->folio,
            'FechaFromAPI' => date('Y-m-d\TH:i:s'),
            'Comentarios' => (string) $invoice->notes,
            'EnviarCorreo' => false,
            'LugarExpedicion' => (string) $this->issuer_zip(),
            'Redondeo' => 2,
        ];
    }

    protected function pac_connection(Model_Core_Billing_Invoice $invoice)
    {
        $query = Model_Core_Integration_Connection::query()->where('active', '=', 1)->where('enabled', '=', 1);
        if ((int) $invoice->pac_connection_id > 0) {
            $query->where('id', '=', (int) $invoice->pac_connection_id);
        } else {
            $query->where('code', '=', 'factura_com_pac');
        }
        $connection = $query->get_one();
        if (!$connection) {
            throw new \RuntimeException('Activa y configura la conexion Factura.com PAC en Integraciones.');
        }
        return $connection;
    }

    protected function pac_connection_options()
    {
        $options = [];
        $provider = \DB::select('id')->from('core_integration_providers')->where('code', '=', 'factura_com')->execute()->current();
        if (!$provider) {
            return $options;
        }
        foreach (\DB::select('id', 'name', 'environment')->from('core_integration_connections')->where('provider_id', '=', (int) $provider['id'])->where('active', '=', 1)->execute() as $row) {
            $options[] = ['value' => (string) $row['id'], 'label' => $row['name'].' ('.$row['environment'].')'];
        }
        return $options;
    }

    /**
     * RECALCULATE INVOICE
     *
     * RECALCULA TOTALES DESDE LOS CONCEPTOS ACTIVOS
     *
     * @access  protected
     * @return  Model_Core_Billing_Invoice
     */
    protected function recalculate_invoice($invoice_id)
    {
        # SE OBTIENE FACTURA
        $invoice = Model_Core_Billing_Invoice::find($invoice_id);
        if (!$invoice) {
            throw new \RuntimeException('Factura no encontrada.');
        }

        # SE SUMAN CONCEPTOS ACTIVOS
        $subtotal = 0;
        $discount = 0;
        $tax = 0;
        $retention = 0;
        $total = 0;
        foreach (Model_Core_Billing_Invoice_Item::query()->where('invoice_id', '=', $invoice_id)->where('active', '=', 1)->get() as $item) {
            $subtotal += ((float) $item->quantity * (float) $item->unit_price);
            $discount += (float) $item->discount_amount;
            $tax += (float) $item->tax_amount;
            $retention += (float) $item->retention_amount;
            $total += (float) $item->line_total;
        }

        # SE ACTUALIZAN TOTALES
        $invoice->set([
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discount, 2),
            'tax_total' => round($tax, 2),
            'retention_total' => round($retention, 2),
            'total' => round($total, 2),
            'balance_due' => round($total, 2),
        ]);
        $invoice->save();

        return $invoice;
    }

    protected function upsert_sat_cfdi(Model_Core_Billing_Invoice $invoice)
    {
        if (!\DBUtil::table_exists('core_sat_cfdi') || trim((string) $invoice->uuid) === '') {
            return (int) $invoice->cfdi_id;
        }
        $party = $invoice->party_id ? Model_Core_Party::find((int) $invoice->party_id) : null;
        $existing = \DB::select('id')->from('core_sat_cfdi')->where('uuid', '=', (string) $invoice->uuid)->execute()->current();
        $data = [
            'uuid' => (string) $invoice->uuid,
            'direction' => 'issued',
            'version' => '4.0',
            'serie' => (string) $invoice->folio,
            'folio' => (string) $invoice->folio,
            'emitter_rfc' => (string) $this->issuer_rfc(),
            'receiver_rfc' => $party ? (string) $party->rfc : '',
            'customer_party_id' => (int) $invoice->party_id,
            'receiver_party_id' => (int) $invoice->party_id,
            'receiver_name' => $party ? (string) ($party->legal_name ?: $party->name) : '',
            'receiver_regime' => $party ? (string) $party->sat_tax_regime_code : '',
            'issued_at' => (string) $invoice->issue_date.' 00:00:00',
            'stamped_at' => date('Y-m-d H:i:s', (int) $invoice->stamped_at ?: time()),
            'subtotal' => (float) $invoice->subtotal,
            'tax_transferred_total' => (float) $invoice->tax_total,
            'tax_withheld_total' => (float) $invoice->retention_total,
            'total' => (float) $invoice->total,
            'currency' => (string) $invoice->currency_code,
            'exchange_rate' => (float) $invoice->exchange_rate,
            'voucher_type' => $invoice->invoice_type === 'credit_note' ? 'E' : 'I',
            'payment_method' => (string) $invoice->sat_payment_method_code,
            'payment_form' => (string) $invoice->sat_payment_form_code,
            'cfdi_use' => (string) $invoice->sat_cfdi_use_code,
            'sat_status' => 'vigente',
            'sales_status' => 'stamped',
            'portal_visible_customer' => 1,
            'origin' => 'billing',
            'processed' => 1,
            'accounted' => 0,
            'xml_path' => (string) $invoice->xml_path,
            'updated_at' => time(),
        ];
        if ($existing) {
            \DB::update('core_sat_cfdi')->set($data)->where('id', '=', (int) $existing['id'])->execute();
            return (int) $existing['id'];
        }
        $data['created_at'] = time();
        list($id) = \DB::insert('core_sat_cfdi')->set($data)->execute();
        return (int) $id;
    }

    protected function mark_sat_cfdi_cancelled(Model_Core_Billing_Invoice $invoice)
    {
        if ((int) $invoice->cfdi_id > 0 && \DBUtil::table_exists('core_sat_cfdi')) {
            \DB::update('core_sat_cfdi')
                ->set(['sat_status' => 'cancelado', 'sales_status' => 'cancelled', 'cancelled_at' => time(), 'updated_at' => time()])
                ->where('id', '=', (int) $invoice->cfdi_id)
                ->execute();
        }
    }

    protected function log_invoice_event($invoice_id, $type, $summary, array $payload = [])
    {
        \DB::insert('core_billing_invoice_events')->set([
            'invoice_id' => (int) $invoice_id,
            'event_type' => $type,
            'summary' => $summary,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by' => (int) $this->user_id,
            'created_at' => time(),
        ])->execute();
    }

    protected function log_integration_event($type, Model_Core_Integration_Connection $connection, array $payload, $status, $external_id = '')
    {
        list($id) = \DB::insert('core_integration_events')->set([
            'provider_code' => 'factura_com',
            'connection_id' => (int) $connection->id,
            'event_type' => $type,
            'external_id' => $external_id,
            'direction' => 'outgoing',
            'status' => $status,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'received_at' => time(),
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
        return (int) $id;
    }

    protected function finish_integration_event($event_id, array $response)
    {
        \DB::update('core_integration_events')->set([
            'status' => !empty($response['success']) ? 'processed' : 'failed',
            'response_json' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'error_message' => !empty($response['success']) ? '' : substr((string) $response['raw'], 0, 1000),
            'processed_at' => time(),
            'updated_at' => time(),
        ])->where('id', '=', (int) $event_id)->execute();
    }

    protected function cfdi_number($value, $decimals)
    {
        return number_format((float) $value, (int) $decimals, '.', '');
    }

    protected function first_value(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && (string) $data[$key] !== '') {
                return (string) $data[$key];
            }
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->first_value($value, $keys);
                if ($found !== '') {
                    return $found;
                }
            }
        }
        return '';
    }

    protected function sat_tax_code($tax_code, $default)
    {
        $row = \DB::select('sat_tax_code')->from('core_catalog_taxes')->where('code', '=', $tax_code)->execute()->current();
        return $row && !empty($row['sat_tax_code']) ? (string) $row['sat_tax_code'] : $default;
    }

    protected function sat_retention_code($retention_code, $default)
    {
        $row = \DB::select('sat_tax_code')->from('core_catalog_retentions')->where('code', '=', $retention_code)->execute()->current();
        return $row && !empty($row['sat_tax_code']) ? (string) $row['sat_tax_code'] : $default;
    }

    protected function issuer_tax_regime()
    {
        $row = \DB::select('setting_value')->from('core_settings')->where('setting_key', '=', 'sat_tax_regime_code')->execute()->current();
        return $row ? (string) $row['setting_value'] : '601';
    }

    protected function issuer_zip()
    {
        $row = \DB::select('postal_code')->from('core_companies')->execute()->current();
        return $row && !empty($row['postal_code']) ? (string) $row['postal_code'] : '';
    }

    protected function issuer_rfc()
    {
        $row = \DB::select('rfc')->from('core_companies')->execute()->current();
        return $row ? (string) $row['rfc'] : '';
    }

    protected function payment_term_name($id)
    {
        if ($id < 1) {
            return '';
        }
        $row = \DB::select('name')->from('core_catalog_payment_terms')->where('id', '=', $id)->execute()->current();
        return $row ? (string) $row['name'] : '';
    }

    /**
     * INVOICE DEFAULTS
     *
     * COMPLETA VALORES BASE PARA QUE ORM NO INSERTE NULL EN BORRADORES.
     *
     * @access  protected
     * @return  Array
     */
    protected function invoice_defaults(array $data = [])
    {
        # SE DEFINEN VALORES DE BORRADOR ANTES DE TIMBRAR CFDI
        $defaults = [
            'folio' => '',
            'invoice_type' => 'sale',
            'party_id' => 0,
            'cfdi_id' => 0,
            'fiscal_document_id' => 0,
            'fiscal_mode' => 'fiscal_required',
            'requires_waybill' => 0,
            'pac_provider_code' => 'factura_com',
            'pac_connection_id' => 0,
            'pac_series_id' => '',
            'pac_receptor_uid' => '',
            'pac_uid' => '',
            'uuid' => '',
            'sat_status' => '',
            'stamped_at' => 0,
            'cancelled_at' => 0,
            'cancel_motive' => '',
            'cancel_substitute_uuid' => '',
            'pac_request_json' => null,
            'pac_response_json' => null,
            'xml_path' => '',
            'pdf_path' => '',
            'source_module' => 'manual',
            'source_entity_type' => '',
            'source_entity_id' => 0,
            'issue_date' => date('Y-m-d'),
            'due_date' => '',
            'currency_code' => 'MXN',
            'exchange_rate' => 1,
            'payment_term_id' => 0,
            'sat_cfdi_use_code' => 'G03',
            'sat_payment_form_code' => '99',
            'sat_payment_method_code' => 'PPD',
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'retention_total' => 0,
            'total' => 0,
            'balance_due' => 0,
            'status' => 'draft',
            'notes' => '',
            'created_by' => (int) $this->user_id,
            'active' => 1,
        ];

        return array_merge($defaults, $data);
    }

    protected function tax_rate($tax_code)
    {
        $row = \DB::select('rate')->from('core_catalog_taxes')->where('code', '=', $tax_code)->execute()->current();
        return $row ? (float) $row['rate'] : 0.16;
    }

    protected function select_options($table, $value_field, $label_field)
    {
        $rows = \DB::select($value_field, $label_field)->from($table)->where('active', '=', 1)->order_by($label_field, 'asc')->execute();
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $options;
    }

    protected function next_invoice_folio()
    {
        return 'FAC-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_billing_invoices') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function next_fiscal_document_folio($prefix = 'CFDI')
    {
        $count = \DBUtil::table_exists('core_fiscal_documents') ? (int) \DB::count_records('core_fiscal_documents') : 0;
        return $prefix.'-'.date('Ymd').'-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function fiscal_mode($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['system_only', 'fiscal_optional', 'fiscal_required'], true) ? $value : 'fiscal_required';
    }

    protected function fiscal_document_type_for_invoice(Model_Core_Billing_Invoice $invoice)
    {
        $type = (string) $invoice->invoice_type;
        if ($type === 'credit_note') {
            return ['credit_note', 'E'];
        }
        if ($type === 'payment_complement') {
            return ['payment_complement', 'P'];
        }
        if ($type === 'retention') {
            return ['retention', 'R'];
        }
        return ['invoice', 'I'];
    }

    protected function ensure_fiscal_document_from_invoice(Model_Core_Billing_Invoice $invoice, array $payload = [])
    {
        if (!\DBUtil::table_exists('core_fiscal_documents')) {
            return null;
        }

        $document = (int) $invoice->fiscal_document_id > 0 ? Model_Core_Fiscal_Document::find((int) $invoice->fiscal_document_id) : null;
        list($document_type, $voucher_type) = $this->fiscal_document_type_for_invoice($invoice);
        $data = [
            'document_type' => $document_type,
            'cfdi_version' => '4.0',
            'voucher_type' => $voucher_type,
            'party_id' => (int) $invoice->party_id,
            'source_module' => (string) ($invoice->source_module ?: 'billing'),
            'source_entity_type' => 'billing_invoice',
            'source_entity_id' => (int) $invoice->id,
            'source_folio' => (string) $invoice->folio,
            'fiscal_mode' => (string) ($invoice->fiscal_mode ?: 'fiscal_required'),
            'pac_provider_code' => (string) ($invoice->pac_provider_code ?: 'factura_com'),
            'pac_connection_id' => (int) $invoice->pac_connection_id,
            'pac_series_id' => (string) $invoice->pac_series_id,
            'sat_status' => (string) ($invoice->sat_status ?: 'draft'),
            'workflow_status' => $invoice->status === 'stamped' ? 'stamped' : ($invoice->status === 'cancelled' ? 'cancelled' : 'ready'),
            'issue_date' => (string) $invoice->issue_date,
            'currency_code' => (string) $invoice->currency_code,
            'total' => (float) $invoice->total,
            'payload_json' => !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $invoice->pac_request_json,
            'notes' => (string) $invoice->notes,
            'active' => 1,
        ];
        if ($document) {
            $document->set($data);
        } else {
            $data['folio'] = $this->next_fiscal_document_folio($voucher_type === 'P' ? 'REP' : ($voucher_type === 'E' ? 'NCR' : 'CFDI'));
            $data['created_by'] = (int) $this->user_id;
            $document = Model_Core_Fiscal_Document::forge($data);
        }
        $document->save();

        if ((int) $invoice->fiscal_document_id !== (int) $document->id) {
            $invoice->fiscal_document_id = (int) $document->id;
            $invoice->save();
        }

        return $document;
    }

    protected function sync_fiscal_document_from_invoice(Model_Core_Billing_Invoice $invoice, array $payload = [], array $response = [])
    {
        $document = $this->ensure_fiscal_document_from_invoice($invoice, $payload);
        if (!$document) {
            return;
        }
        $document->set([
            'pac_uid' => (string) $invoice->pac_uid,
            'uuid' => (string) $invoice->uuid,
            'sat_status' => (string) ($invoice->sat_status ?: $document->sat_status),
            'workflow_status' => (string) ($invoice->status ?: $document->workflow_status),
            'response_json' => !empty($response) ? json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $document->response_json,
            'xml_path' => (string) $invoice->xml_path,
            'pdf_path' => (string) $invoice->pdf_path,
        ]);
        $document->save();
    }

    protected function next_recurring_folio()
    {
        return 'REC-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_billing_recurring_profiles') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function recurring_response($profile_id)
    {
        return $this->json_response([
            'status' => 'ok',
            'recurring_profiles' => $this->get_recurring_profiles(),
            'recurring_items' => $this->get_recurring_items($profile_id),
            'recurring_runs' => $this->get_recurring_runs(),
            'stats' => $this->get_stats(),
        ]);
    }

    protected function create_invoice_item_from_recurring($invoice_id, array $row)
    {
        $quantity = max(0, (float) $row['quantity']);
        $unit_price = max(0, (float) $row['unit_price']);
        $discount = max(0, (float) $row['discount_amount']);
        $tax_rate = max(0, (float) $row['tax_rate']);
        $base = max(0, ($quantity * $unit_price) - $discount);
        $tax_amount = round($base * $tax_rate, 2);
        $retention = max(0, (float) $row['retention_amount']);

        Model_Core_Billing_Invoice_Item::forge([
            'invoice_id' => (int) $invoice_id,
            'product_id' => (int) $row['product_id'],
            'sat_product_service_code' => (string) $row['sat_product_service_code'],
            'description' => (string) $row['description'],
            'quantity' => $quantity,
            'unit_code' => (string) $row['unit_code'],
            'sat_object_tax_code' => (string) $row['sat_object_tax_code'],
            'unit_price' => $unit_price,
            'discount_amount' => $discount,
            'tax_code' => (string) $row['tax_code'],
            'tax_factor_type' => (string) $row['tax_factor_type'],
            'tax_rate' => $tax_rate,
            'tax_amount' => $tax_amount,
            'retention_amount' => $retention,
            'retention_tax_code' => (string) $row['retention_tax_code'],
            'retention_rate' => (float) $row['retention_rate'],
            'line_total' => round($base + $tax_amount - $retention, 2),
            'sort_order' => (int) $row['sort_order'],
            'active' => 1,
        ])->save();
    }

    protected function next_recurring_date($date, $frequency)
    {
        $base = strtotime($date) ?: time();
        switch ($this->recurring_frequency($frequency)) {
            case 'weekly':
                return date('Y-m-d', strtotime('+1 week', $base));
            case 'biweekly':
                return date('Y-m-d', strtotime('+2 weeks', $base));
            case 'quarterly':
                return date('Y-m-d', strtotime('+3 months', $base));
            case 'yearly':
                return date('Y-m-d', strtotime('+1 year', $base));
            case 'monthly':
            default:
                return date('Y-m-d', strtotime('+1 month', $base));
        }
    }

    protected function recurring_frequency($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'], true) ? $value : 'monthly';
    }

    protected function recurring_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['active', 'paused', 'finished'], true) ? $value : 'active';
    }

    protected function assert_schema_ready()
    {
        foreach (['core_billing_invoices', 'core_billing_invoice_items', 'core_billing_invoice_events'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de facturacion.');
            }
        }
        foreach (['core_billing_recurring_profiles', 'core_billing_recurring_items', 'core_billing_recurring_runs'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de facturacion recurrente.');
            }
        }
        if (!\DBUtil::field_exists('core_billing_invoices', ['pac_provider_code', 'pac_uid', 'uuid', 'fiscal_document_id', 'fiscal_mode'])) {
            throw new \RuntimeException('Falta ejecutar migraciones PAC de facturacion.');
        }
        if (!\DBUtil::table_exists('core_fiscal_documents')) {
            throw new \RuntimeException('Falta ejecutar migracion de documentos fiscales transversales.');
        }
        if (!\DBUtil::field_exists('core_commerce_products', ['product_type', 'is_internal_service'])) {
            throw new \RuntimeException('Falta ejecutar migracion de productos tipo servicio.');
        }
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
