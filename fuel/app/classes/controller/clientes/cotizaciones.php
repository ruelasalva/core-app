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

    protected function customer_cfdi($party_id)
    {
        if (!\DBUtil::table_exists('core_sat_cfdi')) {
            return [];
        }

        $items = [];
        $rows = \DB::select('id', 'uuid', 'voucher_type', 'serie', 'folio', 'issued_at', 'currency', 'subtotal', 'tax_transferred_total', 'tax_withheld_total', 'total', 'sat_status', 'sales_status', 'has_payment_complement', 'has_waybill')
            ->from('core_sat_cfdi')
            ->where('customer_party_id', '=', (int) $party_id)
            ->where('portal_visible_customer', '=', 1)
            ->order_by('issued_at', 'desc')
            ->limit(200)
            ->execute();

        foreach ($rows as $row) {
            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y', strtotime($row['issued_at'])) : '';
            $items[] = $row;
        }
        return $items;
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

    protected function customer_account($party_id)
    {
        $invoices = [];
        $balance = 0;
        $overdue = 0;
        if (\DBUtil::table_exists('core_billing_invoices')) {
            $rows = \DB::select('id', 'folio', 'invoice_type', 'cfdi_id', 'issue_date', 'due_date', 'currency_code', 'total', 'balance_due', 'status')
                ->from('core_billing_invoices')
                ->where('party_id', '=', (int) $party_id)
                ->where('invoice_type', '=', 'sale')
                ->where('active', '=', 1)
                ->order_by('issue_date', 'desc')
                ->limit(200)
                ->execute();
            foreach ($rows as $row) {
                $row['issue_label'] = $row['issue_date'] ? date('d/m/Y', strtotime($row['issue_date'])) : '';
                $row['due_label'] = $row['due_date'] ? date('d/m/Y', strtotime($row['due_date'])) : '';
                $row['is_overdue'] = ((float) $row['balance_due'] > 0 && $row['due_date'] && $row['due_date'] < date('Y-m-d')) ? 1 : 0;
                $balance += (float) $row['balance_due'];
                if ((int) $row['is_overdue'] === 1) {
                    $overdue += (float) $row['balance_due'];
                }
                $invoices[] = $row;
            }
        }

        $payments = [];
        if (\DBUtil::table_exists('core_payments')) {
            $rows = \DB::select('id', 'folio', 'payment_date', 'currency_code', 'amount', 'reference', 'status')
                ->from('core_payments')
                ->where('party_id', '=', (int) $party_id)
                ->where('payment_type', '=', 'received')
                ->where('active', '=', 1)
                ->order_by('payment_date', 'desc')
                ->limit(100)
                ->execute();
            foreach ($rows as $row) {
                $row['payment_label'] = $row['payment_date'] ? date('d/m/Y', strtotime($row['payment_date'])) : '';
                $payments[] = $row;
            }
        }

        return [
            'invoices' => $invoices,
            'payments' => $payments,
            'balance_due' => round($balance, 2),
            'overdue_balance' => round($overdue, 2),
        ];
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
