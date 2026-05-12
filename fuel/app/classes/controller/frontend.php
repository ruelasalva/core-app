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

        # SE CARGA EL LISTADO GENERAL
        $this->template->set('content', View::forge('frontend/products', array(
            'title'       => 'Productos',
            'description' => 'Catalogo publico de productos.',
            'products'    => $this->get_public_products(),
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

        # SE CARGA EL LISTADO DE LA CATEGORIA
        $this->template->set('content', View::forge('frontend/products', array(
            'title'       => $category->name,
            'description' => $category->description,
            'products'    => $this->get_public_products(array('category_id' => $category->id)),
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

        # SE CARGA EL LISTADO DEL TAG
        $this->template->set('content', View::forge('frontend/products', array(
            'title'       => $tag->name,
            'description' => 'Productos relacionados con '.$tag->name.'.',
            'products'    => $this->get_public_products(array('tag_id' => $tag->id)),
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
            'featured_products' => $this->get_featured_products(),
            'featured_brands'   => $this->get_featured_brands(),
        );

        if (!empty($data['slider'])) {
            $data['slider_items'] = $this->get_slider_items($data['slider']->id);
        }

        # SE CARGA LA VISTA PRINCIPAL DE PAGINA
        $this->template->set('content', View::forge('frontend/page', $data, false), false);
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
        # SE INICIALIZAN LOS DATOS COMUNES
        $this->template->title           = $title ?: 'Core-App';
        $this->template->seo_description = $description;
        $this->template->menu_items      = $this->get_menu_items('header');
        $this->template->footer_columns  = $this->get_footer_columns();
        $this->template->theme           = $this->get_active_theme();
        $this->template->set('cookie_banner', class_exists('Helper_Core_Legal')
            ? Helper_Core_Legal::render_cookie_banner()
            : '', false);
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
        return Model_Core_Frontend_Footer_Column::query()
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
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
        return DB::select('id', 'sku', 'name', 'slug', 'short_description', 'currency_code', 'price', 'main_image_path')
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
                array('c.name', 'category_name'),
                array('c.slug', 'category_slug')
            )
            ->from(array('core_commerce_products', 'p'))
            ->join(array('core_commerce_categories', 'c'), 'left')
                ->on('p.category_id', '=', 'c.id')
            ->where('p.active', 1)
            ->where('p.published', 1);

        # FILTRO POR CATEGORIA
        if (!empty($filters['category_id'])) {
            $query->where('p.category_id', (int) $filters['category_id']);
        }

        # FILTRO POR TAG
        if (!empty($filters['tag_id'])) {
            $query->join(array('core_commerce_product_tags', 'pt'), 'inner')
                ->on('p.id', '=', 'pt.product_id')
                ->where('pt.tag_id', (int) $filters['tag_id']);
        }

        return $query
            ->order_by('p.sort_order', 'asc')
            ->order_by('p.id', 'desc')
            ->execute()
            ->as_array();
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

        return !empty($result[0]) ? $result[0] : null;
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
}
