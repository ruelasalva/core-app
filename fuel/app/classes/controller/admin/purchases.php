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
            return $this->json_response([
                'orders' => $this->orders(),
                'invoices' => $this->invoices(),
                'receipts' => $this->receipts(),
                'documents' => $this->documents(),
                'options' => $this->options(),
                'stats' => $this->stats(),
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
                'status' => $this->codeify(\Arr::get($val, 'status', 'draft')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'internal_notes' => trim((string) \Arr::get($val, 'internal_notes', '')),
                'external_reference' => trim((string) \Arr::get($val, 'external_reference', '')),
                'active' => 1,
            ];

            if ($id > 0) {
                $order = Model_Core_Purchase_Order::find($id);
                if (!$order) {
                    return $this->json_response(['error' => 'Orden no encontrada.'], 404);
                }
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

            $data = [
                'party_id' => $party_id,
                'order_id' => (int) \Arr::get($val, 'order_id', 0),
                'billing_invoice_id' => (int) \Arr::get($val, 'billing_invoice_id', 0),
                'cfdi_id' => (int) \Arr::get($val, 'cfdi_id', 0),
                'uuid' => strtoupper(trim((string) \Arr::get($val, 'uuid', ''))),
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
                $amount = (float) $invoice->balance_due;
                $total += $amount;
                Model_Core_Purchase_Receipt_Item::forge([
                    'receipt_id' => (int) $receipt->id,
                    'invoice_id' => (int) $invoice->id,
                    'amount' => $amount,
                    'active' => 1,
                ])->save();
                $invoice->status = 'in_receipt';
                $invoice->save();
            }

            $receipt->total = round($total, 2);
            $receipt->save();
            $this->audit('create_receipt', 'purchase_receipt', $receipt, []);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error creando contrarecibo: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear el contrarecibo.'], 400);
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

    protected function orders()
    {
        $rows = \DB::select(['o.id', 'id'], ['o.folio', 'folio'], ['o.party_id', 'party_id'], ['p.name', 'party_name'], ['p.rfc', 'party_rfc'], ['o.order_date', 'order_date'], ['o.expected_date', 'expected_date'], ['o.status', 'status'], ['o.currency_code', 'currency_code'], ['o.subtotal', 'subtotal'], ['o.tax_total', 'tax_total'], ['o.retention_total', 'retention_total'], ['o.total', 'total'], ['o.invoiced_total', 'invoiced_total'], ['o.balance_total', 'balance_total'], ['o.notes', 'notes'], ['o.internal_notes', 'internal_notes'], ['o.created_at', 'created_at'])
            ->from(['core_purchase_orders', 'o'])
            ->join(['core_parties', 'p'], 'left')->on('o.party_id', '=', 'p.id')
            ->where('o.active', '=', 1);
        $this->apply_purchase_scope($rows, 'o', 'p');

        $rows = $rows
            ->order_by('o.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['items'] = $this->order_items((int) $row['id']);
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
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

    protected function invoices()
    {
        $rows = \DB::select(['i.id', 'id'], ['i.folio', 'folio'], ['i.party_id', 'party_id'], ['p.name', 'party_name'], ['i.order_id', 'order_id'], ['o.folio', 'order_folio'], ['i.uuid', 'uuid'], ['i.invoice_date', 'invoice_date'], ['i.due_date', 'due_date'], ['i.currency_code', 'currency_code'], ['i.total', 'total'], ['i.balance_due', 'balance_due'], ['i.status', 'status'], ['i.validation_status', 'validation_status'], ['i.sat_status', 'sat_status'], ['i.message', 'message'], ['i.created_at', 'created_at'])
            ->from(['core_purchase_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->join(['core_purchase_orders', 'o'], 'left')->on('i.order_id', '=', 'o.id')
            ->where('i.active', '=', 1);
        $this->apply_purchase_scope($rows, 'o', 'p');

        $rows = $rows
            ->order_by('i.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
        foreach ($rows as &$row) {
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
        }
        return $rows;
    }

    protected function receipts()
    {
        $rows = \DB::select(['r.id', 'id'], ['r.folio', 'folio'], ['r.party_id', 'party_id'], ['p.name', 'party_name'], ['r.issue_date', 'issue_date'], ['r.scheduled_payment_date', 'scheduled_payment_date'], ['r.currency_code', 'currency_code'], ['r.total', 'total'], ['r.status', 'status'], ['r.notes', 'notes'], ['r.created_at', 'created_at'])
            ->from(['core_purchase_receipts', 'r'])
            ->join(['core_parties', 'p'], 'left')->on('r.party_id', '=', 'p.id')
            ->where('r.active', '=', 1);
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

    protected function documents()
    {
        $rows = \DB::select(['d.id', 'id'], ['l.entity_type', 'entity_type'], ['l.entity_id', 'entity_id'], ['d.title', 'title'], ['d.original_name', 'original_name'], ['d.file_path', 'file_path'], ['d.file_extension', 'file_extension'], ['d.visibility', 'visibility'], ['d.is_evidence', 'is_evidence'], ['d.created_at', 'created_at'])
            ->from(['core_document_links', 'l'])
            ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
            ->where('l.entity_type', 'in', ['purchase_order', 'purchase_invoice', 'purchase_receipt'])
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
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
            'payment_terms' => $this->select_options('core_catalog_payment_terms', 'id', 'name'),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'taxes' => $this->select_rate_options('core_catalog_taxes', 'code', 'name'),
            'retentions' => $this->select_rate_options('core_catalog_retentions', 'code', 'name'),
        ];
    }

    protected function stats()
    {
        return [
            'orders' => (int) \DB::count_records('core_purchase_orders'),
            'open_orders' => (int) \DB::select()->from('core_purchase_orders')->where('status', 'in', ['draft', 'authorized', 'partial'])->where('active', '=', 1)->execute()->count(),
            'invoices' => (int) \DB::count_records('core_purchase_invoices'),
            'pending_invoices' => (int) \DB::select()->from('core_purchase_invoices')->where('validation_status', '=', 'pending')->where('active', '=', 1)->execute()->count(),
            'receipts' => (int) \DB::count_records('core_purchase_receipts'),
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
        foreach (['core_purchase_orders', 'core_purchase_order_items', 'core_purchase_invoices', 'core_purchase_receipts', 'core_purchase_receipt_items', 'core_documents', 'core_document_links'] as $table) {
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
