<?php

/**
 * CONTROLADOR ADMIN_SUPPLIERIMPORT
 *
 * Consulta la infraestructura de importacion de proveedores en modo solo lectura.
 */
class Controller_Admin_Supplierimport extends Controller_Adminbase
{
    public function before()
    {
        parent::before();
        $this->require_access('commerce.access[view]');
    }

    public function action_index()
    {
        $this->template->title = 'Importación de proveedores';
        $this->template->content = \View::forge('admin/supplierimport/index', [
            'title' => 'Importación de proveedores',
            'initial_data' => $this->safe_data(),
        ]);
    }

    public function action_data()
    {
        try {
            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => (new \Service_Core_SupplierImport_Manager())->admin_data(),
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando importacion de proveedores: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar la importacion de proveedores.',
                'data' => [],
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function action_review()
    {
        $this->template->title = 'Revision de staging';
        $this->template->content = \View::forge('admin/supplierimport/review', [
            'title' => 'Revision de staging de proveedores',
            'initial_data' => $this->safe_review_data(),
        ]);
    }

    public function action_review_data()
    {
        try {
            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => (new \Service_Core_SupplierImport_Manager())->review_data($this->review_filters()),
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando revision de staging de proveedores: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar la revision de staging.',
                'data' => [],
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function post_approve_rows()
    {
        return $this->change_review_rows('approved');
    }

    public function post_reject_rows()
    {
        return $this->change_review_rows('rejected');
    }

    public function post_apply_approved()
    {
        $this->require_access('commerce.access[edit]');

        try {
            if (!$this->upload_security_token_valid()) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'La solicitud no paso la validacion de seguridad. Recarga la pantalla e intenta de nuevo.',
                    'data' => [],
                    'errors' => ['La solicitud no paso la validacion de seguridad. Recarga la pantalla e intenta de nuevo.'],
                ], 400);
            }

            $manager = new \Service_Core_SupplierImport_Manager();
            $result = $manager->apply_approved_rows();

            return $this->json_response([
                'success' => true,
                'message' => 'Proceso de productos aprobados completado.',
                'data' => [
                    'result' => $result,
                    'review' => $manager->review_data($this->review_filters()),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error aplicando staging aprobado a catalogo: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudieron crear productos desde staging aprobado.',
                'data' => [],
                'errors' => [$e->getMessage()],
            ], 400);
        }
    }

    public function action_csv_template()
    {
        $manager = new \Service_Core_SupplierImport_Manager();
        $output = fopen('php://temp', 'r+');

        foreach ($manager->csv_template_rows() as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return \Response::forge("\xEF\xBB\xBF".$content, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="plantilla_importacion_proveedores.csv"',
        ]);
    }

    public function post_upload_csv()
    {
        $this->require_access('commerce.access[edit]');

        $debug = (int) \Input::get('debug', 0) === 1;
        $debug_payload = [];

        try {
            $manager = new \Service_Core_SupplierImport_Manager();
            $party_id = (int) \Input::post('party_id', 0);
            $source_code = $manager->normalize_provider_code(\Input::post('source_code', 'csv_manual'));
            $provider = $manager->normalize_provider_code(\Input::post('provider', ''));
            $mode = trim((string) \Input::post('mode', 'dry_run'));
            $dry_run = $mode !== 'staging';
            $file = $this->uploaded_csv_file();
            $debug_payload = $this->upload_debug_payload($file, $party_id, $source_code, $provider, $mode);

            if (!$this->upload_security_token_valid()) {
                return $this->upload_error_response(
                    'La solicitud no paso la validacion de seguridad. Recarga la pantalla e intenta de nuevo.',
                    ['La solicitud no paso la validacion de seguridad. Recarga la pantalla e intenta de nuevo.'],
                    [],
                    $debug_payload,
                    $debug,
                    400
                );
            }

            if ($party_id < 1 && $provider === '') {
                return $this->upload_error_response('Falta proveedor comercial o codigo avanzado de proveedor.', ['Selecciona un proveedor comercial o captura un codigo avanzado de proveedor.'], [], $debug_payload, $debug, 422);
            }

            if ($source_code === '') {
                return $this->upload_error_response('Falta fuente de importacion.', ['Selecciona una fuente de importacion.'], [], $debug_payload, $debug, 422);
            }

            if (!$manager->source_available_for_import($source_code)) {
                return $this->upload_error_response('Fuente de importación inválida o pendiente.', ['Fuente de importación inválida o pendiente.'], [], $debug_payload, $debug, 422);
            }

            $this->validate_csv_upload($file);
            $filename_code = $provider !== '' ? $provider : $source_code;
            $stored_path = $this->store_uploaded_csv($file, $filename_code);

            $result = $manager->import_csv([
                'file' => $stored_path,
                'provider' => $provider,
                'party_id' => $party_id,
                'source_code' => $source_code,
                'dry-run' => $dry_run ? 1 : 0,
            ]);

            \Log::info('Upload CSV proveedor '.(string) \Arr::get($result, 'provider', $provider).' party_id='.$party_id.' fuente='.$source_code.' dry_run='.(int) $dry_run.' filas='.(int) \Arr::get($result, 'total_rows', 0).' insertadas='.(int) \Arr::get($result, 'inserted', 0));

            $summary = $this->upload_summary($result);
            $warnings = $this->upload_warnings($result);

            return $this->json_response([
                'success' => true,
                'message' => $dry_run ? 'Validación completada. No se insertaron filas porque el modo fue validar solamente.' : 'Importación a staging completada.',
                'summary' => $summary,
                'data' => [
                    'result' => $result,
                    'dashboard' => $this->safe_data(),
                    'debug' => $debug ? $debug_payload : null,
                ],
                'errors' => [],
                'warnings' => $warnings,
                'debug' => $debug ? $debug_payload : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error subiendo CSV de proveedor: '.$e->getMessage());
            return $this->upload_error_response($this->upload_exception_message($e), $this->upload_exception_errors($e), [], $debug_payload, $debug, 400);
        }
    }

    protected function safe_data()
    {
        try {
            return (new \Service_Core_SupplierImport_Manager())->admin_data();
        } catch (\Exception $e) {
            return [
                'runs' => [],
                'rows' => [],
                'validation' => [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'invalid_rows' => 0,
                    'duplicates' => 0,
                    'warnings' => 0,
                    'dry_run_runs' => 0,
                ],
                'providers' => [],
                'sources' => [],
                'suppliers' => [],
                'warnings' => [$e->getMessage()],
            ];
        }
    }

    protected function safe_review_data()
    {
        try {
            return (new \Service_Core_SupplierImport_Manager())->review_data($this->review_filters());
        } catch (\Exception $e) {
            return [
                'rows' => [],
                'filters' => [
                    'providers' => [],
                    'brands' => [],
                    'categories' => [],
                    'runs' => [],
                ],
                'status_options' => [],
                'applied_filters' => [],
                'warnings' => [$e->getMessage()],
            ];
        }
    }

    protected function review_filters()
    {
        return [
            'provider' => trim((string) \Input::get('provider', '')),
            'brand' => trim((string) \Input::get('brand', '')),
            'category' => trim((string) \Input::get('category', '')),
            'row_status' => trim((string) \Input::get('row_status', '')),
            'import_run_id' => (int) \Input::get('import_run_id', 0),
        ];
    }

    protected function change_review_rows($status)
    {
        $this->require_access('commerce.access[edit]');

        try {
            if (!$this->upload_security_token_valid()) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'La solicitud no paso la validacion de seguridad. Recarga la pantalla e intenta de nuevo.',
                    'data' => [],
                    'errors' => ['La solicitud no paso la validacion de seguridad. Recarga la pantalla e intenta de nuevo.'],
                ], 400);
            }

            $ids = $this->review_row_ids();
            $manager = new \Service_Core_SupplierImport_Manager();
            $result = $status === 'approved' ? $manager->approve_rows($ids) : $manager->reject_rows($ids);

            return $this->json_response([
                'success' => true,
                'message' => $status === 'approved' ? 'Filas aprobadas correctamente.' : 'Filas rechazadas correctamente.',
                'data' => [
                    'result' => $result,
                    'review' => $manager->review_data($this->review_filters()),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cambiando estado de staging proveedor: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo actualizar el estado de las filas seleccionadas.',
                'data' => [],
                'errors' => [$e->getMessage()],
            ], 400);
        }
    }

    protected function review_row_ids()
    {
        $ids = \Input::post('ids', []);
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            if (is_array($decoded)) {
                $ids = $decoded;
            } else {
                $ids = explode(',', $ids);
            }
        }

        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids), function($id) {
            return $id > 0;
        })));
    }

    protected function upload_error_response($message, array $errors = [], array $warnings = [], array $debug_payload = [], $debug = false, $status = 400)
    {
        $message = trim((string) $message);
        if ($message === '') {
            $message = 'No se pudo procesar el archivo CSV.';
        }

        if (empty($errors)) {
            $errors = [$message];
        }

        return $this->json_response([
            'success' => false,
            'message' => $message,
            'summary' => $this->empty_upload_summary(),
            'data' => [],
            'errors' => array_values($errors),
            'warnings' => array_values($warnings),
            'debug' => $debug ? $debug_payload : null,
        ], $status);
    }

    protected function upload_security_token_valid()
    {
        $csrf_key = \Config::get('security.csrf_token_key', 'fuel_csrf_token');
        $token = \Input::post($csrf_key, null);

        if ($token !== null && \Security::check_token($token)) {
            return true;
        }

        return \Security::check_token();
    }

    protected function upload_exception_message(\Exception $e)
    {
        $message = trim($e->getMessage());
        if ($message === '') {
            return 'No se pudo procesar el archivo CSV.';
        }

        if (stripos($message, 'Columnas no permitidas') !== false || stripos($message, 'encabezados') !== false || stripos($message, 'columna de identidad') !== false) {
            return 'El CSV tiene encabezados invalidos.';
        }

        return $message;
    }

    protected function upload_exception_errors(\Exception $e)
    {
        $message = trim($e->getMessage());
        $errors = [$message !== '' ? $message : 'No se pudo procesar el archivo CSV.'];

        if (stripos($message, 'Columnas no permitidas') !== false || stripos($message, 'encabezados') !== false || stripos($message, 'columna de identidad') !== false) {
            $errors[] = 'Encabezados esperados: '.$this->expected_csv_headers_label().'.';
        }

        return $errors;
    }

    protected function expected_csv_headers_label()
    {
        return 'sku, model, name, brand, category, description, compatibility, price, currency, stock, image_url, source_url';
    }

    protected function validate_csv_upload($file)
    {
        if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Selecciona un archivo CSV valido.');
        }

        $name = (string) \Arr::get($file, 'name', '');
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'], true)) {
            throw new \RuntimeException('Solo se permiten archivos CSV o TXT.');
        }

        if ((int) \Arr::get($file, 'size', 0) > 5242880) {
            throw new \RuntimeException('El archivo no puede superar 5 MB.');
        }
    }

    protected function uploaded_csv_file()
    {
        $file = \Input::file('csv_file');
        if ($file && (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            return $file;
        }

        return \Input::file('file');
    }

    protected function upload_debug_payload($file, $party_id, $source_code, $provider, $mode)
    {
        return [
            'has_file' => $file && (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK,
            'filename' => $file ? (string) \Arr::get($file, 'name', '') : '',
            'party_id' => (int) $party_id,
            'source_code' => (string) $source_code,
            'provider' => (string) $provider,
            'mode' => (string) $mode,
        ];
    }

    protected function empty_upload_summary()
    {
        return [
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'duplicates' => 0,
            'warnings' => 0,
            'errors' => 0,
        ];
    }

    protected function upload_summary(array $result)
    {
        return [
            'total_rows' => (int) \Arr::get($result, 'total_rows', 0),
            'valid_rows' => (int) \Arr::get($result, 'valid_rows', \Arr::get($result, 'normalized', 0)),
            'invalid_rows' => (int) \Arr::get($result, 'invalid_rows', 0),
            'duplicates' => (int) \Arr::get($result, 'duplicates', 0),
            'warnings' => (int) \Arr::get($result, 'warnings', 0),
            'errors' => (int) \Arr::get($result, 'errors', 0),
        ];
    }

    protected function upload_warnings(array $result)
    {
        $warnings = [];
        if ((int) \Arr::get($result, 'total_rows', 0) === 0) {
            $warnings[] = 'El archivo CSV no contiene filas para validar.';
        }
        if ((int) \Arr::get($result, 'total_rows', 0) > 0 && (int) \Arr::get($result, 'valid_rows', \Arr::get($result, 'normalized', 0)) === 0) {
            $warnings[] = 'La validacion no encontro filas validas. Revisa encabezados y datos obligatorios.';
        }

        return $warnings;
    }

    protected function store_uploaded_csv($file, $provider)
    {
        $dir = APPPATH.'storage/supplier_import/csv/'.date('Y').'/'.date('m');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $original = pathinfo((string) \Arr::get($file, 'name', 'catalogo.csv'), PATHINFO_FILENAME);
        $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', 'csv'), PATHINFO_EXTENSION)) ?: 'csv';
        $safe_original = $this->safe_filename($original);
        $filename = date('Ymd_His').'_'.$provider.'_'.$safe_original.'.'.$extension;
        $target = $dir.DS.$filename;

        if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
            throw new \RuntimeException('No se pudo guardar el archivo temporal.');
        }

        return $target;
    }

    protected function safe_filename($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-') ?: 'catalogo';
    }
}
