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
     * PREVIEW
     *
     * MUESTRA UNA PAGINA DEL CMS EN MODO ADMINISTRATIVO, AUNQUE NO ESTE PUBLICADA.
     *
     * @access  public
     * @param   int  $page_id
     * @return  Response
     */
    public function action_preview($page_id = null)
    {
        $this->require_access('frontend.access[view]');

        try {
            $this->assert_schema_ready();

            $page = Model_Core_Frontend_Page::find((int) $page_id);
            if (!$page) {
                throw new \HttpNotFoundException;
            }

            $theme = $this->get_preview_theme();
            $company = class_exists('Model_Core_Company') ? Model_Core_Company::get_current() : null;
            $location = ((int) $page->is_home === 1 || (string) $page->page_type === 'home') ? 'home' : (string) $page->slug;
            $slider = $this->get_preview_slider($location);

            $page_data = [
                'page' => $page,
                'sections' => $this->get_preview_sections($page->id),
                'slider' => $slider,
                'slider_items' => !empty($slider) ? $this->get_preview_slider_items($slider->id) : [],
                'banners' => $this->get_preview_banners($location),
                'featured_products' => ($location === 'home') ? $this->get_preview_featured_products() : [],
                'featured_brands' => $this->get_preview_featured_brands(),
                'contact_form_enabled' => false,
                'contact_success' => '',
                'contact_error' => '',
                'google_maps_embed_url' => class_exists('Helper_Core_Web') ? Helper_Core_Web::google_maps_embed_url() : '',
                'captcha_html' => '',
                'admin_preview' => true,
                'admin_preview_message' => 'Vista previa administrativa: esta página puede no estar publicada.',
            ];

            $content = View::forge('frontend/page', $page_data, false)->render();
            $template = View::forge('frontend/template', [
                'title' => $page->seo_title ?: $page->title,
                'seo_description' => $page->seo_description ?: $this->preview_default_seo_description($theme, $company),
                'site_name' => $this->preview_site_name($theme, $company),
                'canonical_url' => \Uri::base(false).'admin/frontend/preview/'.(int) $page->id,
                'menu_items' => $this->get_preview_menu_items('header'),
                'footer_columns' => $this->get_preview_footer_columns(),
                'theme' => $theme,
                'frontend_user' => [
                    'logged_in' => false,
                    'name' => '',
                ],
                'cart_count' => 0,
                'cookie_banner' => '',
                'frontend_extra_scripts' => '',
            ], false);
            $template->set('content', $content, false);

            \Log::info('Frontend CMS: vista previa administrativa de pagina '.$page->id.' solicitada por usuario '.$this->user_id.'.');

            return \Response::forge($template);
        } catch (\HttpNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error cargando vista previa frontend: '.$e->getMessage());
            throw new \HttpServerErrorException;
        }
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
                return $this->json_response(['error' => 'Sección inválida.'], 422);
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
                return $this->json_response(['error' => 'Selecciona una imagen válida.'], 422);
            }

            # SE VALIDAN DATOS BASICOS
            $section = $this->codeify(\Input::post('section', 'frontend'));
            $field = $this->codeify(\Input::post('field', 'image'));
            $allowed_fields = ['media_path', 'image_path', 'logo_path', 'favicon_path'];
            if (!in_array($field, $allowed_fields)) {
                return $this->json_response(['error' => 'Destino de imagen inválido.'], 422);
            }

            # SE VALIDA EXTENSION Y MIME
            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                return $this->json_response(['error' => 'Solo se permiten imágenes JPG, PNG o WEBP.'], 422);
            }

            if (!@getimagesize((string) \Arr::get($file, 'tmp_name', ''))) {
                return $this->json_response(['error' => 'El archivo no parece ser una imagen válida.'], 422);
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
     * MOVE SECTION
     *
     * REORDENA UNA SECCION DENTRO DE SU PAGINA SIN CAMBIAR SU CONTENIDO.
     *
     * @access  public
     * @return  Response
     */
    public function post_move_section()
    {
        $this->require_access('frontend.access[edit]');

        $val = (array) \Input::json();
        $section_id = (int) \Arr::get($val, 'id', 0);
        $direction = trim((string) \Arr::get($val, 'direction', ''));

        if ($section_id < 1 || !in_array($direction, ['up', 'down'], true)) {
            return $this->json_response(['error' => 'Movimiento de sección inválido.'], 422);
        }

        $transaction_started = false;
        try {
            $this->assert_schema_ready();

            $section = Model_Core_Frontend_Section::find($section_id);
            if (!$section) {
                return $this->json_response(['error' => 'Sección no encontrada.'], 404);
            }

            $sections = array_values(Model_Core_Frontend_Section::query()
                ->where('page_id', (int) $section->page_id)
                ->order_by('sort_order', 'asc')
                ->order_by('id', 'asc')
                ->get());

            $index = -1;
            foreach ($sections as $position => $candidate) {
                if ((int) $candidate->id === (int) $section->id) {
                    $index = (int) $position;
                    break;
                }
            }

            $target_index = $direction === 'up' ? $index - 1 : $index + 1;
            if ($index < 0 || !isset($sections[$target_index])) {
                return $this->json_response([
                    'status' => 'ok',
                    'message' => 'La sección ya está en el límite del orden.',
                    'items' => $this->get_all_items(),
                    'options' => $this->get_options(),
                    'stats' => $this->get_stats(),
                ]);
            }

            \DB::start_transaction();
            $transaction_started = true;

            $ordered = $sections;
            $moving = $ordered[$index];
            array_splice($ordered, $index, 1);
            array_splice($ordered, $target_index, 0, [$moving]);

            foreach ($ordered as $position => $ordered_section) {
                $ordered_section->sort_order = (($position + 1) * 10);
                $ordered_section->save();
            }

            \DB::commit_transaction();

            \Log::info('Frontend CMS: seccion '.$section_id.' movida '.$direction.' en pagina '.(int) $section->page_id.'.');

            return $this->json_response([
                'status' => 'ok',
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            if ($transaction_started) {
                \DB::rollback_transaction();
            }
            \Log::error('Error reordenando seccion frontend: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo reordenar la sección.'], 400);
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
                'title' => 'Páginas',
                'model' => 'Model_Core_Frontend_Page',
                'table' => 'core_frontend_pages',
                'required' => ['title', 'slug'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'default' => ''],
                    ['name' => 'slug', 'label' => 'URL amigable', 'type' => 'text', 'default' => '', 'help' => 'Texto corto usado en la dirección pública de la página.'],
                    ['name' => 'page_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [['value' => 'home', 'label' => 'Inicio'], ['value' => 'content', 'label' => 'Contenido'], ['value' => 'catalog', 'label' => 'Catálogo']], 'default' => 'content'],
                    ['name' => 'template_key', 'label' => 'Plantilla', 'type' => 'text', 'default' => 'default', 'help' => 'Diseño base usado para mostrar esta página.'],
                    ['name' => 'seo_title', 'label' => 'SEO título', 'type' => 'text', 'default' => '', 'help' => 'Título que aparece en buscadores.'],
                    ['name' => 'seo_description', 'label' => 'SEO descripción', 'type' => 'textarea', 'default' => '', 'help' => 'Resumen que aparece en buscadores.'],
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
                    ['name' => 'code', 'label' => 'Código', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'layout_key', 'label' => 'Layout', 'type' => 'select_static', 'options' => [
                        ['value' => 'commerce_default', 'label' => 'Comercial limpio'],
                        ['value' => 'corporate', 'label' => 'Corporativo institucional'],
                        ['value' => 'catalog_dense', 'label' => 'Catálogo denso'],
                        ['value' => 'editorial_showcase', 'label' => 'Editorial / marca'],
                        ['value' => 'industrial_b2b', 'label' => 'Industrial B2B'],
                    ], 'default' => 'commerce_default'],
                    ['name' => 'color_primary', 'label' => 'Color primario', 'type' => 'color', 'default' => '#0f766e'],
                    ['name' => 'color_secondary', 'label' => 'Color secundario', 'type' => 'color', 'default' => '#172033'],
                    ['name' => 'color_accent', 'label' => 'Color acento', 'type' => 'color', 'default' => '#b7791f'],
                    ['name' => 'color_background', 'label' => 'Fondo', 'type' => 'color', 'default' => '#ffffff'],
                    ['name' => 'color_surface', 'label' => 'Superficie', 'type' => 'color', 'default' => '#f4f7fa'],
                    ['name' => 'color_text', 'label' => 'Texto', 'type' => 'color', 'default' => '#172033'],
                    ['name' => 'color_muted', 'label' => 'Texto secundario', 'type' => 'color', 'default' => '#657084'],
                    ['name' => 'font_family', 'label' => 'Fuente base', 'type' => 'text', 'default' => 'Arial, Helvetica, sans-serif'],
                    ['name' => 'heading_font_family', 'label' => 'Fuente títulos', 'type' => 'text', 'default' => 'Arial, Helvetica, sans-serif'],
                    ['name' => 'logo_path', 'label' => 'Logo', 'type' => 'image', 'default' => ''],
                    ['name' => 'favicon_path', 'label' => 'Favicon', 'type' => 'image', 'default' => ''],
                    ['name' => 'site_name', 'label' => 'Nombre del sitio', 'type' => 'text', 'default' => ''],
                    ['name' => 'seo_title_suffix', 'label' => 'Sufijo SEO', 'type' => 'text', 'default' => ''],
                    ['name' => 'default_seo_description', 'label' => 'Descripción SEO predeterminada', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'og_image_path', 'label' => 'Imagen social', 'type' => 'image', 'default' => ''],
                    ['name' => 'robots', 'label' => 'Robots', 'type' => 'select_static', 'options' => [
                        ['value' => 'index,follow', 'label' => 'Indexar y seguir'],
                        ['value' => 'noindex,nofollow', 'label' => 'No indexar'],
                    ], 'default' => 'index,follow'],
                    ['name' => 'header_style', 'label' => 'Header', 'type' => 'select_static', 'options' => [
                        ['value' => 'standard', 'label' => 'Estándar'],
                        ['value' => 'compact', 'label' => 'Compacto'],
                    ], 'default' => 'standard'],
                    ['name' => 'footer_style', 'label' => 'Footer', 'type' => 'select_static', 'options' => [
                        ['value' => 'standard', 'label' => 'Estándar'],
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
                    ['name' => 'page_id', 'label' => 'Página', 'type' => 'select', 'options' => 'pages', 'default' => 0],
                    ['name' => 'section_key', 'label' => 'Código interno', 'type' => 'text', 'default' => ''],
                    ['name' => 'section_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [
                        ['value' => 'content', 'label' => 'Contenido'],
                        ['value' => 'content_image', 'label' => 'Texto con imagen'],
                        ['value' => 'feature_grid', 'label' => 'Servicios'],
                        ['value' => 'products', 'label' => 'Productos'],
                        ['value' => 'brands', 'label' => 'Marcas'],
                        ['value' => 'categories', 'label' => 'Categorías'],
                        ['value' => 'download_cards', 'label' => 'Descargas'],
                        ['value' => 'contact_info', 'label' => 'Contacto'],
                        ['value' => 'cta', 'label' => 'Llamado a acción'],
                        ['value' => 'banner', 'label' => 'Banner'],
                        ['value' => 'block', 'label' => 'Bloque reutilizable'],
                    ], 'default' => 'content'],
                    ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'default' => ''],
                    ['name' => 'subtitle', 'label' => 'Subtítulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'content', 'label' => 'Contenido', 'type' => 'richtext', 'default' => ''],
                    ['name' => 'media_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'target_type', 'label' => 'Relación', 'type' => 'select_static', 'options' => [['value' => 'none', 'label' => 'Ninguna'], ['value' => 'product', 'label' => 'Producto'], ['value' => 'category', 'label' => 'Categoría'], ['value' => 'tag', 'label' => 'Etiqueta'], ['value' => 'block', 'label' => 'Bloque']], 'default' => 'none'],
                    ['name' => 'target_id', 'label' => 'ID relación', 'type' => 'integer', 'default' => 0],
                    ['name' => 'settings_json', 'label' => 'Configuración avanzada', 'type' => 'json', 'default' => ''],
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
                    ['name' => 'code', 'label' => 'Código', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'location', 'label' => 'Ubicación', 'type' => 'text', 'default' => 'home'],
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
                    ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'default' => ''],
                    ['name' => 'subtitle', 'label' => 'Subtítulo', 'type' => 'text', 'default' => ''],
                    ['name' => 'image_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'button_text', 'label' => 'Botón', 'type' => 'text', 'default' => ''],
                    ['name' => 'button_url', 'label' => 'URL botón', 'type' => 'text', 'default' => ''],
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
                    ['name' => 'code', 'label' => 'Código', 'type' => 'text', 'default' => ''],
                    ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'default' => ''],
                    ['name' => 'location', 'label' => 'Ubicación', 'type' => 'text', 'default' => 'home'],
                    ['name' => 'image_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'url', 'label' => 'URL', 'type' => 'text', 'default' => ''],
                    ['name' => 'target_type', 'label' => 'Relación', 'type' => 'text', 'default' => 'none'],
                    ['name' => 'target_id', 'label' => 'ID relación', 'type' => 'integer', 'default' => 0],
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
                    ['name' => 'code', 'label' => 'Código', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'location', 'label' => 'Ubicación', 'type' => 'text', 'default' => 'header'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'menu_items' => [
                'title' => 'Elementos de menú',
                'model' => 'Model_Core_Frontend_Menu_Item',
                'table' => 'core_frontend_menu_items',
                'required' => ['menu_id', 'label'],
                'fields' => [
                    ['name' => 'menu_id', 'label' => 'Menú', 'type' => 'select', 'options' => 'menus', 'default' => 0],
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
                    ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'default' => ''],
                    ['name' => 'column_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [
                        ['value' => 'brand', 'label' => 'Marca / resumen'],
                        ['value' => 'contact', 'label' => 'Contacto'],
                        ['value' => 'links', 'label' => 'Links'],
                        ['value' => 'legal', 'label' => 'Legales'],
                        ['value' => 'social', 'label' => 'Redes sociales'],
                        ['value' => 'badges', 'label' => 'Distintivos'],
                        ['value' => 'text', 'label' => 'Texto libre'],
                    ], 'default' => 'text'],
                    ['name' => 'icon', 'label' => 'Icono', 'type' => 'text', 'default' => ''],
                    ['name' => 'url', 'label' => 'URL principal', 'type' => 'text', 'default' => ''],
                    ['name' => 'content', 'label' => 'Contenido', 'type' => 'richtext', 'default' => ''],
                    ['name' => 'settings_json', 'label' => 'Configuración avanzada', 'type' => 'json', 'default' => ''],
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
                    ['name' => 'code', 'label' => 'Código', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'block_type', 'label' => 'Tipo', 'type' => 'text', 'default' => 'html'],
                    ['name' => 'content', 'label' => 'Contenido', 'type' => 'richtext', 'default' => ''],
                    ['name' => 'settings_json', 'label' => 'Configuración avanzada', 'type' => 'json', 'default' => ''],
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
            'blocks' => $this->select_options('core_frontend_blocks', 'id', 'name'),
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
     * GET PREVIEW SECTIONS
     *
     * OBTIENE SECCIONES ACTIVAS PARA LA VISTA PREVIA ADMINISTRATIVA.
     *
     * @access  protected
     * @param   int  $page_id
     * @return  array
     */
    protected function get_preview_sections($page_id)
    {
        return Model_Core_Frontend_Section::query()
            ->where('page_id', (int) $page_id)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * GET PREVIEW THEME
     *
     * OBTIENE EL TEMA ACTIVO PARA RENDERIZAR LA MISMA PLANTILLA PUBLICA.
     *
     * @access  protected
     * @return  Model_Core_Frontend_Theme|null
     */
    protected function get_preview_theme()
    {
        if (!class_exists('Model_Core_Frontend_Theme') || !\DBUtil::table_exists('core_frontend_themes')) {
            return null;
        }

        return Model_Core_Frontend_Theme::get_active();
    }

    /**
     * GET PREVIEW MENU ITEMS
     *
     * OBTIENE ELEMENTOS ACTIVOS DEL MENU PUBLICO.
     *
     * @access  protected
     * @param   string  $location
     * @return  array
     */
    protected function get_preview_menu_items($location = 'header')
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
            ->where('menu_id', (int) $menu->id)
            ->where('parent_id', 0)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * GET PREVIEW FOOTER COLUMNS
     *
     * OBTIENE COLUMNAS ACTIVAS DEL FOOTER PUBLICO.
     *
     * @access  protected
     * @return  array
     */
    protected function get_preview_footer_columns()
    {
        $columns = Model_Core_Frontend_Footer_Column::query()
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();

        foreach ($columns as $column) {
            $column->settings = $this->decode_preview_settings(isset($column->settings_json) ? (string) $column->settings_json : '');
        }

        return $columns;
    }

    /**
     * GET PREVIEW SLIDER
     *
     * OBTIENE SLIDER ACTIVO PARA LA UBICACION PUBLICA.
     *
     * @access  protected
     * @param   string  $location
     * @return  Model_Core_Frontend_Slider|null
     */
    protected function get_preview_slider($location)
    {
        return Model_Core_Frontend_Slider::query()
            ->where('location', (string) $location)
            ->where('active', 1)
            ->order_by('id', 'asc')
            ->get_one();
    }

    /**
     * GET PREVIEW SLIDER ITEMS
     *
     * OBTIENE DIAPOSITIVAS ACTIVAS DE UN SLIDER.
     *
     * @access  protected
     * @param   int  $slider_id
     * @return  array
     */
    protected function get_preview_slider_items($slider_id)
    {
        return Model_Core_Frontend_Slider_Item::query()
            ->where('slider_id', (int) $slider_id)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * GET PREVIEW BANNERS
     *
     * OBTIENE BANNERS ACTIVOS PARA LA UBICACION PUBLICA.
     *
     * @access  protected
     * @param   string  $location
     * @return  array
     */
    protected function get_preview_banners($location)
    {
        return Model_Core_Frontend_Banner::query()
            ->where('location', (string) $location)
            ->where('active', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();
    }

    /**
     * GET PREVIEW FEATURED PRODUCTS
     *
     * OBTIENE PRODUCTOS DESTACADOS SI EL CATALOGO EXISTE.
     *
     * @access  protected
     * @return  array
     */
    protected function get_preview_featured_products()
    {
        if (!\DBUtil::table_exists('core_commerce_products')) {
            return [];
        }

        return \DB::select('id', 'sku', 'name', 'slug', 'short_description', 'currency_code', 'price', 'main_image_path')
            ->from('core_commerce_products')
            ->where('active', 1)
            ->where('published', 1)
            ->where('product_type', 'product')
            ->where('is_internal_service', 0)
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
     * GET PREVIEW FEATURED BRANDS
     *
     * OBTIENE MARCAS DESTACADAS SI EL CATALOGO EXISTE.
     *
     * @access  protected
     * @return  array
     */
    protected function get_preview_featured_brands()
    {
        if (!\DBUtil::table_exists('core_commerce_brands')) {
            return [];
        }

        return \DB::select('id', 'name', 'slug', 'description', 'logo_path')
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
     * PREVIEW SITE NAME
     *
     * RESUELVE NOMBRE PUBLICO PARA LA PLANTILLA DE PREVIEW.
     *
     * @access  protected
     * @param   mixed  $theme
     * @param   mixed  $company
     * @return  string
     */
    protected function preview_site_name($theme, $company)
    {
        return ($theme && !empty($theme->site_name)) ? (string) $theme->site_name : (string) ($company ? $company->name : 'Core-App');
    }

    /**
     * PREVIEW DEFAULT SEO DESCRIPTION
     *
     * RESUELVE DESCRIPCION SEO DEFAULT PARA PREVIEW.
     *
     * @access  protected
     * @param   mixed  $theme
     * @param   mixed  $company
     * @return  string
     */
    protected function preview_default_seo_description($theme, $company)
    {
        if ($theme && !empty($theme->default_seo_description)) {
            return (string) $theme->default_seo_description;
        }

        return $company && !empty($company->legal_name) ? (string) $company->legal_name : '';
    }

    /**
     * DECODE PREVIEW SETTINGS
     *
     * DECODIFICA JSON DE CONFIGURACION DE FOOTER.
     *
     * @access  protected
     * @param   string  $json
     * @return  array
     */
    protected function decode_preview_settings($json)
    {
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : [];
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
            throw new \InvalidArgumentException('JSON inválido: '.json_last_error_msg());
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}
