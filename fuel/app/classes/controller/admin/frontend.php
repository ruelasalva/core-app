<?php

/**
 * CONTROLADOR ADMIN_FRONTEND
 *
 * Administra paginas, secciones, sliders, banners, menus, footer y bloques reutilizables.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Frontend extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA FRONTEND
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('frontend.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL PRINCIPAL DE FRONTEND ADMINISTRABLE
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Frontend';
        $this->template->content = View::forge('admin/frontend/index');
    }

    /**
     * DATA
     *
     * ENTREGA DEFINICIONES, OPCIONES Y REGISTROS FRONTEND EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'definitions' => $this->get_definitions(),
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando frontend: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar frontend.'], 500);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN REGISTRO FRONTEND
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('frontend.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $section = trim((string) \Arr::get($val, 'section', ''));
            $definitions = $this->get_definitions();
            if (!isset($definitions[$section])) {
                return $this->json_response(['error' => 'Seccion invalida.'], 422);
            }

            # SE PREPARAN DATOS
            $definition = $definitions[$section];
            $data = [];
            foreach ($definition['fields'] as $field) {
                $name = $field['name'];
                $type = \Arr::get($field, 'type', 'text');
                $value = \Arr::get($val, $name, \Arr::get($field, 'default', ''));

                if ($type === 'checkbox') {
                    $value = (int) (bool) $value;
                } elseif ($type === 'number') {
                    $value = (float) $value;
                } elseif ($type === 'integer') {
                    $value = (int) $value;
                } elseif ($type === 'color') {
                    $value = preg_match('/^#[0-9a-fA-F]{6}$/', trim((string) $value)) ? trim((string) $value) : \Arr::get($field, 'default', '#000000');
                } else {
                    $value = trim((string) $value);
                }

                if (in_array($name, ['content', 'seo_description'])) {
                    $value = $this->sanitize_rich_html($value);
                }

                if ($name === 'settings_json') {
                    $value = $this->normalize_json($value);
                }

                if ($name === 'custom_css') {
                    $value = $this->sanitize_custom_css($value);
                }

                $data[$name] = $value;
            }

            # VALIDACIONES MINIMAS
            foreach (\Arr::get($definition, 'required', []) as $required) {
                if (!isset($data[$required]) || $data[$required] === '') {
                    return $this->json_response(['error' => 'El campo '.$required.' es obligatorio.'], 422);
                }
            }

            # SE NORMALIZAN CODIGOS Y SLUGS
            if (isset($data['slug']) && $data['slug'] === '' && isset($data['title'])) {
                $data['slug'] = $this->slugify($data['title']);
            } elseif (isset($data['slug'])) {
                $data['slug'] = $this->slugify($data['slug']);
            }

            foreach (['code', 'section_key'] as $key) {
                if (isset($data[$key])) {
                    $data[$key] = $this->codeify($data[$key]);
                }
            }

            # SE BUSCA EL REGISTRO EXISTENTE O SE CREA UNO NUEVO
            $class = $definition['model'];
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $item = $class::find($id);
                if (!$item) {
                    return $this->json_response(['error' => 'Registro no encontrado.'], 404);
                }
                $item->set($data);
            } else {
                $item = $class::forge($data);
            }

            # SE GUARDA EL REGISTRO
            $item->save();

            # SI ES TEMA ACTIVO, SE DESACTIVAN LOS DEMAS
            if ($section === 'themes' && !empty($data['is_active'])) {
                \DB::update('core_frontend_themes')
                    ->set(['is_active' => 0, 'updated_at' => time()])
                    ->where('id', '!=', (int) $item->id)
                    ->execute();

                $item->is_active = 1;
                $item->save();
            }

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->json_response([
                'status' => 'ok',
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando frontend: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el registro.'], 400);
        }
    }

    /**
     * UPLOAD IMAGE
     *
     * SUBE UNA IMAGEN DEL FRONTEND ADMINISTRABLE
     *
     * @access  public
     * @return  Response
     */
    public function post_upload_image()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('frontend.access[edit]');

        try {
            # SE OBTIENE EL ARCHIVO
            $file = \Input::file('image');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona una imagen valida.'], 422);
            }

            # SE VALIDAN DATOS BASICOS
            $section = $this->codeify(\Input::post('section', 'frontend'));
            $field = $this->codeify(\Input::post('field', 'image'));
            $allowed_fields = ['media_path', 'image_path', 'logo_path', 'favicon_path'];
            if (!in_array($field, $allowed_fields)) {
                return $this->json_response(['error' => 'Destino de imagen invalido.'], 422);
            }

            # SE VALIDA EXTENSION Y MIME
            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                return $this->json_response(['error' => 'Solo se permiten imagenes JPG, PNG o WEBP.'], 422);
            }

            if (!@getimagesize((string) \Arr::get($file, 'tmp_name', ''))) {
                return $this->json_response(['error' => 'El archivo no parece ser una imagen valida.'], 422);
            }

            if ((int) \Arr::get($file, 'size', 0) > 5242880) {
                return $this->json_response(['error' => 'La imagen no puede superar 5 MB.'], 422);
            }

            # SE PREPARA DESTINO
            $relative_dir = 'assets/uploads/frontend/'.$section.'/'.date('Y').'/'.date('m');
            $absolute_dir = DOCROOT.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0755, true);
            }

            # SE GENERA NOMBRE SEGURO
            $base_name = pathinfo((string) \Arr::get($file, 'name', 'image'), PATHINFO_FILENAME);
            $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->slugify($base_name).'.'.$extension;
            $target = $absolute_dir.DS.$filename;

            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->json_response(['error' => 'No se pudo guardar la imagen.'], 400);
            }

            # SE REGRESA RUTA PUBLICA
            $path = str_replace('\\', '/', $relative_dir.'/'.$filename);
            return $this->json_response(['status' => 'ok', 'path' => $path]);
        } catch (\Exception $e) {
            \Log::error('Error subiendo imagen frontend: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo subir la imagen.'], 400);
        }
    }

    /**
     * GET DEFINITIONS
     *
     * DEFINE SECCIONES, MODELOS Y CAMPOS ADMINISTRABLES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_definitions()
    {
        # SE DEFINEN PIEZAS DEL FRONTEND
        return [
            'pages' => [
                'title' => 'Paginas',
                'model' => 'Model_Core_Frontend_Page',
                'table' => 'core_frontend_pages',
                'required' => ['title', 'slug'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Titulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'default' => ''],
                    ['name' => 'page_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [['value' => 'home', 'label' => 'Inicio'], ['value' => 'content', 'label' => 'Contenido'], ['value' => 'catalog', 'label' => 'Catalogo']], 'default' => 'content'],
                    ['name' => 'template_key', 'label' => 'Template', 'type' => 'text', 'default' => 'default'],
                    ['name' => 'seo_title', 'label' => 'SEO titulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'seo_description', 'label' => 'SEO descripcion', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'published', 'label' => 'Publicado', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'is_home', 'label' => 'Inicio', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'themes' => [
                'title' => 'Apariencia',
                'model' => 'Model_Core_Frontend_Theme',
                'table' => 'core_frontend_themes',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'layout_key', 'label' => 'Layout', 'type' => 'select_static', 'options' => [
                        ['value' => 'commerce_default', 'label' => 'Comercial default'],
                        ['value' => 'corporate', 'label' => 'Corporativo'],
                        ['value' => 'catalog_dense', 'label' => 'Catalogo denso'],
                    ], 'default' => 'commerce_default'],
                    ['name' => 'color_primary', 'label' => 'Color primario', 'type' => 'color', 'default' => '#0f766e'],
                    ['name' => 'color_secondary', 'label' => 'Color secundario', 'type' => 'color', 'default' => '#172033'],
                    ['name' => 'color_accent', 'label' => 'Color acento', 'type' => 'color', 'default' => '#b7791f'],
                    ['name' => 'color_background', 'label' => 'Fondo', 'type' => 'color', 'default' => '#ffffff'],
                    ['name' => 'color_surface', 'label' => 'Superficie', 'type' => 'color', 'default' => '#f4f7fa'],
                    ['name' => 'color_text', 'label' => 'Texto', 'type' => 'color', 'default' => '#172033'],
                    ['name' => 'color_muted', 'label' => 'Texto secundario', 'type' => 'color', 'default' => '#657084'],
                    ['name' => 'font_family', 'label' => 'Fuente base', 'type' => 'text', 'default' => 'Arial, Helvetica, sans-serif'],
                    ['name' => 'heading_font_family', 'label' => 'Fuente titulos', 'type' => 'text', 'default' => 'Arial, Helvetica, sans-serif'],
                    ['name' => 'logo_path', 'label' => 'Logo', 'type' => 'image', 'default' => ''],
                    ['name' => 'favicon_path', 'label' => 'Favicon', 'type' => 'image', 'default' => ''],
                    ['name' => 'header_style', 'label' => 'Header', 'type' => 'select_static', 'options' => [
                        ['value' => 'standard', 'label' => 'Estandar'],
                        ['value' => 'compact', 'label' => 'Compacto'],
                    ], 'default' => 'standard'],
                    ['name' => 'footer_style', 'label' => 'Footer', 'type' => 'select_static', 'options' => [
                        ['value' => 'standard', 'label' => 'Estandar'],
                        ['value' => 'minimal', 'label' => 'Minimal'],
                    ], 'default' => 'standard'],
                    ['name' => 'custom_css', 'label' => 'CSS custom controlado', 'type' => 'code_css', 'default' => ''],
                    ['name' => 'is_active', 'label' => 'Tema activo', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'sections' => [
                'title' => 'Secciones',
                'model' => 'Model_Core_Frontend_Section',
                'table' => 'core_frontend_sections',
                'required' => ['page_id', 'section_key'],
                'fields' => [
                    ['name' => 'page_id', 'label' => 'Pagina', 'type' => 'select', 'options' => 'pages', 'default' => 0],
                    ['name' => 'section_key', 'label' => 'Clave', 'type' => 'text', 'default' => ''],
                    ['name' => 'section_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [
                        ['value' => 'content', 'label' => 'Contenido'],
                        ['value' => 'content_image', 'label' => 'Contenido + imagen'],
                        ['value' => 'feature_grid', 'label' => 'Grid de atributos'],
                        ['value' => 'products', 'label' => 'Productos'],
                        ['value' => 'brands', 'label' => 'Marcas'],
                        ['value' => 'categories', 'label' => 'Categorias'],
                        ['value' => 'download_cards', 'label' => 'Descargas'],
                        ['value' => 'contact_info', 'label' => 'Contacto'],
                        ['value' => 'cta', 'label' => 'Llamado a accion'],
                        ['value' => 'banner', 'label' => 'Banner'],
                        ['value' => 'block', 'label' => 'Bloque'],
                    ], 'default' => 'content'],
                    ['name' => 'title', 'label' => 'Titulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'subtitle', 'label' => 'Subtitulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'content', 'label' => 'Contenido', 'type' => 'richtext', 'default' => ''],
                    ['name' => 'media_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'target_type', 'label' => 'Relacion', 'type' => 'select_static', 'options' => [['value' => 'none', 'label' => 'Ninguna'], ['value' => 'product', 'label' => 'Producto'], ['value' => 'category', 'label' => 'Categoria'], ['value' => 'tag', 'label' => 'Tag'], ['value' => 'block', 'label' => 'Bloque']], 'default' => 'none'],
                    ['name' => 'target_id', 'label' => 'ID relacion', 'type' => 'integer', 'default' => 0],
                    ['name' => 'settings_json', 'label' => 'Configuracion', 'type' => 'json', 'default' => ''],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'sliders' => [
                'title' => 'Sliders',
                'model' => 'Model_Core_Frontend_Slider',
                'table' => 'core_frontend_sliders',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'location', 'label' => 'Ubicacion', 'type' => 'text', 'default' => 'home'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'slider_items' => [
                'title' => 'Slides',
                'model' => 'Model_Core_Frontend_Slider_Item',
                'table' => 'core_frontend_slider_items',
                'required' => ['slider_id', 'title'],
                'fields' => [
                    ['name' => 'slider_id', 'label' => 'Slider', 'type' => 'select', 'options' => 'sliders', 'default' => 0],
                    ['name' => 'title', 'label' => 'Titulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'subtitle', 'label' => 'Subtitulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'image_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'button_text', 'label' => 'Boton', 'type' => 'text', 'default' => ''],
                    ['name' => 'button_url', 'label' => 'URL boton', 'type' => 'text', 'default' => ''],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'banners' => [
                'title' => 'Banners',
                'model' => 'Model_Core_Frontend_Banner',
                'table' => 'core_frontend_banners',
                'required' => ['code', 'title'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'title', 'label' => 'Titulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'location', 'label' => 'Ubicacion', 'type' => 'text', 'default' => 'home'],
                    ['name' => 'image_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'url', 'label' => 'URL', 'type' => 'text', 'default' => ''],
                    ['name' => 'target_type', 'label' => 'Relacion', 'type' => 'text', 'default' => 'none'],
                    ['name' => 'target_id', 'label' => 'ID relacion', 'type' => 'integer', 'default' => 0],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'menus' => [
                'title' => 'Menus',
                'model' => 'Model_Core_Frontend_Menu',
                'table' => 'core_frontend_menus',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'location', 'label' => 'Ubicacion', 'type' => 'text', 'default' => 'header'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'menu_items' => [
                'title' => 'Items menu',
                'model' => 'Model_Core_Frontend_Menu_Item',
                'table' => 'core_frontend_menu_items',
                'required' => ['menu_id', 'label'],
                'fields' => [
                    ['name' => 'menu_id', 'label' => 'Menu', 'type' => 'select', 'options' => 'menus', 'default' => 0],
                    ['name' => 'parent_id', 'label' => 'Padre', 'type' => 'integer', 'default' => 0],
                    ['name' => 'label', 'label' => 'Etiqueta', 'type' => 'text', 'default' => ''],
                    ['name' => 'url', 'label' => 'URL', 'type' => 'text', 'default' => ''],
                    ['name' => 'target_type', 'label' => 'Tipo destino', 'type' => 'text', 'default' => 'url'],
                    ['name' => 'target_id', 'label' => 'ID destino', 'type' => 'integer', 'default' => 0],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'footer_columns' => [
                'title' => 'Footer',
                'model' => 'Model_Core_Frontend_Footer_Column',
                'table' => 'core_frontend_footer_columns',
                'required' => ['title'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Titulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'content', 'label' => 'Contenido', 'type' => 'richtext', 'default' => ''],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'blocks' => [
                'title' => 'Bloques',
                'model' => 'Model_Core_Frontend_Block',
                'table' => 'core_frontend_blocks',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'block_type', 'label' => 'Tipo', 'type' => 'text', 'default' => 'html'],
                    ['name' => 'content', 'label' => 'Contenido', 'type' => 'richtext', 'default' => ''],
                    ['name' => 'settings_json', 'label' => 'Configuracion', 'type' => 'json', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
        ];
    }

    /**
     * GET ALL ITEMS
     *
     * OBTIENE REGISTROS DE TODAS LAS SECCIONES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_all_items()
    {
        # SE INICIALIZA RESPUESTA
        $items = [];

        # SE RECORREN SECCIONES
        foreach ($this->get_definitions() as $key => $definition) {
            $class = $definition['model'];
            $items[$key] = [];

            foreach ($class::query()->order_by('id', 'desc')->get() as $row) {
                $items[$key][] = $row->to_array();
            }
        }

        return $items;
    }

    /**
     * GET OPTIONS
     *
     * OBTIENE OPCIONES PARA SELECTS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        # SE PREPARAN OPCIONES DINAMICAS
        return [
            'pages' => $this->select_options('core_frontend_pages', 'id', 'title'),
            'sliders' => $this->select_options('core_frontend_sliders', 'id', 'name'),
            'menus' => $this->select_options('core_frontend_menus', 'id', 'name'),
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASICOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE INICIALIZAN CONTADORES
        $stats = [];

        # SE RECORREN TABLAS
        foreach ($this->get_definitions() as $key => $definition) {
            $stats[$key] = (int) \DB::count_records($definition['table']);
        }

        return $stats;
    }

    /**
     * SELECT OPTIONS
     *
     * FORMATEA OPCIONES ACTIVAS
     *
     * @access  protected
     * @return  Array
     */
    protected function select_options($table, $value_field, $label_field)
    {
        # SE CONSULTAN REGISTROS ACTIVOS
        $rows = \DB::select($value_field, $label_field)
            ->from($table)
            ->where('active', '=', 1)
            ->order_by($label_field, 'asc')
            ->execute();

        # SE FORMATEAN OPCIONES
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }

        return $options;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS FRONTEND EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach ($this->get_definitions() as $definition) {
            if (!\DBUtil::table_exists($definition['table'])) {
                throw new \RuntimeException('Falta ejecutar migraciones de frontend.');
            }
        }
    }

    /**
     * SLUGIFY
     *
     * NORMALIZA SLUGS PARA URL PUBLICAS
     *
     * @access  protected
     * @return  String
     */
    protected function slugify($value)
    {
        # SE NORMALIZA EL VALOR RECIBIDO
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    /**
     * CODEIFY
     *
     * NORMALIZA CODIGOS INTERNOS
     *
     * @access  protected
     * @return  String
     */
    protected function codeify($value)
    {
        # SE NORMALIZA EL CODIGO RECIBIDO
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }

    /**
     * SANITIZE CUSTOM CSS
     *
     * LIMITA CSS PERSONALIZADO PARA EVITAR CARGAS REMOTAS O EXPRESIONES PELIGROSAS
     *
     * @access  protected
     * @param   string  $css
     * @return  string
     */
    protected function sanitize_custom_css($css)
    {
        # SE NORMALIZA EL CSS RECIBIDO
        $css = trim((string) $css);

        # SE BLOQUEAN PATRONES QUE NO DEBEN SER ADMINISTRABLES DESDE UI
        $blocked = [
            '/@import/i',
            '/javascript\s*:/i',
            '/expression\s*\(/i',
            '/behavior\s*:/i',
            '/<\s*\/?\s*script/i',
        ];

        foreach ($blocked as $pattern) {
            $css = preg_replace($pattern, '', $css);
        }

        return $css;
    }

    /**
     * SANITIZE RICH HTML
     *
     * LIMPIA HTML DE EDITORES RICOS SIN CONVERTIRLO EN TEXTO PLANO
     *
     * @access  protected
     * @param   string  $html
     * @return  string
     */
    protected function sanitize_rich_html($html)
    {
        # SE NORMALIZA EL HTML RECIBIDO
        $html = trim((string) $html);

        # SE RETIRAN ETIQUETAS Y ATRIBUTOS DE ALTO RIESGO
        $html = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $html);
        $html = preg_replace('/<\s*iframe[^>]*>.*?<\s*\/\s*iframe\s*>/is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);

        # SE PERMITEN SOLO ETIQUETAS BASICAS DE CONTENIDO
        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><a><span>');
    }

    /**
     * NORMALIZE JSON
     *
     * VALIDA Y FORMATEA JSON DE CONFIGURACIONES AVANZADAS
     *
     * @access  protected
     * @param   string  $json
     * @return  string
     */
    protected function normalize_json($json)
    {
        # SI VIENE VACIO, SE RESPETA VACIO
        $json = trim((string) $json);
        if ($json === '') {
            return '';
        }

        # SE VALIDA JSON
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('JSON invalido: '.json_last_error_msg());
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}
