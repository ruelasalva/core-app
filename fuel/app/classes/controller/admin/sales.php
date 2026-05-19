<?php

/**
 * CONTROLADOR ADMIN_SALES
 *
 * Administra solicitudes comerciales generadas desde el frontend.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Sales extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * Valida sesion administrativa y permiso de lectura.
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y LA SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('sales.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE COTIZACIONES Y PEDIDOS BASE.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Ventas';
        $this->template->content = View::forge('admin/sales/index');
    }

    /**
     * CREATE
     *
     * MUESTRA CAPTURA DE COTIZACION EN PANTALLA COMPLETA.
     *
     * @access  public
     * @return  Void
     */
    public function action_create()
    {
        # VALIDAR PERMISO PARA CREAR COTIZACIONES
        $this->require_access('sales.access[create]');

        # SE CARGA LA MISMA LOGICA DE VENTAS EN MODO CAPTURA
        $this->template->title = 'Nueva cotizacion';
        $this->template->content = View::forge('admin/sales/index', ['capture_page' => true]);
    }

    /**
     * DATA
     *
     * ENTREGA COTIZACIONES EN JSON.
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA ESQUEMA BASE
            $this->assert_schema_ready();
            $this->sync_approved_quotes_to_orders();

            return $this->json_response([
                'quotes' => $this->quotes(),
                'stats' => $this->stats(),
                'options' => $this->options(),
                'orders' => $this->orders(),
                'deliveries' => $this->deliveries(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando ventas: '.$e->getMessage().' | trace='.$e->getTraceAsString());
            return $this->json_response(['error' => 'No se pudo cargar ventas.'], 500);
        }
    }

    /**
     * PRODUCT SEARCH
     *
     * BUSQUEDA LIGERA DE PRODUCTOS PARA CAPTURA RAPIDA.
     *
     * @access  public
     * @return  Response
     */
    public function action_product_search()
    {
        try {
            $this->assert_schema_ready();

            return $this->json_response([
                'products' => $this->product_options([
                    'q' => trim((string) \Input::get('q', '')),
                    'brand_id' => (int) \Input::get('brand_id', 0),
                    'category_id' => (int) \Input::get('category_id', 0),
                    'stock' => trim((string) \Input::get('stock', '')),
                    'limit' => (int) \Input::get('limit', 25),
                ]),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error buscando productos para cotizacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo buscar productos.'], 500);
        }
    }


    /**
     * CREATE QUOTE
     *
     * CREA UNA COTIZACION MANUAL DESDE ADMINISTRACION.
     *
     * @access  public
     * @return  Response
     */
    public function post_create_quote()
    {
        # VALIDAR PERMISO PARA CREAR
        $this->require_access('sales.access[create]');

        try {
            # SE OBTIENE PAYLOAD
            $val = (array) \Input::json();
            $quote_mode = trim((string) \Arr::get($val, 'quote_mode', 'quote'));
            $prequote = $quote_mode === 'prequote';
            $party_id = (int) \Arr::get($val, 'party_id', 0);
            $items = (array) \Arr::get($val, 'items', []);
            $offline_uuid = $this->offline_uuid((string) \Arr::get($val, 'offline_uuid', ''));

            if (!$prequote && $party_id < 1) {
                return $this->json_response(['error' => 'Selecciona cliente para cerrar la cotizacion con precios.'], 422);
            }
            if (empty($items)) {
                return $this->json_response(['error' => 'Agrega al menos un producto.'], 422);
            }
            if ($offline_uuid !== '') {
                $existing = \DB::select('id', 'folio')
                    ->from('core_sales_quotes')
                    ->where('offline_uuid', '=', $offline_uuid)
                    ->execute()
                    ->current();
                if ($existing) {
                    return $this->json_response([
                        'status' => 'ok',
                        'duplicate' => 1,
                        'offline_uuid' => $offline_uuid,
                        'folio' => (string) $existing['folio'],
                        'quotes' => $this->quotes(),
                        'orders' => $this->orders(),
                        'deliveries' => $this->deliveries(),
                        'stats' => $this->stats(),
                    ]);
                }
            }

            # SE CREA ENCABEZADO
            $quote = Model_Core_Sales_Quote::forge([
                'folio' => $this->next_quote_folio(),
                'source' => $prequote ? 'admin_prequote' : ($offline_uuid !== '' ? 'admin_offline' : 'admin_manual'),
                'offline_uuid' => $offline_uuid,
                'synced_from_offline' => $offline_uuid !== '' ? 1 : 0,
                'offline_synced_at' => $offline_uuid !== '' ? time() : 0,
                'cart_id' => 0,
                'user_id' => $this->current_user_id(),
                'party_id' => $party_id,
                'status' => $prequote ? 'prequote' : 'requested',
                'currency_code' => 'MXN',
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'customer_notes' => trim((string) \Arr::get($val, 'customer_notes', '')),
                'internal_notes' => trim((string) \Arr::get($val, 'internal_notes', '')),
                'expires_at' => time() + (60 * 60 * 24 * 15),
            ]);
            $quote->save();

            # SE AGREGAN PARTIDAS
            $subtotal = 0;
            $currency = 'MXN';
            $sort = 10;
            foreach ($items as $item) {
                $product_id = (int) \Arr::get((array) $item, 'product_id', 0);
                $quantity = max(1, (float) \Arr::get((array) $item, 'quantity', 1));
                $product = $this->product_row($product_id);
                if (!$product) {
                    continue;
                }

                $price = $prequote ? ['price' => 0, 'currency_code' => (string) $product['currency_code']] : $this->product_price($product, $party_id, $quantity);
                $currency = $price['currency_code'];
                $line_total = round($price['price'] * $quantity, 2);
                $subtotal += $line_total;

                Model_Core_Sales_Quote_Item::forge([
                    'quote_id' => (int) $quote->id,
                    'product_id' => $product_id,
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

            if (!$prequote && $subtotal <= 0) {
                $quote->delete();
                return $this->json_response(['error' => 'No se pudo crear la cotizacion con esos productos.'], 422);
            }

            # SE ACTUALIZAN TOTALES
            $quote->currency_code = $currency;
            $quote->subtotal = round($subtotal, 2);
            $quote->total = round($subtotal, 2);
            $quote->save();
            $this->log_offline_sync($offline_uuid, 'sales_quote', (int) $quote->id, $val);

            return $this->json_response(['status' => 'ok', 'offline_uuid' => $offline_uuid, 'folio' => $quote->folio, 'quotes' => $this->quotes(), 'orders' => $this->orders(), 'deliveries' => $this->deliveries(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error creando cotizacion manual: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear la cotizacion.'], 400);
        }
    }

    /**
     * CREATE QUOTE ACTION
     *
     * COMPATIBILIDAD DE RUTA PARA PETICIONES AJAX POST/JSON.
     *
     * @access  public
     * @return  Response
     */
    public function action_create_quote()
    {
        if (\Input::method() !== 'POST') {
            return $this->json_response(['error' => 'Metodo no permitido.'], 405);
        }

        # SE REUTILIZA EL FLUJO POST PARA EVITAR RESPUESTAS HTML 404
        return $this->post_create_quote();
    }

    /**
     * UPDATE STATUS
     *
     * ACTUALIZA EL ESTADO DE UNA COTIZACION.
     *
     * @access  public
     * @return  Response
     */
    public function post_update_status()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('sales.access[edit]');

        try {
            # SE OBTIENE PAYLOAD
            $val = (array) \Input::json();
            $quote = Model_Core_Sales_Quote::find((int) \Arr::get($val, 'id', 0));
            if (!$quote) {
                return $this->json_response(['error' => 'Cotizacion no encontrada.'], 404);
            }

            # SE VALIDA ESTADO PERMITIDO
            $status = trim((string) \Arr::get($val, 'status', ''));
            $allowed = ['prequote', 'requested', 'approved', 'rejected', 'converted'];
            if (!in_array($status, $allowed, true)) {
                return $this->json_response(['error' => 'Estado no valido.'], 422);
            }
            if ($quote->status === 'prequote' && in_array($status, ['approved', 'converted'], true)) {
                return $this->json_response(['error' => 'Primero cierra la precotizacion con cliente y precios.'], 422);
            }

            # SE GUARDA CAMBIO
            $quote->status = $status;
            $quote->internal_notes = trim((string) \Arr::get($val, 'internal_notes', $quote->internal_notes));
            $quote->save();

            if ($status === 'approved') {
                \Log::info('Ventas: aprobando cotizacion '.$quote->folio.' id='.(int) $quote->id.' desde update_status.');
                $this->create_order_for_quote($quote);
            }

            # SE AUDITA CAMBIO DE ESTADO COMERCIAL
            if (class_exists('Helper_Core_Audit')) {
                Helper_Core_Audit::log([
                    'module' => 'sales',
                    'action' => 'update_status',
                    'business_event' => 'sales.quote_status_updated',
                    'entity_type' => 'sales_quote',
                    'entity_id' => (int) $quote->id,
                    'table_name' => 'core_sales_quotes',
                    'record_pk' => (string) $quote->id,
                    'summary' => 'Cotizacion '.$quote->folio.' actualizada a '.$status,
                    'new_values' => $quote->to_array(),
                ]);
            }

            return $this->json_response(['status' => 'ok', 'quotes' => $this->quotes(), 'orders' => $this->orders(), 'deliveries' => $this->deliveries(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error actualizando cotizacion: '.$e->getMessage().' | payload='.json_encode((array) \Input::json()));
            return $this->json_response(['error' => 'No se pudo actualizar la cotizacion: '.$e->getMessage()], 400);
        }
    }

    /**
     * UPDATE STATUS ACTION
     *
     * COMPATIBILIDAD DE RUTA PARA PETICIONES AJAX POST/JSON.
     *
     * @access  public
     * @return  Response
     */
    public function action_update_status()
    {
        if (\Input::method() !== 'POST') {
            return $this->json_response(['error' => 'Metodo no permitido.'], 405);
        }

        # SE REUTILIZA EL FLUJO POST PARA MANTENER UNA SOLA LOGICA
        return $this->post_update_status();
    }

    /**
     * CLOSE PREQUOTE
     *
     * CONVIERTE UNA PRECOTIZACION EN COTIZACION CON CLIENTE Y PRECIOS.
     *
     * @access  public
     * @return  Response
     */
    public function post_close_prequote()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('sales.access[edit]');

        try {
            # SE OBTIENE PAYLOAD
            $val = (array) \Input::json();
            $quote = Model_Core_Sales_Quote::find((int) \Arr::get($val, 'id', 0));
            $party_id = (int) \Arr::get($val, 'party_id', 0);

            if (!$quote) {
                return $this->json_response(['error' => 'Precotizacion no encontrada.'], 404);
            }
            if ($party_id < 1) {
                return $this->json_response(['error' => 'Selecciona cliente para cerrar con precios.'], 422);
            }

            # SE RECALCULAN PARTIDAS CON LISTA/RANGO DEL CLIENTE
            $subtotal = 0;
            $currency = 'MXN';
            $items = \DB::select('id', 'product_id', 'quantity')
                ->from('core_sales_quote_items')
                ->where('quote_id', '=', (int) $quote->id)
                ->order_by('sort_order', 'asc')
                ->execute()
                ->as_array();

            foreach ($items as $item) {
                $product = $this->product_row((int) $item['product_id']);
                if (!$product) {
                    continue;
                }
                $price = $this->product_price($product, $party_id, (float) $item['quantity']);
                $currency = $price['currency_code'];
                $line_total = round((float) $price['price'] * (float) $item['quantity'], 2);
                $subtotal += $line_total;

                \DB::update('core_sales_quote_items')
                    ->set([
                        'currency_code' => $currency,
                        'unit_price' => $price['price'],
                        'line_subtotal' => $line_total,
                        'line_total' => $line_total,
                        'updated_at' => time(),
                    ])
                    ->where('id', '=', (int) $item['id'])
                    ->execute();
            }

            if ($subtotal <= 0) {
                return $this->json_response(['error' => 'No se pudo cerrar con precios. Revisa lista de precios o productos.'], 422);
            }

            # SE ACTUALIZA ENCABEZADO
            $quote->party_id = $party_id;
            $quote->status = 'requested';
            $quote->currency_code = $currency;
            $quote->subtotal = round($subtotal, 2);
            $quote->total = round($subtotal, 2);
            $quote->internal_notes = trim((string) \Arr::get($val, 'internal_notes', $quote->internal_notes));
            $quote->save();

            if (class_exists('Helper_Core_Audit')) {
                Helper_Core_Audit::log([
                    'module' => 'sales',
                    'action' => 'close_prequote',
                    'business_event' => 'sales.prequote_closed',
                    'entity_type' => 'sales_quote',
                    'entity_id' => (int) $quote->id,
                    'table_name' => 'core_sales_quotes',
                    'record_pk' => (int) $quote->id,
                    'description' => 'Precotizacion cerrada con precios',
                    'new_values' => ['party_id' => $party_id, 'status' => 'requested', 'total' => $quote->total],
                    'severity' => 'info',
                ]);
            }

            return $this->json_response(['status' => 'ok', 'quotes' => $this->quotes(), 'orders' => $this->orders(), 'deliveries' => $this->deliveries(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error cerrando precotizacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cerrar la precotizacion.'], 400);
        }
    }

    /**
     * CLOSE PREQUOTE ACTION
     *
     * COMPATIBILIDAD DE RUTA PARA PETICIONES AJAX POST/JSON.
     *
     * @access  public
     * @return  Response
     */
    public function action_close_prequote()
    {
        if (\Input::method() !== 'POST') {
            return $this->json_response(['error' => 'Metodo no permitido.'], 405);
        }

        # SE REUTILIZA EL FLUJO POST PARA MANTENER UNA SOLA LOGICA
        return $this->post_close_prequote();
    }

    /**
     * CREATE ORDER FROM QUOTE
     *
     * CREA UN PEDIDO DESDE UNA COTIZACION APROBADA.
     *
     * @access  public
     * @return  Response
     */
    public function post_create_order_from_quote()
    {
        $this->require_access('sales.access[edit]');

        try {
            $quote = Model_Core_Sales_Quote::find((int) \Arr::get((array) \Input::json(), 'id', 0));
            if (!$quote) {
                return $this->json_response(['error' => 'Cotizacion no encontrada.'], 404);
            }
            if ((int) $quote->party_id < 1 || (float) $quote->total <= 0) {
                return $this->json_response(['error' => 'La cotizacion debe tener cliente y total para pasar a pedido.'], 422);
            }
            $order = $this->create_order_for_quote($quote);

            return $this->json_response(['status' => 'ok', 'folio' => $order->folio, 'quotes' => $this->quotes(), 'orders' => $this->orders(), 'deliveries' => $this->deliveries(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error creando pedido desde cotizacion: '.$e->getMessage().' | payload='.json_encode((array) \Input::json()).' | trace='.$e->getTraceAsString());
            return $this->json_response(['error' => 'No se pudo crear el pedido: '.$e->getMessage()], 400);
        }
    }

    /**
     * CREATE ORDER FROM QUOTE ACTION
     *
     * COMPATIBILIDAD DE RUTA PARA PETICIONES AJAX POST/JSON.
     *
     * @access  public
     * @return  Response
     */
    public function action_create_order_from_quote()
    {
        if (\Input::method() !== 'POST') {
            return $this->json_response(['error' => 'Metodo no permitido.'], 405);
        }

        # SE REUTILIZA EL FLUJO POST PARA MANTENER UNA SOLA LOGICA
        return $this->post_create_order_from_quote();
    }

    /**
     * CREATE DELIVERY FROM ORDER
     *
     * CREA ENTREGA DESDE UN PEDIDO Y AFECTA INVENTARIO.
     *
     * @access  public
     * @return  Response
     */
    public function post_create_delivery_from_order()
    {
        $this->require_access('sales.access[edit]');
        $transaction_started = false;

        try {
            $payload = (array) \Input::json();
            $order = Model_Core_Sales_Order::find((int) \Arr::get($payload, 'id', 0));
            if (!$order) {
                return $this->json_response(['error' => 'Pedido no encontrado.'], 404);
            }
            \Log::info('Ventas: inicio de surtido pedido '.$order->folio.' id='.(int) $order->id.' payload='.json_encode($payload));
            if (in_array($order->status, ['closed', 'delivered', 'billed'], true)) {
                return $this->json_response(['error' => 'El pedido ya no tiene pendientes por surtir.'], 422);
            }
            $warehouse_id = (int) \Arr::get($payload, 'warehouse_id', 0);
            if ($warehouse_id < 1) {
                $warehouse_id = $this->default_warehouse_id();
            }
            $requested_items = [];
            foreach ((array) \Arr::get($payload, 'items', []) as $item) {
                $requested_items[(int) \Arr::get((array) $item, 'order_item_id', 0)] = max(0, (float) \Arr::get((array) $item, 'quantity', 0));
            }
            $allow_negative = $this->allow_negative_inventory_sales();
            if (!$allow_negative) {
                $this->validate_delivery_stock($order, $warehouse_id, $requested_items);
            }

            \DB::start_transaction();
            $transaction_started = true;

            $delivery = Model_Core_Sales_Delivery::forge([
                'folio' => $this->next_flow_folio('ENT', 'core_sales_deliveries'),
                'order_id' => (int) $order->id,
                'party_id' => (int) $order->party_id,
                'warehouse_id' => $warehouse_id,
                'status' => 'delivered',
                'delivery_date' => date('Y-m-d'),
                'currency_code' => (string) $order->currency_code,
                'total' => 0,
                'billing_invoice_id' => 0,
                'notes' => 'Entrega creada desde pedido '.$order->folio,
                'created_by' => $this->current_user_id(),
                'active' => 1,
            ]);
            $delivery->save();

            $delivery_total = 0;
            $delivered_any = false;
            foreach (\DB::select()->from('core_sales_order_items')->where('order_id', '=', (int) $order->id)->order_by('sort_order', 'asc')->execute() as $row) {
                $pending = max(0, (float) $row['quantity'] - (float) $row['delivered_quantity']);
                $requested = array_key_exists((int) $row['id'], $requested_items) ? $requested_items[(int) $row['id']] : $pending;
                $quantity = min($pending, $requested);
                if ($pending <= 0 || $quantity <= 0) {
                    continue;
                }
                $line_total = round($quantity * (float) $row['unit_price'], 2);
                Model_Core_Sales_Delivery_Item::forge([
                    'delivery_id' => (int) $delivery->id,
                    'order_item_id' => (int) $row['id'],
                    'product_id' => (int) $row['product_id'],
                    'sku' => (string) $row['sku'],
                    'name' => (string) $row['name'],
                    'quantity' => $quantity,
                    'unit_price' => (float) $row['unit_price'],
                    'line_total' => $line_total,
                    'sort_order' => (int) $row['sort_order'],
                ])->save();
                \DB::update('core_sales_order_items')->set(['delivered_quantity' => (float) $row['delivered_quantity'] + $quantity, 'updated_at' => time()])->where('id', '=', (int) $row['id'])->execute();
                $this->inventory_out((int) $row['product_id'], $warehouse_id, $quantity, 'sales_delivery', (int) $delivery->id, 'Salida por entrega '.$delivery->folio);
                $delivery_total += $line_total;
                $delivered_any = true;
            }

            if (!$delivered_any) {
                \DB::rollback_transaction();
                $transaction_started = false;
                return $this->json_response(['error' => 'Captura al menos una cantidad a surtir.'], 422);
            }

            $delivery->total = round($delivery_total, 2);
            $delivery->save();

            $remaining = $this->order_pending_quantity((int) $order->id);
            $order->status = $remaining > 0 ? 'partial' : 'delivered';
            $order->delivered_total = round($this->order_delivered_total((int) $order->id), 2);
            $order->save();
            \Log::info('Ventas: entrega '.$delivery->folio.' creada desde pedido '.$order->folio.' total='.$delivery->total.' restante='.$remaining);
            $this->audit_flow('create_delivery_from_order', 'Entrega '.$delivery->folio.' creada desde pedido '.$order->folio, 'sales_delivery', (int) $delivery->id, $delivery->to_array());

            \DB::commit_transaction();
            $transaction_started = false;

            return $this->json_response(['status' => 'ok', 'folio' => $delivery->folio, 'quotes' => $this->quotes(), 'orders' => $this->orders(), 'deliveries' => $this->deliveries(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            if ($transaction_started) {
                \DB::rollback_transaction();
            }
            \Log::error('Error creando entrega desde pedido: '.$e->getMessage().' | payload='.json_encode((array) \Input::json()));
            return $this->json_response(['error' => 'No se pudo crear la entrega: '.$e->getMessage()], 400);
        }
    }

    /**
     * CREATE DELIVERY FROM ORDER ACTION
     *
     * COMPATIBILIDAD DE RUTA PARA PETICIONES AJAX POST/JSON.
     *
     * @access  public
     * @return  Response
     */
    public function action_create_delivery_from_order()
    {
        if (\Input::method() !== 'POST') {
            return $this->json_response(['error' => 'Metodo no permitido.'], 405);
        }

        # SE REUTILIZA EL FLUJO POST PARA MANTENER UNA SOLA LOGICA
        return $this->post_create_delivery_from_order();
    }

    /**
     * QUOTES
     *
     * FORMATEA COTIZACIONES RECIENTES.
     *
     * @access  protected
     * @return  Array
     */
    protected function quotes()
    {
        # SE CONSULTAN COTIZACIONES CON TERCERO
        $rows = \DB::select(
                array('q.id', 'id'),
                array('q.folio', 'folio'),
                array('q.source', 'source'),
                array('q.offline_uuid', 'offline_uuid'),
                array('q.synced_from_offline', 'synced_from_offline'),
                array('q.status', 'status'),
                array('q.currency_code', 'currency_code'),
                array('q.subtotal', 'subtotal'),
                array('q.discount_total', 'discount_total'),
                array('q.tax_total', 'tax_total'),
                array('q.total', 'total'),
                array('q.party_id', 'party_id'),
                array('q.customer_notes', 'customer_notes'),
                array('q.internal_notes', 'internal_notes'),
                array('q.expires_at', 'expires_at'),
                array('q.created_at', 'created_at'),
                array('p.name', 'party_name'),
                array('p.email', 'party_email'),
                array('p.phone', 'party_phone'),
                array('p.rfc', 'party_rfc')
            )
            ->from(array('core_sales_quotes', 'q'))
            ->join(array('core_parties', 'p'), 'left')
                ->on('q.party_id', '=', 'p.id');
        $this->apply_party_scope($rows, 'p', 'sales');

        $rows = $rows
            ->order_by('q.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['items'] = $this->quote_items((int) $row['id']);
            $row['orders'] = $this->quote_orders((int) $row['id']);
            $row['created_label'] = !empty($row['created_at']) ? date('Y-m-d H:i', (int) $row['created_at']) : '';
            $row['expires_label'] = !empty($row['expires_at']) ? date('Y-m-d', (int) $row['expires_at']) : '';
        }

        return $rows;
    }

    protected function quote_orders($quote_id)
    {
        $orders = [];
        if (!\DBUtil::table_exists('core_sales_orders')) {
            return $orders;
        }
        foreach (\DB::select('id', 'folio', 'status', 'total')->from('core_sales_orders')->where('source_quote_id', '=', (int) $quote_id)->where('active', '=', 1)->execute() as $order) {
            $order['items'] = $this->order_items((int) $order['id']);
            $order['deliveries'] = $this->order_deliveries((int) $order['id']);
            $orders[] = $order;
        }
        return $orders;
    }

    protected function order_deliveries($order_id)
    {
        $deliveries = [];
        foreach (\DB::select('id', 'folio', 'status', 'billing_invoice_id', 'total')->from('core_sales_deliveries')->where('order_id', '=', (int) $order_id)->where('active', '=', 1)->execute() as $delivery) {
            $deliveries[] = $delivery;
        }
        return $deliveries;
    }

    protected function order_items($order_id)
    {
        $items = [];
        foreach (\DB::select(
                ['i.id', 'id'],
                ['i.product_id', 'product_id'],
                ['i.sku', 'sku'],
                ['i.name', 'name'],
                ['i.currency_code', 'currency_code'],
                ['i.unit_price', 'unit_price'],
                ['i.quantity', 'quantity'],
                ['i.delivered_quantity', 'delivered_quantity'],
                ['i.billed_quantity', 'billed_quantity'],
                ['p.main_image_path', 'image_path'],
                ['p.stock_quantity', 'stock_quantity'],
                ['p.stock_reserved', 'stock_reserved']
            )
            ->from(['core_sales_order_items', 'i'])
            ->join(['core_commerce_products', 'p'], 'left')->on('i.product_id', '=', 'p.id')
            ->where('i.order_id', '=', (int) $order_id)
            ->order_by('i.sort_order', 'asc')
            ->order_by('i.id', 'asc')
            ->execute() as $row) {
            $row['pending_quantity'] = max(0, (float) $row['quantity'] - (float) $row['delivered_quantity']);
            $row['available_stock'] = max(0, (float) $row['stock_quantity'] - (float) $row['stock_reserved']);
            $row['image_url'] = $this->media_url((string) $row['image_path']);
            $items[] = $row;
        }
        return $items;
    }

    /**
     * QUOTE ITEMS
     *
     * OBTIENE RENGLONES DE UNA COTIZACION.
     *
     * @access  protected
     * @return  Array
     */
    protected function quote_items($quote_id)
    {
        # SE CONSULTAN RENGLONES
        $rows = \DB::select(
                ['i.product_id', 'product_id'],
                ['i.sku', 'sku'],
                ['i.name', 'name'],
                ['i.quantity', 'quantity'],
                ['i.unit_price', 'unit_price'],
                ['i.line_total', 'line_total'],
                ['p.main_image_path', 'image_path'],
                ['p.stock_quantity', 'stock_quantity'],
                ['p.stock_reserved', 'stock_reserved']
            )
            ->from(['core_sales_quote_items', 'i'])
            ->join(['core_commerce_products', 'p'], 'left')->on('i.product_id', '=', 'p.id')
            ->where('i.quote_id', '=', (int) $quote_id)
            ->order_by('i.sort_order', 'asc')
            ->order_by('i.id', 'asc')
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['image_url'] = $this->media_url((string) $row['image_path']);
            $row['available_stock'] = max(0, (float) $row['stock_quantity'] - (float) $row['stock_reserved']);
        }
        unset($row);
        return $rows;
    }

    /**
     * STATS
     *
     * OBTIENE CONTADORES BASICOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function stats()
    {
        # SE REGRESAN CONTADORES GENERALES
        return [
            'quotes' => (int) \DB::count_records('core_sales_quotes'),
            'orders' => (int) \DB::count_records('core_sales_orders'),
            'deliveries' => (int) \DB::count_records('core_sales_deliveries'),
            'prequote' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'prequote')->execute()->count(),
            'requested' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'requested')->execute()->count(),
            'approved' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'approved')->execute()->count(),
            'rejected' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'rejected')->execute()->count(),
        ];
    }

    protected function orders()
    {
        $query = \DB::select(['o.id', 'id'], ['o.folio', 'folio'], ['o.status', 'status'], ['o.order_date', 'order_date'], ['o.currency_code', 'currency_code'], ['o.total', 'total'], ['o.delivered_total', 'delivered_total'], ['o.billed_total', 'billed_total'], ['q.folio', 'quote_folio'], ['p.name', 'party_name'])
            ->from(['core_sales_orders', 'o'])
            ->join(['core_sales_quotes', 'q'], 'left')->on('o.source_quote_id', '=', 'q.id')
            ->join(['core_parties', 'p'], 'left')->on('o.party_id', '=', 'p.id')
            ->where('o.active', '=', 1);
        $this->apply_party_scope($query, 'p', 'sales');

        $rows = $query
            ->order_by('o.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['items'] = $this->order_items((int) $row['id']);
            $pending = 0;
            foreach ($row['items'] as $item) {
                $pending += (float) $item['pending_quantity'];
            }
            $row['pending_quantity'] = $pending;
            $row['backorder'] = $pending > 0 && (float) $row['delivered_total'] > 0 ? 1 : 0;
        }
        unset($row);

        return $rows;
    }

    protected function deliveries()
    {
        $query = \DB::select(['d.id', 'id'], ['d.folio', 'folio'], ['d.status', 'status'], ['d.delivery_date', 'delivery_date'], ['d.currency_code', 'currency_code'], ['d.total', 'total'], ['d.billing_invoice_id', 'billing_invoice_id'], ['o.folio', 'order_folio'], ['p.name', 'party_name'], ['w.name', 'warehouse_name'])
            ->from(['core_sales_deliveries', 'd'])
            ->join(['core_sales_orders', 'o'], 'left')->on('d.order_id', '=', 'o.id')
            ->join(['core_parties', 'p'], 'left')->on('d.party_id', '=', 'p.id')
            ->join(['core_inventory_warehouses', 'w'], 'left')->on('d.warehouse_id', '=', 'w.id')
            ->where('d.active', '=', 1);
        $this->apply_party_scope($query, 'p', 'sales');

        return $query
            ->order_by('d.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    /**
     * OPTIONS
     *
     * OPCIONES PARA CREAR COTIZACION MANUAL.
     *
     * @access  protected
     * @return  Array
     */
    protected function options()
    {
        # SE ENTREGAN CLIENTES Y PRODUCTOS ACTIVOS
        return [
            'customers' => $this->select_rows('core_parties', 'id', 'name', ['party_type' => 'customer']),
            'products' => $this->product_options(['limit' => 60]),
            'brands' => $this->select_rows('core_commerce_brands', 'id', 'name'),
            'categories' => $this->select_rows('core_commerce_categories', 'id', 'name'),
            'warehouses' => $this->select_rows('core_inventory_warehouses', 'id', 'name'),
        ];
    }

    /**
     * PRODUCT OPTIONS
     *
     * PRODUCTOS PUBLICADOS PARA COTIZACION.
     *
     * @access  protected
     * @return  Array
     */
    protected function product_options(array $filters = [])
    {
        # SE LISTAN PRODUCTOS ACTIVOS
        $items = [];
        $limit = min(120, max(10, (int) \Arr::get($filters, 'limit', 60)));
        $query = \DB::select(
                ['p.id', 'id'],
                ['p.sku', 'sku'],
                ['p.name', 'name'],
                ['p.currency_code', 'currency_code'],
                ['p.price', 'price'],
                ['p.main_image_path', 'main_image_path'],
                ['p.brand_id', 'brand_id'],
                ['p.category_id', 'category_id'],
                ['p.stock_quantity', 'stock_quantity'],
                ['p.stock_reserved', 'stock_reserved'],
                ['b.name', 'brand_name'],
                ['c.name', 'category_name']
            )
            ->from(['core_commerce_products', 'p'])
            ->join(['core_commerce_brands', 'b'], 'left')->on('p.brand_id', '=', 'b.id')
            ->join(['core_commerce_categories', 'c'], 'left')->on('p.category_id', '=', 'c.id')
            ->where('p.active', '=', 1)
            ->where('p.published', '=', 1)
            ->order_by('p.name', 'asc')
            ->limit($limit);

        $q = trim((string) \Arr::get($filters, 'q', ''));
        if ($q !== '') {
            $query->and_where_open()
                ->where('p.name', 'like', '%'.$q.'%')
                ->or_where('p.sku', 'like', '%'.$q.'%')
                ->or_where('b.name', 'like', '%'.$q.'%')
                ->or_where('c.name', 'like', '%'.$q.'%')
                ->and_where_close();
        }
        if ((int) \Arr::get($filters, 'brand_id', 0) > 0) {
            $query->where('p.brand_id', '=', (int) \Arr::get($filters, 'brand_id', 0));
        }
        if ((int) \Arr::get($filters, 'category_id', 0) > 0) {
            $query->where('p.category_id', '=', (int) \Arr::get($filters, 'category_id', 0));
        }
        if (\Arr::get($filters, 'stock', '') === 'available') {
            $query->where(\DB::expr('(p.stock_quantity - p.stock_reserved)'), '>', 0);
        } elseif (\Arr::get($filters, 'stock', '') === 'zero') {
            $query->where(\DB::expr('(p.stock_quantity - p.stock_reserved)'), '<=', 0);
        }

        $rows = $query->execute();

        foreach ($rows as $row) {
            $items[] = [
                'value' => (int) $row['id'],
                'sku' => (string) $row['sku'],
                'label' => trim($row['name'].' '.($row['sku'] ? '('.$row['sku'].')' : '')),
                'currency_code' => (string) $row['currency_code'],
                'price' => (float) $row['price'],
                'brand_id' => (int) $row['brand_id'],
                'brand_name' => (string) $row['brand_name'],
                'category_id' => (int) $row['category_id'],
                'category_name' => (string) $row['category_name'],
                'stock_quantity' => (float) $row['stock_quantity'],
                'stock_reserved' => (float) $row['stock_reserved'],
                'available_stock' => max(0, (float) $row['stock_quantity'] - (float) $row['stock_reserved']),
                'image_url' => $this->media_url((string) $row['main_image_path']),
                'price_ranges' => $this->product_price_ranges((int) $row['id']),
            ];
        }

        return $items;
    }

    protected function select_rows($table, $value_field, $label_field, array $where = [])
    {
        $items = [];
        $query = \DB::select($value_field, $label_field)->from($table)->where('active', '=', 1);
        foreach ($where as $field => $value) {
            $query->where($field, '=', $value);
        }
        if ($table === 'core_parties') {
            $this->apply_party_scope($query, $table, 'sales');
        }
        foreach ($query->order_by($label_field, 'asc')->execute() as $row) {
            $items[] = ['value' => (int) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $items;
    }

    protected function product_row($product_id)
    {
        $row = \DB::select('id', 'sku', 'name', 'currency_code', 'price', 'main_image_path')
            ->from('core_commerce_products')
            ->where('id', '=', (int) $product_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return $row ?: null;
    }

    protected function product_price(array $product, $party_id, $quantity = 1)
    {
        $price = [
            'price' => (float) $product['price'],
            'currency_code' => (string) $product['currency_code'],
        ];

        $price_list_id = $this->customer_price_list_id($party_id);
        if ($price_list_id < 1 || !\DBUtil::table_exists('core_commerce_product_prices')) {
            return $price;
        }

        $today = date('Y-m-d');
        $rows = \DB::select('price', 'currency_code', 'valid_from', 'valid_until')
            ->from('core_commerce_product_prices')
            ->where('product_id', '=', (int) $product['id'])
            ->where('price_list_id', '=', $price_list_id)
            ->where('active', '=', 1)
            ->where('min_quantity', '<=', (float) $quantity)
            ->order_by('min_quantity', 'desc')
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            if (!empty($row['valid_from']) && $row['valid_from'] > $today) {
                continue;
            }
            if (!empty($row['valid_until']) && $row['valid_until'] < $today) {
                continue;
            }
            return [
                'price' => (float) $row['price'],
                'currency_code' => (string) $row['currency_code'],
            ];
        }

        return $price;
    }

    protected function customer_price_list_id($party_id)
    {
        if ((int) $party_id < 1) {
            return 0;
        }
        if (\DBUtil::field_exists('core_parties', ['price_list_id'])) {
            $party = \DB::select('price_list_id')->from('core_parties')->where('id', '=', (int) $party_id)->execute()->current();
            if ($party && (int) $party['price_list_id'] > 0) {
                return (int) $party['price_list_id'];
            }
        }
        if (\DBUtil::table_exists('core_commerce_customer_price_lists')) {
            $list = \DB::select('price_list_id')->from('core_commerce_customer_price_lists')->where('customer_id', '=', (int) $party_id)->execute()->current();
            return $list ? (int) $list['price_list_id'] : 0;
        }
        return 0;
    }

    protected function product_price_ranges($product_id)
    {
        if (!\DBUtil::table_exists('core_commerce_product_prices')) {
            return [];
        }
        return \DB::select('price_list_id', 'currency_code', 'price', 'min_quantity', 'max_quantity')
            ->from('core_commerce_product_prices')
            ->where('product_id', '=', (int) $product_id)
            ->where('active', '=', 1)
            ->order_by('min_quantity', 'asc')
            ->limit(8)
            ->execute()
            ->as_array();
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

    protected function next_flow_folio($prefix, $table)
    {
        $prefix = strtoupper($prefix).'-'.date('Ymd').'-';
        $row = \DB::select(\DB::expr('COUNT(*) as total'))
            ->from($table)
            ->where('folio', 'like', $prefix.'%')
            ->execute()
            ->current();
        return $prefix.str_pad(((int) $row['total']) + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function create_order_for_quote(Model_Core_Sales_Quote $quote)
    {
        $existing = \DB::select('id')->from('core_sales_orders')->where('source_quote_id', '=', (int) $quote->id)->where('active', '=', 1)->execute()->current();
        if ($existing) {
            \Log::info('Ventas: cotizacion '.$quote->folio.' ya tenia pedido id='.(int) $existing['id']);
            return Model_Core_Sales_Order::find((int) $existing['id']);
        }

        if ((int) $quote->party_id < 1 || (float) $quote->total <= 0) {
            throw new \RuntimeException('La cotizacion debe tener cliente y total para pasar a pedido.');
        }
        \Log::info('Ventas: creando pedido desde cotizacion '.$quote->folio.' id='.(int) $quote->id.' total='.(float) $quote->total);

        $order = Model_Core_Sales_Order::forge([
            'folio' => $this->next_flow_folio('PED', 'core_sales_orders'),
            'source_quote_id' => (int) $quote->id,
            'party_id' => (int) $quote->party_id,
            'status' => 'open',
            'order_date' => date('Y-m-d'),
            'currency_code' => (string) $quote->currency_code,
            'subtotal' => (float) $quote->subtotal,
            'discount_total' => (float) $quote->discount_total,
            'tax_total' => (float) $quote->tax_total,
            'total' => (float) $quote->total,
            'delivered_total' => 0,
            'billed_total' => 0,
            'notes' => 'Pedido creado desde cotizacion '.$quote->folio,
            'created_by' => $this->current_user_id(),
            'active' => 1,
        ]);
        $order->save();

        $item_count = 0;
        foreach (\DB::select()->from('core_sales_quote_items')->where('quote_id', '=', (int) $quote->id)->order_by('sort_order', 'asc')->execute() as $row) {
            Model_Core_Sales_Order_Item::forge([
                'order_id' => (int) $order->id,
                'quote_item_id' => (int) $row['id'],
                'product_id' => (int) $row['product_id'],
                'sku' => (string) $row['sku'],
                'name' => (string) $row['name'],
                'currency_code' => (string) $row['currency_code'],
                'unit_price' => (float) $row['unit_price'],
                'quantity' => (float) $row['quantity'],
                'delivered_quantity' => 0,
                'billed_quantity' => 0,
                'line_total' => (float) $row['line_total'],
                'sort_order' => (int) $row['sort_order'],
            ])->save();
            $item_count++;
        }

        if ($item_count < 1) {
            $order->delete();
            throw new \RuntimeException('La cotizacion no tiene partidas para crear pedido.');
        }

        $quote->status = 'approved';
        $quote->save();
        \Log::info('Ventas: pedido '.$order->folio.' creado desde cotizacion '.$quote->folio.' partidas='.$item_count);
        $this->audit_flow('create_order_from_quote', 'Pedido '.$order->folio.' creado desde cotizacion '.$quote->folio, 'sales_order', (int) $order->id, $order->to_array());

        return $order;
    }

    protected function sync_approved_quotes_to_orders()
    {
        $rows = \DB::select('id')
            ->from('core_sales_quotes')
            ->where('status', '=', 'approved')
            ->where('party_id', '>', 0)
            ->where('total', '>', 0)
            ->limit(50)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $existing = \DB::select('id')
                ->from('core_sales_orders')
                ->where('source_quote_id', '=', (int) $row['id'])
                ->where('active', '=', 1)
                ->execute()
                ->current();
            if ($existing) {
                continue;
            }
            $quote = Model_Core_Sales_Quote::find((int) $row['id']);
            if ($quote) {
                try {
                    \Log::info('Ventas: sincronizando cotizacion aprobada sin pedido id='.(int) $quote->id.' folio='.$quote->folio);
                    $this->create_order_for_quote($quote);
                } catch (\Exception $e) {
                    \Log::error('Ventas: no se pudo sincronizar cotizacion aprobada id='.(int) $quote->id.' folio='.$quote->folio.' error='.$e->getMessage());
                }
            }
        }
    }

    protected function order_pending_quantity($order_id)
    {
        $pending = 0;
        foreach (\DB::select('quantity', 'delivered_quantity')->from('core_sales_order_items')->where('order_id', '=', (int) $order_id)->execute() as $row) {
            $pending += max(0, (float) $row['quantity'] - (float) $row['delivered_quantity']);
        }
        return $pending;
    }

    protected function order_delivered_total($order_id)
    {
        $total = 0;
        foreach (\DB::select('delivered_quantity', 'unit_price')->from('core_sales_order_items')->where('order_id', '=', (int) $order_id)->execute() as $row) {
            $total += (float) $row['delivered_quantity'] * (float) $row['unit_price'];
        }
        return $total;
    }

    protected function default_warehouse_id()
    {
        $row = \DB::select('id')->from('core_inventory_warehouses')->where('is_default', '=', 1)->where('active', '=', 1)->execute()->current();
        if ($row) {
            return (int) $row['id'];
        }
        $row = \DB::select('id')->from('core_inventory_warehouses')->where('active', '=', 1)->order_by('id', 'asc')->execute()->current();
        if ($row) {
            return (int) $row['id'];
        }
        $warehouse = Model_Core_Inventory_Warehouse::forge([
            'code' => 'GENERAL',
            'name' => 'Almacen general',
            'is_default' => 1,
            'active' => 1,
        ]);
        $warehouse->save();
        return (int) $warehouse->id;
    }

    protected function inventory_out($product_id, $warehouse_id, $quantity, $entity_type, $entity_id, $notes)
    {
        if ($product_id < 1 || $quantity <= 0) {
            return;
        }
        Model_Core_Inventory_Movement::forge([
            'warehouse_id' => (int) $warehouse_id,
            'product_id' => (int) $product_id,
            'movement_type' => 'delivery_out',
            'quantity' => -abs((float) $quantity),
            'unit_cost' => $this->inventory_unit_cost((int) $product_id),
            'related_module' => 'sales',
            'related_entity_type' => $entity_type,
            'related_entity_id' => (int) $entity_id,
            'notes' => $notes,
            'created_by' => $this->current_user_id(),
        ])->save();

        if (\DBUtil::table_exists('core_inventory_stock_balances')) {
            $this->adjust_inventory_balance((int) $warehouse_id, (int) $product_id, -abs((float) $quantity));
            $this->refresh_product_stock_from_balances((int) $product_id);
        } else {
            $stock_expr = $this->allow_negative_inventory_sales()
                ? \DB::expr('stock_quantity - '.(float) abs($quantity))
                : \DB::expr('GREATEST(0, stock_quantity - '.(float) abs($quantity).')');
            \DB::update('core_commerce_products')
                ->value('stock_quantity', $stock_expr)
                ->value('stock_updated_at', time())
                ->where('id', '=', (int) $product_id)
            ->execute();
        }
    }

    protected function inventory_unit_cost($product_id)
    {
        $row = \DB::select('sku', 'name', 'cost')
            ->from('core_commerce_products')
            ->where('id', '=', (int) $product_id)
            ->execute()
            ->current();

        if (!$row) {
            \Log::warning('Ventas: no se encontro producto para costo inventario product_id='.(int) $product_id);
            return 0;
        }

        $cost = (float) $row['cost'];
        if ($cost <= 0) {
            \Log::warning('Ventas: producto sin costo para salida inventario product_id='.(int) $product_id.' sku='.(string) $row['sku'].' nombre='.(string) $row['name'].'. El responsable de catalogo/compras debe capturar costo en Comercial > Productos.');
            return 0;
        }

        return $cost;
    }

    protected function adjust_inventory_balance($warehouse_id, $product_id, $quantity)
    {
        $now = time();
        $allow_negative = $this->allow_negative_inventory_sales();
        $row = \DB::select('id')
            ->from('core_inventory_stock_balances')
            ->where('warehouse_id', '=', (int) $warehouse_id)
            ->where('product_id', '=', (int) $product_id)
            ->execute()
            ->current();

        if ($row) {
            $expression = $allow_negative
                ? \DB::expr('quantity_on_hand + '.(float) $quantity)
                : \DB::expr('GREATEST(0, quantity_on_hand + '.(float) $quantity.')');
            \DB::update('core_inventory_stock_balances')
                ->set([
                    'quantity_on_hand' => $expression,
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
            'quantity_on_hand' => $allow_negative ? (float) $quantity : max(0, (float) $quantity),
            'quantity_reserved' => 0,
            'last_movement_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
    }

    protected function refresh_product_stock_from_balances($product_id)
    {
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

    protected function warehouse_available_quantity($warehouse_id, $product_id)
    {
        if (!\DBUtil::table_exists('core_inventory_stock_balances')) {
            $row = \DB::select('stock_quantity', 'stock_reserved')->from('core_commerce_products')->where('id', '=', (int) $product_id)->execute()->current();
            return $row ? max(0, (float) $row['stock_quantity'] - (float) $row['stock_reserved']) : 0;
        }

        $row = \DB::select('quantity_on_hand', 'quantity_reserved')
            ->from('core_inventory_stock_balances')
            ->where('warehouse_id', '=', (int) $warehouse_id)
            ->where('product_id', '=', (int) $product_id)
            ->execute()
            ->current();

        return $row ? ((float) $row['quantity_on_hand'] - (float) $row['quantity_reserved']) : 0;
    }

    protected function validate_delivery_stock(Model_Core_Sales_Order $order, $warehouse_id, array $requested_items)
    {
        $needed_by_product = [];
        $labels = [];
        foreach (\DB::select()->from('core_sales_order_items')->where('order_id', '=', (int) $order->id)->order_by('sort_order', 'asc')->execute() as $row) {
            $pending = max(0, (float) $row['quantity'] - (float) $row['delivered_quantity']);
            $requested = array_key_exists((int) $row['id'], $requested_items) ? $requested_items[(int) $row['id']] : $pending;
            $quantity = min($pending, $requested);
            if ($quantity <= 0) {
                continue;
            }
            $product_id = (int) $row['product_id'];
            if (!isset($needed_by_product[$product_id])) {
                $needed_by_product[$product_id] = 0;
                $labels[$product_id] = trim((string) $row['sku'].' - '.$row['name'], ' -');
            }
            $needed_by_product[$product_id] += $quantity;
        }

        foreach ($needed_by_product as $product_id => $quantity) {
            $available = $this->warehouse_available_quantity($warehouse_id, $product_id);
            if ($available < $quantity) {
                \Log::warning('Ventas: stock insuficiente almacen='.(int) $warehouse_id.' producto='.$product_id.' requerido='.$quantity.' disponible='.$available);
                throw new \RuntimeException('No hay existencia suficiente en el almacen seleccionado para '.$labels[$product_id].'. Disponible: '.$available.', requerido: '.$quantity.'.');
            }
        }
    }

    protected function allow_negative_inventory_sales()
    {
        if (!\DBUtil::table_exists('core_settings')) {
            return false;
        }
        $row = \DB::select('value')
            ->from('core_settings')
            ->where('setting_group', '=', 'operations')
            ->where('setting_key', '=', 'allow_negative_inventory_sales')
            ->execute()
            ->current();
        return $row && (int) $row['value'] === 1;
    }

    protected function audit_flow($action, $summary, $entity_type, $entity_id, array $values)
    {
        if (!class_exists('Helper_Core_Audit')) {
            return;
        }
        Helper_Core_Audit::log([
            'module' => 'sales',
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => (int) $entity_id,
            'summary' => $summary,
            'new_values' => $values,
        ]);
    }

    protected function current_user_id()
    {
        $user_id_data = \Auth::get_user_id();
        return isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA TABLAS REQUERIDAS.
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA
        foreach (['core_sales_quotes', 'core_sales_quote_items'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de ventas.');
            }
        }
        foreach (['core_sales_orders', 'core_sales_order_items', 'core_sales_deliveries', 'core_sales_delivery_items', 'core_inventory_warehouses', 'core_inventory_movements'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de pedido, entrega e inventario.');
            }
        }
        if (!\DBUtil::field_exists('core_sales_quotes', ['offline_uuid'])) {
            throw new \RuntimeException('Falta ejecutar migraciones offline.');
        }
        if (!\DBUtil::field_exists('core_commerce_products', ['stock_quantity', 'stock_reserved'])) {
            throw new \RuntimeException('Falta ejecutar migraciones de existencias comerciales.');
        }
    }

    protected function offline_uuid($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/[^a-zA-Z0-9_\-]/', '', $value);
        return substr($value, 0, 64);
    }

    protected function log_offline_sync($offline_uuid, $entity_type, $entity_id, array $payload)
    {
        if ($offline_uuid === '' || !\DBUtil::table_exists('core_offline_sync_logs')) {
            return;
        }

        \DB::insert('core_offline_sync_logs')->set([
            'offline_uuid' => $offline_uuid,
            'module' => 'sales',
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'status' => 'synced',
            'device_label' => trim((string) \Arr::get($payload, 'device_label', '')),
            'user_id' => $this->current_user_id(),
            'payload_hash' => hash('sha256', json_encode($payload)),
            'message' => 'Cotizacion sincronizada desde borrador offline.',
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}
