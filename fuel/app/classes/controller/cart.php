<?php

/**
 * CONTROLADOR CART
 *
 * Maneja carrito publico del frontend para clientes y visitantes anonimos.
 *
 * @package  app
 * @extends  Controller_Template
 */
class Controller_Cart extends Controller_Template
{
    /**
     * Plantilla publica del sitio.
     *
     * @var string
     */
    public $template = 'frontend/template';

    /**
     * BEFORE
     *
     * PREPARA DATOS COMUNES DEL FRONTEND.
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING
        parent::before();

        # SE PREPARA PLANTILLA PUBLICA
        $this->prepare_template('Carrito', 'Carrito de compra.');
    }

    /**
     * INDEX
     *
     * MUESTRA EL CARRITO ACTUAL.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE OBTIENE CARRITO ACTUAL SIN CREARLO SI NO EXISTE
        $cart = Helper_Core_Cart::current_cart(false);

        # SE CARGA VISTA DEL CARRITO
        $this->template->title = 'Carrito';
        $this->template->content = View::forge('cart/index', [
            'cart' => $cart,
            'items' => $cart ? Helper_Core_Cart::items($cart) : [],
            'success' => \Session::get_flash('success'),
            'error' => \Session::get_flash('error'),
        ]);
    }

    /**
     * ADD
     *
     * AGREGA PRODUCTO AL CARRITO.
     *
     * @access  public
     * @return  Void
     */
    public function post_add()
    {
        # SE REQUIERE CLIENTE PARA NO EXPONER PRECIOS A VISITANTES ANONIMOS
        if (!$this->customer_link()) {
            if ($this->is_ajax_request()) {
                return $this->json_response(['error' => 'Inicia sesion para agregar productos al carrito.', 'redirect' => \Uri::create('acceso')], 401);
            }
            \Session::set_flash('error', 'Inicia sesion para agregar productos al carrito.');
            \Response::redirect('acceso');
        }

        # SE PROCESA ALTA DE PRODUCTO
        try {
            $product_id = (int) \Input::post('product_id', 0);
            $quantity = (float) \Input::post('quantity', 1);
            Helper_Core_Cart::add_product($product_id, $quantity);
            if ($this->is_ajax_request()) {
                return $this->json_response([
                    'status' => 'ok',
                    'message' => 'Producto agregado al carrito.',
                    'cart_count' => Helper_Core_Cart::count(),
                ]);
            }
            \Session::set_flash('success', 'Producto agregado al carrito.');
        } catch (\Exception $e) {
            \Log::warning('No se pudo agregar producto al carrito: '.$e->getMessage());
            if ($this->is_ajax_request()) {
                return $this->json_response(['error' => $e->getMessage()], 422);
            }
            \Session::set_flash('error', $e->getMessage());
        }

        \Response::redirect(\Input::referrer(\Uri::create('carrito')));
    }

    /**
     * ADD
     *
     * SOPORTE DE RUTA PARA SERVIDORES QUE NO RESUELVEN PREFIJO POST EN ROUTES.
     *
     * @access  public
     * @return  Void
     */
    public function action_add()
    {
        # SE DELEGA AL FLUJO POST
        return $this->post_add();
    }

    /**
     * IS AJAX REQUEST
     *
     * DETECTA PETICIONES AJAX DEL FRONTEND.
     *
     * @access  protected
     * @return  Bool
     */
    protected function is_ajax_request()
    {
        # SE REVISA HEADER ESTANDAR O ACCEPT JSON
        return strtolower((string) \Input::server('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest'
            || stripos((string) \Input::server('HTTP_ACCEPT', ''), 'application/json') !== false;
    }

    /**
     * JSON RESPONSE
     *
     * GENERA RESPUESTA JSON PARA AJAX PUBLICO.
     *
     * @access  protected
     * @return  Response
     */
    protected function json_response(array $data, $status = 200)
    {
        # SE REGRESA JSON CON CONTENT TYPE CORRECTO
        return \Response::forge(json_encode($data), $status, ['Content-Type' => 'application/json']);
    }

    /**
     * UPDATE
     *
     * ACTUALIZA CANTIDADES DEL CARRITO.
     *
     * @access  public
     * @return  Void
     */
    public function post_update()
    {
        # SE ACTUALIZAN CANTIDADES
        $quantities = (array) \Input::post('quantity', []);
        foreach ($quantities as $item_id => $quantity) {
            Helper_Core_Cart::update_item((int) $item_id, (float) $quantity);
        }

        \Session::set_flash('success', 'Carrito actualizado.');
        \Response::redirect('carrito');
    }

    /**
     * UPDATE
     *
     * SOPORTE DE RUTA PARA ACTUALIZACION DEL CARRITO.
     *
     * @access  public
     * @return  Void
     */
    public function action_update()
    {
        # SE DELEGA AL FLUJO POST
        return $this->post_update();
    }

    /**
     * REMOVE
     *
     * QUITA UN RENGLON DEL CARRITO.
     *
     * @access  public
     * @param   int|null  $item_id
     * @return  Void
     */
    public function action_remove($item_id = null)
    {
        # SE ELIMINA ITEM SI PERTENECE AL CARRITO
        Helper_Core_Cart::remove_item((int) $item_id);
        \Session::set_flash('success', 'Producto eliminado del carrito.');
        \Response::redirect('carrito');
    }

    /**
     * CLEAR
     *
     * VACIA EL CARRITO ACTUAL.
     *
     * @access  public
     * @return  Void
     */
    public function action_clear()
    {
        # SE VACIA CARRITO
        Helper_Core_Cart::clear();
        \Session::set_flash('success', 'Carrito vacio.');
        \Response::redirect('carrito');
    }

    /**
     * CHECKOUT
     *
     * PUNTO BASE PARA CONVERSION FUTURA A PEDIDO O COTIZACION.
     *
     * @access  public
     * @return  Void
     */
    public function post_checkout()
    {
        # SI NO HAY CLIENTE, SE ENVIA A LOGIN
        if (!$this->customer_link()) {
            \Session::set_flash('error', 'Inicia sesion para continuar con tu carrito.');
            \Response::redirect('acceso');
        }

        # SE CONVIERTE CARRITO EN SOLICITUD DE COTIZACION
        try {
            $quote = Helper_Core_Cart::checkout_quote((string) \Input::post('customer_notes', ''));
            \Session::set_flash('success', 'Solicitud de cotizacion generada: '.$quote->folio.'.');
        } catch (\Exception $e) {
            \Session::set_flash('error', $e->getMessage());
        }

        \Response::redirect('carrito');
    }

    /**
     * CHECKOUT
     *
     * SOPORTE GET: REDIRIGE AL CARRITO PARA USAR FORMULARIO CON CSRF.
     *
     * @access  public
     * @return  Void
     */
    public function action_checkout()
    {
        # CHECKOUT DEBE EJECUTARSE POR POST
        \Response::redirect('carrito');
    }

    /**
     * PREPARE TEMPLATE
     *
     * PREPARA DATOS COMUNES DE LA PLANTILLA PUBLICA.
     *
     * @access  protected
     * @return  Void
     */
    protected function prepare_template($title, $description = '')
    {
        # SE ASIGNAN VARIABLES GLOBALES DEL FRONTEND
        $this->template->title = $title;
        $this->template->seo_description = $description;
        $this->template->menu_items = $this->get_menu_items('header');
        $this->template->footer_columns = $this->get_footer_columns();
        $this->template->theme = $this->get_active_theme();
        $this->template->frontend_user = [
            'logged_in' => \Auth::check(),
            'name' => \Auth::check() ? \Auth::get_screen_name() : '',
        ];
        $this->template->cart_count = class_exists('Helper_Core_Cart') ? Helper_Core_Cart::count() : 0;
        $this->template->set('cookie_banner', class_exists('Helper_Core_Legal') ? Helper_Core_Legal::render_cookie_banner() : '', false);
    }

    protected function get_menu_items($location = 'header')
    {
        $menu = Model_Core_Frontend_Menu::query()
            ->where('location', $location)
            ->where('active', 1)
            ->order_by('id', 'asc')
            ->get_one();

        if (!$menu) {
            return [];
        }

        return Model_Core_Frontend_Menu_Item::query()
            ->where('menu_id', $menu->id)
            ->where('parent_id', 0)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    protected function get_footer_columns()
    {
        return Model_Core_Frontend_Footer_Column::query()
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    protected function get_active_theme()
    {
        if (!\DBUtil::table_exists('core_frontend_themes')) {
            return null;
        }

        return Model_Core_Frontend_Theme::get_active();
    }

    protected function customer_link()
    {
        # SE VALIDA SESION Y LINK CLIENTES
        if (!\Auth::check()) {
            return null;
        }

        $user_id_data = \Auth::get_user_id();
        $user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
        if ($user_id < 1) {
            return null;
        }

        return Model_Core_Party_User_Link::query()
            ->where('user_id', $user_id)
            ->where('portal_code', 'clientes')
            ->where('active', 1)
            ->get_one();
    }
}
