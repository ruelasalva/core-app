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
            'price_lists' => $this->select_options('core_commerce_price_lists', 'id', 'name'),
            'payment_terms' => $this->select_options('core_catalog_payment_terms', 'id', 'name'),
            'sat_cfdi_uses' => $this->select_options('core_sat_cfdi_uses', 'code', 'name'),
            'sat_tax_regimes' => $this->select_options('core_sat_tax_regimes', 'code', 'name'),
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
