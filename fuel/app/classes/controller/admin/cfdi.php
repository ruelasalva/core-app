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
            $this->normalize_month_directions($filters);

            return $this->json_response([
                'filters' => $filters,
                'stats' => $this->stats($filters),
                'items' => $this->items($filters),
                'reports' => $this->reports($filters),
                'ppd_audit' => $this->ppd_audit($filters),
                'selected' => $this->selected_context(),
                'options' => $this->options(),
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
            if (!\DBUtil::table_exists('core_purchase_cfdi_line_mappings')) {
                throw new \RuntimeException('Falta ejecutar migraciones de mapeo XML de Compras.');
            }

            $payload = (array) \Input::json();
            $id = (int) \Arr::get($payload, 'cfdi_id', 0);
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

            $line_mappings = $this->normalize_purchase_mappings($cfdi, (array) \Arr::get($payload, 'mappings', []));

            \DB::start_transaction();
            $transaction_started = true;
            $order = $this->create_purchase_order_from_cfdi($cfdi, $party_id, $line_mappings);
            $invoice = $this->create_purchase_invoice_from_cfdi($cfdi, $party_id, (int) $order->id);
            $this->link_purchase_mappings_to_invoice((int) $cfdi->id, (int) $invoice->id);
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
                    'mapped_lines' => count($line_mappings),
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

    /**
     * MATERIALIZE CATALOGS
     *
     * CREA/ACTUALIZA TERCERO FISCAL Y PRODUCTOS BASE DESDE UN CFDI
     *
     * @access  public
     * @return  Response
     */
    public function action_materialize_catalogs()
    {
        $this->require_access('sat.access[edit]');

        $transaction_started = false;
        try {
            $this->assert_schema_ready();
            $payload = (array) \Input::json();
            $id = (int) \Arr::get($payload, 'cfdi_id', 0);
            $mode = (string) \Arr::get($payload, 'mode', 'both');
            $mode = in_array($mode, ['party', 'products', 'both'], true) ? $mode : 'both';
            $party_data = (array) \Arr::get($payload, 'party', []);

            if (in_array($mode, ['party', 'both'], true)) {
                $this->require_access('parties.access[edit]');
            }
            if (in_array($mode, ['products', 'both'], true)) {
                $this->require_access('commerce.access[edit]');
            }

            $cfdi = \Model_Core_Sat_Cfdi::find($id);
            if (!$cfdi || !$this->can_access_cfdi((int) $cfdi->id)) {
                return $this->json_response(['error' => 'CFDI no encontrado o sin permiso.'], 404);
            }
            if (in_array($mode, ['party', 'both'], true) && !\DBUtil::table_exists('core_parties')) {
                return $this->json_response(['error' => 'Faltan migraciones de terceros.'], 422);
            }
            if (in_array($mode, ['products', 'both'], true) && !\DBUtil::table_exists('core_commerce_products')) {
                return $this->json_response(['error' => 'Faltan migraciones de productos.'], 422);
            }

            $result = ['parties_created' => 0, 'parties_updated' => 0, 'products_created' => 0, 'products_updated' => 0, 'products_skipped' => 0];
            \DB::start_transaction();
            $transaction_started = true;
            if (in_array($mode, ['party', 'both'], true)) {
                $party_result = $this->materialize_cfdi_party($cfdi, $party_data);
                $result['parties_created'] += $party_result['created'];
                $result['parties_updated'] += $party_result['updated'];
            }
            if (in_array($mode, ['products', 'both'], true)) {
                foreach ($this->details((int) $cfdi->id) as $line) {
                    if ((string) $line['line_type'] !== 'concept') {
                        continue;
                    }
                    $product_result = $this->materialize_cfdi_product($cfdi, $line);
                    $result[$product_result]++;
                }
            }
            $cfdi->reviewed_by = (int) $this->user_id;
            $cfdi->reviewed_at = time();
            $cfdi->save();
            \DB::commit_transaction();

            \Helper_Core_Audit::log([
                'module' => 'sat',
                'action' => 'materialize_catalogs',
                'business_event' => 'sat.materialize_catalogs',
                'entity_type' => 'sat_cfdi',
                'entity_id' => (int) $cfdi->id,
                'summary' => 'Catalogos actualizados desde CFDI '.$cfdi->uuid,
                'new_values' => $result,
            ]);

            return $this->json_response([
                'status' => 'ok',
                'message' => 'Catalogos actualizados. Terceros creados: '.$result['parties_created'].', actualizados: '.$result['parties_updated'].'. Productos creados: '.$result['products_created'].', actualizados: '.$result['products_updated'].'.',
                'summary' => $result,
            ]);
        } catch (\Exception $e) {
            if ($transaction_started) {
                \DB::rollback_transaction();
            }
            \Log::error('Error creando catalogos desde CFDI: '.$e->getMessage());
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
        if (in_array($filters['tab'], ['reports', 'ppd_issued', 'ppd_received'], true)) {
            return [];
        }

        $query = \DB::select(
            'id', 'uuid', 'direction', 'voucher_type', 'serie', 'folio',
            'emitter_rfc', 'emitter_name', 'emitter_regime', 'receiver_rfc', 'receiver_name', 'receiver_regime',
            'issued_at', 'stamped_at', 'currency', 'subtotal', 'tax_transferred_total',
            'tax_withheld_total', 'total', 'sat_status', 'missing_xml',
            'cfdi_use',
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
            $row['xml_status'] = ((string) $row['xml_path'] !== '' && (int) $row['missing_xml'] === 0) ? 'available' : 'missing';
            $row['convertible_purchase'] = $this->is_purchase_convertible($row) ? 1 : 0;
            $items[] = $row;
        }

        return $items;
    }

    protected function reports(array $filters)
    {
        $start = $filters['month'].'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($filters['month'].'-01'));

        $summary = [
            'issued_total' => $this->sum_month($start, $end, 'total', ['direction' => 'issued', 'voucher_type' => 'I']),
            'received_total' => $this->sum_month($start, $end, 'total', ['direction' => 'received', 'voucher_type' => 'I']),
            'issued_vat' => $this->sum_month($start, $end, 'tax_transferred_total', ['direction' => 'issued']),
            'received_vat' => $this->sum_month($start, $end, 'tax_transferred_total', ['direction' => 'received']),
            'missing_xml' => $this->count_month($start, $end, ['missing_xml' => 1]),
            'cancelled' => $this->count_month($start, $end, ['sat_status' => 'cancelado']),
        ];
        $summary['vat_balance'] = $summary['issued_vat'] - $summary['received_vat'];

        return [
            'summary' => $summary,
            'customers' => $this->counterparty_report($start, $end, 'issued'),
            'suppliers' => $this->counterparty_report($start, $end, 'received'),
            'missing_xml' => $this->missing_xml_report($start, $end),
        ];
    }

    protected function ppd_audit(array $filters)
    {
        $start = $filters['month'].'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($filters['month'].'-01'));
        $direction_filter = $filters['tab'] === 'ppd_received' ? 'received' : 'issued';
        $payments = $this->payment_totals_by_invoice();
        $items = [];
        $summary = [
            'issued_total' => 0.0,
            'issued_paid' => 0.0,
            'issued_balance' => 0.0,
            'received_total' => 0.0,
            'received_paid' => 0.0,
            'received_balance' => 0.0,
            'without_rep' => 0,
            'partial' => 0,
            'paid' => 0,
            'needs_xml' => 0,
        ];

        $query = \DB::select(
            'id', 'uuid', 'direction', 'serie', 'folio', 'emitter_rfc', 'emitter_name',
            'receiver_rfc', 'receiver_name', 'issued_at', 'currency', 'total',
            'payment_method', 'payment_form', 'sat_status', 'missing_xml', 'xml_path'
        )->from('core_sat_cfdi')
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end)
            ->where('voucher_type', '=', 'I')
            ->where('direction', '=', $direction_filter)
            ->where('sat_status', '!=', 'cancelado')
            ->where_open()
                ->where('payment_method', '=', 'PPD')
                ->or_where('payment_method', '=', '')
            ->where_close();

        $this->apply_cfdi_scope($query);

        foreach ($query->order_by('issued_at', 'desc')->limit(300)->execute() as $row) {
            $uuid = strtoupper((string) $row['uuid']);
            $paid = (float) ($payments[$uuid] ?? 0);
            $total = (float) $row['total'];
            $balance = max(0, $total - $paid);
            $needs_xml = (int) $row['missing_xml'] === 1 && trim((string) $row['payment_method']) === '';
            $status = $needs_xml ? 'needs_xml' : ($paid <= 0 ? 'without_rep' : ($balance > 1 ? 'partial' : 'paid'));
            $direction = (string) $row['direction'];

            if ($direction === 'issued') {
                $summary['issued_total'] += $total;
                $summary['issued_paid'] += $paid;
                $summary['issued_balance'] += $balance;
            } else {
                $summary['received_total'] += $total;
                $summary['received_paid'] += $paid;
                $summary['received_balance'] += $balance;
            }
            $summary[$status]++;

            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y', strtotime($row['issued_at'])) : '';
            $row['paid_amount'] = $paid;
            $row['balance_amount'] = $balance;
            $row['ppd_status'] = $status;
            $row['counterparty_rfc'] = $direction === 'issued' ? $row['receiver_rfc'] : $row['emitter_rfc'];
            $row['counterparty_name'] = $direction === 'issued' ? $row['receiver_name'] : $row['emitter_name'];
            $items[] = $row;
        }

        return ['summary' => $summary, 'items' => $items, 'direction' => $direction_filter];
    }

    protected function sum_month($start, $end, $field, array $where = [])
    {
        $query = \DB::select([\DB::expr('COALESCE(SUM('.$field.'),0)'), 'total'])
            ->from('core_sat_cfdi')
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end);
        $this->apply_cfdi_scope($query);
        foreach ($where as $column => $value) {
            $query->where($column, '=', $value);
        }
        $row = $query->execute()->current();
        return (float) ($row['total'] ?? 0);
    }

    protected function counterparty_report($start, $end, $direction)
    {
        $rfc_field = $direction === 'issued' ? 'receiver_rfc' : 'emitter_rfc';
        $name_field = $direction === 'issued' ? 'receiver_name' : 'emitter_name';
        $query = \DB::select(
            [$rfc_field, 'rfc'],
            [$name_field, 'name'],
            [\DB::expr('COUNT(*)'), 'cfdi_count'],
            [\DB::expr('COALESCE(SUM(total),0)'), 'total'],
            [\DB::expr('COALESCE(SUM(tax_transferred_total),0)'), 'vat'],
            [\DB::expr("SUM(CASE WHEN sat_status = 'cancelado' THEN 1 ELSE 0 END)"), 'cancelled'],
            [\DB::expr('SUM(CASE WHEN missing_xml = 1 THEN 1 ELSE 0 END)'), 'missing_xml']
        )->from('core_sat_cfdi')
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end)
            ->where('direction', '=', $direction)
            ->where('voucher_type', '=', 'I')
            ->group_by($rfc_field)
            ->group_by($name_field)
            ->order_by('total', 'desc')
            ->limit(20);
        $this->apply_cfdi_scope($query);

        return $query->execute()->as_array();
    }

    protected function missing_xml_report($start, $end)
    {
        $query = \DB::select('id', 'uuid', 'direction', 'emitter_rfc', 'emitter_name', 'receiver_rfc', 'receiver_name', 'issued_at', 'total', 'currency')
            ->from('core_sat_cfdi')
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end)
            ->where('missing_xml', '=', 1)
            ->order_by('issued_at', 'desc')
            ->limit(25);
        $this->apply_cfdi_scope($query);

        $items = [];
        foreach ($query->execute() as $row) {
            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y', strtotime($row['issued_at'])) : '';
            $row['counterparty_rfc'] = $row['direction'] === 'issued' ? $row['receiver_rfc'] : $row['emitter_rfc'];
            $row['counterparty_name'] = $row['direction'] === 'issued' ? $row['receiver_name'] : $row['emitter_name'];
            $items[] = $row;
        }
        return $items;
    }

    protected function payment_totals_by_invoice()
    {
        if (!\DBUtil::table_exists('core_sat_payment_details')) {
            return [];
        }

        $totals = [];
        foreach (\DB::select('invoice_uuid', [\DB::expr('COALESCE(SUM(paid_amount),0)'), 'paid'])
            ->from('core_sat_payment_details')
            ->group_by('invoice_uuid')
            ->execute() as $row) {
            $uuid = strtoupper(trim((string) $row['invoice_uuid']));
            if ($uuid !== '') {
                $totals[$uuid] = (float) $row['paid'];
            }
        }
        return $totals;
    }

    protected function normalize_month_directions(array $filters)
    {
        $rfcs = $this->company_rfcs();
        if (empty($rfcs)) {
            return;
        }

        $start = $filters['month'].'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($filters['month'].'-01'));

        \DB::update('core_sat_cfdi')
            ->set(['direction' => 'issued', 'supplier_party_id' => 0])
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end)
            ->where('emitter_rfc', 'in', $rfcs)
            ->execute();

        \DB::update('core_sat_cfdi')
            ->set(['direction' => 'received', 'customer_party_id' => 0])
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end)
            ->where('receiver_rfc', 'in', $rfcs)
            ->where('emitter_rfc', 'not in', $rfcs)
            ->execute();

        \DB::update('core_sat_cfdi')
            ->set(['missing_xml' => 1])
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end)
            ->where('origin', '=', 'metadata')
            ->where('xml_path', '=', '')
            ->execute();

        \DB::update('core_sat_cfdi')
            ->set(['missing_xml' => 0])
            ->where('issued_at', '>=', $start)
            ->where('issued_at', '<=', $end)
            ->where('xml_path', '!=', '')
            ->execute();
    }

    protected function company_rfcs()
    {
        $rfcs = [];
        if (\DBUtil::table_exists('core_companies')) {
            foreach (\DB::select('rfc')->from('core_companies')->execute() as $row) {
                $rfc = strtoupper(trim((string) $row['rfc']));
                if ($rfc !== '') {
                    $rfcs[$rfc] = $rfc;
                }
            }
        }
        if (\DBUtil::table_exists('core_sat_credentials')) {
            foreach (\DB::select('rfc')->from('core_sat_credentials')->where('active', '=', 1)->execute() as $row) {
                $rfc = strtoupper(trim((string) $row['rfc']));
                if ($rfc !== '') {
                    $rfcs[$rfc] = $rfc;
                }
            }
        }
        return array_values($rfcs);
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

    protected function options()
    {
        return [
            'products' => $this->product_options(),
            'warehouses' => $this->warehouse_options(),
            'departments' => $this->department_options(),
            'sat_cfdi_uses' => \Helper_Core_Sat_Catalog::options('core_sat_cfdi_uses'),
            'sat_tax_regimes' => \Helper_Core_Sat_Catalog::options('core_sat_tax_regimes'),
        ];
    }

    protected function product_options()
    {
        if (!\DBUtil::table_exists('core_commerce_products')) {
            return [];
        }

        return \DB::select('id', 'sku', 'name', 'unit_code', 'cost')
            ->from('core_commerce_products')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->limit(800)
            ->execute()
            ->as_array();
    }

    protected function warehouse_options()
    {
        if (!\DBUtil::table_exists('core_inventory_warehouses')) {
            return [];
        }

        return \DB::select('id', 'code', 'name', 'is_default')
            ->from('core_inventory_warehouses')
            ->where('active', '=', 1)
            ->order_by('is_default', 'desc')
            ->order_by('name', 'asc')
            ->execute()
            ->as_array();
    }

    protected function department_options()
    {
        if (!\DBUtil::table_exists('core_departments')) {
            return [];
        }

        return \DB::select('id', 'name')
            ->from('core_departments')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->execute()
            ->as_array();
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

    protected function create_purchase_order_from_cfdi(Model_Core_Sat_Cfdi $cfdi, $party_id, array $line_mappings = [])
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
            $mapping = isset($line_mappings[(int) $line['id']])
                ? $line_mappings[(int) $line['id']]
                : $this->default_purchase_mapping($line);
            $product_id = (int) $mapping['product_id'];
            $warehouse_id = (int) $mapping['warehouse_id'];
            $line_class = (string) $mapping['line_class'];

            if ($line_class === 'inventory_product') {
                if ($product_id < 1 && (int) $mapping['create_product'] === 1) {
                    $product_id = $this->create_product_from_cfdi_line($cfdi, $line, $mapping);
                    $mapping['product_id'] = $product_id;
                }
                if ($product_id < 1) {
                    throw new \RuntimeException('Selecciona o crea un SKU interno para la partida: '.(string) $line['description']);
                }
                if ($warehouse_id < 1) {
                    $warehouse_id = $this->default_warehouse_id();
                    $mapping['warehouse_id'] = $warehouse_id;
                }
            }

            $product = $product_id > 0 ? Model_Core_Commerce_Product::find($product_id) : null;
            $sku = $product ? (string) $product->sku : (string) $line['identification_number'];
            $description = $product && $line_class === 'inventory_product' ? (string) $product->name : ((string) $line['description'] ?: 'Concepto CFDI');

            Model_Core_Purchase_Order_Item::forge([
                'order_id' => (int) $order->id,
                'product_id' => $product_id,
                'sku' => $sku,
                'description' => $description,
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
            $item = \DB::select('id')
                ->from('core_purchase_order_items')
                ->where('order_id', '=', (int) $order->id)
                ->where('sort_order', '=', $sort)
                ->execute()
                ->current();
            $item_id = $item ? (int) $item['id'] : 0;
            $movement_id = 0;
            if ($line_class === 'inventory_product') {
                $movement_id = $this->inventory_in_from_purchase($product_id, $warehouse_id, max(0.0001, (float) $line['quantity']), max(0, (float) $line['unit_value']), $item_id, 'Entrada desde CFDI '.$cfdi->uuid);
                \DB::update('core_purchase_order_items')
                    ->set(['received_quantity' => max(0.0001, (float) $line['quantity'])])
                    ->where('id', '=', $item_id)
                    ->execute();
            }
            $this->save_cfdi_line_mapping($cfdi, $line, $mapping, (int) $order->id, $item_id, $movement_id);
            $sort += 10;
        }

        return $order;
    }

    protected function normalize_purchase_mappings(Model_Core_Sat_Cfdi $cfdi, array $mappings)
    {
        $concept_ids = [];
        foreach ($this->details((int) $cfdi->id) as $line) {
            if ($line['line_type'] === 'concept') {
                $concept_ids[(int) $line['id']] = $line;
            }
        }

        $normalized = [];
        foreach ($mappings as $mapping) {
            $mapping = (array) $mapping;
            $detail_id = (int) \Arr::get($mapping, 'cfdi_detail_id', 0);
            if (!isset($concept_ids[$detail_id])) {
                continue;
            }

            $line_class = (string) \Arr::get($mapping, 'line_class', 'internal_purchase');
            if (!in_array($line_class, ['service', 'internal_purchase', 'inventory_product'], true)) {
                $line_class = 'internal_purchase';
            }

            $normalized[$detail_id] = [
                'cfdi_detail_id' => $detail_id,
                'line_class' => $line_class,
                'product_id' => max(0, (int) \Arr::get($mapping, 'product_id', 0)),
                'warehouse_id' => max(0, (int) \Arr::get($mapping, 'warehouse_id', 0)),
                'create_product' => (int) \Arr::get($mapping, 'create_product', 0) === 1 ? 1 : 0,
                'new_sku' => trim((string) \Arr::get($mapping, 'new_sku', '')),
                'new_name' => trim((string) \Arr::get($mapping, 'new_name', '')),
            ];
        }

        foreach ($concept_ids as $detail_id => $line) {
            if (!isset($normalized[$detail_id])) {
                $normalized[$detail_id] = $this->default_purchase_mapping($line);
            }
        }

        return $normalized;
    }

    protected function default_purchase_mapping(array $line)
    {
        return [
            'cfdi_detail_id' => (int) $line['id'],
            'line_class' => 'internal_purchase',
            'product_id' => 0,
            'warehouse_id' => 0,
            'create_product' => 0,
            'new_sku' => (string) $line['identification_number'],
            'new_name' => (string) $line['description'],
        ];
    }

    protected function create_product_from_cfdi_line(Model_Core_Sat_Cfdi $cfdi, array $line, array $mapping)
    {
        $sku = $this->unique_product_sku($mapping['new_sku'] ?: (string) $line['identification_number'] ?: 'XML-'.$cfdi->id.'-'.$line['id']);
        $name = trim((string) $mapping['new_name']) ?: ((string) $line['description'] ?: 'Producto importado XML');
        $product = Model_Core_Commerce_Product::forge([
            'sku' => $sku,
            'name' => $name,
            'slug' => $this->unique_product_slug($name),
            'short_description' => 'Creado desde XML de proveedor.',
            'description' => (string) $line['description'],
            'brand_id' => 0,
            'category_id' => 0,
            'subcategory_id' => 0,
            'product_type' => 'product',
            'is_internal_service' => 0,
            'unit_code' => (string) $line['unit_code'] ?: 'H87',
            'sat_product_service_code' => (string) $line['product_service_code'] ?: '01010101',
            'sat_unit_code' => (string) $line['unit_code'] ?: 'H87',
            'sat_object_tax_code' => '02',
            'currency_code' => (string) $cfdi->currency ?: 'MXN',
            'price' => 0,
            'cost' => max(0, (float) $line['unit_value']),
            'tax_code' => (float) $line['vat_rate'] > 0 ? 'iva_16' : '',
            'sat_tax_code' => (float) $line['vat_rate'] > 0 ? '002' : '',
            'sat_tax_factor_type' => (float) $line['vat_rate'] > 0 ? 'Tasa' : 'Exento',
            'sat_tax_rate' => max(0, (float) $line['vat_rate']),
            'stock_quantity' => 0,
            'stock_reserved' => 0,
            'stock_min' => 0,
            'stock_updated_at' => 0,
            'main_image_path' => '',
            'show_in_home' => 0,
            'featured' => 0,
            'published' => 0,
            'active' => 1,
            'sort_order' => 0,
        ]);
        $product->save();
        return (int) $product->id;
    }

    protected function materialize_cfdi_party(\Model_Core_Sat_Cfdi $cfdi, array $party_data = [])
    {
        $is_issued = (string) $cfdi->direction === 'issued';
        $rfc = strtoupper(trim((string) ($is_issued ? $cfdi->receiver_rfc : $cfdi->emitter_rfc)));
        $name = trim((string) ($is_issued ? $cfdi->receiver_name : $cfdi->emitter_name));
        $type = $is_issued ? 'customer' : 'supplier';
        if ($rfc === '' && $name === '') {
            return ['created' => 0, 'updated' => 0];
        }
        $defaults = $this->cfdi_party_defaults($cfdi, $type);

        $row = $rfc !== '' ? \DB::select('id')->from('core_parties')->where('rfc', '=', $rfc)->execute()->current() : null;
        if ($row) {
            $party = \Model_Core_Party::find((int) $row['id']);
            $party->party_type = $this->merge_party_type((string) $party->party_type, $type);
            $party->department_id = (int) \Arr::get($party_data, 'department_id', $party->department_id ?: 0);
            $party->name = trim((string) \Arr::get($party_data, 'name', '')) ?: ($party->name ?: ($name ?: $rfc));
            $party->legal_name = trim((string) \Arr::get($party_data, 'legal_name', '')) ?: ($party->legal_name ?: $name);
            $party->email = trim((string) \Arr::get($party_data, 'email', $party->email ?: ''));
            $party->phone = trim((string) \Arr::get($party_data, 'phone', $party->phone ?: ''));
            $party->sat_cfdi_use_code = trim((string) \Arr::get($party_data, 'sat_cfdi_use_code', $party->sat_cfdi_use_code ?: $defaults['sat_cfdi_use_code']));
            $party->sat_tax_regime_code = trim((string) \Arr::get($party_data, 'sat_tax_regime_code', $party->sat_tax_regime_code ?: $defaults['sat_tax_regime_code']));
            $party->active = 1;
            $party->save();
            $this->link_cfdi_party($cfdi, (int) $party->id, $type);
            return ['created' => 0, 'updated' => 1];
        }

        $party = \Model_Core_Party::forge([
            'party_type' => $type,
            'department_id' => (int) \Arr::get($party_data, 'department_id', 0),
            'sales_user_id' => (int) \Arr::get($party_data, 'sales_user_id', 0),
            'default_seller_id' => (int) \Arr::get($party_data, 'default_seller_id', 0),
            'buyer_user_id' => (int) \Arr::get($party_data, 'buyer_user_id', 0),
            'code' => $this->unique_party_code((string) \Arr::get($party_data, 'code', '') ?: ($rfc ?: $name)),
            'name' => trim((string) \Arr::get($party_data, 'name', '')) ?: ($name ?: $rfc),
            'legal_name' => trim((string) \Arr::get($party_data, 'legal_name', '')) ?: $name,
            'rfc' => $rfc,
            'email' => trim((string) \Arr::get($party_data, 'email', '')),
            'phone' => trim((string) \Arr::get($party_data, 'phone', '')),
            'price_list_id' => (int) \Arr::get($party_data, 'price_list_id', 0),
            'payment_term_id' => (int) \Arr::get($party_data, 'payment_term_id', 0),
            'sat_cfdi_use_code' => trim((string) \Arr::get($party_data, 'sat_cfdi_use_code', $defaults['sat_cfdi_use_code'])),
            'sat_tax_regime_code' => trim((string) \Arr::get($party_data, 'sat_tax_regime_code', $defaults['sat_tax_regime_code'])),
            'fiscal_operation_type_id' => (int) \Arr::get($party_data, 'fiscal_operation_type_id', 0),
            'shipping_method_id' => (int) \Arr::get($party_data, 'shipping_method_id', 0),
            'credit_limit' => (float) \Arr::get($party_data, 'credit_limit', 0),
            'credit_days' => (int) \Arr::get($party_data, 'credit_days', 0),
            'notes' => 'Creado desde CFDI SAT '.$cfdi->uuid,
            'onboarding_status' => 'approved',
            'onboarding_notes' => '',
            'reviewed_by' => 0,
            'reviewed_at' => 0,
            'active' => 1,
        ]);
        $party->save();
        $this->link_cfdi_party($cfdi, (int) $party->id, $type);
        return ['created' => 1, 'updated' => 0];
    }

    protected function cfdi_party_defaults(\Model_Core_Sat_Cfdi $cfdi, $type)
    {
        $regime = $type === 'customer' ? (string) $cfdi->receiver_regime : (string) $cfdi->emitter_regime;

        return [
            'sat_cfdi_use_code' => trim((string) $cfdi->cfdi_use) ?: ($type === 'customer' ? 'S01' : 'G03'),
            'sat_tax_regime_code' => trim($regime) ?: '601',
        ];
    }

    protected function materialize_cfdi_product(\Model_Core_Sat_Cfdi $cfdi, array $line)
    {
        $sku_seed = trim((string) $line['identification_number']);
        $name = trim((string) $line['description']);
        if ($name === '') {
            return 'products_skipped';
        }

        $product = null;
        if ($sku_seed !== '') {
            $row = \DB::select('id')->from('core_commerce_products')->where('sku', '=', strtoupper($sku_seed))->execute()->current();
            $product = $row ? \Model_Core_Commerce_Product::find((int) $row['id']) : null;
        }
        if (!$product) {
            $row = \DB::select('id')->from('core_commerce_products')
                ->where('name', '=', $name)
                ->where('sat_product_service_code', '=', (string) $line['product_service_code'])
                ->execute()
                ->current();
            $product = $row ? \Model_Core_Commerce_Product::find((int) $row['id']) : null;
        }

        $data = [
            'name' => $name,
            'short_description' => substr($name, 0, 255),
            'description' => $name,
            'unit_code' => 'pieza',
            'sat_product_service_code' => (string) $line['product_service_code'] ?: '01010101',
            'sat_unit_code' => (string) $line['unit_code'] ?: 'H87',
            'sat_object_tax_code' => '02',
            'currency_code' => (string) $cfdi->currency ?: 'MXN',
            'price' => (string) $cfdi->direction === 'issued' ? max(0, (float) $line['unit_value']) : 0,
            'cost' => (string) $cfdi->direction === 'received' ? max(0, (float) $line['unit_value']) : 0,
            'tax_code' => (float) $line['vat_rate'] > 0 ? 'iva_16' : '',
            'sat_tax_code' => (float) $line['vat_rate'] > 0 ? '002' : '',
            'sat_tax_factor_type' => (float) $line['vat_rate'] > 0 ? 'Tasa' : 'Exento',
            'sat_tax_rate' => max(0, (float) $line['vat_rate']),
            'published' => 0,
            'active' => 1,
        ];

        if ($product) {
            $product->set($data);
            $product->save();
            return 'products_updated';
        }

        $data += [
            'sku' => $this->unique_product_sku($sku_seed ?: 'SAT-'.$cfdi->id.'-'.$line['id']),
            'slug' => $this->unique_product_slug($name),
            'brand_id' => 0,
            'category_id' => 0,
            'subcategory_id' => 0,
            'product_type' => 'product',
            'is_internal_service' => 0,
            'stock_quantity' => 0,
            'stock_reserved' => 0,
            'stock_min' => 0,
            'stock_updated_at' => 0,
            'main_image_path' => '',
            'show_in_home' => 0,
            'featured' => 0,
            'sort_order' => 0,
        ];
        \Model_Core_Commerce_Product::forge($data)->save();
        return 'products_created';
    }

    protected function link_cfdi_party(\Model_Core_Sat_Cfdi $cfdi, $party_id, $type)
    {
        if ($type === 'customer') {
            $cfdi->receiver_party_id = (int) $party_id;
            $cfdi->customer_party_id = (int) $party_id;
            return;
        }
        $cfdi->emitter_party_id = (int) $party_id;
        $cfdi->supplier_party_id = (int) $party_id;
    }

    protected function merge_party_type($current, $incoming)
    {
        if ($current === $incoming || $current === 'both') {
            return $current;
        }
        return in_array($current, ['customer', 'supplier'], true) && in_array($incoming, ['customer', 'supplier'], true) ? 'both' : $incoming;
    }

    protected function unique_party_code($seed)
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim((string) $seed)));
        $base = trim($base, '-') ?: 'tercero';
        $code = substr($base, 0, 60);
        $i = 2;
        while (\DB::select('id')->from('core_parties')->where('code', '=', $code)->execute()->current()) {
            $suffix = '-'.$i++;
            $code = substr($base, 0, 60 - strlen($suffix)).$suffix;
        }
        return $code;
    }

    protected function save_cfdi_line_mapping(Model_Core_Sat_Cfdi $cfdi, array $line, array $mapping, $order_id, $item_id, $movement_id)
    {
        $product = (int) $mapping['product_id'] > 0 ? Model_Core_Commerce_Product::find((int) $mapping['product_id']) : null;
        \DB::insert('core_purchase_cfdi_line_mappings')->set([
            'cfdi_id' => (int) $cfdi->id,
            'cfdi_detail_id' => (int) $line['id'],
            'purchase_order_id' => (int) $order_id,
            'purchase_order_item_id' => (int) $item_id,
            'purchase_invoice_id' => 0,
            'line_class' => (string) $mapping['line_class'],
            'product_id' => (int) $mapping['product_id'],
            'warehouse_id' => (int) $mapping['warehouse_id'],
            'inventory_movement_id' => (int) $movement_id,
            'supplier_sku' => (string) $line['identification_number'],
            'supplier_description' => substr((string) $line['description'], 0, 255),
            'internal_sku' => $product ? (string) $product->sku : '',
            'internal_name' => $product ? (string) $product->name : '',
            'quantity' => max(0.0001, (float) $line['quantity']),
            'unit_code' => (string) $line['unit_code'],
            'unit_cost' => max(0, (float) $line['unit_value']),
            'status' => $movement_id > 0 ? 'received' : 'mapped',
            'created_by' => (int) $this->user_id,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function link_purchase_mappings_to_invoice($cfdi_id, $invoice_id)
    {
        \DB::update('core_purchase_cfdi_line_mappings')
            ->set(['purchase_invoice_id' => (int) $invoice_id, 'updated_at' => time()])
            ->where('cfdi_id', '=', (int) $cfdi_id)
            ->where('purchase_invoice_id', '=', 0)
            ->execute();
    }

    protected function inventory_in_from_purchase($product_id, $warehouse_id, $quantity, $unit_cost, $entity_id, $notes)
    {
        $movement = Model_Core_Inventory_Movement::forge([
            'warehouse_id' => (int) $warehouse_id,
            'product_id' => (int) $product_id,
            'movement_type' => 'purchase_in',
            'quantity' => abs((float) $quantity),
            'unit_cost' => max(0, (float) $unit_cost),
            'related_module' => 'purchases',
            'related_entity_type' => 'purchase_order_item',
            'related_entity_id' => (int) $entity_id,
            'notes' => $notes,
            'created_by' => (int) $this->user_id,
        ]);
        $movement->save();
        $this->adjust_inventory_balance((int) $warehouse_id, (int) $product_id, abs((float) $quantity));
        $this->refresh_product_stock_from_balances((int) $product_id);

        return (int) $movement->id;
    }

    protected function default_warehouse_id()
    {
        if (!\DBUtil::table_exists('core_inventory_warehouses')) {
            throw new \RuntimeException('Falta configurar almacenes para recibir producto.');
        }
        $row = \DB::select('id')->from('core_inventory_warehouses')->where('is_default', '=', 1)->where('active', '=', 1)->execute()->current();
        if ($row) {
            return (int) $row['id'];
        }
        $row = \DB::select('id')->from('core_inventory_warehouses')->where('active', '=', 1)->order_by('id', 'asc')->execute()->current();
        if ($row) {
            return (int) $row['id'];
        }
        throw new \RuntimeException('No hay almacenes activos para recibir producto.');
    }

    protected function adjust_inventory_balance($warehouse_id, $product_id, $quantity)
    {
        if (!\DBUtil::table_exists('core_inventory_stock_balances')) {
            return;
        }

        $now = time();
        $row = \DB::select('id')
            ->from('core_inventory_stock_balances')
            ->where('warehouse_id', '=', (int) $warehouse_id)
            ->where('product_id', '=', (int) $product_id)
            ->execute()
            ->current();

        if ($row) {
            \DB::update('core_inventory_stock_balances')
                ->set([
                    'quantity_on_hand' => \DB::expr('quantity_on_hand + '.abs((float) $quantity)),
                    'last_movement_at' => $now,
                    'updated_at' => $now,
                ])
                ->where('id', '=', (int) $row['id'])
                ->execute();
            return;
        }

        \DB::insert('core_inventory_stock_balances')->set([
            'warehouse_id' => (int) $warehouse_id,
            'product_id' => (int) $product_id,
            'quantity_on_hand' => abs((float) $quantity),
            'quantity_reserved' => 0,
            'last_movement_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
    }

    protected function refresh_product_stock_from_balances($product_id)
    {
        if (!\DBUtil::table_exists('core_inventory_stock_balances')) {
            return;
        }

        $row = \DB::select([\DB::expr('COALESCE(SUM(quantity_on_hand), 0)'), 'stock'], [\DB::expr('COALESCE(SUM(quantity_reserved), 0)'), 'reserved'])
            ->from('core_inventory_stock_balances')
            ->where('product_id', '=', (int) $product_id)
            ->execute()
            ->current();

        \DB::update('core_commerce_products')
            ->set([
                'stock_quantity' => (float) $row['stock'],
                'stock_reserved' => (float) $row['reserved'],
                'stock_updated_at' => time(),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $product_id)
            ->execute();
    }

    protected function unique_product_sku($seed)
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9_-]+/i', '-', trim((string) $seed)));
        $base = trim($base, '-_') ?: 'XML-PRODUCTO';
        $sku = substr($base, 0, 80);
        $i = 2;
        while (\DB::select('id')->from('core_commerce_products')->where('sku', '=', $sku)->execute()->current()) {
            $suffix = '-'.$i++;
            $sku = substr($base, 0, 80 - strlen($suffix)).$suffix;
        }
        return $sku;
    }

    protected function unique_product_slug($seed)
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim((string) $seed)));
        $base = trim($base, '-') ?: 'producto-xml';
        $slug = substr($base, 0, 220);
        $i = 2;
        while (\DB::select('id')->from('core_commerce_products')->where('slug', '=', $slug)->execute()->current()) {
            $suffix = '-'.$i++;
            $slug = substr($base, 0, 220 - strlen($suffix)).$suffix;
        }
        return $slug;
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
