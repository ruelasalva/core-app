<?php

/**
 * CONTROLADOR CLIENTES_COTIZACIONES
 *
 * Cotizaciones, estado de cuenta comercial y catalogo visible para portal clientes.
 *
 * @package  app
 * @extends  Controller_Clientes_Base
 */
class Controller_Clientes_Cotizaciones extends Controller_Clientes_Base
{
    /**
     * INDEX
     *
     * MUESTRA EL PORTAL DE CLIENTES EN LA PESTANA DE COTIZACIONES.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        return $this->action_quotes();
    }

    public function action_quotes()
    {
        $this->template->title = 'Cotizaciones';
        $this->template->content = View::forge('clientes/cotizaciones/index', [
            'party' => $this->party,
            'initial_tab' => 'quotes',
        ]);
    }

    /**
     * QUOTE REQUEST
     *
     * CREA UNA SOLICITUD DE COTIZACION DESDE EL PORTAL DE CLIENTES.
     *
     * @access  public
     * @return  Response
     */
    public function post_quote_request()
    {
        $val = (array) \Input::json();

        try {
            if (!\DBUtil::table_exists('core_sales_quotes') || !\DBUtil::table_exists('core_sales_quote_items')) {
                return $this->json_response(['error' => 'El modulo de ventas no esta listo.'], 422);
            }

            $items = (array) \Arr::get($val, 'items', []);
            if (empty($items)) {
                return $this->json_response(['error' => 'Agrega al menos un producto.'], 422);
            }

            $party_id = (int) $this->portal_link->party_id;
            $quote = Model_Core_Sales_Quote::forge([
                'folio' => $this->next_quote_folio(),
                'source' => 'portal_clientes',
                'offline_uuid' => '',
                'synced_from_offline' => 0,
                'offline_synced_at' => 0,
                'cart_id' => 0,
                'user_id' => $this->user_id,
                'party_id' => $party_id,
                'status' => 'requested',
                'currency_code' => 'MXN',
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'customer_notes' => trim((string) \Arr::get($val, 'customer_notes', '')),
                'internal_notes' => 'Solicitud creada desde portal clientes.',
                'expires_at' => time() + (60 * 60 * 24 * 15),
            ]);
            $quote->save();

            $subtotal = 0;
            $currency = 'MXN';
            $sort = 10;
            foreach ($items as $item) {
                $item = (array) $item;
                $product = $this->product_row((int) \Arr::get($item, 'product_id', 0));
                if (!$product) {
                    continue;
                }
                $quantity = max(1, (float) \Arr::get($item, 'quantity', 1));
                $price = $this->product_price($product, $party_id);
                $currency = $price['currency_code'];
                $line_total = round($price['price'] * $quantity, 2);
                $subtotal += $line_total;

                Model_Core_Sales_Quote_Item::forge([
                    'quote_id' => (int) $quote->id,
                    'product_id' => (int) $product['id'],
                    'sku' => (string) $product['sku'],
                    'name' => (string) $product['name'],
                    'currency_code' => $currency,
                    'unit_price' => $price['price'],
                    'quantity' => $quantity,
                    'line_subtotal' => $line_total,
                    'line_total' => $line_total,
                    'sort_order' => $sort,
                ])->save();
                $sort += 10;
            }

            if ($subtotal <= 0) {
                $quote->delete();
                return $this->json_response(['error' => 'No se pudo crear la cotizacion con esos productos.'], 422);
            }

            $quote->currency_code = $currency;
            $quote->subtotal = round($subtotal, 2);
            $quote->total = round($subtotal, 2);
            $quote->save();

            try {
                $this->notify_sales_admins($quote);
            } catch (\Exception $notify_error) {
                \Log::warning('No se pudo notificar cotizacion portal clientes: '.$notify_error->getMessage());
            }

            return $this->json_response([
                'status' => 'ok',
                'message' => 'Cotizacion enviada.',
                'stats' => $this->customer_stats($party_id),
                'quotes' => $this->customer_quotes($party_id),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creando cotizacion portal clientes: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo enviar la cotizacion.'], 400);
        }
    }

    public function action_quote_request()
    {
        return $this->post_quote_request();
    }

    protected function customer_cfdi($party_id, $filters = array())
    {
        if (!\DBUtil::table_exists('core_sat_cfdi')) {
            return [];
        }

        $filters = $this->normalize_customer_cfdi_filters($filters);
        $items = [];
        $query = \DB::select('id', 'uuid', 'voucher_type', 'serie', 'folio', 'issued_at', 'currency', 'subtotal', 'tax_transferred_total', 'tax_withheld_total', 'total', 'sat_status', 'sales_status', 'has_payment_complement', 'has_waybill', 'xml_path', 'missing_xml')
            ->from('core_sat_cfdi')
            ->where('customer_party_id', '=', (int) $party_id)
            ->where('portal_visible_customer', '=', 1);

        if ($filters['date_from'] !== '') {
            $query->where('issued_at', '>=', $filters['date_from'].' 00:00:00');
        }

        if ($filters['date_to'] !== '') {
            $query->where('issued_at', '<=', $filters['date_to'].' 23:59:59');
        }

        if ($filters['uuid'] !== '') {
            $query->where('uuid', 'like', '%'.$filters['uuid'].'%');
        }

        if ($filters['serie_folio'] !== '') {
            $term = '%'.$filters['serie_folio'].'%';
            $query->where_open()
                ->where('serie', 'like', $term)
                ->or_where('folio', 'like', $term)
                ->where_close();
        }

        if ($filters['sat_status'] !== '') {
            $query->where('sat_status', '=', $filters['sat_status']);
        }

        if ($filters['voucher_type'] !== '') {
            $query->where('voucher_type', '=', $filters['voucher_type']);
        }

        $rows = $query->order_by('issued_at', 'desc')
            ->limit(500)
            ->execute();

        foreach ($rows as $row) {
            $invoice = $this->customer_invoice_for_cfdi($row, (int) $party_id);
            $has_xml = ((int) \Arr::get($row, 'missing_xml', 0) === 0 && $this->customer_cfdi_file_available((string) \Arr::get($row, 'xml_path', '')));
            $has_pdf = ($invoice && $this->customer_cfdi_file_available((string) \Arr::get($invoice, 'pdf_path', '')));

            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y', strtotime($row['issued_at'])) : '';
            $row['serie_folio'] = trim((string) $row['serie'].((string) $row['serie'] !== '' && (string) $row['folio'] !== '' ? '-' : '').(string) $row['folio']);
            $row['payment_status'] = $this->customer_cfdi_payment_status($invoice);
            $row['has_xml'] = $has_xml ? 1 : 0;
            $row['xml_download_url'] = $has_xml ? \Uri::create('clientes/cfdi_xml_download/'.(int) $row['id']) : '';
            $row['has_pdf'] = $has_pdf ? 1 : 0;
            $row['pdf_download_url'] = $has_pdf ? \Uri::create('clientes/cfdi_pdf_download/'.(int) $row['id']) : '';
            unset($row['xml_path'], $row['missing_xml']);
            $items[] = $row;
        }
        return $items;
    }

    protected function normalize_customer_cfdi_filters($filters)
    {
        $filters = (array) $filters;
        $date_from = trim((string) \Arr::get($filters, 'date_from', ''));
        $date_to = trim((string) \Arr::get($filters, 'date_to', ''));

        return [
            'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : '',
            'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) ? $date_to : '',
            'uuid' => strtoupper(substr(trim((string) \Arr::get($filters, 'uuid', '')), 0, 60)),
            'serie_folio' => substr(trim((string) \Arr::get($filters, 'serie_folio', '')), 0, 80),
            'sat_status' => substr(trim((string) \Arr::get($filters, 'sat_status', '')), 0, 30),
            'voucher_type' => substr(trim((string) \Arr::get($filters, 'voucher_type', '')), 0, 5),
        ];
    }

    protected function customer_cfdi_by_id($cfdi_id, $party_id)
    {
        if ((int) $cfdi_id < 1 || !\DBUtil::table_exists('core_sat_cfdi')) {
            return null;
        }

        return \DB::select('id', 'uuid', 'serie', 'folio', 'xml_path', 'missing_xml', 'customer_party_id', 'portal_visible_customer')
            ->from('core_sat_cfdi')
            ->where('id', '=', (int) $cfdi_id)
            ->where('customer_party_id', '=', (int) $party_id)
            ->where('portal_visible_customer', '=', 1)
            ->execute()
            ->current();
    }

    protected function customer_invoice_for_cfdi($cfdi, $party_id)
    {
        if (!\DBUtil::table_exists('core_billing_invoices')) {
            return null;
        }

        $cfdi_id = (int) \Arr::get($cfdi, 'id', 0);
        $uuid = strtoupper(trim((string) \Arr::get($cfdi, 'uuid', '')));

        $query = \DB::select('id', 'folio', 'uuid', 'cfdi_id', 'status', 'balance_due', 'due_date', 'pdf_path', 'xml_path')
            ->from('core_billing_invoices')
            ->where('party_id', '=', (int) $party_id)
            ->where('invoice_type', '=', 'sale')
            ->where('active', '=', 1);

        if ($cfdi_id > 0 && $uuid !== '') {
            $query->where_open()
                ->where('cfdi_id', '=', $cfdi_id)
                ->or_where('uuid', '=', $uuid)
                ->where_close();
        } elseif ($cfdi_id > 0) {
            $query->where('cfdi_id', '=', $cfdi_id);
        } elseif ($uuid !== '') {
            $query->where('uuid', '=', $uuid);
        } else {
            return null;
        }

        return $query->order_by('id', 'desc')->execute()->current();
    }

    protected function customer_cfdi_payment_status($invoice)
    {
        if (!$invoice) {
            return 'No disponible';
        }

        $balance_due = (float) \Arr::get($invoice, 'balance_due', 0);
        if ($balance_due <= 0) {
            return 'Pagada';
        }

        $due_date = trim((string) \Arr::get($invoice, 'due_date', ''));
        if ($due_date !== '' && $due_date < date('Y-m-d')) {
            return 'Vencida';
        }

        return 'Pendiente';
    }

    protected function customer_cfdi_file_available($path)
    {
        return $this->resolve_customer_cfdi_file_path($path) !== '';
    }

    protected function resolve_customer_cfdi_file_path($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $normalized = str_replace('\\', '/', $path);
        if (strpos($normalized, '..') !== false || preg_match('/^[a-z]+:\/\//i', $normalized)) {
            return '';
        }

        $candidates = [];
        if (preg_match('/^[a-z]:\//i', $normalized) || substr($normalized, 0, 1) === '/') {
            $candidates[] = $normalized;
        } else {
            $candidates[] = DOCROOT.ltrim($normalized, '/');
            if (defined('APPPATH')) {
                $project_root = realpath(APPPATH.'../..');
                if ($project_root) {
                    $candidates[] = $project_root.DIRECTORY_SEPARATOR.ltrim($normalized, '/');
                }
            }
        }

        $allowed_roots = [realpath(DOCROOT)];
        if (defined('APPPATH')) {
            $project_root = realpath(APPPATH.'../..');
            if ($project_root) {
                $allowed_roots[] = $project_root;
            }
        }

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if (!$real || !is_file($real)) {
                continue;
            }

            foreach ($allowed_roots as $root) {
                if ($root && strpos($real, $root) === 0) {
                    return $real;
                }
            }
        }

        return '';
    }

    protected function download_customer_cfdi_file($absolute_path, $filename, $mime)
    {
        if ($absolute_path === '' || !is_file($absolute_path)) {
            throw new \RuntimeException('Archivo no encontrado.');
        }

        return \Response::forge(file_get_contents($absolute_path), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', $filename).'"',
            'Content-Length' => (string) filesize($absolute_path),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    protected function cfdi_filename($cfdi, $extension)
    {
        $uuid = strtoupper(trim((string) \Arr::get($cfdi, 'uuid', '')));
        $base = $uuid !== '' ? $uuid : 'cfdi_'.(int) \Arr::get($cfdi, 'id', 0);
        return $base.'.'.$extension;
    }

    protected function customer_stats($party_id)
    {
        $account = $this->customer_account($party_id);
        return [
            'cfdi' => count($this->customer_cfdi($party_id)),
            'quotes' => count($this->customer_quotes($party_id)),
            'orders' => count($this->customer_orders($party_id)),
            'open_balance' => (float) $account['balance_due'],
            'overdue_balance' => (float) $account['overdue_balance'],
        ];
    }

    protected function customer_account($party_id, $filters = array())
    {
        $filters = $this->normalize_customer_account_filters($filters);
        $invoices = [];
        $balance = 0;
        $overdue = 0;
        $open_count = 0;
        $paid_count = 0;
        $aging = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
        ];

        if (\DBUtil::table_exists('core_billing_invoices')) {
            $query = \DB::select('id', 'folio', 'invoice_type', 'cfdi_id', 'issue_date', 'due_date', 'currency_code', 'total', 'balance_due', 'status')
                ->from('core_billing_invoices')
                ->where('party_id', '=', (int) $party_id)
                ->where('invoice_type', '=', 'sale')
                ->where('active', '=', 1);

            if ($filters['date_from'] !== '') {
                $query->where('issue_date', '>=', $filters['date_from']);
            }

            if ($filters['date_to'] !== '') {
                $query->where('issue_date', '<=', $filters['date_to']);
            }

            if ($filters['folio'] !== '') {
                $query->where('folio', 'like', '%'.$filters['folio'].'%');
            }

            if ($filters['currency'] !== '') {
                $query->where('currency_code', '=', $filters['currency']);
            }

            if ($filters['status'] === 'open') {
                $query->where('balance_due', '>', 0);
            } elseif ($filters['status'] === 'paid') {
                $query->where('balance_due', '<=', 0);
            } elseif ($filters['status'] === 'overdue') {
                $query->where('balance_due', '>', 0)
                    ->where('due_date', '!=', '')
                    ->where('due_date', '<', date('Y-m-d'));
            }

            $rows = $query->order_by('issue_date', 'desc')
                ->limit(500)
                ->execute();

            foreach ($rows as $row) {
                $row['issue_label'] = $row['issue_date'] ? date('d/m/Y', strtotime($row['issue_date'])) : '';
                $row['due_label'] = $row['due_date'] ? date('d/m/Y', strtotime($row['due_date'])) : '';
                $row['is_overdue'] = ((float) $row['balance_due'] > 0 && $row['due_date'] && $row['due_date'] < date('Y-m-d')) ? 1 : 0;
                $row['days_overdue'] = $this->customer_invoice_days_overdue((string) $row['due_date'], (float) $row['balance_due']);
                $row['payment_status'] = $this->customer_invoice_payment_status($row);
                $balance += (float) $row['balance_due'];
                if ((int) $row['is_overdue'] === 1) {
                    $overdue += (float) $row['balance_due'];
                }
                if ((float) $row['balance_due'] > 0) {
                    $open_count++;
                    $aging = $this->add_invoice_to_aging($aging, $row);
                } else {
                    $paid_count++;
                }
                $invoices[] = $row;
            }
        }

        $payments = [];
        if (\DBUtil::table_exists('core_payments')) {
            $query = \DB::select('id', 'folio', 'payment_date', 'currency_code', 'amount', 'reference', 'status')
                ->from('core_payments')
                ->where('party_id', '=', (int) $party_id)
                ->where('payment_type', '=', 'received')
                ->where('active', '=', 1);

            if ($filters['date_from'] !== '') {
                $query->where('payment_date', '>=', $filters['date_from']);
            }

            if ($filters['date_to'] !== '') {
                $query->where('payment_date', '<=', $filters['date_to']);
            }

            if ($filters['folio'] !== '') {
                $term = '%'.$filters['folio'].'%';
                $query->where_open()
                    ->where('folio', 'like', $term)
                    ->or_where('reference', 'like', $term)
                    ->where_close();
            }

            if ($filters['currency'] !== '') {
                $query->where('currency_code', '=', $filters['currency']);
            }

            $rows = $query->order_by('payment_date', 'desc')
                ->limit(300)
                ->execute();

            foreach ($rows as $row) {
                $row['payment_label'] = $row['payment_date'] ? date('d/m/Y', strtotime($row['payment_date'])) : '';
                $payments[] = $row;
            }
        }

        $allocations = $this->customer_payment_allocations($party_id, $invoices, $payments);

        return [
            'invoices' => $invoices,
            'payments' => $payments,
            'allocations' => $allocations,
            'aging' => [
                'current' => round((float) $aging['current'], 2),
                'days_1_30' => round((float) $aging['days_1_30'], 2),
                'days_31_60' => round((float) $aging['days_31_60'], 2),
                'days_61_90' => round((float) $aging['days_61_90'], 2),
                'days_over_90' => round((float) $aging['days_over_90'], 2),
            ],
            'summary' => [
                'open_invoices' => (int) $open_count,
                'paid_invoices' => (int) $paid_count,
                'payments_received' => count($payments),
            ],
            'balance_due' => round($balance, 2),
            'overdue_balance' => round($overdue, 2),
        ];
    }

    protected function normalize_customer_account_filters($filters)
    {
        $filters = (array) $filters;
        $date_from = trim((string) \Arr::get($filters, 'date_from', ''));
        $date_to = trim((string) \Arr::get($filters, 'date_to', ''));
        $status = trim((string) \Arr::get($filters, 'status', 'all'));
        $allowed_status = ['all', 'open', 'paid', 'overdue'];

        return [
            'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : '',
            'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) ? $date_to : '',
            'status' => in_array($status, $allowed_status, true) ? $status : 'all',
            'folio' => substr(trim((string) \Arr::get($filters, 'folio', '')), 0, 80),
            'currency' => strtoupper(substr(trim((string) \Arr::get($filters, 'currency', '')), 0, 3)),
        ];
    }

    protected function customer_invoice_days_overdue($due_date, $balance_due)
    {
        if ((float) $balance_due <= 0 || trim((string) $due_date) === '' || $due_date >= date('Y-m-d')) {
            return 0;
        }

        $due = strtotime($due_date);
        if (!$due) {
            return 0;
        }

        return max(0, (int) floor((strtotime(date('Y-m-d')) - $due) / 86400));
    }

    protected function customer_invoice_payment_status(array $invoice)
    {
        $balance_due = (float) \Arr::get($invoice, 'balance_due', 0);
        if ($balance_due <= 0) {
            return 'Pagada';
        }

        if ((int) \Arr::get($invoice, 'is_overdue', 0) === 1) {
            return 'Vencida';
        }

        return 'Pendiente';
    }

    protected function add_invoice_to_aging(array $aging, array $invoice)
    {
        $amount = (float) \Arr::get($invoice, 'balance_due', 0);
        $days = (int) \Arr::get($invoice, 'days_overdue', 0);

        if ($days <= 0) {
            $aging['current'] += $amount;
        } elseif ($days <= 30) {
            $aging['days_1_30'] += $amount;
        } elseif ($days <= 60) {
            $aging['days_31_60'] += $amount;
        } elseif ($days <= 90) {
            $aging['days_61_90'] += $amount;
        } else {
            $aging['days_over_90'] += $amount;
        }

        return $aging;
    }

    protected function customer_payment_allocations($party_id, array $invoices, array $payments)
    {
        if (!\DBUtil::table_exists('core_payment_allocations') || !\DBUtil::table_exists('core_payments') || !\DBUtil::table_exists('core_billing_invoices')) {
            return [];
        }

        $invoice_ids = array_values(array_unique(array_filter(array_map(function ($invoice) {
            return (int) \Arr::get($invoice, 'id', 0);
        }, $invoices))));

        $payment_ids = array_values(array_unique(array_filter(array_map(function ($payment) {
            return (int) \Arr::get($payment, 'id', 0);
        }, $payments))));

        if (empty($invoice_ids) || empty($payment_ids)) {
            return [];
        }

        $rows = \DB::select(
                ['a.id', 'id'],
                ['a.payment_id', 'payment_id'],
                ['a.entity_id', 'invoice_id'],
                ['a.amount', 'amount'],
                ['a.notes', 'notes'],
                ['p.folio', 'payment_folio'],
                ['p.payment_date', 'payment_date'],
                ['p.currency_code', 'currency_code'],
                ['p.reference', 'reference'],
                ['i.folio', 'invoice_folio']
            )
            ->from(['core_payment_allocations', 'a'])
            ->join(['core_payments', 'p'], 'inner')->on('p.id', '=', 'a.payment_id')
            ->join(['core_billing_invoices', 'i'], 'inner')->on('i.id', '=', 'a.entity_id')
            ->where('a.entity_type', '=', 'billing_invoice')
            ->where('a.active', '=', 1)
            ->where('p.party_id', '=', (int) $party_id)
            ->where('p.payment_type', '=', 'received')
            ->where('p.active', '=', 1)
            ->where('i.party_id', '=', (int) $party_id)
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('a.entity_id', 'in', $invoice_ids)
            ->where('a.payment_id', 'in', $payment_ids)
            ->order_by('p.payment_date', 'desc')
            ->limit(500)
            ->execute();

        $allocations = [];
        foreach ($rows as $row) {
            $row['payment_label'] = $row['payment_date'] ? date('d/m/Y', strtotime($row['payment_date'])) : '';
            $allocations[] = $row;
        }

        return $allocations;
    }

    protected function customer_quotes($party_id)
    {
        if (!\DBUtil::table_exists('core_sales_quotes')) {
            return [];
        }
        $rows = \DB::select('id', 'folio', 'source', 'status', 'currency_code', 'subtotal', 'discount_total', 'tax_total', 'total', 'customer_notes', 'internal_notes', 'expires_at', 'created_at')
            ->from('core_sales_quotes')
            ->where('party_id', '=', (int) $party_id)
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute();
        $items = [];
        foreach ($rows as $row) {
            $row['items'] = $this->quote_items((int) $row['id']);
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
            $row['expires_label'] = $row['expires_at'] ? date('d/m/Y', (int) $row['expires_at']) : '';
            $items[] = $row;
        }
        return $items;
    }

    protected function customer_orders($party_id)
    {
        if (!\DBUtil::table_exists('core_sales_orders')) {
            return [];
        }

        $rows = \DB::select('id', 'folio', 'status', 'currency_code', 'total', 'created_at', 'updated_at')
            ->from('core_sales_orders')
            ->where('party_id', '=', (int) $party_id)
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute();

        $items = [];
        foreach ($rows as $row) {
            $row['created_label'] = $row['created_at'] ? date('d/m/Y H:i', (int) $row['created_at']) : '';
            $items[] = $row;
        }
        return $items;
    }

    protected function quote_items($quote_id)
    {
        if (!\DBUtil::table_exists('core_sales_quote_items')) {
            return [];
        }
        $rows = \DB::select('i.product_id', 'i.sku', 'i.name', 'i.quantity', 'i.unit_price', 'i.line_total', ['p.main_image_path', 'image_path'])
            ->from(['core_sales_quote_items', 'i'])
            ->join(['core_commerce_products', 'p'], 'left')->on('i.product_id', '=', 'p.id')
            ->where('i.quote_id', '=', (int) $quote_id)
            ->order_by('i.sort_order', 'asc')
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['image_url'] = $this->media_url((string) $row['image_path']);
        }
        unset($row);
        return $rows;
    }

    protected function customer_options($party_id)
    {
        return [
            'products' => $this->product_options($party_id),
        ];
    }

    protected function product_options($party_id)
    {
        if (!\DBUtil::table_exists('core_commerce_products')) {
            return [];
        }
        $rows = \DB::select('id', 'sku', 'name', 'currency_code', 'price', 'main_image_path')
            ->from('core_commerce_products')
            ->where('active', '=', 1)
            ->where('published', '=', 1)
            ->order_by('name', 'asc')
            ->limit(500)
            ->execute();
        $items = [];
        foreach ($rows as $row) {
            $price = $this->product_price($row, $party_id);
            $items[] = [
                'value' => (int) $row['id'],
                'label' => trim($row['name'].' '.($row['sku'] ? '('.$row['sku'].')' : '')),
                'currency_code' => $price['currency_code'],
                'price' => $price['price'],
                'image_url' => $this->media_url((string) $row['main_image_path']),
            ];
        }
        return $items;
    }

    protected function product_row($product_id)
    {
        if (!\DBUtil::table_exists('core_commerce_products')) {
            return null;
        }
        $row = \DB::select('id', 'sku', 'name', 'currency_code', 'price', 'main_image_path')
            ->from('core_commerce_products')
            ->where('id', '=', (int) $product_id)
            ->where('active', '=', 1)
            ->where('published', '=', 1)
            ->execute()
            ->current();
        return $row ?: null;
    }

    protected function media_url($path)
    {
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        return \Uri::base(false).ltrim($path, '/');
    }

    protected function product_price(array $product, $party_id)
    {
        $price = (float) $product['price'];
        $currency = (string) $product['currency_code'];
        if (\DBUtil::table_exists('core_commerce_customer_price_lists') && \DBUtil::table_exists('core_commerce_product_prices')) {
            $list = \DB::select('price_list_id')->from('core_commerce_customer_price_lists')->where('customer_id', '=', (int) $party_id)->execute()->current();
            if ($list) {
                $row = \DB::select('price', 'currency_code')
                    ->from('core_commerce_product_prices')
                    ->where('product_id', '=', (int) $product['id'])
                    ->where('price_list_id', '=', (int) $list['price_list_id'])
                    ->order_by('min_quantity', 'asc')
                    ->execute()
                    ->current();
                if ($row) {
                    $price = (float) $row['price'];
                    $currency = (string) $row['currency_code'];
                }
            }
        }
        return ['price' => $price, 'currency_code' => $currency ?: 'MXN'];
    }

    protected function next_quote_folio()
    {
        $prefix = 'COT-'.date('Ymd').'-';
        $row = \DB::select(\DB::expr('COUNT(*) as total'))
            ->from('core_sales_quotes')
            ->where('folio', 'like', $prefix.'%')
            ->execute()
            ->current();
        return $prefix.str_pad(((int) $row['total']) + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function notify_sales_admins(Model_Core_Sales_Quote $quote)
    {
        Helper_Core_Notification::create([
            'event_code' => 'sales.portal_quote_requested',
            'notification_type' => 'sales',
            'title' => 'Nueva cotizacion de cliente',
            'message' => $this->party->name.' envio la solicitud '.$quote->folio,
            'url' => \Uri::create('admin/sales'),
            'icon' => 'bi bi-receipt',
            'priority' => 2,
            'created_by' => $this->user_id,
        ], $this->admin_user_ids());
    }
}
