<?php

/**
 * CONTROLADOR ADMIN_CFDI
 *
 * Auditoria fiscal de CFDI descargados, importados y relacionados con Compras.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Cfdi extends Controller_Adminbase
{
    public function before()
    {
        parent::before();
        $this->require_access('sat.access[view]');
    }

    public function action_index()
    {
        $this->template->title = 'Auditoria SAT';
        $this->template->content = View::forge('admin/cfdi/index');
    }

    public function action_data()
    {
        try {
            $this->assert_schema_ready();
            $filters = $this->filters();

            return $this->json_response([
                'filters' => $filters,
                'stats' => $this->stats($filters),
                'items' => $this->items($filters),
                'selected' => $this->selected_context(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando auditoria SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar auditoria SAT.'], 500);
        }
    }

    public function post_import_xml()
    {
        $this->require_access('sat.access[import]');

        try {
            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona un XML valido.'], 422);
            }

            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if ($extension !== 'xml') {
                return $this->json_response(['error' => 'Solo se permiten archivos XML.'], 422);
            }

            $relative_dir = 'assets/uploads/documents/sat/'.date('Y').'/'.date('m');
            $absolute_dir = DOCROOT.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0755, true);
            }

            $filename = time().'_'.\Str::random('alnum', 10).'.xml';
            $target = $absolute_dir.DS.$filename;
            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->json_response(['error' => 'No se pudo guardar el XML.'], 400);
            }

            $cfdi = (new Service_Core_Sat_Cfdi_Importer())->import_file($target, [
                'xml_path' => str_replace('\\', '/', $relative_dir.'/'.$filename),
                'origin' => 'admin_upload',
            ]);

            return $this->json_response([
                'status' => 'ok',
                'message' => 'CFDI '.$cfdi->uuid.' importado.',
                'cfdi_id' => (int) $cfdi->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error importando XML SAT: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_import_xml()
    {
        return $this->post_import_xml();
    }

    /**
     * CONVERT TO PURCHASE
     *
     * CONVIERTE UN CFDI RECIBIDO EN ORDEN Y FACTURA DE COMPRA
     *
     * @access  public
     * @return  Response
     */
    public function action_convert_purchase()
    {
        $this->require_access('sat.access[edit]');
        $this->require_access('purchases.access[create]');

        $transaction_started = false;
        try {
            $this->assert_schema_ready();
            if (!\DBUtil::table_exists('core_purchase_orders') || !\DBUtil::table_exists('core_purchase_invoices')) {
                throw new \RuntimeException('Falta ejecutar migraciones de Compras.');
            }

            $id = (int) \Arr::get((array) \Input::json(), 'cfdi_id', 0);
            $cfdi = Model_Core_Sat_Cfdi::find($id);
            if (!$cfdi || !$this->can_access_cfdi((int) $cfdi->id)) {
                return $this->json_response(['error' => 'CFDI no encontrado o sin permiso.'], 404);
            }
            if ($cfdi->direction !== 'received') {
                return $this->json_response(['error' => 'Solo CFDI recibidos pueden convertirse a compras.'], 422);
            }
            if ($cfdi->voucher_type === 'P') {
                return $this->json_response(['error' => 'Los REP se relacionan con pagos, no generan orden de compra.'], 422);
            }
            if ($cfdi->sat_status === 'cancelado') {
                return $this->json_response(['error' => 'No se puede convertir un CFDI cancelado.'], 422);
            }

            $party_id = (int) $cfdi->supplier_party_id ?: (int) $cfdi->emitter_party_id;
            if ($party_id < 1) {
                return $this->json_response(['error' => 'El RFC emisor no esta ligado a un proveedor. Revisa Socios comerciales.'], 422);
            }

            $existing = \DB::select('id')->from('core_purchase_invoices')->where('cfdi_id', '=', (int) $cfdi->id)->where('active', '=', 1)->execute()->current();
            if ($existing) {
                return $this->json_response(['error' => 'Este CFDI ya esta ligado a una factura de compra.'], 422);
            }

            \DB::start_transaction();
            $transaction_started = true;
            $order = $this->create_purchase_order_from_cfdi($cfdi, $party_id);
            $invoice = $this->create_purchase_invoice_from_cfdi($cfdi, $party_id, (int) $order->id);
            $this->recalculate_purchase_order((int) $order->id);
            $cfdi->purchase_status = 'linked';
            $cfdi->reviewed_by = (int) $this->user_id;
            $cfdi->reviewed_at = time();
            $cfdi->save();
            \DB::commit_transaction();

            Helper_Core_Audit::log([
                'module' => 'sat',
                'action' => 'convert_cfdi_to_purchase',
                'business_event' => 'sat.convert_purchase',
                'entity_type' => 'sat_cfdi',
                'entity_id' => (int) $cfdi->id,
                'summary' => 'CFDI '.$cfdi->uuid.' convertido a compra '.$order->folio.' / '.$invoice->folio,
                'new_values' => [
                    'purchase_order_id' => (int) $order->id,
                    'purchase_invoice_id' => (int) $invoice->id,
                ],
            ]);

            return $this->json_response([
                'status' => 'ok',
                'message' => 'Compra creada: '.$order->folio.' y factura '.$invoice->folio,
            ]);
        } catch (\Exception $e) {
            if ($transaction_started) {
                \DB::rollback_transaction();
            }
            \Log::error('Error convirtiendo CFDI a compra: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    protected function filters()
    {
        $month = trim((string) \Input::get('month', date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        return [
            'month' => $month,
            'tab' => trim((string) \Input::get('tab', 'received')) ?: 'received',
            'doc_type' => trim((string) \Input::get('doc_type', 'invoices')) ?: 'invoices',
            'q' => trim((string) \Input::get('q', '')),
        ];
    }

    protected function items(array $filters)
    {
        $query = \DB::select(
            'id', 'uuid', 'direction', 'voucher_type', 'serie', 'folio',
            'emitter_rfc', 'emitter_name', 'receiver_rfc', 'receiver_name',
            'issued_at', 'stamped_at', 'currency', 'subtotal', 'tax_transferred_total',
            'tax_withheld_total', 'total', 'sat_status', 'missing_xml',
            'has_payment_complement', 'has_waybill', 'sales_status', 'purchase_status',
            'portal_visible_customer', 'portal_visible_supplier', 'origin', 'xml_path',
            'supplier_party_id', 'customer_party_id', 'reviewed_by', 'reviewed_at'
        )->from('core_sat_cfdi');

        $this->apply_cfdi_scope($query);

        $start = $filters['month'].'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($filters['month'].'-01'));
        $query->where('issued_at', '>=', $start)->where('issued_at', '<=', $end);

        if (in_array($filters['tab'], ['received', 'issued'], true)) {
            $query->where('direction', '=', $filters['tab']);
        } elseif ($filters['tab'] === 'cancelled') {
            $query->where('sat_status', '=', 'cancelado');
        } elseif ($filters['tab'] === 'payments') {
            $query->where('has_payment_complement', '=', 1);
        }

        $this->apply_doc_type_filter($query, $filters['doc_type']);

        if ($filters['q'] !== '') {
            $q = '%'.$filters['q'].'%';
            $query->where_open()
                ->where('uuid', 'like', $q)
                ->or_where('emitter_rfc', 'like', $q)
                ->or_where('receiver_rfc', 'like', $q)
                ->or_where('emitter_name', 'like', $q)
                ->or_where('receiver_name', 'like', $q)
            ->where_close();
        }

        $items = [];
        foreach ($query->order_by('issued_at', 'desc')->limit(300)->execute() as $row) {
            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y H:i', strtotime($row['issued_at'])) : '';
            $row['type_label'] = $this->voucher_label((string) $row['voucher_type']);
            $row['convertible_purchase'] = $this->is_purchase_convertible($row) ? 1 : 0;
            $items[] = $row;
        }

        return $items;
    }

    protected function selected_context()
    {
        $cfdi_id = (int) \Input::get('cfdi_id', 0);
        if ($cfdi_id < 1 || !$this->can_access_cfdi($cfdi_id)) {
            return [
                'details' => [],
                'payments' => [],
                'relations' => [],
                'linked' => [],
            ];
        }

        return [
            'details' => $this->details($cfdi_id),
            'payments' => $this->payments($cfdi_id),
            'relations' => $this->relations($cfdi_id),
            'linked' => $this->linked_records($cfdi_id),
        ];
    }

    protected function details($cfdi_id)
    {
        return \DB::select()->from('core_sat_cfdi_details')
            ->where('cfdi_id', '=', $cfdi_id)
            ->order_by('line_type', 'asc')
            ->order_by('line_number', 'asc')
            ->execute()
            ->as_array();
    }

    protected function payments($cfdi_id)
    {
        $query = \DB::select(['p.id', 'id'], ['c.uuid', 'payment_uuid'], ['p.invoice_uuid', 'invoice_uuid'], ['p.series', 'series'], ['p.folio', 'folio'], ['p.currency', 'currency'], ['p.partiality_number', 'partiality_number'], ['p.paid_amount', 'paid_amount'], ['p.remaining_balance', 'remaining_balance'])
            ->from(['core_sat_payment_details', 'p'])
            ->join(['core_sat_cfdi', 'c'], 'left')->on('c.id', '=', 'p.payment_cfdi_id')
            ->where_open()
                ->where('p.payment_cfdi_id', '=', $cfdi_id)
                ->or_where('p.invoice_cfdi_id', '=', $cfdi_id)
            ->where_close();
        $this->apply_cfdi_scope($query, 'c');

        return $query
            ->order_by('p.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function relations($cfdi_id)
    {
        $query = \DB::select(['r.id', 'id'], ['c.uuid', 'uuid'], ['r.related_uuid', 'related_uuid'], ['r.relation_type', 'relation_type'], ['r.exists_in_system', 'exists_in_system'])
            ->from(['core_sat_cfdi_relations', 'r'])
            ->join(['core_sat_cfdi', 'c'], 'left')->on('c.id', '=', 'r.cfdi_id')
            ->where('r.cfdi_id', '=', $cfdi_id);
        $this->apply_cfdi_scope($query, 'c');

        return $query
            ->order_by('r.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function linked_records($cfdi_id)
    {
        $items = [];
        if (\DBUtil::table_exists('core_purchase_invoices')) {
            foreach (\DB::select('id', 'folio', 'status', 'validation_status', 'total')->from('core_purchase_invoices')->where('cfdi_id', '=', $cfdi_id)->where('active', '=', 1)->execute() as $row) {
                $row['module'] = 'Compras';
                $row['type'] = 'Factura proveedor';
                $items[] = $row;
            }
        }
        return $items;
    }

    protected function stats(array $filters)
    {
        $start = $filters['month'].'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($filters['month'].'-01'));

        return [
            'total_month' => $this->count_month($start, $end),
            'received' => $this->count_month($start, $end, ['direction' => 'received']),
            'issued' => $this->count_month($start, $end, ['direction' => 'issued']),
            'cancelled' => $this->count_month($start, $end, ['sat_status' => 'cancelado']),
            'payments' => $this->count_month($start, $end, ['has_payment_complement' => 1]),
            'invoices' => $this->count_month($start, $end, ['voucher_type' => 'I']),
            'credit_notes' => $this->count_month($start, $end, ['voucher_type' => 'E']),
            'transfers' => $this->count_month($start, $end, ['voucher_type' => 'T']),
            'relations' => (int) \DB::count_records('core_sat_cfdi_relations'),
            'details' => (int) \DB::count_records('core_sat_cfdi_details'),
        ];
    }

    protected function count_month($start, $end, array $where = [])
    {
        $query = \DB::select()->from('core_sat_cfdi')->where('issued_at', '>=', $start)->where('issued_at', '<=', $end);
        $this->apply_cfdi_scope($query);
        foreach ($where as $field => $value) {
            $query->where($field, '=', $value);
        }
        return (int) $query->execute()->count();
    }

    protected function apply_cfdi_scope($query, $alias = 'core_sat_cfdi')
    {
        if ($this->can_view_all_operational()) {
            return $query;
        }

        $party_ids = $this->scoped_party_ids();
        if (empty($party_ids)) {
            $query->where($alias.'.id', '=', -1);
            return $query;
        }

        $query->where_open()
            ->where($alias.'.customer_party_id', 'in', $party_ids)
            ->or_where($alias.'.supplier_party_id', 'in', $party_ids)
        ->where_close();

        return $query;
    }

    protected function apply_doc_type_filter($query, $doc_type)
    {
        if ($doc_type === 'invoices') {
            $query->where('voucher_type', '=', 'I');
        } elseif ($doc_type === 'credit_notes') {
            $query->where('voucher_type', '=', 'E');
        } elseif ($doc_type === 'transfers') {
            $query->where('voucher_type', '=', 'T');
        } elseif ($doc_type === 'payments') {
            $query->where('voucher_type', '=', 'P');
        } elseif ($doc_type === 'payroll') {
            $query->where('voucher_type', '=', 'N');
        }
        return $query;
    }

    protected function can_access_cfdi($cfdi_id)
    {
        $query = \DB::select('id')->from('core_sat_cfdi')->where('id', '=', (int) $cfdi_id);
        $this->apply_cfdi_scope($query);
        return (bool) $query->execute()->current();
    }

    protected function scoped_party_ids()
    {
        $department_id = $this->employee_department_id();
        $query = \DB::select('id')->from('core_parties')->where('active', '=', 1);
        $query->where_open()
            ->where('sales_user_id', '=', (int) $this->user_id)
            ->or_where('buyer_user_id', '=', (int) $this->user_id);
        if ($department_id > 0) {
            $query->or_where('department_id', '=', $department_id);
        }
        $query->where_close();

        $ids = [];
        foreach ($query->execute() as $row) {
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }

    protected function voucher_label($type)
    {
        $labels = [
            'I' => 'Ingreso',
            'E' => 'Egreso',
            'T' => 'Traslado',
            'P' => 'Pago',
            'N' => 'Nomina',
        ];
        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    protected function is_purchase_convertible(array $row)
    {
        return $row['direction'] === 'received'
            && in_array($row['voucher_type'], ['I', 'T'], true)
            && $row['sat_status'] !== 'cancelado'
            && (int) $row['missing_xml'] === 0
            && (string) $row['purchase_status'] !== 'linked';
    }

    protected function create_purchase_order_from_cfdi(Model_Core_Sat_Cfdi $cfdi, $party_id)
    {
        $order = Model_Core_Purchase_Order::forge([
            'folio' => $this->next_folio('OC-SAT', 'core_purchase_orders'),
            'source' => 'sat_cfdi',
            'portal_code' => '',
            'party_id' => (int) $party_id,
            'department_id' => 0,
            'requested_by' => (int) $this->user_id,
            'order_date' => substr((string) $cfdi->issued_at, 0, 10),
            'expected_date' => '',
            'payment_term_id' => 0,
            'currency_code' => (string) $cfdi->currency ?: 'MXN',
            'exchange_rate' => (float) $cfdi->exchange_rate > 0 ? (float) $cfdi->exchange_rate : 1,
            'subtotal' => 0,
            'tax_total' => 0,
            'retention_total' => 0,
            'total' => 0,
            'invoiced_total' => 0,
            'balance_total' => 0,
            'status' => 'authorized',
            'notes' => 'Creada desde CFDI '.$cfdi->uuid,
            'internal_notes' => 'Conversion SAT CFDI '.$cfdi->id,
            'external_reference' => 'sat_cfdi:'.$cfdi->id,
            'created_by' => (int) $this->user_id,
            'active' => 1,
        ]);
        $order->save();

        $sort = 10;
        foreach ($this->details((int) $cfdi->id) as $line) {
            if ($line['line_type'] !== 'concept') {
                continue;
            }
            Model_Core_Purchase_Order_Item::forge([
                'order_id' => (int) $order->id,
                'product_id' => 0,
                'sku' => (string) $line['identification_number'],
                'description' => (string) $line['description'] ?: 'Concepto CFDI',
                'quantity' => max(0.0001, (float) $line['quantity']),
                'unit_code' => (string) $line['unit_code'] ?: 'H87',
                'unit_price' => max(0, (float) $line['unit_value']),
                'discount_amount' => max(0, (float) $line['discount']),
                'tax_rate' => (float) $line['vat_rate'],
                'tax_amount' => max(0, (float) $line['vat_amount']),
                'retention_amount' => max(0, (float) $line['retention_amount']),
                'line_total' => max(0, (float) $line['amount'] + (float) $line['vat_amount'] - (float) $line['retention_amount']),
                'sort_order' => $sort,
                'active' => 1,
            ])->save();
            $sort += 10;
        }

        return $order;
    }

    protected function create_purchase_invoice_from_cfdi(Model_Core_Sat_Cfdi $cfdi, $party_id, $order_id)
    {
        $invoice = Model_Core_Purchase_Invoice::forge([
            'folio' => $this->next_folio('FCP-SAT', 'core_purchase_invoices'),
            'party_id' => (int) $party_id,
            'order_id' => (int) $order_id,
            'billing_invoice_id' => 0,
            'cfdi_id' => (int) $cfdi->id,
            'uuid' => (string) $cfdi->uuid,
            'invoice_date' => substr((string) $cfdi->issued_at, 0, 10),
            'due_date' => '',
            'currency_code' => (string) $cfdi->currency ?: 'MXN',
            'subtotal' => (float) $cfdi->subtotal,
            'tax_total' => (float) $cfdi->tax_transferred_total,
            'retention_total' => (float) $cfdi->tax_withheld_total,
            'total' => (float) $cfdi->total,
            'balance_due' => (float) $cfdi->total,
            'status' => 'submitted',
            'validation_status' => 'validated',
            'sat_status' => (string) $cfdi->sat_status,
            'message' => 'Generada desde Auditoria SAT.',
            'created_by' => (int) $this->user_id,
            'active' => 1,
        ]);
        $invoice->save();
        return $invoice;
    }

    protected function recalculate_purchase_order($order_id)
    {
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
        $order = Model_Core_Purchase_Order::find($order_id);
        if (!$order) {
            return;
        }
        $order->subtotal = round((float) $totals['subtotal'], 2);
        $order->tax_total = round((float) $totals['tax_total'], 2);
        $order->retention_total = round((float) $totals['retention_total'], 2);
        $order->total = round((float) $totals['total'], 2);
        $order->invoiced_total = round((float) $invoiced['total'], 2);
        $order->balance_total = max(0, round($order->total - $order->invoiced_total, 2));
        $order->status = $order->balance_total > 0 ? 'partial' : 'closed';
        $order->save();
    }

    protected function next_folio($prefix, $table)
    {
        $base = $prefix.'-'.date('Ymd').'-';
        $row = \DB::select(\DB::expr('COUNT(*) as total'))->from($table)->where('folio', 'like', $base.'%')->execute()->current();
        return $base.str_pad(((int) $row['total']) + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_sat_cfdi', 'core_sat_cfdi_details', 'core_sat_payment_details', 'core_sat_cfdi_relations'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de Auditoria SAT.');
            }
        }
    }
}
