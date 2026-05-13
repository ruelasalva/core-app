<?php

/**
 * CONTROLADOR ADMIN_COMMERCE
 *
 * Administra catalogos comerciales, productos y banderas de publicacion.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Commerce extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA COMERCIAL
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('commerce.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL PRINCIPAL DE CATALOGOS COMERCIALES
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Comercial';
        $this->template->content = View::forge('admin/commerce/index');
    }

    /**
     * DATA
     *
     * ENTREGA DEFINICIONES, OPCIONES Y REGISTROS COMERCIALES EN JSON
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
            \Log::error('Error cargando comercial: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar el modulo comercial.'], 500);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN REGISTRO COMERCIAL
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('commerce.access[edit]');

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
                } else {
                    $value = trim((string) $value);
                }

                $data[$name] = $value;
            }

            # VALIDACIONES MINIMAS
            foreach (\Arr::get($definition, 'required', []) as $required) {
                if (!isset($data[$required]) || $data[$required] === '') {
                    return $this->json_response(['error' => 'El campo '.$required.' es obligatorio.'], 422);
                }
            }

            # SE NORMALIZAN SLUGS
            if (isset($data['slug']) && $data['slug'] === '' && isset($data['name'])) {
                $data['slug'] = $this->slugify($data['name']);
            } elseif (isset($data['slug'])) {
                $data['slug'] = $this->slugify($data['slug']);
            }

            if (isset($data['code'])) {
                $data['code'] = $this->codeify($data['code']);
            }

            if (isset($data['sku'])) {
                $data['sku'] = strtoupper(trim($data['sku']));
            }

            # VALIDAR RELACIONES DE PRODUCTO
            if ($section === 'product_relations' && (int) $data['product_id'] === (int) $data['related_product_id']) {
                return $this->json_response(['error' => 'El producto relacionado debe ser distinto al producto principal.'], 422);
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

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->json_response([
                'status' => 'ok',
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando comercial: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el registro.'], 400);
        }
    }

    /**
     * UPLOAD IMAGE
     *
     * SUBE UNA IMAGEN COMERCIAL Y REGRESA LA RUTA PUBLICA RELATIVA
     *
     * @access  public
     * @return  Response
     */
    public function post_upload_image()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('commerce.access[edit]');

        try {
            # SE OBTIENE EL ARCHIVO
            $file = \Input::file('image');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona una imagen valida.'], 422);
            }

            # SE VALIDAN DATOS BASICOS
            $section = $this->codeify(\Input::post('section', 'products'));
            $field = $this->codeify(\Input::post('field', 'image'));
            $allowed_sections = ['brands', 'categories', 'subcategories', 'products', 'product_images'];
            $allowed_fields = ['logo_path', 'image_path', 'main_image_path'];

            if (!in_array($section, $allowed_sections) || !in_array($field, $allowed_fields)) {
                return $this->json_response(['error' => 'Destino de imagen invalido.'], 422);
            }

            # SE VALIDA EXTENSION Y MIME
            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($extension, $allowed_extensions)) {
                return $this->json_response(['error' => 'Solo se permiten imagenes JPG, PNG o WEBP.'], 422);
            }

            $image_info = @getimagesize((string) \Arr::get($file, 'tmp_name', ''));
            if (!$image_info) {
                return $this->json_response(['error' => 'El archivo no parece ser una imagen valida.'], 422);
            }

            # SE VALIDA TAMANO MAXIMO
            if ((int) \Arr::get($file, 'size', 0) > 5242880) {
                return $this->json_response(['error' => 'La imagen no puede superar 5 MB.'], 422);
            }

            # SE PREPARA LA RUTA DE DESTINO
            $relative_dir = 'assets/uploads/commerce/'.$section.'/'.date('Y').'/'.date('m');
            $absolute_dir = DOCROOT.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0755, true);
            }

            # SE GENERA NOMBRE SEGURO
            $base_name = pathinfo((string) \Arr::get($file, 'name', 'image'), PATHINFO_FILENAME);
            $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->slugify($base_name).'.'.$extension;
            $target = $absolute_dir.DS.$filename;

            # SE MUEVE EL ARCHIVO
            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->json_response(['error' => 'No se pudo guardar la imagen.'], 400);
            }

            # SE REGRESA LA RUTA PUBLICA
            $path = str_replace('\\', '/', $relative_dir.'/'.$filename);
            return $this->json_response(['status' => 'ok', 'path' => $path]);
        } catch (\Exception $e) {
            \Log::error('Error subiendo imagen comercial: '.$e->getMessage());
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
        # SE DEFINEN CATALOGOS COMERCIALES
        return [
            'brands' => [
                'title' => 'Marcas',
                'model' => 'Model_Core_Commerce_Brand',
                'table' => 'core_commerce_brands',
                'required' => ['name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'default' => ''],
                    ['name' => 'description', 'label' => 'Descripcion', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'logo_path', 'label' => 'Logo', 'type' => 'image', 'default' => ''],
                    ['name' => 'show_in_home', 'label' => 'Mostrar inicio', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'categories' => [
                'title' => 'Categorias',
                'model' => 'Model_Core_Commerce_Category',
                'table' => 'core_commerce_categories',
                'required' => ['name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'default' => ''],
                    ['name' => 'description', 'label' => 'Descripcion', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'image_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'show_in_home', 'label' => 'Mostrar inicio', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'subcategories' => [
                'title' => 'Subcategorias',
                'model' => 'Model_Core_Commerce_Subcategory',
                'table' => 'core_commerce_subcategories',
                'required' => ['category_id', 'name'],
                'fields' => [
                    ['name' => 'category_id', 'label' => 'Categoria', 'type' => 'select', 'options' => 'categories', 'default' => 0],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'default' => ''],
                    ['name' => 'description', 'label' => 'Descripcion', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'image_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'show_in_home', 'label' => 'Mostrar inicio', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'tags' => [
                'title' => 'Tags',
                'model' => 'Model_Core_Commerce_Tag',
                'table' => 'core_commerce_tags',
                'required' => ['name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'default' => ''],
                    ['name' => 'tag_type', 'label' => 'Tipo', 'type' => 'text', 'default' => 'general'],
                    ['name' => 'color', 'label' => 'Color', 'type' => 'text', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'products' => [
                'title' => 'Productos',
                'model' => 'Model_Core_Commerce_Product',
                'table' => 'core_commerce_products',
                'required' => ['sku', 'name'],
                'fields' => [
                    ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'default' => ''],
                    ['name' => 'short_description', 'label' => 'Descripcion corta', 'type' => 'text', 'default' => ''],
                    ['name' => 'description', 'label' => 'Descripcion', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'brand_id', 'label' => 'Marca', 'type' => 'select', 'options' => 'brands', 'default' => 0],
                    ['name' => 'category_id', 'label' => 'Categoria', 'type' => 'select', 'options' => 'categories', 'default' => 0],
                    ['name' => 'subcategory_id', 'label' => 'Subcategoria', 'type' => 'select', 'options' => 'subcategories', 'default' => 0],
                    ['name' => 'unit_code', 'label' => 'Unidad', 'type' => 'select', 'options' => 'units', 'default' => 'pieza'],
                    ['name' => 'currency_code', 'label' => 'Moneda', 'type' => 'select', 'options' => 'currencies', 'default' => 'MXN'],
                    ['name' => 'price', 'label' => 'Precio', 'type' => 'number', 'default' => 0],
                    ['name' => 'cost', 'label' => 'Costo', 'type' => 'number', 'default' => 0],
                    ['name' => 'tax_code', 'label' => 'Impuesto', 'type' => 'select', 'options' => 'taxes', 'default' => 'iva_16'],
                    ['name' => 'main_image_path', 'label' => 'Imagen principal', 'type' => 'image', 'default' => ''],
                    ['name' => 'show_in_home', 'label' => 'Mostrar inicio', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'featured', 'label' => 'Destacado', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'published', 'label' => 'Publicado', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                ],
            ],
            'price_lists' => [
                'title' => 'Listas de precios',
                'model' => 'Model_Core_Commerce_Price_List',
                'table' => 'core_commerce_price_lists',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'description', 'label' => 'Descripcion', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'currency_code', 'label' => 'Moneda', 'type' => 'select', 'options' => 'currencies', 'default' => 'MXN'],
                    ['name' => 'is_default', 'label' => 'Predeterminada', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'priority', 'label' => 'Prioridad', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'product_prices' => [
                'title' => 'Precios por producto',
                'model' => 'Model_Core_Commerce_Product_Price',
                'table' => 'core_commerce_product_prices',
                'required' => ['product_id', 'price_list_id', 'price'],
                'fields' => [
                    ['name' => 'product_id', 'label' => 'Producto', 'type' => 'select', 'options' => 'products', 'default' => 0],
                    ['name' => 'price_list_id', 'label' => 'Lista', 'type' => 'select', 'options' => 'price_lists', 'default' => 0],
                    ['name' => 'currency_code', 'label' => 'Moneda', 'type' => 'select', 'options' => 'currencies', 'default' => 'MXN'],
                    ['name' => 'price', 'label' => 'Precio', 'type' => 'number', 'default' => 0],
                    ['name' => 'min_quantity', 'label' => 'Cantidad minima', 'type' => 'number', 'default' => 1],
                    ['name' => 'max_quantity', 'label' => 'Cantidad maxima', 'type' => 'number', 'default' => ''],
                    ['name' => 'valid_from', 'label' => 'Vigente desde', 'type' => 'date', 'default' => ''],
                    ['name' => 'valid_until', 'label' => 'Vigente hasta', 'type' => 'date', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'product_tags' => [
                'title' => 'Tags por producto',
                'model' => 'Model_Core_Commerce_Product_Tag',
                'table' => 'core_commerce_product_tags',
                'required' => ['product_id', 'tag_id'],
                'fields' => [
                    ['name' => 'product_id', 'label' => 'Producto', 'type' => 'select', 'options' => 'products', 'default' => 0],
                    ['name' => 'tag_id', 'label' => 'Tag', 'type' => 'select', 'options' => 'tags', 'default' => 0],
                ],
            ],
            'product_images' => [
                'title' => 'Imagenes de producto',
                'model' => 'Model_Core_Commerce_Product_Image',
                'table' => 'core_commerce_product_images',
                'required' => ['product_id', 'image_path'],
                'fields' => [
                    ['name' => 'product_id', 'label' => 'Producto', 'type' => 'select', 'options' => 'products', 'default' => 0],
                    ['name' => 'image_path', 'label' => 'Imagen', 'type' => 'image', 'default' => ''],
                    ['name' => 'alt_text', 'label' => 'Texto alternativo', 'type' => 'text', 'default' => ''],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'product_relations' => [
                'title' => 'Productos relacionados',
                'model' => 'Model_Core_Commerce_Product_Relation',
                'table' => 'core_commerce_product_relations',
                'required' => ['product_id', 'related_product_id'],
                'fields' => [
                    ['name' => 'product_id', 'label' => 'Producto principal', 'type' => 'select', 'options' => 'products', 'default' => 0],
                    ['name' => 'related_product_id', 'label' => 'Producto relacionado', 'type' => 'select', 'options' => 'products', 'default' => 0],
                    ['name' => 'relation_type', 'label' => 'Tipo relacion', 'type' => 'select', 'options' => 'relation_types', 'default' => 'manual'],
                    ['name' => 'sort_order', 'label' => 'Orden', 'type' => 'integer', 'default' => 0],
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
     * OBTIENE OPCIONES PARA SELECTS DEL MODULO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        # SE PREPARAN OPCIONES DINAMICAS
        return [
            'brands' => $this->select_options('core_commerce_brands', 'id', 'name'),
            'categories' => $this->select_options('core_commerce_categories', 'id', 'name'),
            'subcategories' => $this->select_options('core_commerce_subcategories', 'id', 'name'),
            'tags' => $this->select_options('core_commerce_tags', 'id', 'name'),
            'products' => $this->select_options('core_commerce_products', 'id', 'name'),
            'price_lists' => $this->select_options('core_commerce_price_lists', 'id', 'name'),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'units' => $this->select_options('core_catalog_units', 'code', 'name'),
            'taxes' => $this->select_options('core_catalog_taxes', 'code', 'name'),
            'relation_types' => [
                ['value' => 'manual', 'label' => 'Manual'],
                ['value' => 'complement', 'label' => 'Complemento'],
                ['value' => 'substitute', 'label' => 'Sustituto'],
                ['value' => 'upsell', 'label' => 'Venta sugerida'],
            ],
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASICOS COMERCIALES
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
     * VALIDA QUE LAS TABLAS COMERCIALES EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach ($this->get_definitions() as $definition) {
            if (!\DBUtil::table_exists($definition['table'])) {
                throw new \RuntimeException('Falta ejecutar migraciones comerciales.');
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
     * NORMALIZA CODIGOS INTERNOS COMERCIALES
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
}
