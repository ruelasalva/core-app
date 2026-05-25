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

            if ($section === 'products') {
                $data['sat_product_service_code'] = trim((string) \Arr::get($data, 'sat_product_service_code', '01010101')) ?: '01010101';
                $data['sat_unit_code'] = trim((string) \Arr::get($data, 'sat_unit_code', '')) ?: (((string) \Arr::get($data, 'product_type', 'product') === 'service') ? 'E48' : 'H87');
                $data['sat_object_tax_code'] = trim((string) \Arr::get($data, 'sat_object_tax_code', '02')) ?: '02';
                $data['sat_tax_code'] = trim((string) \Arr::get($data, 'sat_tax_code', '002')) ?: '002';
                $data['sat_tax_factor_type'] = trim((string) \Arr::get($data, 'sat_tax_factor_type', 'Tasa')) ?: 'Tasa';
                $data['sat_tax_rate'] = max(0, (float) \Arr::get($data, 'sat_tax_rate', 0.16));
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
     * CSV TEMPLATE
     *
     * DESCARGA PLANTILLA CSV PARA PRODUCTOS
     *
     * @access  public
     * @return  Response
     */
    public function action_csv_template()
    {
        $rows = [
            ['sku', 'name', 'short_description', 'description', 'brand', 'category', 'subcategory', 'product_type', 'unit_code', 'sat_product_service_code', 'sat_unit_code', 'currency_code', 'price', 'cost', 'tax_code', 'published', 'show_in_home', 'featured'],
            ['SKU-001', 'Producto ejemplo', 'Descripcion corta', 'Descripcion completa', 'Marca ejemplo', 'Categoria ejemplo', 'Subcategoria ejemplo', 'product', 'pieza', '01010101', 'H87', 'MXN', '100.00', '70.00', 'iva_16', '0', '0', '0'],
        ];

        return $this->csv_response('plantilla_productos.csv', $rows);
    }

    /**
     * IMPORT CSV
     *
     * IMPORTA PRODUCTOS DESDE ARCHIVO CSV
     *
     * @access  public
     * @return  Response
     */
    public function action_import_csv()
    {
        $this->require_access('commerce.access[edit]');

        try {
            $this->assert_schema_ready();
            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona un archivo CSV valido.'], 422);
            }

            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'txt'], true)) {
                return $this->json_response(['error' => 'Solo se permiten archivos CSV o TXT.'], 422);
            }

            $result = $this->import_products_csv((string) \Arr::get($file, 'tmp_name', ''));
            return $this->json_response([
                'status' => 'ok',
                'message' => 'Importacion terminada. Creados: '.$result['created'].', actualizados: '.$result['updated'].', omitidos: '.$result['skipped'].'.',
                'summary' => $result,
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error importando productos CSV: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
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
                    ['name' => 'product_type', 'label' => 'Tipo', 'type' => 'select', 'options' => 'product_types', 'default' => 'product'],
                    ['name' => 'is_internal_service', 'label' => 'Servicio interno', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'unit_code', 'label' => 'Unidad interna', 'type' => 'select', 'options' => 'units', 'default' => 'pieza'],
                    ['name' => 'sat_product_service_code', 'label' => 'Clave producto/servicio SAT', 'type' => 'select', 'options' => 'sat_product_service_keys', 'default' => '01010101'],
                    ['name' => 'sat_unit_code', 'label' => 'Clave unidad SAT', 'type' => 'select', 'options' => 'sat_unit_keys', 'default' => 'H87'],
                    ['name' => 'sat_object_tax_code', 'label' => 'Objeto impuesto SAT', 'type' => 'select', 'options' => 'sat_object_tax_codes', 'default' => '02'],
                    ['name' => 'currency_code', 'label' => 'Moneda', 'type' => 'select', 'options' => 'currencies', 'default' => 'MXN'],
                    ['name' => 'price', 'label' => 'Precio', 'type' => 'number', 'default' => 0],
                    ['name' => 'cost', 'label' => 'Costo', 'type' => 'number', 'default' => 0],
                    ['name' => 'tax_code', 'label' => 'Impuesto', 'type' => 'select', 'options' => 'taxes', 'default' => 'iva_16'],
                    ['name' => 'sat_tax_code', 'label' => 'Impuesto SAT', 'type' => 'select', 'options' => 'sat_taxes', 'default' => '002'],
                    ['name' => 'sat_tax_factor_type', 'label' => 'Factor SAT', 'type' => 'select', 'options' => 'sat_factor_types', 'default' => 'Tasa'],
                    ['name' => 'sat_tax_rate', 'label' => 'Tasa SAT', 'type' => 'number', 'default' => 0.16],
                    ['name' => 'stock_quantity', 'label' => 'Existencia', 'type' => 'number', 'default' => 0],
                    ['name' => 'stock_reserved', 'label' => 'Reservado', 'type' => 'number', 'default' => 0],
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
            'sat_product_service_keys' => Helper_Core_Sat_Catalog::options('core_sat_product_service_keys'),
            'sat_unit_keys' => Helper_Core_Sat_Catalog::options('core_sat_unit_keys'),
            'sat_object_tax_codes' => Helper_Core_Sat_Catalog::options('core_sat_object_tax_codes'),
            'sat_taxes' => Helper_Core_Sat_Catalog::options('core_sat_taxes'),
            'sat_factor_types' => [
                ['value' => 'Tasa', 'label' => 'Tasa'],
                ['value' => 'Cuota', 'label' => 'Cuota'],
                ['value' => 'Exento', 'label' => 'Exento'],
            ],
            'relation_types' => [
                ['value' => 'manual', 'label' => 'Manual'],
                ['value' => 'complement', 'label' => 'Complemento'],
                ['value' => 'substitute', 'label' => 'Sustituto'],
                ['value' => 'upsell', 'label' => 'Venta sugerida'],
            ],
            'product_types' => [
                ['value' => 'product', 'label' => 'Producto fisico'],
                ['value' => 'service', 'label' => 'Servicio'],
                ['value' => 'rental', 'label' => 'Renta / arrendamiento'],
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

    protected function import_products_csv($path)
    {
        $rows = $this->read_csv_rows($path);
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        foreach ($rows as $index => $row) {
            $sku = strtoupper(trim((string) \Arr::get($row, 'sku', '')));
            $name = trim((string) \Arr::get($row, 'name', ''));
            if ($sku === '' || $name === '') {
                $result['errors'][] = 'Fila '.($index + 2).': SKU y nombre son obligatorios.';
                $result['skipped']++;
                continue;
            }

            $data = [
                'sku' => $sku,
                'name' => $name,
                'slug' => $this->unique_product_slug(\Arr::get($row, 'slug', $name), $sku),
                'short_description' => trim((string) \Arr::get($row, 'short_description', '')),
                'description' => trim((string) \Arr::get($row, 'description', '')),
                'brand_id' => $this->ensure_named_catalog('core_commerce_brands', 'Model_Core_Commerce_Brand', \Arr::get($row, 'brand', '')),
                'category_id' => $this->ensure_named_catalog('core_commerce_categories', 'Model_Core_Commerce_Category', \Arr::get($row, 'category', '')),
                'subcategory_id' => 0,
                'product_type' => $this->product_type(\Arr::get($row, 'product_type', 'product')),
                'is_internal_service' => 0,
                'unit_code' => trim((string) \Arr::get($row, 'unit_code', 'pieza')) ?: 'pieza',
                'sat_product_service_code' => trim((string) \Arr::get($row, 'sat_product_service_code', '01010101')) ?: '01010101',
                'sat_unit_code' => trim((string) \Arr::get($row, 'sat_unit_code', 'H87')) ?: 'H87',
                'sat_object_tax_code' => trim((string) \Arr::get($row, 'sat_object_tax_code', '02')) ?: '02',
                'currency_code' => strtoupper(substr((string) \Arr::get($row, 'currency_code', 'MXN'), 0, 3)) ?: 'MXN',
                'price' => max(0, (float) \Arr::get($row, 'price', 0)),
                'cost' => max(0, (float) \Arr::get($row, 'cost', 0)),
                'tax_code' => trim((string) \Arr::get($row, 'tax_code', 'iva_16')),
                'sat_tax_code' => trim((string) \Arr::get($row, 'sat_tax_code', '002')) ?: '002',
                'sat_tax_factor_type' => trim((string) \Arr::get($row, 'sat_tax_factor_type', 'Tasa')) ?: 'Tasa',
                'sat_tax_rate' => max(0, (float) \Arr::get($row, 'sat_tax_rate', 0.16)),
                'stock_quantity' => max(0, (float) \Arr::get($row, 'stock_quantity', 0)),
                'stock_reserved' => 0,
                'stock_updated_at' => time(),
                'main_image_path' => trim((string) \Arr::get($row, 'main_image_path', '')),
                'show_in_home' => (int) (bool) \Arr::get($row, 'show_in_home', 0),
                'featured' => (int) (bool) \Arr::get($row, 'featured', 0),
                'published' => (int) (bool) \Arr::get($row, 'published', 0),
                'active' => 1,
                'sort_order' => (int) \Arr::get($row, 'sort_order', 0),
            ];

            if ($data['category_id'] > 0) {
                $data['subcategory_id'] = $this->ensure_subcategory((int) $data['category_id'], \Arr::get($row, 'subcategory', ''));
            }

            $existing = \DB::select('id')->from('core_commerce_products')->where('sku', '=', $sku)->execute()->current();
            if ($existing) {
                $product = \Model_Core_Commerce_Product::find((int) $existing['id']);
                if ($product) {
                    $data['slug'] = $this->unique_product_slug(\Arr::get($row, 'slug', $name), $sku, (int) $product->id);
                    $product->set($data);
                    $product->save();
                    $result['updated']++;
                    continue;
                }
            }

            \Model_Core_Commerce_Product::forge($data)->save();
            $result['created']++;
        }
        return $result;
    }

    protected function read_csv_rows($path)
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('No se pudo leer el archivo CSV.');
        }
        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            return [];
        }
        $delimiter = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            return [];
        }
        $headers = array_map([$this, 'csv_key'], $headers);
        $rows = [];
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = isset($line[$index]) ? trim((string) $line[$index]) : '';
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    protected function csv_response($filename, array $rows)
    {
        $output = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return \Response::forge("\xEF\xBB\xBF".$content, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function csv_key($value)
    {
        return strtolower(trim(preg_replace('/[^a-z0-9_]+/i', '_', (string) $value), '_'));
    }

    protected function ensure_named_catalog($table, $model, $name)
    {
        $name = trim((string) $name);
        if ($name === '' || !\DBUtil::table_exists($table)) {
            return 0;
        }
        $row = \DB::select('id')->from($table)->where('name', '=', $name)->execute()->current();
        if ($row) {
            return (int) $row['id'];
        }
        $item = $model::forge([
            'name' => $name,
            'slug' => $this->unique_slug_for_table($table, $name),
            'description' => '',
            'active' => 1,
        ]);
        $item->save();
        return (int) $item->id;
    }

    protected function ensure_subcategory($category_id, $name)
    {
        $name = trim((string) $name);
        if ($name === '' || !\DBUtil::table_exists('core_commerce_subcategories')) {
            return 0;
        }
        $row = \DB::select('id')->from('core_commerce_subcategories')->where('category_id', '=', (int) $category_id)->where('name', '=', $name)->execute()->current();
        if ($row) {
            return (int) $row['id'];
        }
        $item = \Model_Core_Commerce_Subcategory::forge([
            'category_id' => (int) $category_id,
            'name' => $name,
            'slug' => $this->unique_slug_for_table('core_commerce_subcategories', $name),
            'description' => '',
            'active' => 1,
        ]);
        $item->save();
        return (int) $item->id;
    }

    protected function unique_slug_for_table($table, $seed, $id = 0)
    {
        $base = $this->slugify($seed) ?: 'registro';
        $slug = substr($base, 0, 220);
        $i = 2;
        while (true) {
            $query = \DB::select('id')->from($table)->where('slug', '=', $slug);
            if ($id > 0) {
                $query->where('id', '!=', (int) $id);
            }
            if (!$query->execute()->current()) {
                return $slug;
            }
            $suffix = '-'.$i++;
            $slug = substr($base, 0, 220 - strlen($suffix)).$suffix;
        }
    }

    protected function unique_product_slug($seed, $sku, $id = 0)
    {
        return $this->unique_slug_for_table('core_commerce_products', $seed ?: $sku, $id);
    }

    protected function product_type($value)
    {
        $value = $this->codeify($value);
        $aliases = [
            'producto' => 'product',
            'producto_fisico' => 'product',
            'servicio' => 'service',
            'renta' => 'rental',
            'arrendamiento' => 'rental',
        ];
        if (isset($aliases[$value])) {
            return $aliases[$value];
        }
        return in_array($value, ['product', 'service', 'rental'], true) ? $value : 'product';
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
