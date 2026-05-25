<?php

/**
 * CONTROLADOR ADMIN_PARTIES
 *
 * Administra terceros comerciales: clientes, proveedores, direcciones y contactos.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Parties extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE TERCEROS
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('parties.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE CLIENTES, PROVEEDORES, DIRECCIONES Y CONTACTOS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Terceros';
        $this->template->content = View::forge('admin/parties/index');
    }

    /**
     * DATA
     *
     * ENTREGA DEFINICIONES, OPCIONES Y REGISTROS DE TERCEROS EN JSON
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
            \Log::error('Error cargando terceros: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar terceros.'], 500);
        }
    }

    public function action_approve_supplier()
    {
        return $this->review_supplier('approved');
    }

    public function action_reject_supplier()
    {
        return $this->review_supplier('rejected');
    }

    /**
     * CSV TEMPLATE
     *
     * DESCARGA PLANTILLA CSV PARA IMPORTAR CLIENTES O PROVEEDORES
     *
     * @access  public
     * @return  Response
     */
    public function action_csv_template()
    {
        $section = trim((string) \Input::get('section', 'customers'));
        $type = $section === 'suppliers' ? 'supplier' : 'customer';
        $filename = $type === 'supplier' ? 'plantilla_proveedores.csv' : 'plantilla_clientes.csv';
        $rows = [
            ['party_type', 'code', 'name', 'legal_name', 'rfc', 'email', 'phone', 'sat_cfdi_use_code', 'sat_tax_regime_code', 'credit_limit', 'credit_days', 'notes'],
            [$type, strtoupper(substr($type, 0, 3)).'-001', 'Empresa ejemplo', 'Empresa Ejemplo SA de CV', 'XAXX010101000', 'contacto@ejemplo.com', '3330000000', $type === 'supplier' ? 'G03' : 'S01', '601', '0', '0', 'Registro de ejemplo'],
        ];

        return $this->csv_response($filename, $rows);
    }

    /**
     * IMPORT CSV
     *
     * IMPORTA CLIENTES O PROVEEDORES DESDE ARCHIVO CSV
     *
     * @access  public
     * @return  Response
     */
    public function action_import_csv()
    {
        $this->require_access('parties.access[edit]');

        try {
            $this->assert_schema_ready();
            $section = trim((string) \Input::post('section', 'customers'));
            $default_type = $section === 'suppliers' ? 'supplier' : 'customer';
            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona un archivo CSV valido.'], 422);
            }

            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'txt'], true)) {
                return $this->json_response(['error' => 'Solo se permiten archivos CSV o TXT.'], 422);
            }

            $result = $this->import_parties_csv((string) \Arr::get($file, 'tmp_name', ''), $default_type);
            return $this->json_response([
                'status' => 'ok',
                'message' => 'Importacion terminada. Creados: '.$result['created'].', actualizados: '.$result['updated'].', omitidos: '.$result['skipped'].'.',
                'summary' => $result,
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error importando terceros CSV: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN REGISTRO DE TERCEROS
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('parties.access[edit]');

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

            # SE NORMALIZAN CODIGOS
            if (isset($data['code']) && $data['code'] === '' && isset($data['name'])) {
                $data['code'] = $this->codeify($data['name']);
            } elseif (isset($data['code'])) {
                $data['code'] = $this->codeify($data['code']);
            }

            if (isset($data['rfc'])) {
                $data['rfc'] = strtoupper($data['rfc']);
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
            \Log::error('Error guardando terceros: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el registro.'], 400);
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
        # SE DEFINEN SECCIONES DE TERCEROS
        return [
            'customers' => [
                'title' => 'Clientes',
                'model' => 'Model_Core_Party',
                'table' => 'core_parties',
                'filter' => ['party_type', ['customer', 'both']],
                'required' => ['code', 'name'],
                'fields' => $this->party_fields('customer'),
            ],
            'suppliers' => [
                'title' => 'Proveedores',
                'model' => 'Model_Core_Party',
                'table' => 'core_parties',
                'filter' => ['party_type', ['supplier', 'both']],
                'required' => ['code', 'name'],
                'fields' => $this->party_fields('supplier'),
            ],
            'addresses' => [
                'title' => 'Direcciones',
                'model' => 'Model_Core_Party_Address',
                'table' => 'core_party_addresses',
                'required' => ['party_id', 'address_type', 'postal_code'],
                'fields' => [
                    ['name' => 'party_id', 'label' => 'Tercero', 'type' => 'select', 'options' => 'parties', 'default' => 0],
                    ['name' => 'address_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [
                        ['value' => 'fiscal', 'label' => 'Fiscal'],
                        ['value' => 'shipping', 'label' => 'Envio'],
                        ['value' => 'billing', 'label' => 'Cobranza'],
                    ], 'default' => 'shipping'],
                    ['name' => 'name', 'label' => 'Alias', 'type' => 'text', 'default' => ''],
                    ['name' => 'street', 'label' => 'Calle', 'type' => 'text', 'default' => ''],
                    ['name' => 'exterior_number', 'label' => 'Exterior', 'type' => 'text', 'default' => ''],
                    ['name' => 'interior_number', 'label' => 'Interior', 'type' => 'text', 'default' => ''],
                    ['name' => 'neighborhood', 'label' => 'Colonia', 'type' => 'text', 'default' => ''],
                    ['name' => 'city', 'label' => 'Ciudad', 'type' => 'text', 'default' => ''],
                    ['name' => 'state', 'label' => 'Estado', 'type' => 'text', 'default' => ''],
                    ['name' => 'country_code', 'label' => 'Pais', 'type' => 'text', 'default' => 'MX'],
                    ['name' => 'postal_code', 'label' => 'Codigo postal', 'type' => 'text', 'default' => ''],
                    ['name' => 'is_default', 'label' => 'Predeterminada', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'contacts' => [
                'title' => 'Contactos',
                'model' => 'Model_Core_Party_Contact',
                'table' => 'core_party_contacts',
                'required' => ['party_id', 'name'],
                'fields' => [
                    ['name' => 'party_id', 'label' => 'Tercero', 'type' => 'select', 'options' => 'parties', 'default' => 0],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'position', 'label' => 'Puesto', 'type' => 'text', 'default' => ''],
                    ['name' => 'email', 'label' => 'Correo', 'type' => 'text', 'default' => ''],
                    ['name' => 'phone', 'label' => 'Telefono', 'type' => 'text', 'default' => ''],
                    ['name' => 'receives_notifications', 'label' => 'Recibe notificaciones', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
        ];
    }

    /**
     * PARTY FIELDS
     *
     * REGRESA CAMPOS COMUNES PARA CLIENTES Y PROVEEDORES
     *
     * @access  protected
     * @return  Array
     */
    protected function party_fields($type)
    {
        return [
            ['name' => 'party_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [
                ['value' => 'customer', 'label' => 'Cliente'],
                ['value' => 'supplier', 'label' => 'Proveedor'],
                ['value' => 'both', 'label' => 'Cliente y proveedor'],
            ], 'default' => $type],
            ['name' => 'department_id', 'label' => 'Departamento', 'type' => 'select', 'options' => 'departments', 'default' => 0],
            ['name' => 'sales_user_id', 'label' => 'Vendedor asignado', 'type' => 'select', 'options' => 'users', 'default' => 0],
            ['name' => 'default_seller_id', 'label' => 'Perfil vendedor/comision', 'type' => 'select', 'options' => 'sellers', 'default' => 0],
            ['name' => 'buyer_user_id', 'label' => 'Comprador asignado', 'type' => 'select', 'options' => 'users', 'default' => 0],
            ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
            ['name' => 'name', 'label' => 'Nombre comercial', 'type' => 'text', 'default' => ''],
            ['name' => 'legal_name', 'label' => 'Razon social', 'type' => 'text', 'default' => ''],
            ['name' => 'rfc', 'label' => 'RFC', 'type' => 'text', 'default' => ''],
            ['name' => 'email', 'label' => 'Correo', 'type' => 'text', 'default' => ''],
            ['name' => 'phone', 'label' => 'Telefono', 'type' => 'text', 'default' => ''],
            ['name' => 'price_list_id', 'label' => 'Lista de precios', 'type' => 'select', 'options' => 'price_lists', 'default' => 0],
            ['name' => 'payment_term_id', 'label' => 'Condicion de pago', 'type' => 'select', 'options' => 'payment_terms', 'default' => 0],
            ['name' => 'sat_cfdi_use_code', 'label' => 'Uso CFDI', 'type' => 'select', 'options' => 'sat_cfdi_uses', 'default' => 'G03'],
            ['name' => 'sat_tax_regime_code', 'label' => 'Regimen fiscal', 'type' => 'select', 'options' => 'sat_tax_regimes', 'default' => '601'],
            ['name' => 'fiscal_operation_type_id', 'label' => 'Operacion fiscal', 'type' => 'select', 'options' => 'fiscal_operation_types', 'default' => 0],
            ['name' => 'shipping_method_id', 'label' => 'Tipo envio', 'type' => 'select', 'options' => 'shipping_methods', 'default' => 0],
            ['name' => 'credit_limit', 'label' => 'Limite credito', 'type' => 'number', 'default' => 0],
            ['name' => 'credit_days', 'label' => 'Dias credito', 'type' => 'integer', 'default' => 0],
            ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'default' => ''],
            ['name' => 'onboarding_status', 'label' => 'Validacion proveedor', 'type' => 'select_static', 'options' => [
                ['value' => 'approved', 'label' => 'Aprobado'],
                ['value' => 'pending', 'label' => 'Pendiente'],
                ['value' => 'rejected', 'label' => 'Rechazado'],
            ], 'default' => 'approved'],
            ['name' => 'onboarding_notes', 'label' => 'Notas de validacion', 'type' => 'textarea', 'default' => ''],
            ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
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
        # SE RECORREN SECCIONES
        $items = [];
        foreach ($this->get_definitions() as $key => $definition) {
            $class = $definition['model'];
            $query = $class::query()->order_by('id', 'desc');

            if (isset($definition['filter'])) {
                $query->where($definition['filter'][0], 'in', $definition['filter'][1]);
            }
            if ($definition['table'] === 'core_parties') {
                $this->apply_party_scope($query, 't0', 'any');
            }

            $items[$key] = [];
            foreach ($query->get() as $row) {
                $items[$key][] = $row->to_array();
            }
        }

        return $items;
    }

    /**
     * GET OPTIONS
     *
     * OBTIENE OPCIONES PARA SELECTS ENTRE MODULOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        return [
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'departments' => $this->select_options('core_departments', 'id', 'name'),
            'users' => $this->select_user_options(),
            'sellers' => \DBUtil::table_exists('core_sales_sellers') ? $this->select_options('core_sales_sellers', 'id', 'name') : [],
            'price_lists' => $this->select_options('core_commerce_price_lists', 'id', 'name'),
            'payment_terms' => $this->select_options('core_catalog_payment_terms', 'id', 'name'),
            'sat_cfdi_uses' => Helper_Core_Sat_Catalog::options('core_sat_cfdi_uses'),
            'sat_tax_regimes' => Helper_Core_Sat_Catalog::options('core_sat_tax_regimes'),
            'fiscal_operation_types' => $this->select_options('core_catalog_fiscal_operation_types', 'id', 'name'),
            'shipping_methods' => $this->select_options('core_catalog_shipping_methods', 'id', 'name'),
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASE DE TERCEROS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        return [
            'customers' => (int) \DB::select()->from('core_parties')->where('party_type', 'in', ['customer', 'both'])->execute()->count(),
            'suppliers' => (int) \DB::select()->from('core_parties')->where('party_type', 'in', ['supplier', 'both'])->execute()->count(),
            'supplier_requests' => $this->field_exists('core_parties', 'onboarding_status') ? (int) \DB::select()->from('core_parties')->where('party_type', 'in', ['supplier', 'both'])->where('onboarding_status', '=', 'pending')->execute()->count() : 0,
            'addresses' => (int) \DB::count_records('core_party_addresses'),
            'contacts' => (int) \DB::count_records('core_party_contacts'),
        ];
    }

    protected function review_supplier($status)
    {
        $this->require_access('parties.access[edit]');
        $val = (array) \Input::json();

        try {
            $this->assert_schema_ready();
            if (!$this->field_exists('core_parties', 'onboarding_status')) {
                return $this->json_response(['error' => 'Ejecuta migraciones de proveedores.'], 422);
            }

            $party = Model_Core_Party::find((int) \Arr::get($val, 'id', 0));
            if (!$party || !in_array($party->party_type, ['supplier', 'both'], true)) {
                return $this->json_response(['error' => 'Proveedor no encontrado.'], 404);
            }

            $party->onboarding_status = $status;
            $party->onboarding_notes = trim((string) \Arr::get($val, 'notes', $party->onboarding_notes));
            $party->reviewed_by = (int) $this->user_id;
            $party->reviewed_at = time();
            $party->active = $status === 'approved' ? 1 : 0;
            $party->save();

            Helper_Core_Audit::log([
                'module' => 'parties',
                'action' => 'supplier_'.$status,
                'business_event' => 'parties.supplier_'.$status,
                'entity_type' => 'party',
                'entity_id' => (int) $party->id,
                'table_name' => 'core_parties',
                'summary' => 'Proveedor '.$party->name.' '.$status,
                'new_values' => $party->to_array(),
            ]);

            return $this->json_response([
                'status' => 'ok',
                'items' => $this->get_all_items(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error validando proveedor: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo validar proveedor.'], 400);
        }
    }

    protected function import_parties_csv($path, $default_type)
    {
        $rows = $this->read_csv_rows($path);
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        foreach ($rows as $index => $row) {
            $name = trim((string) \Arr::get($row, 'name', ''));
            $rfc = strtoupper(trim((string) \Arr::get($row, 'rfc', '')));
            if ($name === '' && $rfc === '') {
                $result['skipped']++;
                continue;
            }

            $party_type = $this->party_type(\Arr::get($row, 'party_type', $default_type));
            $data = [
                'party_type' => $party_type,
                'code' => $this->unique_party_code(trim((string) \Arr::get($row, 'code', '')), $name, 0),
                'name' => $name ?: $rfc,
                'legal_name' => trim((string) \Arr::get($row, 'legal_name', $name)),
                'rfc' => $rfc,
                'email' => trim((string) \Arr::get($row, 'email', '')),
                'phone' => trim((string) \Arr::get($row, 'phone', '')),
                'sat_cfdi_use_code' => trim((string) \Arr::get($row, 'sat_cfdi_use_code', $party_type === 'supplier' ? 'G03' : 'S01')),
                'sat_tax_regime_code' => trim((string) \Arr::get($row, 'sat_tax_regime_code', '601')),
                'credit_limit' => max(0, (float) \Arr::get($row, 'credit_limit', 0)),
                'credit_days' => max(0, (int) \Arr::get($row, 'credit_days', 0)),
                'notes' => trim((string) \Arr::get($row, 'notes', '')),
                'active' => 1,
            ];

            if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $result['errors'][] = 'Fila '.($index + 2).': correo invalido.';
                $result['skipped']++;
                continue;
            }

            $existing = $this->find_party_by_rfc_or_code($data['rfc'], (string) \Arr::get($row, 'code', ''));
            if ($existing) {
                $party = \Model_Core_Party::find((int) $existing['id']);
                if ($party) {
                    $data['party_type'] = $this->merge_party_type((string) $party->party_type, $party_type);
                    unset($data['code']);
                    $party->set($data);
                    $party->save();
                    $result['updated']++;
                    continue;
                }
            }

            \Model_Core_Party::forge($data)->save();
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
        $delimiter = $this->detect_csv_delimiter($first);
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

    protected function detect_csv_delimiter($line)
    {
        return substr_count((string) $line, ';') > substr_count((string) $line, ',') ? ';' : ',';
    }

    protected function csv_key($value)
    {
        return strtolower(trim(preg_replace('/[^a-z0-9_]+/i', '_', (string) $value), '_'));
    }

    protected function party_type($value)
    {
        $value = $this->codeify($value);
        $aliases = [
            'cliente' => 'customer',
            'proveedor' => 'supplier',
            'ambos' => 'both',
            'cliente_y_proveedor' => 'both',
        ];
        if (isset($aliases[$value])) {
            return $aliases[$value];
        }
        return in_array($value, ['customer', 'supplier', 'both'], true) ? $value : 'customer';
    }

    protected function merge_party_type($current, $incoming)
    {
        if ($current === $incoming || $current === 'both') {
            return $current;
        }
        return in_array($current, ['customer', 'supplier'], true) && in_array($incoming, ['customer', 'supplier'], true) ? 'both' : $incoming;
    }

    protected function find_party_by_rfc_or_code($rfc, $code)
    {
        if ($rfc !== '') {
            $row = \DB::select('id')->from('core_parties')->where('rfc', '=', $rfc)->execute()->current();
            if ($row) {
                return $row;
            }
        }
        $code = $this->codeify($code);
        if ($code !== '') {
            return \DB::select('id')->from('core_parties')->where('code', '=', $code)->execute()->current();
        }
        return null;
    }

    protected function unique_party_code($code, $name, $id)
    {
        $base = $this->codeify($code ?: $name);
        $base = $base ?: 'tercero';
        $candidate = substr($base, 0, 60);
        $i = 2;
        while (true) {
            $query = \DB::select('id')->from('core_parties')->where('code', '=', $candidate);
            if ($id > 0) {
                $query->where('id', '!=', (int) $id);
            }
            if (!$query->execute()->current()) {
                return $candidate;
            }
            $suffix = '-'.$i++;
            $candidate = substr($base, 0, 60 - strlen($suffix)).$suffix;
        }
    }

    /**
     * SELECT OPTIONS
     *
     * FORMATEA OPCIONES PARA CAMPOS SELECT
     *
     * @access  protected
     * @return  Array
     */
    protected function select_options($table, $value_field, $label_field)
    {
        $rows = \DB::select($value_field, $label_field)
            ->from($table)
            ->where('active', '=', 1)
            ->order_by($label_field, 'asc')
            ->execute();

        $options = [];
        foreach ($rows as $row) {
            $options[] = [
                'value' => (string) $row[$value_field],
                'label' => (string) $row[$label_field],
            ];
        }

        return $options;
    }

    protected function select_user_options()
    {
        $options = [['value' => '0', 'label' => 'Sin asignar']];
        foreach (\DB::select('id', 'username', 'email')->from('users')->order_by('username', 'asc')->execute() as $row) {
            $label = trim((string) $row['username']);
            if ($label === '') {
                $label = (string) $row['email'];
            }
            $options[] = ['value' => (string) $row['id'], 'label' => $label];
        }
        return $options;
    }

    protected function field_exists($table, $field)
    {
        return \DBUtil::field_exists($table, [$field]);
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS NECESARIAS EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        foreach ($this->get_definitions() as $definition) {
            if (!\DBUtil::table_exists($definition['table'])) {
                throw new \RuntimeException('Falta ejecutar migraciones de terceros.');
            }
        }
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
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
