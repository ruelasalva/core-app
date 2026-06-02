<?php

/**
 * SERVICE CORE SUPPLIERIMPORT IMAGEMANAGER
 *
 * Descarga imagenes remotas de filas de staging ya creadas/mapeadas.
 * No borra, no reemplaza y no toca inventario ni precios.
 */
class Service_Core_SupplierImport_ImageManager
{
    protected $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    protected $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    protected $max_bytes = 5242880;

    public function download_images()
    {
        $this->assert_tables();

        $rows = \DB::select()
            ->from('core_supplier_import_rows')
            ->where('active', '=', 1)
            ->where('row_status', 'in', ['created', 'mapped'])
            ->where('product_id', '>', 0)
            ->where('image_url', '!=', '')
            ->order_by('id', 'asc')
            ->limit(200)
            ->execute();

        $result = [
            'products_processed' => 0,
            'images_downloaded' => 0,
            'images_skipped' => 0,
            'errors' => 0,
            'messages' => [],
        ];

        foreach ($rows as $row) {
            $result['products_processed']++;
            try {
                $action = $this->process_row($row);
                if ($action === 'downloaded') {
                    $result['images_downloaded']++;
                } else {
                    $result['images_skipped']++;
                }
            } catch (\Exception $e) {
                $result['errors']++;
                $result['messages'][] = 'Fila '.$row['id'].': '.$e->getMessage();
                \Log::warning('Error descargando imagen de proveedor fila='.$row['id'].': '.$e->getMessage());
            }
        }

        \Log::info('Descarga de imagenes proveedor: procesados='.$result['products_processed'].' descargadas='.$result['images_downloaded'].' omitidas='.$result['images_skipped'].' errores='.$result['errors']);

        return $result;
    }

    protected function process_row(array $row)
    {
        $product_id = (int) \Arr::get($row, 'product_id', 0);
        $image_url = trim((string) \Arr::get($row, 'image_url', ''));
        $local_image_path = trim((string) \Arr::get($row, 'local_image_path', ''));

        if ($product_id < 1 || $image_url === '') {
            return 'skipped';
        }

        $product = \Model_Core_Commerce_Product::find($product_id);
        if (!$product) {
            throw new \RuntimeException('Producto interno no encontrado.');
        }

        if ($local_image_path !== '' && $this->product_image_exists($product_id, $local_image_path)) {
            return 'skipped';
        }

        $this->validate_url($image_url);
        $extension_hint = $this->extension_from_url($image_url);
        if ($extension_hint !== '' && !in_array($extension_hint, $this->allowed_extensions, true)) {
            throw new \RuntimeException('Extension de imagen no permitida.');
        }

        $download = $this->download_remote_image($image_url);
        $mime = $this->detect_mime($download['tmp_path']);
        if (!isset($this->allowed_mimes[$mime])) {
            @unlink($download['tmp_path']);
            throw new \RuntimeException('MIME de imagen no permitido: '.$mime.'.');
        }

        $extension = $extension_hint !== '' ? $extension_hint : $this->allowed_mimes[$mime];
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $relative_path = $this->store_image($product_id, $download['tmp_path'], $extension);
        $this->create_product_image($product, $relative_path);
        $this->update_staging_row((int) $row['id'], $relative_path);

        return 'downloaded';
    }

    protected function validate_url($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('URL de imagen invalida.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('Solo se permiten URLs http o https.');
        }
    }

    protected function extension_from_url($url)
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    protected function download_remote_image($url)
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('La extension cURL no esta disponible para descargar imagenes.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'supplier_img_');
        if (!$tmp) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }

        $handle = fopen($tmp, 'wb');
        if (!$handle) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo abrir archivo temporal.');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $handle);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CORE-APP ERP Supplier Image Import');
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($handle);

        if (!$ok || $status < 200 || $status >= 300) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo descargar imagen remota'.($error ? ': '.$error : '').'.');
        }

        $size = filesize($tmp);
        if ($size === false || $size < 1) {
            @unlink($tmp);
            throw new \RuntimeException('La imagen descargada esta vacia.');
        }
        if ($size > $this->max_bytes) {
            @unlink($tmp);
            throw new \RuntimeException('La imagen supera el tamano maximo de 5 MB.');
        }

        return [
            'tmp_path' => $tmp,
            'size' => (int) $size,
        ];
    }

    protected function detect_mime($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                return (string) $mime;
            }
        }

        $info = @getimagesize($path);
        return $info && !empty($info['mime']) ? (string) $info['mime'] : '';
    }

    protected function store_image($product_id, $tmp_path, $extension)
    {
        $dir = DOCROOT.'uploads'.DS.'products'.DS.(int) $product_id;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $index = 1;
        do {
            $filename = $index.'.'.$extension;
            $target = $dir.DS.$filename;
            $relative = 'uploads/products/'.(int) $product_id.'/'.$filename;
            $index++;
        } while (file_exists($target) || $this->product_image_exists($product_id, $relative));

        if (!@rename($tmp_path, $target)) {
            @unlink($tmp_path);
            throw new \RuntimeException('No se pudo guardar la imagen descargada.');
        }

        return $relative;
    }

    protected function create_product_image(\Model_Core_Commerce_Product $product, $relative_path)
    {
        $product_id = (int) $product->id;
        if ($this->product_image_exists($product_id, $relative_path)) {
            return;
        }

        $sort_order = $this->next_sort_order($product_id);
        \Model_Core_Commerce_Product_Image::forge([
            'product_id' => $product_id,
            'image_path' => $relative_path,
            'alt_text' => (string) $product->name,
            'sort_order' => $sort_order,
            'active' => 1,
        ])->save();

        if (trim((string) $product->main_image_path) === '') {
            $product->main_image_path = $relative_path;
            $product->save();
        }
    }

    protected function product_image_exists($product_id, $relative_path)
    {
        if (!\DBUtil::table_exists('core_commerce_product_images')) {
            return false;
        }

        return (bool) \DB::select('id')
            ->from('core_commerce_product_images')
            ->where('product_id', '=', (int) $product_id)
            ->where('image_path', '=', (string) $relative_path)
            ->where('active', '=', 1)
            ->execute()
            ->current();
    }

    protected function next_sort_order($product_id)
    {
        $row = \DB::select([\DB::expr('MAX(sort_order)'), 'max_sort'])
            ->from('core_commerce_product_images')
            ->where('product_id', '=', (int) $product_id)
            ->execute()
            ->current();

        return $row ? ((int) $row['max_sort'] + 1) : 1;
    }

    protected function update_staging_row($row_id, $relative_path)
    {
        \DB::update('core_supplier_import_rows')
            ->set([
                'local_image_path' => $relative_path,
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $row_id)
            ->where('active', '=', 1)
            ->execute();
    }

    protected function assert_tables()
    {
        foreach (['core_supplier_import_rows', 'core_commerce_products', 'core_commerce_product_images'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla requerida: '.$table.'.');
            }
        }
    }
}
