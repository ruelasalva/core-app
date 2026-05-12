<?php
namespace Fuel\Tasks;

/**
 * TASK IMPORTSAJOR
 *
 * Importa muestras controladas desde la base sajor hacia Core-App.
 *
 * @package  app
 */
class Importsajor
{
    protected $source_db = 'sajor';
    protected $limit = 24;
    protected $brand_map = [];
    protected $category_map = [];
    protected $subcategory_map = [];
    protected $product_map = [];

    /**
     * SAMPLES
     *
     * IMPORTA PRODUCTOS, MARCAS, CATEGORIAS, SUBCATEGORIAS Y PRECIOS DE PRUEBA
     *
     * @access  public
     * @return  Void
     */
    public function samples($limit = 24)
    {
        try {
            # SE NORMALIZA LIMITE PARA NO TRAER DEMASIADOS DATOS
            $this->limit = max(1, min(80, (int) $limit));
            $this->assert_schema_ready();

            # SE OBTIENEN PRODUCTOS BASE CON PRECIO
            $products = $this->source_products();
            if (empty($products)) {
                echo "\n [INFO] No se encontraron productos activos con precio en sajor.\n";
                return;
            }

            # SE IMPORTAN DEPENDENCIAS RELACIONADAS
            $this->import_brands($products);
            $this->import_categories($products);
            $this->import_subcategories($products);

            # SE IMPORTAN PRODUCTOS Y PRECIOS
            $this->import_products($products);
            $this->import_product_prices($products);

            echo "\n [SUCCESS] Muestras importadas desde sajor.\n";
            echo " - Productos: ".count($this->product_map)."\n";
            echo " - Marcas relacionadas: ".count($this->brand_map)."\n";
            echo " - Categorias relacionadas: ".count($this->category_map)."\n";
            echo " - Subcategorias relacionadas: ".count($this->subcategory_map)."\n";
        } catch (\Exception $e) {
            echo "\n [ERROR] ".$e->getMessage()."\n";
            \Log::error('Fallo importsajor:samples: '.$e->getMessage());
        }
    }

    /**
     * RUN
     *
     * ATAJO PARA EJECUTAR LA IMPORTACION DE MUESTRAS
     *
     * @access  public
     * @return  Void
     */
    public function run($limit = 24)
    {
        $this->samples($limit);
    }

    /**
     * SOURCE PRODUCTS
     *
     * OBTIENE PRODUCTOS ACTIVOS CON PRECIO DE LA BASE SAJOR
     *
     * @access  protected
     * @return  Array
     */
    protected function source_products()
    {
        # SE PRIORIZAN PRODUCTOS ACTIVOS CON PRECIO PUBLICO
        $sql = "
            SELECT
                p.id,
                p.category_id,
                p.subcategory_id,
                p.brand_id,
                p.slug,
                p.name,
                p.code,
                p.sku,
                p.description,
                p.image,
                p.available,
                p.order AS sort_order,
                p.created_at,
                p.updated_at,
                pp.price AS public_price
            FROM `{$this->source_db}`.`products` p
            INNER JOIN `{$this->source_db}`.`products_prices` pp
                ON pp.product_id = p.id AND pp.type_id = 1 AND pp.price > 0
            WHERE p.deleted = 0
                AND p.status = 1
            ORDER BY p.id ASC
            LIMIT {$this->limit}
        ";

        return \DB::query($sql)->execute()->as_array();
    }

    /**
     * IMPORT BRANDS
     *
     * IMPORTA MARCAS RELACIONADAS A LOS PRODUCTOS
     *
     * @access  protected
     * @return  Void
     */
    protected function import_brands(array $products)
    {
        $ids = $this->ids_from($products, 'brand_id');
        if (empty($ids)) {
            return;
        }

        $rows = \DB::query("SELECT * FROM `{$this->source_db}`.`brands` WHERE id IN (".implode(',', $ids).")")->execute()->as_array();
        foreach ($rows as $row) {
            $slug = $this->unique_slug('core_commerce_brands', $this->slug($row['slug'] ?: $row['name']), (int) $row['id']);
            $id = $this->upsert_by_slug('core_commerce_brands', $slug, [
                'name' => $this->text($row['name']),
                'slug' => $slug,
                'description' => '',
                'logo_path' => '',
                'show_in_home' => 1,
                'sort_order' => (int) $row['code'],
                'active' => (int) ((int) $row['deleted'] === 0 && (int) $row['status'] === 1),
                'created_at' => (int) ($row['created_at'] ?: time()),
                'updated_at' => (int) ($row['updated_at'] ?: time()),
            ]);
            $this->brand_map[(int) $row['id']] = $id;
        }
    }

    /**
     * IMPORT CATEGORIES
     *
     * IMPORTA CATEGORIAS RELACIONADAS A LOS PRODUCTOS
     *
     * @access  protected
     * @return  Void
     */
    protected function import_categories(array $products)
    {
        $ids = $this->ids_from($products, 'category_id');
        if (empty($ids)) {
            return;
        }

        $rows = \DB::query("SELECT * FROM `{$this->source_db}`.`categories` WHERE id IN (".implode(',', $ids).")")->execute()->as_array();
        foreach ($rows as $row) {
            $slug = $this->unique_slug('core_commerce_categories', $this->slug($row['slug'] ?: $row['name']), (int) $row['id']);
            $id = $this->upsert_by_slug('core_commerce_categories', $slug, [
                'name' => $this->text($row['name']),
                'slug' => $slug,
                'description' => '',
                'image_path' => '',
                'show_in_home' => 1,
                'sort_order' => (int) $row['code'],
                'active' => (int) ((int) $row['deleted'] === 0 && (int) $row['status'] === 1),
                'created_at' => (int) ($row['created_at'] ?: time()),
                'updated_at' => (int) ($row['updated_at'] ?: time()),
            ]);
            $this->category_map[(int) $row['id']] = $id;
        }
    }

    /**
     * IMPORT SUBCATEGORIES
     *
     * IMPORTA SUBCATEGORIAS RELACIONADAS A LOS PRODUCTOS
     *
     * @access  protected
     * @return  Void
     */
    protected function import_subcategories(array $products)
    {
        $ids = $this->ids_from($products, 'subcategory_id');
        if (empty($ids)) {
            return;
        }

        $rows = \DB::query("SELECT * FROM `{$this->source_db}`.`subcategories` WHERE id IN (".implode(',', $ids).")")->execute()->as_array();
        foreach ($rows as $row) {
            $slug = $this->unique_slug('core_commerce_subcategories', $this->slug($row['slug'] ?: $row['name']), (int) $row['id']);
            $id = $this->upsert_by_slug('core_commerce_subcategories', $slug, [
                'category_id' => isset($this->category_map[(int) $row['category_id']]) ? $this->category_map[(int) $row['category_id']] : 0,
                'name' => $this->text($row['name']),
                'slug' => $slug,
                'description' => '',
                'image_path' => '',
                'show_in_home' => 1,
                'sort_order' => (int) $row['code'],
                'active' => (int) ((int) $row['deleted'] === 0 && (int) $row['status'] === 1),
                'created_at' => (int) ($row['created_at'] ?: time()),
                'updated_at' => (int) ($row['updated_at'] ?: time()),
            ]);
            $this->subcategory_map[(int) $row['id']] = $id;
        }
    }

    /**
     * IMPORT PRODUCTS
     *
     * IMPORTA PRODUCTOS DE PRUEBA PUBLICADOS
     *
     * @access  protected
     * @return  Void
     */
    protected function import_products(array $products)
    {
        foreach ($products as $index => $row) {
            $slug = $this->unique_slug('core_commerce_products', $this->slug($row['slug'] ?: $row['name']), (int) $row['id']);
            $sku = trim((string) ($row['sku'] ?: $row['code'] ?: 'SAJOR-'.$row['id']));
            $data = [
                'sku' => $this->unique_sku($sku, (int) $row['id']),
                'name' => $this->text($row['name']),
                'slug' => $slug,
                'short_description' => substr(strip_tags($this->text($row['description'])), 0, 250),
                'description' => $this->text($row['description']),
                'brand_id' => isset($this->brand_map[(int) $row['brand_id']]) ? $this->brand_map[(int) $row['brand_id']] : 0,
                'category_id' => isset($this->category_map[(int) $row['category_id']]) ? $this->category_map[(int) $row['category_id']] : 0,
                'subcategory_id' => isset($this->subcategory_map[(int) $row['subcategory_id']]) ? $this->subcategory_map[(int) $row['subcategory_id']] : 0,
                'unit_code' => 'pieza',
                'currency_code' => 'MXN',
                'price' => (float) $row['public_price'],
                'cost' => 0,
                'tax_code' => 'iva_16',
                'main_image_path' => '',
                'show_in_home' => $index < 8 ? 1 : 0,
                'featured' => $index < 8 ? 1 : 0,
                'published' => 1,
                'active' => 1,
                'sort_order' => (int) ($row['sort_order'] ?: $index + 1),
                'created_at' => (int) ($row['created_at'] ?: time()),
                'updated_at' => (int) ($row['updated_at'] ?: time()),
            ];

            $id = $this->upsert_by_slug('core_commerce_products', $slug, $data);
            $this->product_map[(int) $row['id']] = $id;
        }
    }

    /**
     * IMPORT PRODUCT PRICES
     *
     * IMPORTA PRECIOS DE PUBLICO GENERAL Y MAYOREO PARA PRUEBAS
     *
     * @access  protected
     * @return  Void
     */
    protected function import_product_prices(array $products)
    {
        $public_list = $this->price_list_id('publico_general');
        $wholesale_list = $this->price_list_id('mayoreo');

        foreach ($products as $row) {
            $source_id = (int) $row['id'];
            if (empty($this->product_map[$source_id])) {
                continue;
            }

            if ($public_list > 0) {
                $this->upsert_product_price($this->product_map[$source_id], $public_list, (float) $row['public_price'], 1, null);
            }
        }

        if ($wholesale_list < 1) {
            return;
        }

        $ids = array_keys($this->product_map);
        if (empty($ids)) {
            return;
        }

        $rows = \DB::query("
            SELECT product_id, min_quantity, max_quantity, price
            FROM `{$this->source_db}`.`products_prices_wholesales`
            WHERE product_id IN (".implode(',', $ids).")
                AND price > 0
            ORDER BY product_id ASC, min_quantity ASC
        ")->execute()->as_array();

        foreach ($rows as $row) {
            if (empty($this->product_map[(int) $row['product_id']])) {
                continue;
            }

            $this->upsert_product_price(
                $this->product_map[(int) $row['product_id']],
                $wholesale_list,
                (float) $row['price'],
                (float) $row['min_quantity'],
                (float) $row['max_quantity']
            );
        }
    }

    protected function upsert_product_price($product_id, $price_list_id, $price, $min_quantity, $max_quantity)
    {
        $exists = \DB::select('id')
            ->from('core_commerce_product_prices')
            ->where('product_id', '=', (int) $product_id)
            ->where('price_list_id', '=', (int) $price_list_id)
            ->where('min_quantity', '=', (float) $min_quantity)
            ->execute()
            ->current();

        $data = [
            'product_id' => (int) $product_id,
            'price_list_id' => (int) $price_list_id,
            'currency_code' => 'MXN',
            'price' => (float) $price,
            'min_quantity' => (float) $min_quantity,
            'max_quantity' => $max_quantity,
            'valid_from' => null,
            'valid_until' => null,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ];

        if ($exists) {
            \DB::update('core_commerce_product_prices')->set($data)->where('id', '=', (int) $exists['id'])->execute();
            return;
        }

        \DB::insert('core_commerce_product_prices')->set($data)->execute();
    }

    protected function upsert_by_slug($table, $slug, array $data)
    {
        $exists = \DB::select('id')->from($table)->where('slug', '=', $slug)->execute()->current();
        if ($exists) {
            \DB::update($table)->set($data)->where('id', '=', (int) $exists['id'])->execute();
            return (int) $exists['id'];
        }

        list($id) = \DB::insert($table)->set($data)->execute();
        return (int) $id;
    }

    protected function price_list_id($code)
    {
        $row = \DB::select('id')->from('core_commerce_price_lists')->where('code', '=', $code)->execute()->current();
        return $row ? (int) $row['id'] : 0;
    }

    protected function ids_from(array $rows, $field)
    {
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) $row[$field];
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    protected function unique_slug($table, $slug, $source_id)
    {
        $slug = $slug ?: 'sajor-'.$source_id;
        return $slug;
    }

    protected function unique_sku($sku, $source_id)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            $sku = 'SAJOR-'.$source_id;
        }

        return $sku;
    }

    protected function text($value)
    {
        return trim((string) $value);
    }

    protected function slug($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    protected function assert_schema_ready()
    {
        foreach (['core_commerce_brands', 'core_commerce_categories', 'core_commerce_subcategories', 'core_commerce_products', 'core_commerce_product_prices'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        $source_exists = \DB::query("SHOW DATABASES LIKE '{$this->source_db}'")->execute()->current();
        if (!$source_exists) {
            throw new \Exception('No existe la base de datos sajor en este servidor MySQL.');
        }
    }
}
