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
            $filters = $this->period_filters();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'payments' => $this->get_payments($filters),
                'receivables' => $this->get_receivables(),
                'payables' => $this->get_payables(),
                'movements' => $this->get_movements($filters),
                'reconciliations' => $this->get_reconciliations($filters),
                'statement_imports' => $this->get_statement_imports($filters),
                'suggestions' => $this->get_reconciliation_suggestions(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats($filters),
                'period_filters' => $filters,
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
            } elseif ($id === 0 && $allocation_entity_type === 'purchase_invoice' && $allocation_entity_id > 0) {
                $this->apply_payment_to_purchase_invoice($payment, $allocation_entity_id);
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

            return $this->json_response(['status' => 'ok', 'payments' => $this->get_payments(), 'receivables' => $this->get_receivables(), 'payables' => $this->get_payables(), 'stats' => $this->get_stats()]);
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
                'balance_after' => (float) \Arr::get($val, 'balance_after', 0),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'reference' => trim((string) \Arr::get($val, 'reference', '')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'source' => $this->codeify(\Arr::get($val, 'source', 'manual')),
                'statement_import_id' => (int) \Arr::get($val, 'statement_import_id', 0),
                'checksum' => trim((string) \Arr::get($val, 'checksum', '')),
                'source_row_json' => trim((string) \Arr::get($val, 'source_row_json', '')),
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
     * IMPORT STATEMENT
     *
     * IMPORTA ESTADO DE CUENTA CSV/TXT/PDF COMO MOVIMIENTOS BANCARIOS.
     *
     * @access  public
     * @return  Response
     */
    public function action_import_statement()
    {
        $this->require_access('payments.access[edit]');

        try {
            $this->assert_schema_ready();
            $bank_account_id = (int) \Input::post('bank_account_id', 0);
            \Log::info('Pagos: importacion estado cuenta bank_account_id='.$bank_account_id.' file='.(string) \Arr::get((array) \Input::file('file'), 'name', ''));
            if ($bank_account_id < 1) {
                return $this->json_response(['error' => 'Selecciona una cuenta bancaria.'], 422);
            }

            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona un archivo CSV, TXT o PDF valido.'], 422);
            }

            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'txt', 'pdf'], true)) {
                return $this->json_response(['error' => 'Por seguridad solo se aceptan CSV, TXT o PDF bancarios.'], 422);
            }

            $relative_dir = 'assets/uploads/documents/bank_statements/'.date('Y').'/'.date('m');
            $absolute_dir = DOCROOT.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0755, true);
            }

            $filename = time().'_'.\Str::random('alnum', 10).'_'.$this->codeify(pathinfo((string) \Arr::get($file, 'name', 'estado'), PATHINFO_FILENAME)).'.'.$extension;
            $target = $absolute_dir.DS.$filename;
            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->json_response(['error' => 'No se pudo guardar el archivo.'], 400);
            }

            $source_format = $extension === 'pdf' ? 'bbva_pdf' : 'csv';
            $rows = $extension === 'pdf' ? $this->parse_statement_pdf($target) : $this->parse_statement_csv($target);
            \Log::info('Pagos: estado cuenta parseado formato='.$source_format.' filas='.count($rows));
            if (empty($rows)) {
                return $this->json_response(['error' => 'No se detectaron movimientos en el archivo.'], 422);
            }

            $period_start = $this->statement_period_date($rows, 'min');
            $period_end = $this->statement_period_date($rows, 'max');
            $original_name = (string) \Arr::get($file, 'name', '');
            $statement = $this->find_existing_statement_import($bank_account_id, $source_format, $original_name, $period_start, $period_end);
            $already_loaded = (bool) $statement;

            if (!$statement) {
                $statement = Model_Core_Bank_Statement_Import::forge([
                    'bank_account_id' => $bank_account_id,
                    'source_format' => $source_format,
                    'original_name' => $original_name,
                    'file_path' => str_replace('\\', '/', $relative_dir.'/'.$filename),
                    'period_start' => $period_start,
                    'period_end' => $period_end,
                    'rows_count' => count($rows),
                    'imported_count' => 0,
                    'duplicate_count' => 0,
                    'status' => 'processed',
                    'notes' => trim((string) \Input::post('notes', '')),
                    'created_by' => (int) $this->user_id,
                ]);
                $statement->save();
            }

            $imported = 0;
            $duplicates = 0;
            foreach ($rows as $row) {
                $row['reference'] = $this->limit_text((string) $row['reference'], 120);
                $row['description'] = $this->limit_text((string) $row['description'], 255);
                $checksum = $this->movement_checksum($bank_account_id, $row);
                if ($this->find_existing_bank_movement($bank_account_id, $row, $checksum)) {
                    $duplicates++;
                    continue;
                }

                Model_Core_Bank_Movement::forge([
                    'bank_account_id' => $bank_account_id,
                    'movement_date' => $row['movement_date'],
                    'movement_type' => $row['movement_type'],
                    'amount' => $row['amount'],
                    'balance_after' => $row['balance_after'],
                    'currency_code' => $row['currency_code'],
                    'reference' => $row['reference'],
                    'description' => $row['description'],
                    'checksum' => $checksum,
                    'source_row_json' => json_encode($row['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'source' => 'statement_'.$source_format,
                    'statement_import_id' => (int) $statement->id,
                    'payment_id' => 0,
                    'reconciled' => 0,
                    'active' => 1,
                ])->save();
                $imported++;
            }

            $statement->rows_count = max((int) $statement->rows_count, count($rows));
            $statement->file_path = $statement->file_path ?: str_replace('\\', '/', $relative_dir.'/'.$filename);
            $statement->imported_count = (int) $statement->imported_count + $imported;
            $statement->duplicate_count = $duplicates;
            $statement->save();
            if ($already_loaded && is_file($target)) {
                @unlink($target);
            }
            $this->generate_reconciliation_suggestions();
            $response_filters = [
                'start_date' => $statement->period_start ?: date('Y-m-01'),
                'end_date' => $statement->period_end ?: date('Y-m-t'),
            ];

            Helper_Core_Audit::log([
                'module' => 'payments',
                'action' => 'import_bank_statement',
                'entity_type' => 'bank_statement_import',
                'entity_id' => (int) $statement->id,
                'summary' => ($already_loaded ? 'Estado de cuenta ya cargado revisado: ' : 'Estado de cuenta importado: ').$imported.' movimientos, '.$duplicates.' duplicados',
                'new_values' => $statement->to_array(),
            ]);

            $message = $already_loaded
                ? 'Este estado de cuenta ya estaba cargado. Se agregaron '.$imported.' movimientos faltantes y se omitieron '.$duplicates.' duplicados.'
                : 'Estado de cuenta importado: '.$imported.' movimientos nuevos, '.$duplicates.' duplicados.';

            return $this->json_response([
                'status' => 'ok',
                'message' => $message,
                'movements' => $this->get_movements($response_filters),
                'statement_imports' => $this->get_statement_imports($response_filters),
                'statement_import' => $this->get_statement_import_summary($statement),
                'suggestions' => $this->get_reconciliation_suggestions(),
                'stats' => $this->get_stats($response_filters),
                'period_filters' => $response_filters,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error importando estado de cuenta: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * SUGGEST RECONCILIATION
     *
     * GENERA SUGERENCIAS DE CRUCE PARA MOVIMIENTOS NO CONCILIADOS.
     *
     * @access  public
     * @return  Response
     */
    public function action_suggest_reconciliation()
    {
        $this->require_access('payments.access[edit]');

        try {
            $created = $this->generate_reconciliation_suggestions();
            return $this->json_response([
                'status' => 'ok',
                'message' => 'Sugerencias generadas: '.$created,
                'statement_imports' => $this->get_statement_imports(),
                'suggestions' => $this->get_reconciliation_suggestions(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generando sugerencias de conciliacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron generar sugerencias.'], 400);
        }
    }

    /**
     * APPLY SUGGESTION
     *
     * APLICA UNA SUGERENCIA DE CONCILIACION CONFIRMADA POR EL USUARIO.
     *
     * @access  public
     * @return  Response
     */
    public function action_apply_suggestion()
    {
        $this->require_access('payments.access[edit]');
        $val = (array) \Input::json();

        try {
            $suggestion = Model_Core_Bank_Reconciliation_Suggestion::find((int) \Arr::get($val, 'id', 0));
            if (!$suggestion || $suggestion->status !== 'pending') {
                return $this->json_response(['error' => 'Sugerencia no encontrada o ya aplicada.'], 404);
            }

            $movement = Model_Core_Bank_Movement::find((int) $suggestion->movement_id);
            if (!$movement || (int) $movement->reconciled === 1) {
                return $this->json_response(['error' => 'Movimiento no disponible para conciliacion.'], 422);
            }

            if ($suggestion->suggested_entity_type === 'payment') {
                $payment = Model_Core_Payment::find((int) $suggestion->suggested_entity_id);
                if (!$payment) {
                    return $this->json_response(['error' => 'Pago sugerido no encontrado.'], 404);
                }
            } elseif ($suggestion->suggested_entity_type === 'billing_invoice') {
                $invoice = Model_Core_Billing_Invoice::find((int) $suggestion->suggested_entity_id);
                if (!$invoice) {
                    return $this->json_response(['error' => 'Factura de cliente no encontrada.'], 404);
                }
                $payment = $this->create_payment_from_movement($movement, 'received', (int) $invoice->party_id, (string) $invoice->folio);
                $this->apply_payment_to_invoice($payment, (int) $invoice->id);
            } elseif ($suggestion->suggested_entity_type === 'purchase_invoice') {
                $invoice = Model_Core_Purchase_Invoice::find((int) $suggestion->suggested_entity_id);
                if (!$invoice) {
                    return $this->json_response(['error' => 'Factura de proveedor no encontrada.'], 404);
                }
                $payment = $this->create_payment_from_movement($movement, 'sent', (int) $invoice->party_id, (string) $invoice->folio);
                $this->apply_payment_to_purchase_invoice($payment, (int) $invoice->id);
            } else {
                return $this->json_response(['error' => 'Tipo de sugerencia no soportado.'], 422);
            }

            $movement->payment_id = (int) $payment->id;
            $movement->reconciled = 1;
            $movement->save();

            $suggestion->status = 'applied';
            $suggestion->applied_by = (int) $this->user_id;
            $suggestion->applied_at = time();
            $suggestion->save();

            Helper_Core_Audit::log([
                'module' => 'payments',
                'action' => 'apply_bank_reconciliation_suggestion',
                'entity_type' => 'bank_movement',
                'entity_id' => (int) $movement->id,
                'summary' => 'Movimiento bancario conciliado con '.$suggestion->suggested_entity_type.' #'.$suggestion->suggested_entity_id,
                'new_values' => [
                    'movement_id' => (int) $movement->id,
                    'payment_id' => (int) $payment->id,
                    'suggestion_id' => (int) $suggestion->id,
                ],
            ]);

            return $this->json_response([
                'status' => 'ok',
                'message' => 'Movimiento conciliado.',
                'payments' => $this->get_payments(),
                'receivables' => $this->get_receivables(),
                'payables' => $this->get_payables(),
                'movements' => $this->get_movements(),
                'statement_imports' => $this->get_statement_imports(),
                'suggestions' => $this->get_reconciliation_suggestions(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error aplicando sugerencia de conciliacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo aplicar la sugerencia.'], 400);
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
    protected function get_payments(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $items = [];
        foreach (Model_Core_Payment::query()->where('payment_date', '>=', $filters['start_date'])->where('payment_date', '<=', $filters['end_date'])->order_by('id', 'desc')->limit(200)->get() as $payment) {
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
    protected function get_movements(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $items = [];
        foreach (Model_Core_Bank_Movement::query()->where('movement_date', '>=', $filters['start_date'])->where('movement_date', '<=', $filters['end_date'])->order_by('id', 'desc')->limit(200)->get() as $movement) {
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

    /**
     * GET PAYABLES
     *
     * LISTA FACTURAS DE PROVEEDOR CON SALDO PARA PAGO.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_payables()
    {
        if (!\DBUtil::table_exists('core_purchase_invoices')) {
            return [];
        }

        $query = \DB::select(
                ['i.id', 'id'],
                ['i.folio', 'folio'],
                ['i.uuid', 'uuid'],
                ['i.party_id', 'party_id'],
                ['p.name', 'party_name'],
                ['i.invoice_date', 'invoice_date'],
                ['i.due_date', 'due_date'],
                ['i.currency_code', 'currency_code'],
                ['i.total', 'total'],
                ['i.balance_due', 'balance_due'],
                ['i.status', 'status'],
                ['i.validation_status', 'validation_status']
            )
            ->from(['core_purchase_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.active', '=', 1)
            ->where('i.balance_due', '>', 0);
        $this->apply_party_scope($query, 'p', 'purchases');

        return $query
            ->order_by('i.due_date', 'asc')
            ->order_by('i.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function get_reconciliations(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $items = [];
        foreach (Model_Core_Bank_Reconciliation::query()->where('period_start', '<=', $filters['end_date'])->where('period_end', '>=', $filters['start_date'])->order_by('id', 'desc')->limit(100)->get() as $reconciliation) {
            $items[] = $reconciliation->to_array();
        }
        return $items;
    }

    protected function get_statement_imports(array $filters = [])
    {
        if (!\DBUtil::table_exists('core_bank_statement_imports')) {
            return [];
        }

        $filters = $filters ?: $this->period_filters();
        $items = [];
        foreach (Model_Core_Bank_Statement_Import::query()->where('period_start', '<=', $filters['end_date'])->where('period_end', '>=', $filters['start_date'])->order_by('id', 'desc')->limit(50)->get() as $statement) {
            $items[] = $this->get_statement_import_summary($statement);
        }
        return $items;
    }

    /**
     * GET STATEMENT IMPORT SUMMARY
     *
     * AGREGA TOTALES DE MOVIMIENTOS, CONCILIADOS Y SUGERENCIAS POR ESTADO.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_statement_import_summary(Model_Core_Bank_Statement_Import $statement)
    {
        $row = $statement->to_array();
        $row['created_at_label'] = $statement->created_at ? date('d/m/Y H:i', (int) $statement->created_at) : '';
        $row['period_label'] = trim(($statement->period_start ?: '-').' a '.($statement->period_end ?: '-'));

        $movement_totals = \DB::select(
                \DB::expr('COUNT(*) as movements_count'),
                \DB::expr('COALESCE(SUM(CASE WHEN reconciled = 1 THEN 1 ELSE 0 END), 0) as reconciled_count'),
                \DB::expr('COALESCE(SUM(CASE WHEN reconciled = 0 THEN 1 ELSE 0 END), 0) as pending_count'),
                \DB::expr("COALESCE(SUM(CASE WHEN movement_type = 'deposit' THEN amount ELSE 0 END), 0) as deposits_total"),
                \DB::expr("COALESCE(SUM(CASE WHEN movement_type = 'withdrawal' THEN amount ELSE 0 END), 0) as withdrawals_total")
            )
            ->from('core_bank_movements')
            ->where('statement_import_id', '=', (int) $statement->id)
            ->execute()
            ->current();

        $suggestion_totals = \DB::select(\DB::expr('COUNT(*) as suggestions_count'))
            ->from(['core_bank_reconciliation_suggestions', 's'])
            ->join(['core_bank_movements', 'm'], 'left')->on('s.movement_id', '=', 'm.id')
            ->where('m.statement_import_id', '=', (int) $statement->id)
            ->where('s.status', '=', 'pending')
            ->execute()
            ->current();

        $row['movements_count'] = (int) \Arr::get($movement_totals, 'movements_count', 0);
        $row['reconciled_count'] = (int) \Arr::get($movement_totals, 'reconciled_count', 0);
        $row['pending_count'] = (int) \Arr::get($movement_totals, 'pending_count', 0);
        $row['deposits_total'] = (float) \Arr::get($movement_totals, 'deposits_total', 0);
        $row['withdrawals_total'] = (float) \Arr::get($movement_totals, 'withdrawals_total', 0);
        $row['suggestions_count'] = (int) \Arr::get($suggestion_totals, 'suggestions_count', 0);
        $row['reconciliation_progress'] = $row['movements_count'] > 0 ? round(($row['reconciled_count'] / $row['movements_count']) * 100) : 0;

        return $row;
    }

    protected function get_reconciliation_suggestions()
    {
        if (!\DBUtil::table_exists('core_bank_reconciliation_suggestions')) {
            return [];
        }

        $items = [];
        $rows = \DB::select(
                ['s.id', 'id'],
                ['s.movement_id', 'movement_id'],
                ['s.suggested_entity_type', 'suggested_entity_type'],
                ['s.suggested_entity_id', 'suggested_entity_id'],
                ['s.payment_type', 'payment_type'],
                ['s.party_id', 'party_id'],
                ['p.name', 'party_name'],
                ['s.amount', 'amount'],
                ['s.currency_code', 'currency_code'],
                ['s.score', 'score'],
                ['s.reasons_json', 'reasons_json'],
                ['s.status', 'status'],
                ['m.movement_date', 'movement_date'],
                ['m.movement_type', 'movement_type'],
                ['m.reference', 'movement_reference'],
                ['m.description', 'movement_description']
            )
            ->from(['core_bank_reconciliation_suggestions', 's'])
            ->join(['core_bank_movements', 'm'], 'left')->on('s.movement_id', '=', 'm.id')
            ->join(['core_parties', 'p'], 'left')->on('s.party_id', '=', 'p.id')
            ->where('s.status', '=', 'pending')
            ->order_by('s.score', 'desc')
            ->order_by('s.id', 'desc')
            ->limit(200)
            ->execute();

        foreach ($rows as $row) {
            $row['reasons'] = json_decode((string) $row['reasons_json'], true) ?: [];
            $row['entity_label'] = $this->reconciliation_entity_label((string) $row['suggested_entity_type'], (int) $row['suggested_entity_id']);
            $items[] = $row;
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

    protected function get_stats(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $receivable_rows = $this->get_receivables();
        $payable_rows = $this->get_payables();
        $receivables = count($receivable_rows);
        $payables = count($payable_rows);
        $receivable_total = 0;
        $payable_total = 0;
        foreach ($receivable_rows as $row) {
            $receivable_total += (float) $row['balance_due'];
        }
        foreach ($payable_rows as $row) {
            $payable_total += (float) $row['balance_due'];
        }

        return [
            'payments' => (int) \DB::select()->from('core_payments')->where('payment_date', '>=', $filters['start_date'])->where('payment_date', '<=', $filters['end_date'])->execute()->count(),
            'receivables' => $receivables,
            'receivable_total' => $receivable_total,
            'payables' => $payables,
            'payable_total' => $payable_total,
            'pending' => (int) \DB::select()->from('core_payments')->where('status', '=', 'pending')->where('payment_date', '>=', $filters['start_date'])->where('payment_date', '<=', $filters['end_date'])->execute()->count(),
            'rep_pending' => \DBUtil::field_exists('core_payments', ['rep_status']) ? (int) \DB::select()->from('core_payments')->where('rep_status', '=', 'pending')->where('payment_date', '>=', $filters['start_date'])->where('payment_date', '<=', $filters['end_date'])->execute()->count() : 0,
            'movements' => (int) \DB::select()->from('core_bank_movements')->where('movement_date', '>=', $filters['start_date'])->where('movement_date', '<=', $filters['end_date'])->execute()->count(),
            'unreconciled' => (int) \DB::select()->from('core_bank_movements')->where('reconciled', '=', 0)->where('movement_date', '>=', $filters['start_date'])->where('movement_date', '<=', $filters['end_date'])->execute()->count(),
            'statement_imports' => count($this->get_statement_imports($filters)),
            'reconciliation_suggestions' => \DBUtil::table_exists('core_bank_reconciliation_suggestions') ? (int) \DB::select()->from('core_bank_reconciliation_suggestions')->where('status', '=', 'pending')->execute()->count() : 0,
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
        if (!\DBUtil::table_exists('core_bank_statement_imports') || !\DBUtil::table_exists('core_bank_reconciliation_suggestions')) {
            throw new \RuntimeException('Falta ejecutar migracion de conciliacion bancaria.');
        }
        if (!\DBUtil::field_exists('core_bank_movements', ['statement_import_id', 'checksum'])) {
            throw new \RuntimeException('Falta ejecutar migracion de estados de cuenta bancarios.');
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

    protected function apply_payment_to_purchase_invoice(Model_Core_Payment $payment, $invoice_id)
    {
        $invoice = Model_Core_Purchase_Invoice::find((int) $invoice_id);
        if (!$invoice || (int) $invoice->active !== 1) {
            return;
        }

        $balance = max(0, (float) $invoice->balance_due);
        $amount = min(max(0, (float) $payment->amount), $balance);
        if ($amount <= 0) {
            return;
        }

        Model_Core_Payment_Allocation::forge([
            'payment_id' => (int) $payment->id,
            'entity_type' => 'purchase_invoice',
            'entity_id' => (int) $invoice->id,
            'amount' => round($amount, 2),
            'notes' => 'Aplicacion de pago a proveedor desde conciliacion bancaria',
            'active' => 1,
        ])->save();

        $invoice->balance_due = round(max(0, $balance - $amount), 2);
        $invoice->status = $invoice->balance_due <= 0 ? 'paid' : 'partial';
        $invoice->save();
    }

    protected function create_payment_from_movement(Model_Core_Bank_Movement $movement, $payment_type, $party_id, $reference)
    {
        $payment = Model_Core_Payment::forge([
            'folio' => $this->next_payment_folio(),
            'payment_type' => $payment_type,
            'party_id' => (int) $party_id,
            'bank_account_id' => (int) $movement->bank_account_id,
            'integration_connection_id' => 0,
            'fiscal_document_id' => 0,
            'fiscal_mode' => $payment_type === 'received' ? 'fiscal_optional' : 'system_only',
            'rep_status' => 'not_required',
            'payment_date' => (string) $movement->movement_date,
            'currency_code' => (string) $movement->currency_code,
            'exchange_rate' => 1,
            'amount' => (float) $movement->amount,
            'sat_payment_form_code' => '03',
            'reference' => trim((string) $reference.' '.(string) $movement->reference),
            'external_id' => (string) $movement->checksum,
            'status' => 'confirmed',
            'notes' => 'Creado desde conciliacion bancaria: '.$movement->description,
            'created_by' => (int) $this->user_id,
            'active' => 1,
        ]);
        $payment->save();
        return $payment;
    }

    protected function parse_statement_csv($path)
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('No se pudo leer el CSV.');
        }

        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            return [];
        }
        $delimiter = $this->detect_csv_delimiter($first);
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            return [];
        }
        $map = $this->statement_header_map($headers);
        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($data, 'strlen')) === 0) {
                continue;
            }
            $raw = [];
            foreach ($headers as $index => $header) {
                $raw[(string) $header] = isset($data[$index]) ? $data[$index] : '';
            }
            $row = $this->normalize_statement_row($data, $map, $raw);
            if ($row) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        return $rows;
    }

    protected function parse_statement_pdf($path)
    {
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            throw new \RuntimeException('Falta instalar smalot/pdfparser para importar estados PDF.');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $text = (string) $parser->parseFile($path)->getText();
        if (stripos($text, 'BBVA') !== false) {
            return $this->parse_bbva_statement_text($text);
        }

        throw new \RuntimeException('Formato PDF no reconocido. Por ahora se soporta BBVA con texto extraible.');
    }

    /**
     * FIND EXISTING STATEMENT IMPORT
     *
     * UBICA UN ESTADO YA CARGADO PARA REIMPORTAR SOLO MOVIMIENTOS FALTANTES.
     *
     * @access  protected
     * @return  Model_Core_Bank_Statement_Import|null
     */
    protected function find_existing_statement_import($bank_account_id, $source_format, $original_name, $period_start, $period_end)
    {
        $statement = Model_Core_Bank_Statement_Import::query()
            ->where('bank_account_id', (int) $bank_account_id)
            ->where('source_format', (string) $source_format)
            ->where('period_start', (string) $period_start)
            ->where('period_end', (string) $period_end)
            ->where('original_name', (string) $original_name)
            ->order_by('id', 'desc')
            ->get_one();
        if ($statement) {
            return $statement;
        }

        return Model_Core_Bank_Statement_Import::query()
            ->where('bank_account_id', (int) $bank_account_id)
            ->where('source_format', (string) $source_format)
            ->where('period_start', (string) $period_start)
            ->where('period_end', (string) $period_end)
            ->order_by('id', 'desc')
            ->get_one();
    }

    /**
     * FIND EXISTING BANK MOVEMENT
     *
     * DETECTA DUPLICADOS POR CHECKSUM Y POR LLAVE NORMALIZADA DEL MOVIMIENTO.
     *
     * @access  protected
     * @return  Array|null
     */
    protected function find_existing_bank_movement($bank_account_id, array $row, $checksum)
    {
        $existing = \DB::select('id')
            ->from('core_bank_movements')
            ->where('checksum', '=', (string) $checksum)
            ->execute()
            ->current();
        if ($existing) {
            return $existing;
        }

        $candidates = \DB::select('id', 'reference', 'description')
            ->from('core_bank_movements')
            ->where('bank_account_id', '=', (int) $bank_account_id)
            ->where('movement_date', '=', (string) $row['movement_date'])
            ->where('movement_type', '=', (string) $row['movement_type'])
            ->where('currency_code', '=', (string) $row['currency_code'])
            ->where('amount', 'between', [
                max(0, (float) $row['amount'] - 0.01),
                (float) $row['amount'] + 0.01,
            ])
            ->limit(30)
            ->execute();

        $reference = $this->codeify((string) $row['reference']);
        $description = $this->codeify((string) $row['description']);
        foreach ($candidates as $candidate) {
            if ($this->codeify((string) $candidate['reference']) === $reference && $this->codeify((string) $candidate['description']) === $description) {
                return $candidate;
            }
        }

        return null;
    }

    protected function parse_bbva_statement_text($text)
    {
        $year = $this->bbva_statement_year($text);
        $lines = preg_split('/\R/u', (string) $text);
        $rows = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', (string) $line));
            if ($line === '') {
                continue;
            }

            $movement = $this->parse_bbva_movement_header($line, $year);
            if ($movement) {
                if ($current) {
                    $rows[] = $this->finalize_bbva_movement($current);
                }
                $current = $movement;
                continue;
            }

            if ($current && !$this->is_bbva_noise_line($line)) {
                $current['extra'][] = $line;
            }
        }

        if ($current) {
            $rows[] = $this->finalize_bbva_movement($current);
        }

        return array_values(array_filter($rows));
    }

    protected function bbva_statement_year($text)
    {
        if (preg_match('/Fecha de Corte\s+\d{2}\/\d{2}\/(\d{4})/i', (string) $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/AL\s+\d{2}\/\d{2}\/(\d{4})/i', (string) $text, $m)) {
            return (int) $m[1];
        }
        return (int) date('Y');
    }

    protected function parse_bbva_movement_header($line, $year)
    {
        if (!preg_match('/^(\d{2})\/([A-ZÁÉÍÓÚ]{3})(\d{2})\/([A-ZÁÉÍÓÚ]{3})\s+([A-Z0-9]{2,3})\s+(.+?)\s+(-?\(?[\d,]+\.\d{2}\)?)(?:\s+(-?[\d,]+\.\d{2})\s+(-?[\d,]+\.\d{2}))?$/u', $line, $m)) {
            return null;
        }

        $date = $this->bbva_date((int) $m[1], $m[2], $year);
        if ($date === '') {
            return null;
        }

        $code = strtoupper((string) $m[5]);
        $description = trim((string) $m[6]);
        $amount = abs($this->money_value($m[7]));

        return [
            'movement_date' => $date,
            'movement_type' => $this->bbva_movement_type($code, $description),
            'amount' => round($amount, 2),
            'balance_after' => isset($m[8]) && $m[8] !== '' ? round($this->money_value($m[8]), 2) : 0,
            'currency_code' => 'MXN',
            'reference' => '',
            'description' => trim($code.' '.$description),
            'extra' => [],
            'raw' => ['line' => $line, 'bank' => 'BBVA', 'code' => $code],
        ];
    }

    protected function finalize_bbva_movement(array $movement)
    {
        $extra = trim(implode(' ', (array) $movement['extra']));
        $movement['description'] = trim($movement['description'].' '.$extra);
        $movement['reference'] = $this->bbva_reference($movement['description']);
        $movement['raw']['extra'] = $movement['extra'];
        unset($movement['extra']);

        return $movement;
    }

    protected function bbva_reference($text)
    {
        if (preg_match('/Ref\.?\s*[:.]?\s*([A-Z0-9\-]+)/i', (string) $text, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/REF:?\s*([A-Z0-9\-]+)/i', (string) $text, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/\b(BNET[0-9A-Z]+)\b/i', (string) $text, $m)) {
            return strtoupper($m[1]);
        }
        return '';
    }

    protected function bbva_movement_type($code, $description)
    {
        $text = strtoupper($code.' '.$description);
        if (preg_match('/(DEPOSITO|RECIBIDO|DEVOLUCION|TRANSFER BBVA|PAGO CUENTA DE TERCERO)/', $text)) {
            return 'deposit';
        }
        if (preg_match('/(ENVIADO|KONFIO|SAT|SERV BANCA|COM SERV|IVA COM|RETIRO|CARGO|PAGO CIE)/', $text)) {
            return 'withdrawal';
        }
        return in_array($code, ['T17', 'P14', 'S39', 'S40'], true) ? 'withdrawal' : 'deposit';
    }

    protected function bbva_date($day, $month, $year)
    {
        $months = [
            'ENE' => '01', 'FEB' => '02', 'MAR' => '03', 'ABR' => '04',
            'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AGO' => '08',
            'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DIC' => '12',
        ];
        $month = strtoupper($this->remove_accents((string) $month));
        if (!isset($months[$month])) {
            return '';
        }
        return sprintf('%04d-%02d-%02d', (int) $year, (int) $months[$month], (int) $day);
    }

    protected function is_bbva_noise_line($line)
    {
        return (bool) preg_match('/^(PAGINA|MAESTRA PYME|BBVA MEXICO|No\. Cuenta|No\. Cliente|Estado de Cuenta|FECHA\s+SALDO|OPERLIQ|Informaci[oó]n Financiera|DOMICILIO FISCAL)/i', (string) $line);
    }

    protected function detect_csv_delimiter($line)
    {
        $counts = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];
        arsort($counts);
        return key($counts) ?: ',';
    }

    protected function statement_header_map(array $headers)
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $key = $this->codeify($header);
            if (in_array($key, ['fecha', 'date', 'fecha_operacion', 'fecha_movimiento'], true)) {
                $map['date'] = $index;
            } elseif (in_array($key, ['descripcion', 'description', 'concepto', 'detalle', 'movimiento'], true)) {
                $map['description'] = $index;
            } elseif (in_array($key, ['referencia', 'reference', 'ref', 'folio', 'numero_referencia'], true)) {
                $map['reference'] = $index;
            } elseif (in_array($key, ['cargo', 'retiro', 'debit', 'egreso', 'salida'], true)) {
                $map['withdrawal'] = $index;
            } elseif (in_array($key, ['abono', 'deposito', 'credit', 'ingreso', 'entrada'], true)) {
                $map['deposit'] = $index;
            } elseif (in_array($key, ['importe', 'amount', 'monto'], true)) {
                $map['amount'] = $index;
            } elseif (in_array($key, ['saldo', 'balance', 'saldo_final'], true)) {
                $map['balance'] = $index;
            }
        }
        return $map;
    }

    protected function normalize_statement_row(array $data, array $map, array $raw)
    {
        $date_raw = isset($map['date']) ? trim((string) \Arr::get($data, $map['date'], '')) : '';
        $date = $this->normalize_statement_date($date_raw);
        if ($date === '') {
            return null;
        }

        $deposit = isset($map['deposit']) ? $this->money_value(\Arr::get($data, $map['deposit'], 0)) : 0;
        $withdrawal = isset($map['withdrawal']) ? $this->money_value(\Arr::get($data, $map['withdrawal'], 0)) : 0;
        $amount = isset($map['amount']) ? $this->money_value(\Arr::get($data, $map['amount'], 0)) : 0;
        $movement_type = 'deposit';
        if ($withdrawal > 0) {
            $amount = $withdrawal;
            $movement_type = 'withdrawal';
        } elseif ($deposit > 0) {
            $amount = $deposit;
            $movement_type = 'deposit';
        } elseif ($amount < 0) {
            $amount = abs($amount);
            $movement_type = 'withdrawal';
        }

        if ($amount <= 0) {
            return null;
        }

        return [
            'movement_date' => $date,
            'movement_type' => $movement_type,
            'amount' => round($amount, 2),
            'balance_after' => isset($map['balance']) ? round($this->money_value(\Arr::get($data, $map['balance'], 0)), 2) : 0,
            'currency_code' => 'MXN',
            'reference' => isset($map['reference']) ? trim((string) \Arr::get($data, $map['reference'], '')) : '',
            'description' => isset($map['description']) ? trim((string) \Arr::get($data, $map['description'], '')) : '',
            'raw' => $raw,
        ];
    }

    protected function normalize_statement_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $value, $m)) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }
        $time = strtotime($value);
        return $time ? date('Y-m-d', $time) : '';
    }

    protected function money_value($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }
        $value = str_replace(['$', ' ', ','], '', $value);
        $negative = strpos($value, '(') !== false || strpos($value, '-') === 0;
        $value = str_replace(['(', ')', '+', '-'], '', $value);
        $amount = (float) $value;
        return $negative ? -$amount : $amount;
    }

    protected function limit_text($value, $length)
    {
        $value = trim((string) $value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, (int) $length, 'UTF-8');
        }
        return substr($value, 0, (int) $length);
    }

    protected function movement_checksum($bank_account_id, array $row)
    {
        return hash('sha256', implode('|', [
            (int) $bank_account_id,
            $row['movement_date'],
            $row['movement_type'],
            number_format((float) $row['amount'], 2, '.', ''),
            $this->codeify($row['reference']),
            $this->codeify($row['description']),
        ]));
    }

    protected function statement_period_date(array $rows, $mode)
    {
        $dates = array_map(function ($row) { return $row['movement_date']; }, $rows);
        sort($dates);
        return $mode === 'max' ? end($dates) : reset($dates);
    }

    protected function generate_reconciliation_suggestions()
    {
        if (!\DBUtil::table_exists('core_bank_reconciliation_suggestions')) {
            return 0;
        }

        $created = 0;
        $movements = Model_Core_Bank_Movement::query()
            ->where('reconciled', 0)
            ->where('active', 1)
            ->order_by('id', 'desc')
            ->limit(200)
            ->get();

        foreach ($movements as $movement) {
            $existing = \DB::select('id')
                ->from('core_bank_reconciliation_suggestions')
                ->where('movement_id', '=', (int) $movement->id)
                ->where('status', '=', 'pending')
                ->execute()
                ->current();
            if ($existing) {
                continue;
            }

            foreach ($this->movement_candidates($movement) as $candidate) {
                Model_Core_Bank_Reconciliation_Suggestion::forge([
                    'movement_id' => (int) $movement->id,
                    'suggested_entity_type' => $candidate['entity_type'],
                    'suggested_entity_id' => (int) $candidate['entity_id'],
                    'payment_type' => $candidate['payment_type'],
                    'party_id' => (int) $candidate['party_id'],
                    'amount' => (float) $movement->amount,
                    'currency_code' => (string) $movement->currency_code,
                    'score' => (int) $candidate['score'],
                    'reasons_json' => json_encode($candidate['reasons'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status' => 'pending',
                    'applied_by' => 0,
                    'applied_at' => 0,
                    'created_by' => (int) $this->user_id,
                ])->save();
                $created++;
            }
        }

        return $created;
    }

    protected function movement_candidates(Model_Core_Bank_Movement $movement)
    {
        $candidates = [];
        $payment_type = $movement->movement_type === 'withdrawal' ? 'sent' : 'received';
        $candidates = array_merge($candidates, $this->payment_candidates($movement, $payment_type));
        if ($payment_type === 'received') {
            $candidates = array_merge($candidates, $this->billing_invoice_candidates($movement));
        } else {
            $candidates = array_merge($candidates, $this->purchase_invoice_candidates($movement));
        }

        usort($candidates, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        return array_slice(array_filter($candidates, function ($candidate) {
            return (int) $candidate['score'] >= 60;
        }), 0, 3);
    }

    protected function payment_candidates(Model_Core_Bank_Movement $movement, $payment_type)
    {
        $items = [];
        $rows = \DB::select('id', 'party_id', 'payment_date', 'amount', 'currency_code', 'reference', 'external_id')
            ->from('core_payments')
            ->where('payment_type', '=', $payment_type)
            ->where('active', '=', 1)
            ->where('status', '!=', 'cancelled')
            ->where('amount', 'between', [max(0, (float) $movement->amount - 0.01), (float) $movement->amount + 0.01])
            ->limit(50)
            ->execute();
        foreach ($rows as $row) {
            $score = $this->candidate_score($movement, $row['payment_date'], $row['reference'], 'pago existente');
            $items[] = [
                'entity_type' => 'payment',
                'entity_id' => (int) $row['id'],
                'payment_type' => $payment_type,
                'party_id' => (int) $row['party_id'],
                'score' => $score['score'],
                'reasons' => $score['reasons'],
            ];
        }
        return $items;
    }

    protected function billing_invoice_candidates(Model_Core_Bank_Movement $movement)
    {
        if (!\DBUtil::table_exists('core_billing_invoices')) {
            return [];
        }

        $items = [];
        $rows = \DB::select(['i.id', 'id'], ['i.party_id', 'party_id'], ['i.issue_date', 'issue_date'], ['i.due_date', 'due_date'], ['i.balance_due', 'balance_due'], ['i.folio', 'folio'], ['i.uuid', 'uuid'], ['p.name', 'party_name'], ['p.rfc', 'party_rfc'])
            ->from(['core_billing_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('i.balance_due', 'between', [max(0, (float) $movement->amount - 0.01), (float) $movement->amount + 0.01])
            ->limit(50)
            ->execute();
        foreach ($rows as $row) {
            $score = $this->candidate_score($movement, $row['due_date'] ?: $row['issue_date'], $row['folio'].' '.$row['uuid'].' '.$row['party_name'].' '.$row['party_rfc'], 'factura cliente');
            $items[] = [
                'entity_type' => 'billing_invoice',
                'entity_id' => (int) $row['id'],
                'payment_type' => 'received',
                'party_id' => (int) $row['party_id'],
                'score' => $score['score'],
                'reasons' => $score['reasons'],
            ];
        }
        return $items;
    }

    protected function purchase_invoice_candidates(Model_Core_Bank_Movement $movement)
    {
        if (!\DBUtil::table_exists('core_purchase_invoices')) {
            return [];
        }

        $items = [];
        $rows = \DB::select(['i.id', 'id'], ['i.party_id', 'party_id'], ['i.invoice_date', 'invoice_date'], ['i.due_date', 'due_date'], ['i.balance_due', 'balance_due'], ['i.folio', 'folio'], ['i.uuid', 'uuid'], ['p.name', 'party_name'], ['p.rfc', 'party_rfc'])
            ->from(['core_purchase_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.active', '=', 1)
            ->where('i.balance_due', 'between', [max(0, (float) $movement->amount - 0.01), (float) $movement->amount + 0.01])
            ->limit(50)
            ->execute();
        foreach ($rows as $row) {
            $score = $this->candidate_score($movement, $row['due_date'] ?: $row['invoice_date'], $row['folio'].' '.$row['uuid'].' '.$row['party_name'].' '.$row['party_rfc'], 'factura proveedor');
            $items[] = [
                'entity_type' => 'purchase_invoice',
                'entity_id' => (int) $row['id'],
                'payment_type' => 'sent',
                'party_id' => (int) $row['party_id'],
                'score' => $score['score'],
                'reasons' => $score['reasons'],
            ];
        }
        return $items;
    }

    protected function candidate_score(Model_Core_Bank_Movement $movement, $candidate_date, $candidate_text, $base_reason)
    {
        $score = 45;
        $reasons = ['Importe exacto', ucfirst($base_reason)];

        $days = $candidate_date ? abs((strtotime((string) $movement->movement_date) - strtotime((string) $candidate_date)) / 86400) : 99;
        if ($days <= 1) {
            $score += 25;
            $reasons[] = 'Fecha muy cercana';
        } elseif ($days <= 5) {
            $score += 15;
            $reasons[] = 'Fecha cercana';
        }

        $movement_text = $this->codeify($movement->reference.' '.$movement->description);
        $candidate_text = $this->codeify($candidate_text);
        foreach (explode('_', $movement_text) as $token) {
            if (strlen($token) >= 5 && strpos($candidate_text, $token) !== false) {
                $score += 20;
                $reasons[] = 'Referencia o texto coincide';
                break;
            }
        }

        return ['score' => min(100, $score), 'reasons' => array_values(array_unique($reasons))];
    }

    protected function reconciliation_entity_label($entity_type, $entity_id)
    {
        if ($entity_type === 'payment') {
            $row = \DB::select('folio')->from('core_payments')->where('id', '=', $entity_id)->execute()->current();
            return $row ? 'Pago '.$row['folio'] : 'Pago #'.$entity_id;
        }
        if ($entity_type === 'billing_invoice') {
            $row = \DB::select('folio')->from('core_billing_invoices')->where('id', '=', $entity_id)->execute()->current();
            return $row ? 'Factura cliente '.$row['folio'] : 'Factura cliente #'.$entity_id;
        }
        if ($entity_type === 'purchase_invoice') {
            $row = \DB::select('folio')->from('core_purchase_invoices')->where('id', '=', $entity_id)->execute()->current();
            return $row ? 'Factura proveedor '.$row['folio'] : 'Factura proveedor #'.$entity_id;
        }
        return $entity_type.' #'.$entity_id;
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

    protected function remove_accents($value)
    {
        $value = trim((string) $value);
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        return (string) $value;
    }
}
