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

            return $this->json_response([
                'quotes' => $this->quotes(),
                'stats' => $this->stats(),
                'options' => $this->options(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando ventas: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar ventas.'], 500);
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
                'status' => $prequote ? 'prequote' : 'reviewed',
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

            return $this->json_response(['status' => 'ok', 'offline_uuid' => $offline_uuid, 'folio' => $quote->folio, 'quotes' => $this->quotes(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error creando cotizacion manual: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear la cotizacion.'], 400);
        }
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
            $allowed = ['prequote', 'requested', 'reviewed', 'approved', 'rejected', 'converted'];
            if (!in_array($status, $allowed, true)) {
                return $this->json_response(['error' => 'Estado no valido.'], 422);
            }
            if ($quote->status === 'prequote' && in_array($status, ['reviewed', 'approved', 'converted'], true)) {
                return $this->json_response(['error' => 'Primero cierra la precotizacion con cliente y precios.'], 422);
            }

            # SE GUARDA CAMBIO
            $quote->status = $status;
            $quote->internal_notes = trim((string) \Arr::get($val, 'internal_notes', $quote->internal_notes));
            $quote->save();

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

            return $this->json_response(['status' => 'ok', 'quotes' => $this->quotes(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error actualizando cotizacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo actualizar la cotizacion.'], 400);
        }
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
            $quote->status = 'reviewed';
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
                    'new_values' => ['party_id' => $party_id, 'status' => 'reviewed', 'total' => $quote->total],
                    'severity' => 'info',
                ]);
            }

            return $this->json_response(['status' => 'ok', 'quotes' => $this->quotes(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error cerrando precotizacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cerrar la precotizacion.'], 400);
        }
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
            $row['created_label'] = !empty($row['created_at']) ? date('Y-m-d H:i', (int) $row['created_at']) : '';
            $row['expires_label'] = !empty($row['expires_at']) ? date('Y-m-d', (int) $row['expires_at']) : '';
        }

        return $rows;
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
            'prequote' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'prequote')->execute()->count(),
            'requested' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'requested')->execute()->count(),
            'reviewed' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'reviewed')->execute()->count(),
            'approved' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'approved')->execute()->count(),
            'rejected' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'rejected')->execute()->count(),
        ];
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
            'products' => $this->product_options(),
            'brands' => $this->select_rows('core_commerce_brands', 'id', 'name'),
            'categories' => $this->select_rows('core_commerce_categories', 'id', 'name'),
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
    protected function product_options()
    {
        # SE LISTAN PRODUCTOS ACTIVOS
        $items = [];
        $rows = \DB::select(
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
            ->limit(500)
            ->execute();

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
