<?php

/**
 * HELPER CORE_CART
 *
 * Centraliza carrito publico, precios por cliente y totales.
 *
 * @package  app
 */
class Helper_Core_Cart
{
    /**
     * TOKEN
     *
     * OBTIENE O CREA TOKEN ANONIMO DEL CARRITO.
     *
     * @access  public
     * @return  String
     */
    public static function token($create = true)
    {
        # SE REUSA COOKIE DE CARRITO
        $token = \Cookie::get('core_cart_token');
        if (!$token && $create) {
            $token = sha1(uniqid(mt_rand(), true));
            \Cookie::set('core_cart_token', $token, 60 * 60 * 24 * 60);
        }

        return $token;
    }

    /**
     * CURRENT CART
     *
     * OBTIENE EL CARRITO ABIERTO ACTUAL O LO CREA SI SE SOLICITA.
     *
     * @access  public
     * @return  Model_Core_Cart_Cart|null
     */
    public static function current_cart($create = true)
    {
        # SE VALIDA QUE EXISTAN TABLAS
        if (!\DBUtil::table_exists('core_cart_carts')) {
            return null;
        }

        # SE RESUELVE IDENTIDAD ACTUAL
        $user_id = self::current_user_id();
        $party = self::customer_party();

        # PRIORIDAD: CARRITO DEL USUARIO/CLIENTE
        $cart = null;
        if ($user_id > 0) {
            $cart = Model_Core_Cart_Cart::query()
                ->where('user_id', $user_id)
                ->where('status', 'open')
                ->order_by('id', 'desc')
                ->get_one();
        }

        # SI NO HAY USUARIO, SE BUSCA POR TOKEN
        if (!$cart && ($create || self::token(false))) {
            $cart = Model_Core_Cart_Cart::query()
                ->where('token', self::token($create))
                ->where('status', 'open')
                ->order_by('id', 'desc')
                ->get_one();
        }

        if (!$cart && !$create) {
            return null;
        }

        # SE CREA CARRITO SI NO EXISTE
        if (!$cart) {
            $cart = Model_Core_Cart_Cart::forge([
                'token' => self::token(),
                'user_id' => $user_id,
                'party_id' => $party ? (int) $party->id : 0,
                'portal_code' => 'frontend',
                'status' => 'open',
                'currency_code' => 'MXN',
                'items_count' => 0,
                'subtotal' => 0,
                'total' => 0,
                'expires_at' => time() + (60 * 60 * 24 * 60),
                'converted_at' => 0,
            ]);
            $cart->save();
        }

        # SE VINCULA CARRITO ANONIMO SI EL CLIENTE YA INICIO SESION
        $changed = false;
        if ($user_id > 0 && (int) $cart->user_id < 1) {
            $cart->user_id = $user_id;
            $changed = true;
        }
        if ($party && (int) $cart->party_id < 1) {
            $cart->party_id = (int) $party->id;
            $changed = true;
        }
        if ($changed) {
            $cart->save();
        }

        return $cart;
    }

    /**
     * ADD PRODUCT
     *
     * AGREGA PRODUCTO PUBLICADO AL CARRITO.
     *
     * @access  public
     * @return  Model_Core_Cart_Cart
     */
    public static function add_product($product_id, $quantity = 1)
    {
        # SE NORMALIZA CANTIDAD
        $quantity = max(1, min(999, (float) $quantity));

        # SE BUSCA PRODUCTO PUBLICADO
        $product = self::product($product_id);
        if (!$product) {
            throw new \InvalidArgumentException('Producto no disponible.');
        }

        # SE CREA O ACTUALIZA RENGLON
        $cart = self::current_cart(true);
        $price = self::price_for_product($product);
        $item = Model_Core_Cart_Item::query()
            ->where('cart_id', (int) $cart->id)
            ->where('product_id', (int) $product['id'])
            ->get_one();

        if (!$item) {
            $item = Model_Core_Cart_Item::forge([
                'cart_id' => (int) $cart->id,
                'product_id' => (int) $product['id'],
                'sku' => (string) $product['sku'],
                'name' => (string) $product['name'],
                'currency_code' => (string) $price['currency_code'],
                'unit_price' => (float) $price['price'],
                'quantity' => 0,
                'line_total' => 0,
                'price_list_id' => (int) $price['price_list_id'],
            ]);
        }

        $item->currency_code = $price['currency_code'];
        $item->unit_price = $price['price'];
        $item->price_list_id = $price['price_list_id'];
        $item->quantity = (float) $item->quantity + $quantity;
        $item->line_total = round(((float) $item->unit_price) * ((float) $item->quantity), 2);
        $item->save();

        self::recalculate($cart);
        return $cart;
    }

    /**
     * ITEMS
     *
     * OBTIENE RENGLONES DEL CARRITO.
     *
     * @access  public
     * @return  Array
     */
    public static function items(Model_Core_Cart_Cart $cart = null)
    {
        # SE RESUELVE CARRITO
        $cart = $cart ?: self::current_cart(false);
        if (!$cart) {
            return [];
        }

        return Model_Core_Cart_Item::query()
            ->where('cart_id', (int) $cart->id)
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * UPDATE ITEM
     *
     * ACTUALIZA CANTIDAD DE UN RENGLON DEL CARRITO.
     *
     * @access  public
     * @return  Void
     */
    public static function update_item($item_id, $quantity)
    {
        # SE VALIDA PERTENENCIA AL CARRITO ACTUAL
        $cart = self::current_cart(false);
        $item = self::owned_item($cart, $item_id);

        if (!$item) {
            return;
        }

        $quantity = (float) $quantity;
        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->quantity = min(999, $quantity);
            $item->line_total = round(((float) $item->unit_price) * ((float) $item->quantity), 2);
            $item->save();
        }

        self::recalculate($cart);
    }

    /**
     * REMOVE ITEM
     *
     * QUITA UN RENGLON DEL CARRITO.
     *
     * @access  public
     * @return  Void
     */
    public static function remove_item($item_id)
    {
        # SE VALIDA PERTENENCIA AL CARRITO ACTUAL
        $cart = self::current_cart(false);
        $item = self::owned_item($cart, $item_id);
        if ($item) {
            $item->delete();
            self::recalculate($cart);
        }
    }

    /**
     * CLEAR
     *
     * VACIA EL CARRITO ACTUAL.
     *
     * @access  public
     * @return  Void
     */
    public static function clear()
    {
        # SE BORRAN ITEMS DEL CARRITO ACTUAL
        $cart = self::current_cart(false);
        if (!$cart) {
            return;
        }

        foreach (self::items($cart) as $item) {
            $item->delete();
        }
        self::recalculate($cart);
    }

    /**
     * CHECKOUT QUOTE
     *
     * CONVIERTE EL CARRITO ACTUAL EN SOLICITUD DE COTIZACION.
     *
     * @access  public
     * @return  Model_Core_Sales_Quote
     */
    public static function checkout_quote($customer_notes = '')
    {
        # SE VALIDA CLIENTE Y CARRITO
        $cart = self::current_cart(false);
        $party = self::customer_party();
        if (!$cart || !$party) {
            throw new \InvalidArgumentException('Inicia sesion como cliente para continuar.');
        }

        $items = self::items($cart);
        if (empty($items)) {
            throw new \InvalidArgumentException('El carrito esta vacio.');
        }

        # SE CREA ENCABEZADO DE COTIZACION
        $quote = Model_Core_Sales_Quote::forge([
            'folio' => self::next_quote_folio(),
            'source' => 'frontend_cart',
            'cart_id' => (int) $cart->id,
            'user_id' => self::current_user_id(),
            'party_id' => (int) $party->id,
            'status' => 'requested',
            'currency_code' => (string) $cart->currency_code,
            'subtotal' => (float) $cart->subtotal,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => (float) $cart->total,
            'customer_notes' => trim((string) $customer_notes),
            'internal_notes' => '',
            'expires_at' => time() + (60 * 60 * 24 * 15),
        ]);
        $quote->save();

        # SE COPIAN RENGLONES
        $sort = 10;
        foreach ($items as $item) {
            Model_Core_Sales_Quote_Item::forge([
                'quote_id' => (int) $quote->id,
                'product_id' => (int) $item->product_id,
                'sku' => (string) $item->sku,
                'name' => (string) $item->name,
                'currency_code' => (string) $item->currency_code,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'line_subtotal' => (float) $item->line_total,
                'line_total' => (float) $item->line_total,
                'sort_order' => $sort,
            ])->save();
            $sort += 10;
        }

        # SE CIERRA CARRITO PARA EVITAR DUPLICIDAD
        $cart->status = 'converted';
        $cart->converted_at = time();
        $cart->save();

        return $quote;
    }

    /**
     * COUNT
     *
     * OBTIENE TOTAL DE PIEZAS DEL CARRITO ACTUAL.
     *
     * @access  public
     * @return  Int
     */
    public static function count()
    {
        # SE REGRESA CONTADOR CACHEADO
        $cart = self::current_cart(false);
        return $cart ? (int) $cart->items_count : 0;
    }

    /**
     * NEXT QUOTE FOLIO
     *
     * GENERA FOLIO CONSECUTIVO PARA COTIZACIONES WEB.
     *
     * @access  protected
     * @return  String
     */
    protected static function next_quote_folio()
    {
        # SE GENERA FOLIO DIARIO SIMPLE
        $prefix = 'COT-'.date('Ymd').'-';
        $row = \DB::select(\DB::expr('COUNT(*) as total'))
            ->from('core_sales_quotes')
            ->where('folio', 'like', $prefix.'%')
            ->execute()
            ->current();

        return $prefix.str_pad(((int) $row['total']) + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * RECALCULATE
     *
     * RECALCULA TOTALES DEL CARRITO.
     *
     * @access  public
     * @return  Void
     */
    public static function recalculate(Model_Core_Cart_Cart $cart)
    {
        # SE SUMAN TOTALES DESDE ITEMS
        $items_count = 0;
        $subtotal = 0;
        $currency = $cart->currency_code ?: 'MXN';

        foreach (self::items($cart) as $item) {
            $items_count += (int) ceil((float) $item->quantity);
            $subtotal += (float) $item->line_total;
            $currency = $item->currency_code ?: $currency;
        }

        $cart->currency_code = $currency;
        $cart->items_count = $items_count;
        $cart->subtotal = round($subtotal, 2);
        $cart->total = round($subtotal, 2);
        $cart->save();
    }

    /**
     * PRODUCT
     *
     * OBTIENE PRODUCTO ACTIVO Y PUBLICADO.
     *
     * @access  protected
     * @return  Array|null
     */
    protected static function product($product_id)
    {
        # SE BUSCA PRODUCTO PUBLICO
        $row = \DB::select('id', 'sku', 'name', 'slug', 'currency_code', 'price')
            ->from('core_commerce_products')
            ->where('id', '=', (int) $product_id)
            ->where('active', '=', 1)
            ->where('published', '=', 1)
            ->execute()
            ->current();

        return $row ?: null;
    }

    /**
     * PRICE FOR PRODUCT
     *
     * RESUELVE PRECIO DEL PRODUCTO SEGUN LISTA DEL CLIENTE.
     *
     * @access  protected
     * @return  Array
     */
    protected static function price_for_product(array $product)
    {
        # PRECIO BASE POR DEFECTO
        $price = [
            'price' => (float) $product['price'],
            'currency_code' => (string) $product['currency_code'],
            'price_list_id' => 0,
        ];

        $party = self::customer_party();
        if (!$party) {
            return $price;
        }

        $price_list_id = self::customer_price_list_id($party);
        if ($price_list_id < 1) {
            return $price;
        }

        $today = date('Y-m-d');
        $rows = \DB::select('price', 'currency_code', 'valid_from', 'valid_until')
            ->from('core_commerce_product_prices')
            ->where('product_id', '=', (int) $product['id'])
            ->where('price_list_id', '=', $price_list_id)
            ->where('active', '=', 1)
            ->where('min_quantity', '<=', 1)
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
                'price_list_id' => $price_list_id,
            ];
        }

        return $price;
    }

    /**
     * CUSTOMER PRICE LIST ID
     *
     * RESUELVE LISTA DE PRECIOS DEL CLIENTE.
     *
     * @access  protected
     * @return  Int
     */
    protected static function customer_price_list_id(Model_Core_Party $party)
    {
        # PRIORIDAD 1: LISTA DIRECTA
        if ((int) $party->price_list_id > 0) {
            return (int) $party->price_list_id;
        }

        # PRIORIDAD 2: RELACION CLIENTE-LISTA
        $link = \DB::select('price_list_id')
            ->from('core_commerce_customer_price_lists')
            ->where('customer_id', '=', (int) $party->id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return $link ? (int) $link['price_list_id'] : 0;
    }

    /**
     * CUSTOMER PARTY
     *
     * OBTIENE TERCERO CLIENTE VINCULADO A LA SESION.
     *
     * @access  protected
     * @return  Model_Core_Party|null
     */
    protected static function customer_party()
    {
        # SOLO CLIENTE LOGUEADO
        $user_id = self::current_user_id();
        if ($user_id < 1 || !\DBUtil::table_exists('core_party_user_links')) {
            return null;
        }

        $link = Model_Core_Party_User_Link::query()
            ->where('user_id', $user_id)
            ->where('portal_code', 'clientes')
            ->where('active', 1)
            ->get_one();

        return $link ? Model_Core_Party::find((int) $link->party_id) : null;
    }

    /**
     * CURRENT USER ID
     *
     * OBTIENE ID DE USUARIO ORM AUTH.
     *
     * @access  protected
     * @return  Int
     */
    protected static function current_user_id()
    {
        # SE RESUELVE ID DESDE AUTH
        if (!\Auth::check()) {
            return 0;
        }

        $user_id_data = \Auth::get_user_id();
        return isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
    }

    /**
     * OWNED ITEM
     *
     * VALIDA QUE UN ITEM PERTENEZCA AL CARRITO ACTUAL.
     *
     * @access  protected
     * @return  Model_Core_Cart_Item|null
     */
    protected static function owned_item($cart, $item_id)
    {
        # SE VALIDA CARRITO E ITEM
        if (!$cart) {
            return null;
        }

        return Model_Core_Cart_Item::query()
            ->where('id', (int) $item_id)
            ->where('cart_id', (int) $cart->id)
            ->get_one();
    }
}
