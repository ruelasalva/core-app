<?php

/**
 * CONTROLADOR ADMIN_PAYMENTS
 *
 * Administra pagos, movimientos bancarios y conciliaciones base.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Payments extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE PAGOS
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('payments.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE PAGOS Y BANCOS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Pagos y Bancos';
        $this->template->content = View::forge('admin/payments/index');
    }

    /**
     * DATA
     *
     * ENTREGA PAGOS, MOVIMIENTOS, CONCILIACIONES Y OPCIONES
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
                'payments' => $this->get_payments(),
                'receivables' => $this->get_receivables(),
                'movements' => $this->get_movements(),
                'reconciliations' => $this->get_reconciliations(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando pagos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar pagos y bancos.'], 500);
        }
    }

    /**
     * SAVE PAYMENT
     *
     * CREA O ACTUALIZA UN PAGO
     *
     * @access  public
     * @return  Response
     */
    public function action_save_payment()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('payments.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN CAMPOS
            if ((float) \Arr::get($val, 'amount', 0) <= 0) {
                return $this->json_response(['error' => 'El importe debe ser mayor a cero.'], 422);
            }

            # SE PREPARAN DATOS
            $id = (int) \Arr::get($val, 'id', 0);
            $allocation_entity_type = $this->codeify(\Arr::get($val, 'allocation_entity_type', ''));
            $allocation_entity_id = (int) \Arr::get($val, 'allocation_entity_id', 0);
            $data = [
                'payment_type' => $this->codeify(\Arr::get($val, 'payment_type', 'received')),
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'bank_account_id' => (int) \Arr::get($val, 'bank_account_id', 0),
                'integration_connection_id' => (int) \Arr::get($val, 'integration_connection_id', 0),
                'fiscal_document_id' => (int) \Arr::get($val, 'fiscal_document_id', 0),
                'fiscal_mode' => $this->fiscal_mode(\Arr::get($val, 'fiscal_mode', 'system_only')),
                'rep_status' => $this->rep_status(\Arr::get($val, 'rep_status', 'not_required')),
                'payment_date' => trim((string) \Arr::get($val, 'payment_date', date('Y-m-d'))),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'amount' => (float) \Arr::get($val, 'amount', 0),
                'sat_payment_form_code' => trim((string) \Arr::get($val, 'sat_payment_form_code', '99')),
                'reference' => trim((string) \Arr::get($val, 'reference', '')),
                'external_id' => trim((string) \Arr::get($val, 'external_id', '')),
                'status' => $this->codeify(\Arr::get($val, 'status', 'pending')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE CREA O ACTUALIZA
            if ($id > 0) {
                $payment = Model_Core_Payment::find($id);
                if (!$payment) {
                    return $this->json_response(['error' => 'Pago no encontrado.'], 404);
                }
                $old = $payment->to_array();
                $payment->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_payment_folio();
                $data['created_by'] = $this->user_id;
                $payment = Model_Core_Payment::forge($data);
            }
            $payment->save();

            if ($id === 0 && $allocation_entity_type === 'billing_invoice' && $allocation_entity_id > 0) {
                $this->apply_payment_to_invoice($payment, $allocation_entity_id);
                $invoice = Model_Core_Billing_Invoice::find($allocation_entity_id);
                if ($invoice && (string) $invoice->sat_payment_method_code === 'PPD' && (string) $payment->fiscal_mode === 'fiscal_required') {
                    $this->create_payment_complement_document($payment, $invoice);
                }
            }

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'payments',
                'action' => $id > 0 ? 'update_payment' : 'create_payment',
                'entity_type' => 'payment',
                'entity_id' => (int) $payment->id,
                'summary' => 'Pago '.$payment->folio.' por '.$payment->amount.' '.$payment->currency_code,
                'old_values' => $old,
                'new_values' => $payment->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'payments' => $this->get_payments(), 'receivables' => $this->get_receivables(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando pago: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el pago.'], 400);
        }
    }

    /**
     * SAVE MOVEMENT
     *
     * CREA O ACTUALIZA MOVIMIENTO BANCARIO
     *
     * @access  public
     * @return  Response
     */
    public function action_save_movement()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('payments.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN CAMPOS
            if ((int) \Arr::get($val, 'bank_account_id', 0) < 1 || (float) \Arr::get($val, 'amount', 0) <= 0) {
                return $this->json_response(['error' => 'Cuenta bancaria e importe son obligatorios.'], 422);
            }

            # SE PREPARAN DATOS
            $data = [
                'bank_account_id' => (int) \Arr::get($val, 'bank_account_id', 0),
                'movement_date' => trim((string) \Arr::get($val, 'movement_date', date('Y-m-d'))),
                'movement_type' => $this->codeify(\Arr::get($val, 'movement_type', 'deposit')),
                'amount' => (float) \Arr::get($val, 'amount', 0),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'reference' => trim((string) \Arr::get($val, 'reference', '')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'source' => $this->codeify(\Arr::get($val, 'source', 'manual')),
                'payment_id' => (int) \Arr::get($val, 'payment_id', 0),
                'reconciled' => (int) (bool) \Arr::get($val, 'reconciled', false),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE CREA O ACTUALIZA
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $movement = Model_Core_Bank_Movement::find($id);
                if (!$movement) {
                    return $this->json_response(['error' => 'Movimiento no encontrado.'], 404);
                }
                $old = $movement->to_array();
                $movement->set($data);
            } else {
                $old = [];
                $movement = Model_Core_Bank_Movement::forge($data);
            }
            $movement->save();

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'payments',
                'action' => $id > 0 ? 'update_bank_movement' : 'create_bank_movement',
                'entity_type' => 'bank_movement',
                'entity_id' => (int) $movement->id,
                'summary' => 'Movimiento bancario '.$movement->movement_type.' por '.$movement->amount,
                'old_values' => $old,
                'new_values' => $movement->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'movements' => $this->get_movements(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando movimiento bancario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el movimiento.'], 400);
        }
    }

    /**
     * GET PAYMENTS
     *
     * FORMATEA PAGOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_payments()
    {
        $items = [];
        foreach (Model_Core_Payment::query()->order_by('id', 'desc')->limit(200)->get() as $payment) {
            $row = $payment->to_array();
            $row['created_at'] = $payment->created_at ? date('d/m/Y H:i', $payment->created_at) : '';
            $items[] = $row;
        }
        return $items;
    }

    /**
     * GET MOVEMENTS
     *
     * FORMATEA MOVIMIENTOS BANCARIOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_movements()
    {
        $items = [];
        foreach (Model_Core_Bank_Movement::query()->order_by('id', 'desc')->limit(200)->get() as $movement) {
            $row = $movement->to_array();
            $row['created_at'] = $movement->created_at ? date('d/m/Y H:i', $movement->created_at) : '';
            $items[] = $row;
        }
        return $items;
    }

    /**
     * GET RECEIVABLES
     *
     * LISTA FACTURAS DE VENTA CON SALDO PARA COBRANZA.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_receivables()
    {
        if (!\DBUtil::table_exists('core_billing_invoices')) {
            return [];
        }

        $query = \DB::select(
                ['i.id', 'id'],
                ['i.folio', 'folio'],
                ['i.uuid', 'uuid'],
                ['i.party_id', 'party_id'],
                ['p.name', 'party_name'],
                ['i.issue_date', 'issue_date'],
                ['i.due_date', 'due_date'],
                ['i.currency_code', 'currency_code'],
                ['i.total', 'total'],
                ['i.balance_due', 'balance_due'],
                ['i.status', 'status'],
                ['i.sat_payment_method_code', 'sat_payment_method_code'],
                ['i.sat_payment_form_code', 'sat_payment_form_code']
            )
            ->from(['core_billing_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('i.balance_due', '>', 0);
        $this->apply_party_scope($query, 'p', 'sales');

        return $query
            ->order_by('i.due_date', 'asc')
            ->order_by('i.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function get_reconciliations()
    {
        $items = [];
        foreach (Model_Core_Bank_Reconciliation::query()->order_by('id', 'desc')->limit(100)->get() as $reconciliation) {
            $items[] = $reconciliation->to_array();
        }
        return $items;
    }

    protected function get_options()
    {
        return [
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'bank_accounts' => $this->select_options('core_catalog_bank_accounts', 'id', 'name'),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'sat_payment_forms' => Helper_Core_Sat_Catalog::options('core_sat_payment_forms'),
            'integrations' => $this->select_options('core_integration_connections', 'id', 'name'),
        ];
    }

    protected function get_stats()
    {
        $receivable_rows = $this->get_receivables();
        $receivables = count($receivable_rows);
        $receivable_total = 0;
        foreach ($receivable_rows as $row) {
            $receivable_total += (float) $row['balance_due'];
        }

        return [
            'payments' => (int) \DB::count_records('core_payments'),
            'receivables' => $receivables,
            'receivable_total' => $receivable_total,
            'pending' => (int) \DB::select()->from('core_payments')->where('status', '=', 'pending')->execute()->count(),
            'rep_pending' => \DBUtil::field_exists('core_payments', ['rep_status']) ? (int) \DB::select()->from('core_payments')->where('rep_status', '=', 'pending')->execute()->count() : 0,
            'movements' => (int) \DB::count_records('core_bank_movements'),
            'unreconciled' => (int) \DB::select()->from('core_bank_movements')->where('reconciled', '=', 0)->execute()->count(),
        ];
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

    protected function next_payment_folio()
    {
        return 'PAY-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_payments') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_payments', 'core_payment_allocations', 'core_bank_movements', 'core_bank_reconciliations', 'core_fiscal_documents'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de pagos.');
            }
        }
        if (!\DBUtil::field_exists('core_payments', ['fiscal_mode', 'rep_status', 'fiscal_document_id'])) {
            throw new \RuntimeException('Falta ejecutar migracion fiscal de pagos.');
        }
    }

    /**
     * APPLY PAYMENT TO INVOICE
     *
     * APLICA UN COBRO A UNA FACTURA DE VENTA Y ACTUALIZA SALDO.
     *
     * @access  protected
     * @param   Model_Core_Payment  $payment
     * @param   int                 $invoice_id
     * @return  Void
     */
    protected function apply_payment_to_invoice(Model_Core_Payment $payment, $invoice_id)
    {
        $invoice = Model_Core_Billing_Invoice::find((int) $invoice_id);
        if (!$invoice || $invoice->invoice_type !== 'sale' || (int) $invoice->active !== 1) {
            return;
        }

        $balance = max(0, (float) $invoice->balance_due);
        $amount = min(max(0, (float) $payment->amount), $balance);
        if ($amount <= 0) {
            return;
        }

        Model_Core_Payment_Allocation::forge([
            'payment_id' => (int) $payment->id,
            'entity_type' => 'billing_invoice',
            'entity_id' => (int) $invoice->id,
            'amount' => round($amount, 2),
            'notes' => 'Aplicacion de cobro desde Pagos y Bancos',
            'active' => 1,
        ])->save();

        $invoice->balance_due = round(max(0, $balance - $amount), 2);
        $invoice->status = $invoice->balance_due <= 0 ? 'paid' : 'partial';
        $invoice->save();
    }

    protected function create_payment_complement_document(Model_Core_Payment $payment, Model_Core_Billing_Invoice $invoice)
    {
        if (!\DBUtil::table_exists('core_fiscal_documents')) {
            return;
        }
        if ((int) $payment->fiscal_document_id > 0) {
            return;
        }

        $payload = [
            'TipoDocumento' => 'complemento_pago',
            'Pago' => [
                'FechaPago' => (string) $payment->payment_date,
                'FormaDePagoP' => (string) $payment->sat_payment_form_code,
                'MonedaP' => (string) $payment->currency_code,
                'Monto' => (float) $payment->amount,
                'Referencia' => (string) $payment->reference,
            ],
            'DocumentosRelacionados' => [[
                'IdDocumento' => (string) $invoice->uuid,
                'Folio' => (string) $invoice->folio,
                'MonedaDR' => (string) $invoice->currency_code,
                'MetodoDePagoDR' => (string) $invoice->sat_payment_method_code,
                'ImpPagado' => (float) $payment->amount,
                'ImpSaldoInsoluto' => (float) $invoice->balance_due,
            ]],
        ];

        list($id) = \DB::insert('core_fiscal_documents')->set([
            'folio' => $this->next_fiscal_document_folio('REP'),
            'document_type' => 'payment_complement',
            'cfdi_version' => '4.0',
            'voucher_type' => 'P',
            'party_id' => (int) $payment->party_id,
            'source_module' => 'payments',
            'source_entity_type' => 'payment',
            'source_entity_id' => (int) $payment->id,
            'source_folio' => (string) $payment->folio,
            'fiscal_mode' => (string) $payment->fiscal_mode,
            'pac_provider_code' => 'factura_com',
            'related_uuid' => (string) $invoice->uuid,
            'sat_status' => 'draft',
            'workflow_status' => 'draft',
            'issue_date' => (string) $payment->payment_date,
            'currency_code' => (string) $payment->currency_code,
            'total' => (float) $payment->amount,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'notes' => 'REP pendiente para pago '.$payment->folio.' aplicado a '.$invoice->folio,
            'created_by' => (int) $this->user_id,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        $payment->fiscal_document_id = (int) $id;
        $payment->rep_status = 'pending';
        $payment->save();
    }

    protected function next_fiscal_document_folio($prefix)
    {
        $count = \DBUtil::table_exists('core_fiscal_documents') ? (int) \DB::count_records('core_fiscal_documents') : 0;
        return $prefix.'-'.date('Ymd').'-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function fiscal_mode($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['system_only', 'fiscal_optional', 'fiscal_required'], true) ? $value : 'system_only';
    }

    protected function rep_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['not_required', 'pending', 'prepared', 'stamped', 'cancelled'], true) ? $value : 'not_required';
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
