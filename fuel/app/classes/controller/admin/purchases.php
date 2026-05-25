<?php

/**
 * CONTROLADOR ADMIN_PURCHASES
 *
 * Administra ordenes de compra, facturas de proveedor, contrarecibos y evidencias.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Purchases extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMINISTRATIVA Y PERMISO DE LECTURA DE COMPRAS
     *
     * @return  Void
     */
    public function before()
    {
        parent::before();
        $this->require_access('purchases.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE COMPRAS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Compras';
        $this->template->content = View::forge('admin/purchases/index');
    }

    /**
     * DATA
     *
     * ENTREGA ORDENES, FACTURAS, CONTRARECIBOS, EVIDENCIAS Y OPCIONES
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            $this->assert_schema_ready();
            $filters = $this->period_filters();
            return $this->json_response([
                'orders' => $this->orders($filters),
                'invoices' => $this->invoices($filters),
                'receipts' => $this->receipts($filters),
                'documents' => $this->documents($filters),
                'options' => $this->options(),
                'stats' => $this->stats($filters),
                'period_filters' => $filters,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando compras: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar compras.'], 500);
        }
    }

    /**
     * SAVE ORDER
     *
     * CREA O ACTUALIZA UNA ORDEN DE COMPRA
     *
     * @access  public
     * @return  Response
     */
    public function post_save_order()
    {
        $this->require_access('purchases.access[create]');
        $val = (array) \Input::json();

        try {
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $this->require_access('purchases.access[edit]');
            }

            $party_id = (int) \Arr::get($val, 'party_id', 0);
            $items = (array) \Arr::get($val, 'items', []);
            if ($party_id < 1 || empty($items)) {
                return $this->json_response(['error' => 'Selecciona proveedor y al menos un concepto.'], 422);
            }

            $order = null;
            if ($id > 0) {
                $order = Model_Core_Purchase_Order::find($id);
                if (!$order) {
                    return $this->json_response(['error' => 'Orden no encontrada.'], 404);
                }
            }

            $requested_status = $this->codeify(\Arr::get($val, 'status', 'draft'));
            $editable_statuses = ['draft', 'rejected'];
            if ($id > 0 && !in_array((string) $order->status, $editable_statuses, true) && !$this->can_authorize_purchase()) {
                return $this->json_response(['error' => 'Solo autorizadores pueden modificar ordenes solicitadas, autorizadas o cerradas.'], 422);
            }
            if (!in_array($requested_status, ['draft', 'rejected', 'cancelled'], true) && !$this->can_authorize_purchase()) {
                $requested_status = 'draft';
            }

            $data = [
                'source' => 'admin',
                'portal_code' => '',
                'party_id' => $party_id,
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'requested_by' => (int) \Arr::get($val, 'requested_by', $this->user_id),
                'order_date' => trim((string) \Arr::get($val, 'order_date', date('Y-m-d'))),
                'expected_date' => trim((string) \Arr::get($val, 'expected_date', '')),
                'payment_term_id' => (int) \Arr::get($val, 'payment_term_id', 0),
                'currency_code' => trim((string) \Arr::get($val, 'currency_code', 'MXN')) ?: 'MXN',
                'exchange_rate' => max(0.000001, (float) \Arr::get($val, 'exchange_rate', 1)),
                'status' => $requested_status,
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'internal_notes' => trim((string) \Arr::get($val, 'internal_notes', '')),
                'approval_notes' => trim((string) \Arr::get($val, 'approval_notes', '')),
                'external_reference' => trim((string) \Arr::get($val, 'external_reference', '')),
                'active' => 1,
            ];

            if ($id > 0) {
                $old = $order->to_array();
                $order->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_folio('OC', 'core_purchase_orders');
                $data['created_by'] = $this->user_id;
                $order = Model_Core_Purchase_Order::forge($data);
            }
            $order->save();

            if ($id > 0) {
                \DB::update('core_purchase_order_items')->set(['active' => 0])->where('order_id', '=', (int) $order->id)->execute();
            }

            $sort = 10;
            foreach ($items as $line) {
                $line = (array) $line;
                $quantity = max(0.0001, (float) \Arr::get($line, 'quantity', 1));
                $unit_price = max(0, (float) \Arr::get($line, 'unit_price', 0));
                $tax_rate = max(0, (float) \Arr::get($line, 'tax_rate', 0.16));
                $discount = max(0, (float) \Arr::get($line, 'discount_amount', 0));
                $line_subtotal = max(0, ($quantity * $unit_price) - $discount);
                $tax = round($line_subtotal * $tax_rate, 2);
                $retention = max(0, (float) \Arr::get($line, 'retention_amount', 0));
                $total = round($line_subtotal + $tax - $retention, 2);

                Model_Core_Purchase_Order_Item::forge([
                    'order_id' => (int) $order->id,
                    'product_id' => (int) \Arr::get($line, 'product_id', 0),
                    'sku' => trim((string) \Arr::get($line, 'sku', '')),
                    'description' => trim((string) \Arr::get($line, 'description', 'Concepto')),
                    'quantity' => $quantity,
                    'unit_code' => trim((string) \Arr::get($line, 'unit_code', 'H87')) ?: 'H87',
                    'unit_price' => $unit_price,
                    'discount_amount' => $discount,
                    'tax_rate' => $tax_rate,
                    'tax_amount' => $tax,
                    'retention_amount' => $retention,
                    'line_total' => $total,
                    'sort_order' => $sort,
                    'active' => 1,
                ])->save();
                $sort += 10;
            }

            $this->recalculate_order((int) $order->id);
            $order = Model_Core_Purchase_Order::find((int) $order->id);
            $this->audit($id > 0 ? 'update_order' : 'create_order', 'purchase_order', $order, $old);
            $this->notify_admins('purchases.order_saved', 'Orden de compra '.$order->folio, 'Se guardo la orden '.$order->folio, 'admin/purchases');

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando orden de compra: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la orden.'], 400);
        }
    }

    public function action_save_order()
    {
        return $this->post_save_order();
    }

    public function action_submit_order()
    {
        $this->require_access('purchases.access[edit]');
        return $this->change_order_approval('submit');
    }

    public function action_authorize_order()
    {
        return $this->change_order_approval('authorize');
    }

    public function action_reject_order()
    {
        return $this->change_order_approval('reject');
    }

    public function action_cancel_order()
    {
        $this->require_access('purchases.access[edit]');
        return $this->change_order_approval('cancel');
    }

    public function action_close_order()
    {
        $this->require_access('purchases.access[edit]');
        return $this->change_order_approval('close');
    }

    /**
     * SAVE INVOICE
     *
     * REGISTRA FACTURA DE PROVEEDOR Y LA RELACIONA CON ORDEN SI APLICA
     *
     * @access  public
     * @return  Response
     */
    public function post_save_invoice()
    {
        $this->require_access('purchases.access[create]');
        $val = (array) \Input::json();

        try {
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $this->require_access('purchases.access[edit]');
            }

            $party_id = (int) \Arr::get($val, 'party_id', 0);
            if ($party_id < 1) {
                return $this->json_response(['error' => 'Selecciona proveedor.'], 422);
            }
            $order_id = (int) \Arr::get($val, 'order_id', 0);
            if ($order_id > 0) {
                $order = Model_Core_Purchase_Order::find($order_id);
                if (!$order || !in_array((string) $order->status, ['authorized', 'partial', 'closed'], true)) {
                    return $this->json_response(['error' => 'La factura solo puede ligarse a una OC autorizada.'], 422);
                }
            }
            $uuid = strtoupper(trim((string) \Arr::get($val, 'uuid', '')));
            $cfdi_id = (int) \Arr::get($val, 'cfdi_id', 0);
            if ($cfdi_id < 1 && $uuid !== '' && \DBUtil::table_exists('core_sat_cfdi')) {
                $cfdi_row = \DB::select('id', 'sat_status')
                    ->from('core_sat_cfdi')
                    ->where('uuid', '=', $uuid)
                    ->execute()
                    ->current();
                if ($cfdi_row) {
                    $cfdi_id = (int) $cfdi_row['id'];
                    if ((string) $cfdi_row['sat_status'] === 'cancelado') {
                        return $this->json_response(['error' => 'No se puede registrar compra con CFDI cancelado.'], 422);
                    }
                }
            }

            $data = [
                'party_id' => $party_id,
                'order_id' => $order_id,
                'billing_invoice_id' => (int) \Arr::get($val, 'billing_invoice_id', 0),
                'cfdi_id' => $cfdi_id,
                'uuid' => $uuid,
                'invoice_date' => trim((string) \Arr::get($val, 'invoice_date', date('Y-m-d'))),
                'due_date' => trim((string) \Arr::get($val, 'due_date', '')),
                'currency_code' => trim((string) \Arr::get($val, 'currency_code', 'MXN')) ?: 'MXN',
                'subtotal' => max(0, (float) \Arr::get($val, 'subtotal', 0)),
                'tax_total' => max(0, (float) \Arr::get($val, 'tax_total', 0)),
                'retention_total' => max(0, (float) \Arr::get($val, 'retention_total', 0)),
                'status' => $this->codeify(\Arr::get($val, 'status', 'submitted')),
                'validation_status' => $this->codeify(\Arr::get($val, 'validation_status', 'pending')),
                'sat_status' => trim((string) \Arr::get($val, 'sat_status', '')),
                'message' => trim((string) \Arr::get($val, 'message', '')),
                'active' => 1,
            ];
            $data['total'] = max(0, (float) \Arr::get($val, 'total', $data['subtotal'] + $data['tax_total'] - $data['retention_total']));
            $data['balance_due'] = max(0, (float) \Arr::get($val, 'balance_due', $data['total']));

            if ($id > 0) {
                $invoice = Model_Core_Purchase_Invoice::find($id);
                if (!$invoice) {
                    return $this->json_response(['error' => 'Factura no encontrada.'], 404);
                }
                $old = $invoice->to_array();
                $invoice->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_folio('FCP', 'core_purchase_invoices');
                $data['created_by'] = $this->user_id;
                $invoice = Model_Core_Purchase_Invoice::forge($data);
            }
            $invoice->save();
            if ((int) $invoice->cfdi_id > 0 && \DBUtil::table_exists('core_sat_cfdi')) {
                \DB::update('core_sat_cfdi')
                    ->set(['purchase_status' => 'linked', 'reviewed_by' => (int) $this->user_id, 'reviewed_at' => time()])
                    ->where('id', '=', (int) $invoice->cfdi_id)
                    ->execute();
            }
            if ((int) $invoice->order_id > 0) {
                $this->recalculate_order((int) $invoice->order_id);
            }
            $this->audit($id > 0 ? 'update_invoice' : 'create_invoice', 'purchase_invoice', $invoice, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando factura proveedor: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la factura.'], 400);
        }
    }

    public function action_save_invoice()
    {
        return $this->post_save_invoice();
    }

    /**
     * SAVE RECEIPT
     *
     * CREA CONTRARECIBO Y LO RELACIONA CON FACTURAS
     *
     * @access  public
     * @return  Response
     */
    public function post_save_receipt()
    {
        $this->require_access('purchases.access[create]');
        $val = (array) \Input::json();

        try {
            $party_id = (int) \Arr::get($val, 'party_id', 0);
            $invoice_ids = array_filter(array_map('intval', (array) \Arr::get($val, 'invoice_ids', [])));
            if ($party_id < 1 || empty($invoice_ids)) {
                return $this->json_response(['error' => 'Selecciona proveedor y facturas.'], 422);
            }

            $receipt = Model_Core_Purchase_Receipt::forge([
                'folio' => $this->next_folio('CRP', 'core_purchase_receipts'),
                'party_id' => $party_id,
                'issue_date' => trim((string) \Arr::get($val, 'issue_date', date('Y-m-d'))),
                'scheduled_payment_date' => trim((string) \Arr::get($val, 'scheduled_payment_date', '')),
                'currency_code' => trim((string) \Arr::get($val, 'currency_code', 'MXN')) ?: 'MXN',
                'total' => 0,
                'payment_id' => (int) \Arr::get($val, 'payment_id', 0),
                'status' => $this->codeify(\Arr::get($val, 'status', 'draft')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'created_by' => $this->user_id,
                'active' => 1,
            ]);
            $receipt->save();

            $total = 0;
            foreach ($invoice_ids as $invoice_id) {
                $invoice = Model_Core_Purchase_Invoice::find($invoice_id);
                if (!$invoice || (int) $invoice->party_id !== $party_id) {
                    continue;
                }
                if ((string) $invoice->validation_status !== 'validated') {
                    continue;
                }
                $amount = (float) $invoice->balance_due;
                $total += $amount;
                Model_Core_Purchase_Receipt_Item::forge([
                    'receipt_id' => (int) $receipt->id,
                    'invoice_id' => (int) $invoice->id,
                    'amount' => $amount,
                    'notes' => trim((string) \Arr::get($val, 'notes', '')),
                    'active' => 1,
                ])->save();
                $invoice->status = 'in_receipt';
                $invoice->save();
            }

            if ($total <= 0) {
                $receipt->active = 0;
                $receipt->save();
                throw new \RuntimeException('No hay facturas validadas para generar contrarecibo.');
            }

            $receipt->total = round($total, 2);
            if ((int) $receipt->payment_id > 0) {
                $receipt->status = 'paid';
                \DB::update('core_purchase_invoices')
                    ->set(['status' => 'paid', 'balance_due' => 0])
                    ->where('id', 'in', $invoice_ids)
                    ->execute();
            }
            $receipt->save();
            $this->audit('create_receipt', 'purchase_receipt', $receipt, []);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error creando contrarecibo: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_save_receipt()
    {
        return $this->post_save_receipt();
    }

    /**
     * UPLOAD DOCUMENT
     *
     * ADJUNTA DOCUMENTO O EVIDENCIA A ORDEN, FACTURA O CONTRARECIBO
     *
     * @access  public
     * @return  Response
     */
    public function post_upload_document()
    {
        $this->require_access('purchases.access[edit]');

        try {
            $entity_type = $this->document_entity_type((string) \Input::post('entity_type', ''));
            $entity_id = (int) \Input::post('entity_id', 0);
            if ($entity_id < 1 || $entity_type === '') {
                return $this->json_response(['error' => 'Selecciona un registro valido.'], 422);
            }

            $document = $this->store_document($entity_type, $entity_id, 'internal');
            $this->audit('upload_document', 'document', $document, []);
            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error adjuntando documento compras: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_upload_document()
    {
        return $this->post_upload_document();
    }

    protected function orders(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $rows = \DB::select(['o.id', 'id'], ['o.folio', 'folio'], ['o.party_id', 'party_id'], ['p.name', 'party_name'], ['p.rfc', 'party_rfc'], ['o.department_id', 'department_id'], ['d.name', 'department_name'], ['o.requested_by', 'requested_by'], ['ur.username', 'requested_by_name'], ['o.requested_at', 'requested_at'], ['o.authorized_by', 'authorized_by'], ['ua.username', 'authorized_by_name'], ['o.authorized_at', 'authorized_at'], ['o.order_date', 'order_date'], ['o.expected_date', 'expected_date'], ['o.payment_term_id', 'payment_term_id'], ['o.status', 'status'], ['o.approval_status', 'approval_status'], ['o.approval_required', 'approval_required'], ['o.approval_rule_id', 'approval_rule_id'], ['o.currency_code', 'currency_code'], ['o.exchange_rate', 'exchange_rate'], ['o.subtotal', 'subtotal'], ['o.tax_total', 'tax_total'], ['o.retention_total', 'retention_total'], ['o.total', 'total'], ['o.invoiced_total', 'invoiced_total'], ['o.balance_total', 'balance_total'], ['o.notes', 'notes'], ['o.internal_notes', 'internal_notes'], ['o.approval_notes', 'approval_notes'], ['o.external_reference', 'external_reference'], ['o.created_at', 'created_at'])
            ->from(['core_purchase_orders', 'o'])
            ->join(['core_parties', 'p'], 'left')->on('o.party_id', '=', 'p.id')
            ->join(['core_departments', 'd'], 'left')->on('o.department_id', '=', 'd.id')
            ->join(['users', 'ur'], 'left')->on('o.requested_by', '=', 'ur.id')
            ->join(['users', 'ua'], 'left')->on('o.authorized_by', '=', 'ua.id')
            ->where('o.active', '=', 1)
            ->where('o.order_date', '>=', $filters['start_date'])
            ->where('o.order_date', '<=', $filters['end_date']);
        $this->apply_purchase_scope($rows, 'o', 'p');

        $rows = $rows
            ->order_by('o.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['items'] = $this->order_items((int) $row['id']);
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
            $row['requested_label'] = $row['requested_at'] ? date('d/m/Y H:i', (int) $row['requested_at']) : '';
            $row['authorized_label'] = $row['authorized_at'] ? date('d/m/Y H:i', (int) $row['authorized_at']) : '';
            $row['can_authorize'] = $this->can_authorize_order_row($row) ? 1 : 0;
        }
        return $rows;
    }

    protected function order_items($order_id)
    {
        return \DB::select('id', 'product_id', 'sku', 'description', 'quantity', 'unit_code', 'unit_price', 'discount_amount', 'tax_rate', 'tax_amount', 'retention_amount', 'line_total')
            ->from('core_purchase_order_items')
            ->where('order_id', '=', (int) $order_id)
            ->where('active', '=', 1)
            ->order_by('sort_order', 'asc')
            ->execute()
            ->as_array();
    }

    protected function invoices(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $rows = \DB::select(['i.id', 'id'], ['i.folio', 'folio'], ['i.party_id', 'party_id'], ['p.name', 'party_name'], ['i.order_id', 'order_id'], ['o.folio', 'order_folio'], ['o.status', 'order_status'], ['i.cfdi_id', 'cfdi_id'], ['i.uuid', 'uuid'], ['c.sat_status', 'cfdi_sat_status'], ['c.voucher_type', 'cfdi_type'], ['i.invoice_date', 'invoice_date'], ['i.due_date', 'due_date'], ['i.currency_code', 'currency_code'], ['i.subtotal', 'subtotal'], ['i.tax_total', 'tax_total'], ['i.retention_total', 'retention_total'], ['i.total', 'total'], ['i.balance_due', 'balance_due'], ['i.status', 'status'], ['i.validation_status', 'validation_status'], ['i.sat_status', 'sat_status'], ['i.message', 'message'], ['i.created_at', 'created_at'])
            ->from(['core_purchase_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->join(['core_purchase_orders', 'o'], 'left')->on('i.order_id', '=', 'o.id')
            ->join(['core_sat_cfdi', 'c'], 'left')->on('i.cfdi_id', '=', 'c.id')
            ->where('i.active', '=', 1)
            ->where('i.invoice_date', '>=', $filters['start_date'])
            ->where('i.invoice_date', '<=', $filters['end_date']);
        $this->apply_purchase_scope($rows, 'o', 'p');

        $rows = $rows
            ->order_by('i.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
        foreach ($rows as &$row) {
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
            $row['flow'] = $this->purchase_flow((int) $row['id']);
        }
        return $rows;
    }

    protected function receipts(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $rows = \DB::select(['r.id', 'id'], ['r.folio', 'folio'], ['r.party_id', 'party_id'], ['p.name', 'party_name'], ['r.issue_date', 'issue_date'], ['r.scheduled_payment_date', 'scheduled_payment_date'], ['r.currency_code', 'currency_code'], ['r.total', 'total'], ['r.payment_id', 'payment_id'], ['pay.folio', 'payment_folio'], ['pay.status', 'payment_status'], ['r.status', 'status'], ['r.notes', 'notes'], ['r.created_at', 'created_at'])
            ->from(['core_purchase_receipts', 'r'])
            ->join(['core_parties', 'p'], 'left')->on('r.party_id', '=', 'p.id')
            ->join(['core_payments', 'pay'], 'left')->on('r.payment_id', '=', 'pay.id')
            ->where('r.active', '=', 1)
            ->where('r.issue_date', '>=', $filters['start_date'])
            ->where('r.issue_date', '<=', $filters['end_date']);
        $this->apply_party_scope($rows, 'p', 'purchases');

        $rows = $rows
            ->order_by('r.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
        foreach ($rows as &$row) {
            $row['items'] = $this->receipt_items((int) $row['id']);
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
        }
        return $rows;
    }

    protected function receipt_items($receipt_id)
    {
        return \DB::select(['ri.invoice_id', 'invoice_id'], ['i.folio', 'invoice_folio'], ['i.uuid', 'uuid'], ['ri.amount', 'amount'])
            ->from(['core_purchase_receipt_items', 'ri'])
            ->join(['core_purchase_invoices', 'i'], 'left')->on('ri.invoice_id', '=', 'i.id')
            ->where('ri.receipt_id', '=', (int) $receipt_id)
            ->where('ri.active', '=', 1)
            ->execute()
            ->as_array();
    }

    protected function documents(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $rows = \DB::select(['d.id', 'id'], ['l.entity_type', 'entity_type'], ['l.entity_id', 'entity_id'], ['d.title', 'title'], ['d.original_name', 'original_name'], ['d.file_path', 'file_path'], ['d.file_extension', 'file_extension'], ['d.visibility', 'visibility'], ['d.is_evidence', 'is_evidence'], ['d.created_at', 'created_at'])
            ->from(['core_document_links', 'l'])
            ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
            ->where('l.entity_type', 'in', ['purchase_order', 'purchase_invoice', 'purchase_receipt'])
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->where('d.created_at', '>=', strtotime($filters['start_date'].' 00:00:00'))
            ->where('d.created_at', '<=', strtotime($filters['end_date'].' 23:59:59'))
            ->order_by('d.id', 'desc')
            ->limit(300)
            ->execute()
            ->as_array();
        foreach ($rows as &$row) {
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
        }
        return $rows;
    }

    protected function options()
    {
        return [
            'suppliers' => $this->select_options('core_parties', 'id', 'name', ['party_type' => 'supplier']),
            'departments' => $this->select_options('core_departments', 'id', 'name'),
            'users' => $this->user_options(),
            'payment_terms' => $this->select_options('core_catalog_payment_terms', 'id', 'name'),
            'payments' => $this->payment_options(),
            'approval_rules' => $this->approval_rule_options(),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'taxes' => $this->select_rate_options('core_catalog_taxes', 'code', 'name'),
            'retentions' => $this->select_rate_options('core_catalog_retentions', 'code', 'name'),
        ];
    }

    protected function stats(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        return [
            'orders' => (int) \DB::select()->from('core_purchase_orders')->where('order_date', '>=', $filters['start_date'])->where('order_date', '<=', $filters['end_date'])->execute()->count(),
            'open_orders' => (int) \DB::select()->from('core_purchase_orders')->where('status', 'in', ['draft', 'pending_authorization', 'authorized', 'partial'])->where('active', '=', 1)->where('order_date', '>=', $filters['start_date'])->where('order_date', '<=', $filters['end_date'])->execute()->count(),
            'pending_authorizations' => (int) \DB::select()->from('core_purchase_orders')->where('approval_status', '=', 'pending')->where('active', '=', 1)->where('order_date', '>=', $filters['start_date'])->where('order_date', '<=', $filters['end_date'])->execute()->count(),
            'invoices' => (int) \DB::select()->from('core_purchase_invoices')->where('invoice_date', '>=', $filters['start_date'])->where('invoice_date', '<=', $filters['end_date'])->execute()->count(),
            'pending_invoices' => (int) \DB::select()->from('core_purchase_invoices')->where('validation_status', '=', 'pending')->where('active', '=', 1)->where('invoice_date', '>=', $filters['start_date'])->where('invoice_date', '<=', $filters['end_date'])->execute()->count(),
            'receipts' => (int) \DB::select()->from('core_purchase_receipts')->where('issue_date', '>=', $filters['start_date'])->where('issue_date', '<=', $filters['end_date'])->execute()->count(),
        ];
    }

    protected function recalculate_order($order_id)
    {
        $order = Model_Core_Purchase_Order::find($order_id);
        if (!$order) {
            return null;
        }

        $totals = \DB::select(\DB::expr('SUM(line_total - tax_amount + retention_amount) as subtotal'), \DB::expr('SUM(tax_amount) as tax_total'), \DB::expr('SUM(retention_amount) as retention_total'), \DB::expr('SUM(line_total) as total'))
            ->from('core_purchase_order_items')
            ->where('order_id', '=', (int) $order_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        $invoiced = \DB::select(\DB::expr('SUM(total) as total'))
            ->from('core_purchase_invoices')
            ->where('order_id', '=', (int) $order_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        $order->subtotal = round((float) $totals['subtotal'], 2);
        $order->tax_total = round((float) $totals['tax_total'], 2);
        $order->retention_total = round((float) $totals['retention_total'], 2);
        $order->total = round((float) $totals['total'], 2);
        $order->invoiced_total = round((float) $invoiced['total'], 2);
        $order->balance_total = max(0, round($order->total - $order->invoiced_total, 2));
        $order->save();

        return $order;
    }

    protected function change_order_approval($action)
    {
        $val = (array) \Input::json();
        $order = Model_Core_Purchase_Order::find((int) \Arr::get($val, 'id', 0));
        if (!$order) {
            return $this->json_response(['error' => 'Orden no encontrada.'], 404);
        }

        try {
            $old = $order->to_array();
            $notes = trim((string) \Arr::get($val, 'notes', ''));

            if ($action === 'submit') {
                if (!in_array((string) $order->status, ['draft', 'rejected'], true)) {
                    return $this->json_response(['error' => 'Solo ordenes en borrador o rechazadas se pueden solicitar.'], 422);
                }
                $rule = $this->approval_rule_for_order($order);
                $order->approval_rule_id = $rule ? (int) $rule['id'] : 0;
                $order->approval_required = $rule && (int) $rule['auto_approve'] === 0 ? 1 : 0;
                $order->requested_at = time();
                $order->approval_notes = $notes;
                if (!$rule || (int) $rule['auto_approve'] === 1) {
                    $order->status = 'authorized';
                    $order->approval_status = 'approved';
                    $order->authorized_by = (int) $this->user_id;
                    $order->authorized_at = time();
                } else {
                    $order->status = 'pending_authorization';
                    $order->approval_status = 'pending';
                    $this->notify_authorizers($order, $rule);
                }
            } elseif ($action === 'authorize') {
                if (!$this->can_authorize_order($order)) {
                    return $this->json_response(['error' => 'No tienes permiso o regla de monto para autorizar esta orden.'], 403);
                }
                if (!in_array((string) $order->status, ['pending_authorization', 'draft', 'rejected'], true)) {
                    return $this->json_response(['error' => 'La orden no esta pendiente de autorizacion.'], 422);
                }
                $order->status = 'authorized';
                $order->approval_status = 'approved';
                $order->approval_required = 1;
                $order->authorized_by = (int) $this->user_id;
                $order->authorized_at = time();
                $order->approval_notes = $notes ?: $order->approval_notes;
            } elseif ($action === 'reject') {
                if (!$this->can_authorize_order($order)) {
                    return $this->json_response(['error' => 'No tienes permiso para rechazar esta orden.'], 403);
                }
                $order->status = 'rejected';
                $order->approval_status = 'rejected';
                $order->rejected_by = (int) $this->user_id;
                $order->rejected_at = time();
                $order->approval_notes = $notes ?: $order->approval_notes;
            } elseif ($action === 'cancel') {
                if ((float) $order->invoiced_total > 0) {
                    return $this->json_response(['error' => 'No se puede cancelar una OC con facturas relacionadas.'], 422);
                }
                $order->status = 'cancelled';
                $order->approval_status = 'cancelled';
                $order->approval_notes = $notes ?: $order->approval_notes;
            } elseif ($action === 'close') {
                if (!in_array((string) $order->status, ['authorized', 'partial'], true)) {
                    return $this->json_response(['error' => 'Solo ordenes autorizadas o parciales se pueden cerrar.'], 422);
                }
                $order->status = 'closed';
                $order->approval_notes = $notes ?: $order->approval_notes;
            }

            $order->save();
            $this->audit($action.'_order', 'purchase_order', $order, $old);
            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error actualizando autorizacion de compra: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo actualizar la autorizacion.'], 400);
        }
    }

    protected function approval_rule_for_order(Model_Core_Purchase_Order $order)
    {
        if (!\DBUtil::table_exists('core_purchase_approval_rules')) {
            return null;
        }

        $query = \DB::select()
            ->from('core_purchase_approval_rules')
            ->where('active', '=', 1)
            ->where('min_amount', '<=', (float) $order->total)
            ->where_open()
                ->where('max_amount', '=', 0)
                ->or_where('max_amount', '>=', (float) $order->total)
            ->where_close()
            ->where('department_id', 'in', [0, (int) $order->department_id])
            ->order_by('department_id', 'desc')
            ->order_by('sort_order', 'asc')
            ->limit(1)
            ->execute()
            ->current();

        return $query ?: null;
    }

    protected function can_authorize_order(Model_Core_Purchase_Order $order)
    {
        if ($this->is_super_admin) {
            return true;
        }
        if (!$this->can_authorize_purchase()) {
            return false;
        }
        $rule = $this->approval_rule_for_order($order);
        if (!$rule) {
            return $this->user_group >= 70;
        }
        if ((int) $rule['approver_user_id'] > 0 && (int) $rule['approver_user_id'] !== (int) $this->user_id) {
            return false;
        }
        return $this->user_group >= (int) $rule['approver_group_id'];
    }

    protected function can_authorize_order_row(array $row)
    {
        $order = Model_Core_Purchase_Order::find((int) $row['id']);
        if (!$order) {
            return false;
        }
        return $this->can_authorize_order($order);
    }

    protected function can_authorize_purchase()
    {
        return $this->is_super_admin
            || \Auth::has_access('purchases.access[authorize]')
            || in_array($this->user_group, [70, 90, 100], true);
    }

    protected function notify_authorizers(Model_Core_Purchase_Order $order, array $rule)
    {
        $ids = [];
        if ((int) $rule['approver_user_id'] > 0) {
            $ids[] = (int) $rule['approver_user_id'];
        } else {
            foreach (\DB::select('id')->from('users')->where('group_id', '>=', (int) $rule['approver_group_id'])->execute() as $row) {
                $ids[] = (int) $row['id'];
            }
        }
        Helper_Core_Notification::create([
            'event_code' => 'purchases.order_authorization_requested',
            'notification_type' => 'purchases',
            'title' => 'OC pendiente de autorizacion',
            'message' => $order->folio.' requiere autorizacion por '.number_format((float) $order->total, 2),
            'url' => \Uri::create('admin/purchases'),
            'icon' => 'bi bi-shield-check',
            'priority' => 2,
            'created_by' => $this->user_id,
        ], $ids);
    }

    protected function purchase_flow($invoice_id)
    {
        $flow = ['cfdi' => null, 'order' => null, 'receipts' => [], 'payments' => []];
        $invoice = \DB::select()
            ->from('core_purchase_invoices')
            ->where('id', '=', (int) $invoice_id)
            ->execute()
            ->current();
        if (!$invoice) {
            return $flow;
        }

        if ((int) $invoice['cfdi_id'] > 0 && \DBUtil::table_exists('core_sat_cfdi')) {
            $flow['cfdi'] = \DB::select('id', 'uuid', 'sat_status', 'voucher_type', 'total')
                ->from('core_sat_cfdi')
                ->where('id', '=', (int) $invoice['cfdi_id'])
                ->execute()
                ->current();
        }
        if ((int) $invoice['order_id'] > 0) {
            $flow['order'] = \DB::select('id', 'folio', 'status', 'approval_status', 'total')
                ->from('core_purchase_orders')
                ->where('id', '=', (int) $invoice['order_id'])
                ->execute()
                ->current();
        }

        $receipts = \DB::select(['r.id', 'id'], ['r.folio', 'folio'], ['r.status', 'status'], ['r.payment_id', 'payment_id'], ['ri.amount', 'amount'])
            ->from(['core_purchase_receipt_items', 'ri'])
            ->join(['core_purchase_receipts', 'r'], 'inner')->on('r.id', '=', 'ri.receipt_id')
            ->where('ri.invoice_id', '=', (int) $invoice_id)
            ->where('ri.active', '=', 1)
            ->where('r.active', '=', 1)
            ->execute()
            ->as_array();
        $flow['receipts'] = $receipts;

        $payment_ids = [];
        foreach ($receipts as $receipt) {
            if ((int) $receipt['payment_id'] > 0) {
                $payment_ids[] = (int) $receipt['payment_id'];
            }
        }
        if (!empty($payment_ids)) {
            $flow['payments'] = \DB::select('id', 'folio', 'payment_date', 'amount', 'status', 'reference')
                ->from('core_payments')
                ->where('id', 'in', array_unique($payment_ids))
                ->execute()
                ->as_array();
        }

        return $flow;
    }

    protected function store_document($entity_type, $entity_id, $visibility)
    {
        $file = \Input::file('file');
        if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Selecciona un archivo valido.');
        }
        $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
        $allowed = ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Tipo de archivo no permitido.');
        }
        if ((int) \Arr::get($file, 'size', 0) > 15728640) {
            throw new \RuntimeException('El archivo no puede superar 15 MB.');
        }

        $relative_dir = 'assets/uploads/documents/purchases/'.date('Y').'/'.date('m');
        $absolute_dir = DOCROOT.$relative_dir;
        if (!is_dir($absolute_dir)) {
            mkdir($absolute_dir, 0755, true);
        }
        $base_name = pathinfo((string) \Arr::get($file, 'name', 'documento'), PATHINFO_FILENAME);
        $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->codeify($base_name).'.'.$extension;
        $target = $absolute_dir.DS.$filename;
        if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        $document = Model_Core_Document::forge([
            'document_type' => (string) \Input::post('document_type', $entity_type),
            'title' => trim((string) \Input::post('title', '')) ?: $base_name,
            'description' => trim((string) \Input::post('description', '')),
            'file_path' => str_replace('\\', '/', $relative_dir.'/'.$filename),
            'original_name' => (string) \Arr::get($file, 'name', ''),
            'mime_type' => (string) \Arr::get($file, 'type', ''),
            'file_extension' => $extension,
            'file_size' => (int) \Arr::get($file, 'size', 0),
            'checksum' => is_file($target) ? hash_file('sha256', $target) : '',
            'visibility' => $visibility,
            'is_evidence' => (int) (bool) \Input::post('is_evidence', true),
            'uploaded_by' => $this->user_id,
            'active' => 1,
        ]);
        $document->save();

        Model_Core_Document_Link::forge([
            'document_id' => (int) $document->id,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'relation_type' => 'attachment',
            'notes' => trim((string) \Input::post('notes', '')),
            'created_by' => $this->user_id,
            'active' => 1,
        ])->save();

        return $document;
    }

    protected function select_options($table, $value_field, $label_field, array $where = [])
    {
        $query = \DB::select($value_field, $label_field)->from($table)->where('active', '=', 1);
        foreach ($where as $field => $value) {
            if ($field === 'party_type') {
                $query->where($field, 'in', [$value, 'both']);
            } else {
                $query->where($field, '=', $value);
            }
        }
        if ($table === 'core_parties') {
            $this->apply_party_scope($query, $table, 'purchases');
        }
        $items = [];
        foreach ($query->order_by($label_field, 'asc')->execute() as $row) {
            $items[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $items;
    }

    protected function user_options()
    {
        $items = [];
        foreach (\DB::select('id', 'username', 'email')->from('users')->order_by('username', 'asc')->execute() as $row) {
            $label = (string) $row['username'];
            if (!empty($row['email'])) {
                $label .= ' - '.$row['email'];
            }
            $items[] = ['value' => (string) $row['id'], 'label' => $label];
        }
        return $items;
    }

    protected function payment_options()
    {
        if (!\DBUtil::table_exists('core_payments')) {
            return [];
        }
        $items = [];
        foreach (\DB::select('id', 'folio', 'amount', 'currency_code', 'status')->from('core_payments')->where('payment_type', '=', 'outgoing')->where('active', '=', 1)->order_by('id', 'desc')->limit(100)->execute() as $row) {
            $items[] = [
                'value' => (string) $row['id'],
                'label' => $row['folio'].' - '.$row['currency_code'].' '.number_format((float) $row['amount'], 2).' - '.$row['status'],
            ];
        }
        return $items;
    }

    protected function approval_rule_options()
    {
        if (!\DBUtil::table_exists('core_purchase_approval_rules')) {
            return [];
        }
        $items = [];
        foreach (\DB::select(['r.id', 'id'], ['r.name', 'name'], ['r.department_id', 'department_id'], ['d.name', 'department_name'], ['r.min_amount', 'min_amount'], ['r.max_amount', 'max_amount'], ['r.approver_group_id', 'approver_group_id'], ['r.auto_approve', 'auto_approve'])
            ->from(['core_purchase_approval_rules', 'r'])
            ->join(['core_departments', 'd'], 'left')->on('r.department_id', '=', 'd.id')
            ->where('r.active', '=', 1)
            ->order_by('r.sort_order', 'asc')
            ->execute() as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'department' => (string) ($row['department_name'] ?: 'Todos'),
                'min_amount' => (float) $row['min_amount'],
                'max_amount' => (float) $row['max_amount'],
                'approver_group_id' => (int) $row['approver_group_id'],
                'auto_approve' => (int) $row['auto_approve'],
            ];
        }
        return $items;
    }

    protected function apply_purchase_scope($query, $order_alias, $party_alias)
    {
        if ($this->can_view_all_operational()) {
            return $query;
        }

        $department_id = $this->employee_department_id();
        $query->where_open()
            ->where($party_alias.'.buyer_user_id', '=', (int) $this->user_id);
        if ($department_id > 0) {
            $query->or_where($party_alias.'.department_id', '=', $department_id)
                ->or_where($order_alias.'.department_id', '=', $department_id);
        }
        $query->where_close();

        return $query;
    }

    protected function select_rate_options($table, $value_field, $label_field, array $where = [])
    {
        $query = \DB::select($value_field, $label_field, 'rate')->from($table)->where('active', '=', 1);
        foreach ($where as $field => $value) {
            $query->where($field, '=', $value);
        }
        $items = [];
        foreach ($query->order_by($label_field, 'asc')->execute() as $row) {
            $rate = (float) $row['rate'];
            $items[] = [
                'value' => (string) $row[$value_field],
                'label' => (string) $row[$label_field],
                'rate' => $rate,
                'rate_label' => rtrim(rtrim(number_format($rate * 100, 4, '.', ''), '0'), '.').'%',
            ];
        }
        return $items;
    }

    protected function next_folio($prefix, $table)
    {
        $base = $prefix.'-'.date('Ymd').'-';
        $row = \DB::select(\DB::expr('COUNT(*) as total'))->from($table)->where('folio', 'like', $base.'%')->execute()->current();
        return $base.str_pad(((int) $row['total']) + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function document_entity_type($value)
    {
        $allowed = ['purchase_order', 'purchase_invoice', 'purchase_receipt'];
        $value = $this->codeify($value);
        return in_array($value, $allowed, true) ? $value : '';
    }

    protected function audit($action, $entity_type, $model, array $old)
    {
        $tables = [
            'purchase_order' => 'core_purchase_orders',
            'purchase_invoice' => 'core_purchase_invoices',
            'purchase_receipt' => 'core_purchase_receipts',
            'document' => 'core_documents',
        ];

        Helper_Core_Audit::log([
            'module' => 'purchases',
            'action' => $action,
            'business_event' => 'purchases.'.$action,
            'entity_type' => $entity_type,
            'entity_id' => (int) $model->id,
            'table_name' => isset($tables[$entity_type]) ? $tables[$entity_type] : '',
            'record_pk' => (string) $model->id,
            'summary' => ucfirst(str_replace('_', ' ', $action)).' '.$entity_type,
            'old_values' => $old,
            'new_values' => $model->to_array(),
        ]);
    }

    protected function notify_admins($event_code, $title, $message, $url)
    {
        $ids = [];
        foreach (\DB::select('id')->from('users')->where('group_id', '>=', 70)->execute() as $row) {
            $ids[] = (int) $row['id'];
        }
        Helper_Core_Notification::create([
            'event_code' => $event_code,
            'notification_type' => 'purchases',
            'title' => $title,
            'message' => $message,
            'url' => \Uri::create($url),
            'icon' => 'bi bi-cart-check',
            'priority' => 2,
            'created_by' => $this->user_id,
        ], $ids);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_purchase_orders', 'core_purchase_order_items', 'core_purchase_invoices', 'core_purchase_receipts', 'core_purchase_receipt_items', 'core_purchase_approval_rules', 'core_documents', 'core_document_links'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de compras.');
            }
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
