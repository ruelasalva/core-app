<?php
/**
 * CONTROLADOR FRONTEND PUBLICO
 *
 * Controlador responsable de publicar las paginas administrables del sitio.
 *
 * @package  app
 * @extends  Controller_Template
 */
class Controller_Frontend extends Controller_Template
{
    /**
     * Plantilla publica del sitio.
     *
     * @var string
     */
    public $template = 'frontend/template';

    /**
     * Pagina de inicio publica.
     *
     * @access  public
     * @return  void
     */
    public function action_index()
    {
        # SE BUSCA LA PAGINA MARCADA COMO INICIO
        $page = Model_Core_Frontend_Page::query()
            ->where('is_home', 1)
            ->where('published', 1)
            ->where('active', 1)
            ->get_one();

        if (empty($page)) {
            throw new HttpNotFoundException;
        }

        # SE CARGA LA PAGINA PUBLICA
        $this->render_page($page, 'home');
    }

    /**
     * Pagina publica por slug.
     *
     * @access  public
     * @param   string|null  $slug
     * @return  void
     */
    public function action_page($slug = null)
    {
        # SE NORMALIZA EL SLUG RECIBIDO
        $slug = trim((string) $slug, '/');

        if ($slug === '') {
            Response::redirect(Uri::base(false));
        }

        # SE BUSCA LA PAGINA PUBLICADA
        $page = Model_Core_Frontend_Page::query()
            ->where('slug', $slug)
            ->where('published', 1)
            ->where('active', 1)
            ->get_one();

        if (empty($page)) {
            throw new HttpNotFoundException;
        }

        # SE CARGA LA PAGINA PUBLICA
        $this->render_page($page, $slug);
    }

    /**
     * Listado publico de productos.
     *
     * @access  public
     * @return  void
     */
    public function action_products()
    {
        # SE INICIALIZA LA PLANTILLA PUBLICA
        $this->prepare_template('Productos', 'Catalogo publico de productos.');

        # SE OBTIENEN FILTROS DEL CATALOGO
        $filters = $this->catalog_filters();

        # SE CARGA EL LISTADO GENERAL
        $this->template->set('content', View::forge('frontend/products', array(
            'title'       => 'Productos',
            'description' => 'Catalogo publico de productos.',
            'products'    => $this->get_public_products($filters),
            'filters'     => $filters,
            'options'     => $this->catalog_filter_options(),
            'scope'       => null,
        ), false), false);
    }

    /**
     * Detalle publico de producto.
     *
     * @access  public
     * @param   string|null  $slug
     * @return  void
     */
    public function action_product($slug = null)
    {
        # SE NORMALIZA EL SLUG RECIBIDO
        $slug = trim((string) $slug, '/');

        if ($slug === '') {
            throw new HttpNotFoundException;
        }

        # SE BUSCA EL PRODUCTO PUBLICADO
        $product = $this->get_public_product($slug);

        if (empty($product)) {
            throw new HttpNotFoundException;
        }

        # SE INICIALIZA LA PLANTILLA PUBLICA
        $this->prepare_template($product['name'], $product['short_description']);

        # SE CARGA EL DETALLE DEL PRODUCTO
        $this->template->set('content', View::forge('frontend/product', array(
            'product' => $product,
            'images'  => $this->get_product_images($product['id']),
            'tags'    => $this->get_product_tags($product['id']),
            'related_products' => $this->get_related_products($product),
        ), false), false);
    }

    /**
     * Listado publico por categoria.
     *
     * @access  public
     * @param   string|null  $slug
     * @return  void
     */
    public function action_category($slug = null)
    {
        # SE BUSCA LA CATEGORIA ACTIVA
        $category = Model_Core_Commerce_Category::query()
            ->where('slug', trim((string) $slug, '/'))
            ->where('active', 1)
            ->get_one();

        if (empty($category)) {
            throw new HttpNotFoundException;
        }

        # SE INICIALIZA LA PLANTILLA PUBLICA
        $this->prepare_template($category->name, $category->description);

        # SE OBTIENEN FILTROS FIJANDO CATEGORIA ACTUAL
        $filters = $this->catalog_filters(['category_id' => (int) $category->id]);

        # SE CARGA EL LISTADO DE LA CATEGORIA
        $this->template->set('content', View::forge('frontend/products', array(
            'title'       => $category->name,
            'description' => $category->description,
            'products'    => $this->get_public_products($filters),
            'filters'     => $filters,
            'options'     => $this->catalog_filter_options(),
            'scope'       => 'category',
        ), false), false);
    }

    /**
     * Listado publico por tag.
     *
     * @access  public
     * @param   string|null  $slug
     * @return  void
     */
    public function action_tag($slug = null)
    {
        # SE BUSCA EL TAG ACTIVO
        $tag = Model_Core_Commerce_Tag::query()
            ->where('slug', trim((string) $slug, '/'))
            ->where('active', 1)
            ->get_one();

        if (empty($tag)) {
            throw new HttpNotFoundException;
        }

        # SE INICIALIZA LA PLANTILLA PUBLICA
        $this->prepare_template($tag->name, 'Productos relacionados con '.$tag->name.'.');

        # SE OBTIENEN FILTROS FIJANDO TAG ACTUAL
        $filters = $this->catalog_filters(['tag_id' => (int) $tag->id]);

        # SE CARGA EL LISTADO DEL TAG
        $this->template->set('content', View::forge('frontend/products', array(
            'title'       => $tag->name,
            'description' => 'Productos relacionados con '.$tag->name.'.',
            'products'    => $this->get_public_products($filters),
            'filters'     => $filters,
            'options'     => $this->catalog_filter_options(),
            'scope'       => 'tag',
        ), false), false);
    }

    /**
     * Renderiza una pagina administrable con sus elementos publicos.
     *
     * @access  protected
     * @param   Model_Core_Frontend_Page  $page
     * @param   string                    $location
     * @return  void
     */
    protected function render_page(Model_Core_Frontend_Page $page, $location)
    {
        # SE INICIALIZAN LOS DATOS DE PLANTILLA
        $this->prepare_template($page->seo_title ?: $page->title, $page->seo_description);

        # SE INICIALIZAN LOS DATOS DE CONTENIDO
        $data = array(
            'page'              => $page,
            'sections'          => $this->get_sections($page->id),
            'slider'            => $this->get_slider($location),
            'slider_items'      => array(),
            'banners'           => $this->get_banners($location),
            'featured_products' => ($location === 'home') ? $this->get_featured_products() : array(),
            'featured_brands'   => $this->get_featured_brands(),
            'contact_form_enabled' => $page->slug === 'contacto',
            'contact_success' => \Session::get_flash('contact_success'),
            'contact_error' => \Session::get_flash('contact_error'),
            'google_maps_embed_url' => class_exists('Helper_Core_Web') ? Helper_Core_Web::google_maps_embed_url() : '',
            'captcha_html' => class_exists('Helper_Core_Web') ? Helper_Core_Web::render_captcha() : '',
        );

        if (!empty($data['slider'])) {
            $data['slider_items'] = $this->get_slider_items($data['slider']->id);
        }

        # SE CARGA LA VISTA PRINCIPAL DE PAGINA
        $this->template->set('content', View::forge('frontend/page', $data, false), false);
        if ($page->slug === 'contacto') {
            $this->template->set('frontend_extra_scripts', class_exists('Helper_Core_Web') ? Helper_Core_Web::captcha_script() : '', false);
        }
    }

    /**
     * CONTACT SUBMIT
     *
     * RECIBE FORMULARIO PUBLICO DE CONTACTO Y CREA NOTIFICACION INTERNA.
     *
     * @access  public
     * @return  void
     */
    public function post_contact_submit()
    {
        # SE VALIDA CAPTCHA SI ESTA CONFIGURADO
        try {
            if (class_exists('Helper_Core_Web') && !Helper_Core_Web::verify_captcha((string) \Input::post('g-recaptcha-response', ''))) {
                throw new \InvalidArgumentException('No se pudo validar el captcha. Intenta nuevamente.');
            }

            # SE VALIDA PAYLOAD PUBLICO
            $name = trim((string) \Input::post('name', ''));
            $email = strtolower(trim((string) \Input::post('email', '')));
            $phone = trim((string) \Input::post('phone', ''));
            $message = trim((string) \Input::post('message', ''));

            if ($name === '' || $email === '' || $message === '') {
                throw new \InvalidArgumentException('Nombre, correo y mensaje son obligatorios.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Captura un correo valido.');
            }

            # SE CREA NOTIFICACION PARA ADMINISTRADORES
            $recipients = $this->contact_notification_recipients();
            if (!empty($recipients) && class_exists('Helper_Core_Notification')) {
                Helper_Core_Notification::create([
                    'event_code' => 'contact.web.message',
                    'notification_type' => 'contact',
                    'title' => 'Nuevo mensaje de contacto',
                    'message' => $name.' escribio desde el frontend.',
                    'url' => 'admin/communications',
                    'icon' => 'bi bi-envelope',
                    'priority' => 2,
                    'payload' => [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'message' => $message,
                        'ip' => \Input::real_ip(),
                    ],
                    'created_by' => 0,
                ], $recipients);
            }

            \Session::set_flash('contact_success', 'Recibimos tu mensaje. Te contactaremos pronto.');
        } catch (\InvalidArgumentException $e) {
            \Session::set_flash('contact_error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Error procesando contacto frontend: '.$e->getMessage());
            \Session::set_flash('contact_error', 'No se pudo enviar el mensaje. Intenta nuevamente.');
        }

        \Response::redirect('contacto');
    }

    /**
     * CONTACT SUBMIT
     *
     * SOPORTE DE RUTA SIN PREFIJO POST.
     *
     * @access  public
     * @return  void
     */
    public function action_contact_submit()
    {
        # SE DELEGA AL FLUJO POST
        return $this->post_contact_submit();
    }

    /**
     * Prepara los datos comunes de la plantilla publica.
     *
     * @access  protected
     * @param   string  $title
     * @param   string  $description
     * @return  void
     */
    protected function prepare_template($title, $description = '')
    {
        # SE RESUELVE TEMA Y EMPRESA PARA BRANDING/SEO
        $theme = $this->get_active_theme();
        $company = Model_Core_Company::get_current();

        # SE INICIALIZAN LOS DATOS COMUNES
        $this->template->title           = $title ?: $this->site_name($theme, $company);
        $this->template->seo_description = $description ?: $this->default_seo_description($theme, $company);
        $this->template->site_name       = $this->site_name($theme, $company);
        $this->template->canonical_url   = \Uri::current();
        $this->template->menu_items      = $this->get_menu_items('header');
        $this->template->footer_columns  = $this->get_footer_columns();
        $this->template->theme           = $theme;
        $this->template->frontend_user   = [
            'logged_in' => (bool) $this->get_customer_party(),
            'name' => \Auth::check() ? \Auth::get_screen_name() : '',
        ];
        $this->template->cart_count = class_exists('Helper_Core_Cart') ? Helper_Core_Cart::count() : 0;
        $this->template->set('cookie_banner', class_exists('Helper_Core_Legal')
            ? Helper_Core_Legal::render_cookie_banner()
            : '', false);
    }

    /**
     * SITE NAME
     *
     * RESUELVE NOMBRE PUBLICO DEL SITIO
     *
     * @access  protected
     * @return  string
     */
    protected function site_name($theme, $company)
    {
        return ($theme && !empty($theme->site_name)) ? (string) $theme->site_name : (string) ($company ? $company->name : 'Core-App');
    }

    /**
     * DEFAULT SEO DESCRIPTION
     *
     * RESUELVE DESCRIPCION SEO POR DEFECTO
     *
     * @access  protected
     * @return  string
     */
    protected function default_seo_description($theme, $company)
    {
        if ($theme && !empty($theme->default_seo_description)) {
            return (string) $theme->default_seo_description;
        }

        return $company && !empty($company->legal_name) ? (string) $company->legal_name : '';
    }

    /**
     * Obtiene las secciones activas de una pagina.
     *
     * @access  protected
     * @param   int  $page_id
     * @return  array
     */
    protected function get_sections($page_id)
    {
        # SE BUSCAN LAS SECCIONES ACTIVAS
        return Model_Core_Frontend_Section::query()
            ->where('page_id', $page_id)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * Obtiene los elementos del menu principal.
     *
     * @access  protected
     * @param   string  $location
     * @return  array
     */
    protected function get_menu_items($location = 'header')
    {
        # SE BUSCA EL MENU ACTIVO
        $menu = Model_Core_Frontend_Menu::query()
            ->where('location', $location)
            ->where('active', 1)
            ->order_by('id', 'asc')
            ->get_one();

        if (empty($menu)) {
            return array();
        }

        # SE BUSCAN SUS ELEMENTOS ACTIVOS
        return Model_Core_Frontend_Menu_Item::query()
            ->where('menu_id', $menu->id)
            ->where('parent_id', 0)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * Obtiene el slider activo de una ubicacion.
     *
     * @access  protected
     * @param   string  $location
     * @return  Model_Core_Frontend_Slider|null
     */
    protected function get_slider($location)
    {
        # SE BUSCA EL SLIDER DE LA UBICACION
        return Model_Core_Frontend_Slider::query()
            ->where('location', $location)
            ->where('active', 1)
            ->order_by('id', 'asc')
            ->get_one();
    }

    /**
     * Obtiene los elementos activos de un slider.
     *
     * @access  protected
     * @param   int  $slider_id
     * @return  array
     */
    protected function get_slider_items($slider_id)
    {
        # SE BUSCAN LAS DIAPOSITIVAS ACTIVAS
        return Model_Core_Frontend_Slider_Item::query()
            ->where('slider_id', $slider_id)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * Obtiene banners activos por ubicacion.
     *
     * @access  protected
     * @param   string  $location
     * @return  array
     */
    protected function get_banners($location)
    {
        # SE BUSCAN LOS BANNERS ACTIVOS
        return Model_Core_Frontend_Banner::query()
            ->where('location', $location)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * Obtiene las columnas activas del footer.
     *
     * @access  protected
     * @return  array
     */
    protected function get_footer_columns()
    {
        # SE BUSCAN LAS COLUMNAS ACTIVAS
        $columns = Model_Core_Frontend_Footer_Column::query()
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();

        foreach ($columns as $column) {
            $column->settings = $this->decode_settings(isset($column->settings_json) ? (string) $column->settings_json : '');
        }

        return $columns;
    }

    protected function decode_settings($json)
    {
        $json = trim((string) $json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene el tema activo del frontend.
     *
     * @access  protected
     * @return  Model_Core_Frontend_Theme|null
     */
    protected function get_active_theme()
    {
        # SI EXISTE LA TABLA, SE BUSCA EL TEMA ACTIVO
        if (!\DBUtil::table_exists('core_frontend_themes')) {
            return null;
        }

        return Model_Core_Frontend_Theme::get_active();
    }

    /**
     * Obtiene productos destacados para el inicio.
     *
     * @access  protected
     * @return  array
     */
    protected function get_featured_products()
    {
        # SE BUSCAN LOS PRODUCTOS PUBLICADOS PARA EL INICIO
        $products = DB::select('id', 'sku', 'name', 'slug', 'short_description', 'currency_code', 'price', 'main_image_path')
            ->from('core_commerce_products')
            ->where('active', 1)
            ->where('published', 1)
            ->where_open()
                ->where('featured', 1)
                ->or_where('show_in_home', 1)
            ->where_close()
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'desc')
            ->limit(8)
            ->execute()
            ->as_array();

        return $this->apply_customer_prices($products);
    }

    /**
     * Obtiene marcas destacadas para paginas publicas.
     *
     * @access  protected
     * @return  array
     */
    protected function get_featured_brands()
    {
        # SE BUSCAN MARCAS ACTIVAS PARA FRONTEND
        return DB::select('id', 'name', 'slug', 'description', 'logo_path')
            ->from('core_commerce_brands')
            ->where('active', 1)
            ->where('show_in_home', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('name', 'asc')
            ->limit(12)
            ->execute()
            ->as_array();
    }

    /**
     * CONTACT NOTIFICATION RECIPIENTS
     *
     * OBTIENE USUARIOS ADMINISTRATIVOS PARA MENSAJES PUBLICOS DE CONTACTO.
     *
     * @access  protected
     * @return  Array
     */
    protected function contact_notification_recipients()
    {
        # SE ENVIA A ADMINISTRADORES GENERALES Y CONFIGURACION/COMUNICACIONES
        if (!\DBUtil::table_exists('users')) {
            return [];
        }

        $rows = \DB::select('id')
            ->from('users')
            ->where('group_id', 'in', [100, 90, 50])
            ->execute()
            ->as_array();

        return array_map(function ($row) {
            return (int) $row['id'];
        }, $rows);
    }

    /**
     * Obtiene productos publicos con filtros comerciales.
     *
     * @access  protected
     * @param   array  $filters
     * @return  array
     */
    protected function get_public_products(array $filters = array())
    {
        # SE PREPARA EL QUERY BASE DE PRODUCTOS PUBLICADOS
        $query = DB::select(
                array('p.id', 'id'),
                array('p.sku', 'sku'),
                array('p.name', 'name'),
                array('p.slug', 'slug'),
                array('p.short_description', 'short_description'),
                array('p.currency_code', 'currency_code'),
                array('p.price', 'price'),
                array('p.main_image_path', 'main_image_path'),
                array('p.featured', 'featured'),
                array('p.show_in_home', 'show_in_home'),
                array('b.name', 'brand_name'),
                array('b.slug', 'brand_slug'),
                array('c.name', 'category_name'),
                array('c.slug', 'category_slug'),
                array('s.name', 'subcategory_name'),
                array('s.slug', 'subcategory_slug')
            )
            ->from(array('core_commerce_products', 'p'))
            ->join(array('core_commerce_brands', 'b'), 'left')
                ->on('p.brand_id', '=', 'b.id')
            ->join(array('core_commerce_categories', 'c'), 'left')
                ->on('p.category_id', '=', 'c.id')
            ->join(array('core_commerce_subcategories', 's'), 'left')
                ->on('p.subcategory_id', '=', 's.id')
            ->where('p.active', 1)
            ->where('p.published', 1);

        # FILTRO POR BUSQUEDA
        if (!empty($filters['q'])) {
            $query->where_open()
                ->where('p.name', 'like', '%'.$filters['q'].'%')
                ->or_where('p.sku', 'like', '%'.$filters['q'].'%')
                ->or_where('p.short_description', 'like', '%'.$filters['q'].'%')
                ->where_close();
        }

        # FILTRO POR CATEGORIA
        if (!empty($filters['category_id'])) {
            $query->where('p.category_id', (int) $filters['category_id']);
        }

        # FILTRO POR SUBCATEGORIA
        if (!empty($filters['subcategory_id'])) {
            $query->where('p.subcategory_id', (int) $filters['subcategory_id']);
        }

        # FILTRO POR MARCA
        if (!empty($filters['brand_id'])) {
            $query->where('p.brand_id', (int) $filters['brand_id']);
        }

        # FILTRO POR DESTACADOS
        if (!empty($filters['featured'])) {
            $query->where_open()
                ->where('p.featured', 1)
                ->or_where('p.show_in_home', 1)
                ->where_close();
        }

        # FILTRO POR TAG
        if (!empty($filters['tag_id'])) {
            $query->join(array('core_commerce_product_tags', 'pt'), 'inner')
                ->on('p.id', '=', 'pt.product_id')
                ->where('pt.tag_id', (int) $filters['tag_id']);
        }

        # ORDENAMIENTO CONTROLADO
        switch ($filters['sort']) {
            case 'name_desc':
                $query->order_by('p.name', 'desc');
                break;
            case 'price_asc':
                $query->order_by('p.price', 'asc');
                break;
            case 'price_desc':
                $query->order_by('p.price', 'desc');
                break;
            case 'recent':
                $query->order_by('p.id', 'desc');
                break;
            case 'name_asc':
                $query->order_by('p.name', 'asc');
                break;
            default:
                $query->order_by('p.sort_order', 'asc')->order_by('p.id', 'desc');
                break;
        }

        $products = $query->limit(240)->execute()->as_array();

        return $this->apply_customer_prices($products);
    }

    /**
     * CATALOG FILTERS
     *
     * NORMALIZA FILTROS PUBLICOS DEL CATALOGO
     *
     * @access  protected
     * @return  Array
     */
    protected function catalog_filters(array $fixed = array())
    {
        # FILTROS CONTROLADOS POR QUERY STRING
        $filters = [
            'q' => trim((string) \Input::get('q', '')),
            'category_id' => (int) \Input::get('category_id', 0),
            'subcategory_id' => (int) \Input::get('subcategory_id', 0),
            'brand_id' => (int) \Input::get('brand_id', 0),
            'featured' => (int) (bool) \Input::get('featured', 0),
            'tag_id' => 0,
            'sort' => trim((string) \Input::get('sort', 'relevance')),
        ];

        # SE LIMITA LONGITUD DE BUSQUEDA
        $filters['q'] = substr($filters['q'], 0, 80);

        # SE LIMITA ORDEN A OPCIONES SOPORTADAS
        $allowed_sort = ['relevance', 'name_asc', 'name_desc', 'price_asc', 'price_desc', 'recent'];
        if (!in_array($filters['sort'], $allowed_sort, true)) {
            $filters['sort'] = 'relevance';
        }

        # FILTROS FIJOS DE RUTA TIENEN PRIORIDAD
        foreach ($fixed as $key => $value) {
            $filters[$key] = $value;
        }

        return $filters;
    }

    /**
     * CATALOG FILTER OPTIONS
     *
     * OBTIENE OPCIONES PARA FILTROS PUBLICOS
     *
     * @access  protected
     * @return  Array
     */
    protected function catalog_filter_options()
    {
        # OPCIONES VISIBLES EN CATALOGO
        return [
            'categories' => $this->catalog_options('core_commerce_categories'),
            'subcategories' => $this->catalog_subcategory_options(),
            'brands' => $this->catalog_options('core_commerce_brands'),
            'sorts' => [
                ['value' => 'relevance', 'label' => 'Relevancia'],
                ['value' => 'name_asc', 'label' => 'Nombre A-Z'],
                ['value' => 'name_desc', 'label' => 'Nombre Z-A'],
                ['value' => 'price_asc', 'label' => 'Precio menor'],
                ['value' => 'price_desc', 'label' => 'Precio mayor'],
                ['value' => 'recent', 'label' => 'Recientes'],
            ],
        ];
    }

    /**
     * CATALOG OPTIONS
     *
     * FORMATEA OPCIONES SIMPLES DEL CATALOGO
     *
     * @access  protected
     * @return  Array
     */
    protected function catalog_options($table)
    {
        $items = [];
        $rows = \DB::select('id', 'name')
            ->from($table)
            ->where('active', '=', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('name', 'asc')
            ->execute();

        foreach ($rows as $row) {
            $items[] = ['value' => (int) $row['id'], 'label' => (string) $row['name']];
        }

        return $items;
    }

    /**
     * CATALOG SUBCATEGORY OPTIONS
     *
     * FORMATEA SUBCATEGORIAS CON CATEGORIA PADRE
     *
     * @access  protected
     * @return  Array
     */
    protected function catalog_subcategory_options()
    {
        $items = [];
        $rows = \DB::select(
                array('s.id', 'id'),
                array('s.name', 'name'),
                array('s.category_id', 'category_id'),
                array('c.name', 'category_name')
            )
            ->from(array('core_commerce_subcategories', 's'))
            ->join(array('core_commerce_categories', 'c'), 'left')
                ->on('s.category_id', '=', 'c.id')
            ->where('s.active', '=', 1)
            ->order_by('c.name', 'asc')
            ->order_by('s.name', 'asc')
            ->execute();

        foreach ($rows as $row) {
            $items[] = [
                'value' => (int) $row['id'],
                'category_id' => (int) $row['category_id'],
                'label' => trim(($row['category_name'] ? $row['category_name'].' / ' : '').$row['name']),
            ];
        }

        return $items;
    }

    /**
     * Obtiene un producto publico por slug.
     *
     * @access  protected
     * @param   string  $slug
     * @return  array|null
     */
    protected function get_public_product($slug)
    {
        # SE BUSCA EL PRODUCTO PUBLICADO CON SUS DATOS RELACIONADOS
        $result = DB::select(
                array('p.id', 'id'),
                array('p.sku', 'sku'),
                array('p.name', 'name'),
                array('p.slug', 'slug'),
                array('p.short_description', 'short_description'),
                array('p.description', 'description'),
                array('p.brand_id', 'brand_id'),
                array('p.category_id', 'category_id'),
                array('p.subcategory_id', 'subcategory_id'),
                array('p.currency_code', 'currency_code'),
                array('p.price', 'price'),
                array('p.main_image_path', 'main_image_path'),
                array('b.name', 'brand_name'),
                array('c.name', 'category_name'),
                array('c.slug', 'category_slug'),
                array('s.name', 'subcategory_name'),
                array('s.slug', 'subcategory_slug')
            )
            ->from(array('core_commerce_products', 'p'))
            ->join(array('core_commerce_brands', 'b'), 'left')
                ->on('p.brand_id', '=', 'b.id')
            ->join(array('core_commerce_categories', 'c'), 'left')
                ->on('p.category_id', '=', 'c.id')
            ->join(array('core_commerce_subcategories', 's'), 'left')
                ->on('p.subcategory_id', '=', 's.id')
            ->where('p.slug', $slug)
            ->where('p.active', 1)
            ->where('p.published', 1)
            ->execute()
            ->as_array();

        if (empty($result[0])) {
            return null;
        }

        $priced = $this->apply_customer_prices([$result[0]]);
        return !empty($priced[0]) ? $priced[0] : $result[0];
    }

    /**
     * Obtiene imagenes activas de producto.
     *
     * @access  protected
     * @param   int  $product_id
     * @return  array
     */
    protected function get_product_images($product_id)
    {
        # SE BUSCAN LAS IMAGENES ACTIVAS DEL PRODUCTO
        return DB::select('image_path', 'alt_text')
            ->from('core_commerce_product_images')
            ->where('product_id', (int) $product_id)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->execute()
            ->as_array();
    }

    /**
     * Obtiene tags activos de producto.
     *
     * @access  protected
     * @param   int  $product_id
     * @return  array
     */
    protected function get_product_tags($product_id)
    {
        # SE BUSCAN LOS TAGS RELACIONADOS AL PRODUCTO
        return DB::select(array('t.name', 'name'), array('t.slug', 'slug'), array('t.color', 'color'))
            ->from(array('core_commerce_tags', 't'))
            ->join(array('core_commerce_product_tags', 'pt'), 'inner')
                ->on('t.id', '=', 'pt.tag_id')
            ->where('pt.product_id', (int) $product_id)
            ->where('t.active', 1)
            ->order_by('t.name', 'asc')
            ->execute()
            ->as_array();
    }

    /**
     * GET RELATED PRODUCTS
     *
     * OBTIENE PRODUCTOS RELACIONADOS MANUALES Y COMPLEMENTA POR FAMILIA/MARCA.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_related_products(array $product)
    {
        # SE INICIALIZA CONTROL DE DUPLICADOS
        $product_id = (int) $product['id'];
        $seen = [$product_id => true];
        $rows = [];

        # PRIORIDAD 1: RELACIONES MANUALES CONFIGURADAS EN ADMIN
        if (\DBUtil::table_exists('core_commerce_product_relations')) {
            $manual = \DB::select(
                    ['p.id', 'id'],
                    ['p.sku', 'sku'],
                    ['p.name', 'name'],
                    ['p.slug', 'slug'],
                    ['p.short_description', 'short_description'],
                    ['p.currency_code', 'currency_code'],
                    ['p.price', 'price'],
                    ['p.main_image_path', 'main_image_path'],
                    ['b.name', 'brand_name'],
                    ['c.name', 'category_name']
                )
                ->from(['core_commerce_product_relations', 'r'])
                ->join(['core_commerce_products', 'p'], 'inner')
                    ->on('r.related_product_id', '=', 'p.id')
                ->join(['core_commerce_brands', 'b'], 'left')
                    ->on('p.brand_id', '=', 'b.id')
                ->join(['core_commerce_categories', 'c'], 'left')
                    ->on('p.category_id', '=', 'c.id')
                ->where('r.product_id', '=', $product_id)
                ->where('r.active', '=', 1)
                ->where('p.active', '=', 1)
                ->where('p.published', '=', 1)
                ->order_by('r.sort_order', 'asc')
                ->order_by('r.id', 'asc')
                ->limit(8)
                ->execute()
                ->as_array();

            foreach ($manual as $row) {
                $seen[(int) $row['id']] = true;
                $rows[] = $row;
            }
        }

        # PRIORIDAD 2: MISMA SUBCATEGORIA, CATEGORIA O MARCA COMO SUGERENCIA AUTOMATICA
        if (count($rows) < 8 && (!empty($product['subcategory_id']) || !empty($product['category_id']) || !empty($product['brand_id']))) {
            $query = \DB::select(
                    ['p.id', 'id'],
                    ['p.sku', 'sku'],
                    ['p.name', 'name'],
                    ['p.slug', 'slug'],
                    ['p.short_description', 'short_description'],
                    ['p.currency_code', 'currency_code'],
                    ['p.price', 'price'],
                    ['p.main_image_path', 'main_image_path'],
                    ['b.name', 'brand_name'],
                    ['c.name', 'category_name']
                )
                ->from(['core_commerce_products', 'p'])
                ->join(['core_commerce_brands', 'b'], 'left')
                    ->on('p.brand_id', '=', 'b.id')
                ->join(['core_commerce_categories', 'c'], 'left')
                    ->on('p.category_id', '=', 'c.id')
                ->where('p.active', '=', 1)
                ->where('p.published', '=', 1)
                ->where('p.id', 'not in', array_keys($seen))
                ->where_open();

            if (!empty($product['subcategory_id'])) {
                $query->or_where('p.subcategory_id', '=', (int) $product['subcategory_id']);
            }
            if (!empty($product['category_id'])) {
                $query->or_where('p.category_id', '=', (int) $product['category_id']);
            }
            if (!empty($product['brand_id'])) {
                $query->or_where('p.brand_id', '=', (int) $product['brand_id']);
            }

            $auto = $query->where_close()
                ->order_by('p.featured', 'desc')
                ->order_by('p.sort_order', 'asc')
                ->order_by('p.id', 'desc')
                ->limit(8 - count($rows))
                ->execute()
                ->as_array();

            foreach ($auto as $row) {
                $rows[] = $row;
            }
        }

        return $this->apply_customer_prices($rows);
    }

    /**
     * GET CUSTOMER PARTY
     *
     * OBTIENE EL CLIENTE LOGUEADO EN FRONTEND SI EXISTE
     *
     * @access  protected
     * @return  Model_Core_Party|null
     */
    protected function get_customer_party()
    {
        # SE VALIDA SESION Y VINCULO CON PORTAL CLIENTES
        if (!\Auth::check()) {
            return null;
        }

        $user_id_data = \Auth::get_user_id();
        $user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
        if ($user_id < 1) {
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
     * APPLY CUSTOMER PRICES
     *
     * APLICA LISTA DE PRECIOS DEL CLIENTE Y OCULTA PRECIOS SIN SESION
     *
     * @access  protected
     * @return  Array
     */
    protected function apply_customer_prices(array $products)
    {
        # SIN CLIENTE NO SE MUESTRA PRECIO
        $party = $this->get_customer_party();
        if (!$party) {
            foreach ($products as &$product) {
                $product['can_view_price'] = false;
            }
            return $products;
        }

        # SE RESUELVE LISTA DE PRECIOS Y SE APLICA PRECIO ESPECIFICO SI EXISTE
        $price_list_id = $this->customer_price_list_id($party);
        foreach ($products as &$product) {
            $product['can_view_price'] = true;
            if ($price_list_id > 0) {
                $price = $this->product_price_for_list((int) $product['id'], $price_list_id);
                if ($price) {
                    $product['price'] = $price['price'];
                    $product['currency_code'] = $price['currency_code'];
                }
            }
        }

        return $products;
    }

    /**
     * CUSTOMER PRICE LIST ID
     *
     * RESUELVE LISTA DE PRECIO PRINCIPAL PARA EL CLIENTE
     *
     * @access  protected
     * @return  Int
     */
    protected function customer_price_list_id(Model_Core_Party $party)
    {
        # PRIORIDAD 1: LISTA DIRECTA DEL CLIENTE
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
        if ($link) {
            return (int) $link['price_list_id'];
        }

        # PRIORIDAD 3: LISTA DEFAULT
        $default = \DB::select('id')
            ->from('core_commerce_price_lists')
            ->where('active', '=', 1)
            ->where('is_default', '=', 1)
            ->order_by('priority', 'desc')
            ->execute()
            ->current();

        return $default ? (int) $default['id'] : 0;
    }

    /**
     * PRODUCT PRICE FOR LIST
     *
     * OBTIENE PRECIO VIGENTE PARA PRODUCTO Y LISTA
     *
     * @access  protected
     * @return  Array|null
     */
    protected function product_price_for_list($product_id, $price_list_id)
    {
        # SE BUSCA PRECIO VIGENTE PARA CANTIDAD BASE
        $today = date('Y-m-d');
        $rows = \DB::select('price', 'currency_code', 'valid_from', 'valid_until')
            ->from('core_commerce_product_prices')
            ->where('product_id', '=', (int) $product_id)
            ->where('price_list_id', '=', (int) $price_list_id)
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
            return $row;
        }

        return null;
    }
}
