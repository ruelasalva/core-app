<?php

/**
 * CONTROLADOR ADMIN_CATALOGS
 *
 * Administra los catalogos base del ERP.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Catalogs extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE CATALOGOS
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('catalogs.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL PRINCIPAL DE CATALOGOS BASE
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE OBTIENE EL GRUPO DE CATALOGOS SOLICITADO
        $group = $this->current_group();
        $groups = $this->get_catalog_groups();

        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Catalogos - '.$groups[$group]['title'];
        $this->template->content = View::forge('admin/catalogs/index', [
            'current_group' => $group,
            'group_title' => $groups[$group]['title'],
            'group_description' => $groups[$group]['description'],
        ]);
    }

    /**
     * DATA
     *
     * ENTREGA DEFINICIONES, OPCIONES Y REGISTROS DE CATALOGOS EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();
            $definitions = $this->get_filtered_catalog_definitions();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'definitions' => $definitions,
                'items' => $this->get_all_items($definitions),
                'options' => $this->get_options(),
                'stats' => $this->get_stats($definitions),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando catalogos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar los catalogos.'], 500);
        }
    }

    /**
     * SAVE
     *
     * CREA O ACTUALIZA UN REGISTRO DE CATALOGO
     *
     * @access  public
     * @return  Response
     */
    public function post_save()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('catalogs.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE INICIALIZAN VARIABLES PRINCIPALES
            $catalog = trim((string) \Arr::get($val, 'catalog', ''));
            $definitions = $this->get_catalog_definitions();

            # VALIDAR CATALOGO
            if (!isset($definitions[$catalog])) {
                return $this->json_response(['error' => 'Catalogo invalido.'], 422);
            }

            # SE PREPARAN DATOS
            $definition = $definitions[$catalog];
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

            if ($catalog === 'currencies' && isset($data['code'])) {
                $data['code'] = strtoupper(substr($data['code'], 0, 3));
            } elseif (isset($data['code'])) {
                $data['code'] = $this->normalize_code($data['code']);
            }

            if (isset($data['currency_code'])) {
                $data['currency_code'] = strtoupper(substr($data['currency_code'], 0, 3));
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
            $filtered_definitions = $this->get_filtered_catalog_definitions();
            return $this->json_response([
                'status' => 'ok',
                'items' => $this->get_all_items($filtered_definitions),
                'options' => $this->get_options(),
                'stats' => $this->get_stats($filtered_definitions),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando catalogo: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el registro.'], 400);
        }
    }

    /**
     * GET CATALOG DEFINITIONS
     *
     * DEFINE LOS CATALOGOS, MODELOS Y CAMPOS ADMINISTRABLES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_catalog_definitions()
    {
        # SE DEFINEN CATALOGOS BASE DEL ERP
        return [
            'currencies' => [
                'title' => 'Monedas',
                'model' => 'Model_Core_Catalog_Currency',
                'table' => 'core_catalog_currencies',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'symbol', 'label' => 'Simbolo', 'type' => 'text', 'default' => ''],
                    ['name' => 'decimals', 'label' => 'Decimales', 'type' => 'integer', 'default' => 2],
                    ['name' => 'is_base', 'label' => 'Moneda base', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'exchange_rates' => [
                'title' => 'Tipos de cambio',
                'model' => 'Model_Core_Catalog_Exchange_Rate',
                'table' => 'core_catalog_exchange_rates',
                'required' => ['currency_code', 'rate_date', 'rate'],
                'fields' => [
                    ['name' => 'currency_code', 'label' => 'Moneda', 'type' => 'select', 'options' => 'currencies', 'default' => 'USD'],
                    ['name' => 'rate_date', 'label' => 'Fecha', 'type' => 'date', 'default' => date('Y-m-d')],
                    ['name' => 'rate', 'label' => 'Tipo de cambio', 'type' => 'number', 'default' => 1],
                    ['name' => 'source', 'label' => 'Fuente', 'type' => 'text', 'default' => 'manual'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'banks' => [
                'title' => 'Bancos',
                'model' => 'Model_Core_Catalog_Bank',
                'table' => 'core_catalog_banks',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'sat_code', 'label' => 'Codigo SAT', 'type' => 'text', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'bank_accounts' => [
                'title' => 'Cuentas bancarias',
                'model' => 'Model_Core_Catalog_Bank_Account',
                'table' => 'core_catalog_bank_accounts',
                'required' => ['bank_id', 'name'],
                'fields' => [
                    ['name' => 'bank_id', 'label' => 'Banco', 'type' => 'select', 'options' => 'banks', 'default' => 0],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'account_number', 'label' => 'Cuenta', 'type' => 'text', 'default' => ''],
                    ['name' => 'clabe', 'label' => 'CLABE', 'type' => 'text', 'default' => ''],
                    ['name' => 'currency_code', 'label' => 'Moneda', 'type' => 'select', 'options' => 'currencies', 'default' => 'MXN'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'taxes' => [
                'title' => 'Impuestos internos',
                'model' => 'Model_Core_Catalog_Tax',
                'table' => 'core_catalog_taxes',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'rate', 'label' => 'Tasa', 'type' => 'number', 'default' => 0],
                    ['name' => 'sat_tax_code', 'label' => 'Impuesto SAT', 'type' => 'select', 'options' => 'sat_taxes', 'default' => '002'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'retentions' => [
                'title' => 'Retenciones internas',
                'model' => 'Model_Core_Catalog_Retention',
                'table' => 'core_catalog_retentions',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'rate', 'label' => 'Tasa', 'type' => 'number', 'default' => 0],
                    ['name' => 'sat_tax_code', 'label' => 'Impuesto SAT', 'type' => 'select', 'options' => 'sat_taxes', 'default' => '001'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'discounts' => [
                'title' => 'Descuentos',
                'model' => 'Model_Core_Catalog_Discount',
                'table' => 'core_catalog_discounts',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'discount_type', 'label' => 'Tipo', 'type' => 'select_static', 'options' => [['value' => 'percent', 'label' => 'Porcentaje'], ['value' => 'amount', 'label' => 'Importe']], 'default' => 'percent'],
                    ['name' => 'value', 'label' => 'Valor', 'type' => 'number', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'units' => [
                'title' => 'Unidades internas',
                'model' => 'Model_Core_Catalog_Unit',
                'table' => 'core_catalog_units',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'sat_unit_code', 'label' => 'Clave unidad SAT', 'type' => 'select', 'options' => 'sat_unit_keys', 'default' => 'H87'],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'document_types' => [
                'title' => 'Tipos de documento',
                'model' => 'Model_Core_Catalog_Document_Type',
                'table' => 'core_catalog_document_types',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'module', 'label' => 'Modulo', 'type' => 'text', 'default' => 'general'],
                    ['name' => 'affects_inventory', 'label' => 'Afecta inventario', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'affects_accounting', 'label' => 'Afecta contabilidad', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'payment_terms' => [
                'title' => 'Condiciones de pago',
                'model' => 'Model_Core_Catalog_Payment_Term',
                'table' => 'core_catalog_payment_terms',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'days', 'label' => 'Dias', 'type' => 'integer', 'default' => 0],
                    ['name' => 'requires_credit', 'label' => 'Requiere credito', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'shipping_carriers' => [
                'title' => 'Paqueterias',
                'model' => 'Model_Core_Catalog_Shipping_Carrier',
                'table' => 'core_catalog_shipping_carriers',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'tracking_url', 'label' => 'URL rastreo', 'type' => 'text', 'default' => ''],
                    ['name' => 'requires_account', 'label' => 'Requiere cuenta', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'shipping_zones' => [
                'title' => 'Zonas de envio',
                'model' => 'Model_Core_Catalog_Shipping_Zone',
                'table' => 'core_catalog_shipping_zones',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'country_code', 'label' => 'Pais', 'type' => 'text', 'default' => 'MX'],
                    ['name' => 'state_codes', 'label' => 'Estados', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'postal_codes', 'label' => 'Codigos postales', 'type' => 'textarea', 'default' => ''],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'shipping_methods' => [
                'title' => 'Tipos de envio',
                'model' => 'Model_Core_Catalog_Shipping_Method',
                'table' => 'core_catalog_shipping_methods',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'delivery_type', 'label' => 'Tipo entrega', 'type' => 'select_static', 'options' => [
                        ['value' => 'parcel', 'label' => 'Paqueteria'],
                        ['value' => 'pickup', 'label' => 'Recoge cliente'],
                        ['value' => 'local_delivery', 'label' => 'Entrega local'],
                        ['value' => 'digital', 'label' => 'Digital'],
                    ], 'default' => 'parcel'],
                    ['name' => 'requires_address', 'label' => 'Requiere direccion', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'carrier_services' => [
                'title' => 'Servicios de paqueteria',
                'model' => 'Model_Core_Catalog_Carrier_Service',
                'table' => 'core_catalog_carrier_services',
                'required' => ['carrier_id', 'code', 'name'],
                'fields' => [
                    ['name' => 'carrier_id', 'label' => 'Paqueteria', 'type' => 'select', 'options' => 'shipping_carriers', 'default' => 0],
                    ['name' => 'shipping_method_id', 'label' => 'Tipo envio', 'type' => 'select', 'options' => 'shipping_methods', 'default' => 0],
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'estimated_days', 'label' => 'Dias estimados', 'type' => 'integer', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'shipment_statuses' => [
                'title' => 'Estatus de guia/envio',
                'model' => 'Model_Core_Catalog_Shipment_Status',
                'table' => 'core_catalog_shipment_statuses',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'color', 'label' => 'Color badge', 'type' => 'select_static', 'options' => [
                        ['value' => 'secondary', 'label' => 'Gris'],
                        ['value' => 'info', 'label' => 'Azul'],
                        ['value' => 'warning', 'label' => 'Amarillo'],
                        ['value' => 'success', 'label' => 'Verde'],
                        ['value' => 'danger', 'label' => 'Rojo'],
                    ], 'default' => 'secondary'],
                    ['name' => 'is_final', 'label' => 'Estado final', 'type' => 'checkbox', 'default' => 0],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'fiscal_operation_types' => [
                'title' => 'Tipos de operacion fiscal',
                'model' => 'Model_Core_Catalog_Fiscal_Operation_Type',
                'table' => 'core_catalog_fiscal_operation_types',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'operation_scope', 'label' => 'Ambito', 'type' => 'select_static', 'options' => [
                        ['value' => 'sales', 'label' => 'Ventas'],
                        ['value' => 'purchases', 'label' => 'Compras'],
                        ['value' => 'payments', 'label' => 'Pagos'],
                        ['value' => 'returns', 'label' => 'Devoluciones'],
                        ['value' => 'general', 'label' => 'General'],
                    ], 'default' => 'sales'],
                    ['name' => 'requires_cfdi', 'label' => 'Requiere CFDI', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'fiscal_document_rules' => [
                'title' => 'Reglas fiscales por documento',
                'model' => 'Model_Core_Catalog_Fiscal_Document_Rule',
                'table' => 'core_catalog_fiscal_document_rules',
                'required' => ['code', 'name'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Codigo', 'type' => 'text', 'default' => ''],
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'default' => ''],
                    ['name' => 'document_type_id', 'label' => 'Documento', 'type' => 'select', 'options' => 'document_types', 'default' => 0],
                    ['name' => 'operation_type_id', 'label' => 'Operacion fiscal', 'type' => 'select', 'options' => 'fiscal_operation_types', 'default' => 0],
                    ['name' => 'sat_cfdi_use_code', 'label' => 'Uso CFDI', 'type' => 'select', 'options' => 'sat_cfdi_uses', 'default' => 'G03'],
                    ['name' => 'sat_payment_form_code', 'label' => 'Forma pago SAT', 'type' => 'select', 'options' => 'sat_payment_forms', 'default' => '99'],
                    ['name' => 'sat_payment_method_code', 'label' => 'Metodo pago SAT', 'type' => 'select', 'options' => 'sat_payment_methods', 'default' => 'PPD'],
                    ['name' => 'sat_tax_regime_code', 'label' => 'Regimen fiscal', 'type' => 'select', 'options' => 'sat_tax_regimes', 'default' => '601'],
                    ['name' => 'requires_rfc', 'label' => 'Requiere RFC', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'requires_fiscal_address', 'label' => 'Requiere domicilio fiscal', 'type' => 'checkbox', 'default' => 1],
                    ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
        ];
    }

    /**
     * GET FILTERED CATALOG DEFINITIONS
     *
     * REGRESA SOLO LOS CATALOGOS DEL GRUPO ACTUAL PARA NO SATURAR LA PANTALLA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_filtered_catalog_definitions()
    {
        # SE OBTIENE MAPA DEL GRUPO
        $group = $this->current_group();
        $groups = $this->get_catalog_groups();
        $allowed = $groups[$group]['catalogs'];
        $definitions = $this->get_catalog_definitions();

        # SE FILTRAN DEFINICIONES CONSERVANDO EL ORDEN DEL GRUPO
        $filtered = [];
        foreach ($allowed as $key) {
            if (isset($definitions[$key])) {
                $filtered[$key] = $definitions[$key];
            }
        }

        return $filtered;
    }

    /**
     * GET CATALOG GROUPS
     *
     * DEFINE LA AGRUPACION OPERATIVA DE LOS CATALOGOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_catalog_groups()
    {
        return [
            'general' => [
                'title' => 'Generales',
                'description' => 'Catalogos transversales para documentos, descuentos, unidades y condiciones base.',
                'catalogs' => ['document_types', 'discounts', 'units', 'payment_terms'],
            ],
            'financial' => [
                'title' => 'Monedas y bancos',
                'description' => 'Monedas, tipos de cambio, bancos y cuentas bancarias.',
                'catalogs' => ['currencies', 'exchange_rates', 'banks', 'bank_accounts'],
            ],
            'fiscal' => [
                'title' => 'Fiscales',
                'description' => 'Impuestos internos, retenciones y reglas fiscales conectadas con SAT.',
                'catalogs' => ['taxes', 'retentions', 'fiscal_operation_types', 'fiscal_document_rules'],
            ],
            'logistics' => [
                'title' => 'Logisticos',
                'description' => 'Paqueterias, zonas, tipos de envio, servicios y estatus de guia.',
                'catalogs' => ['shipping_carriers', 'shipping_zones', 'shipping_methods', 'carrier_services', 'shipment_statuses'],
            ],
        ];
    }

    /**
     * CURRENT GROUP
     *
     * NORMALIZA EL GRUPO SOLICITADO POR URL
     *
     * @access  protected
     * @return  String
     */
    protected function current_group()
    {
        # SE VALIDA CONTRA GRUPOS PERMITIDOS
        $group = trim((string) \Input::get('group', 'general'));
        return isset($this->get_catalog_groups()[$group]) ? $group : 'general';
    }

    /**
     * GET ALL ITEMS
     *
     * OBTIENE LOS REGISTROS DE TODOS LOS CATALOGOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_all_items(array $definitions = null)
    {
        # SE INICIALIZA RESPUESTA
        $items = [];
        $definitions = $definitions ?: $this->get_catalog_definitions();

        # SE RECORREN CATALOGOS
        foreach ($definitions as $key => $definition) {
            $class = $definition['model'];
            $rows = $class::query()->order_by('id', 'desc')->get();
            $items[$key] = [];

            foreach ($rows as $row) {
                $items[$key][] = $row->to_array();
            }
        }

        return $items;
    }

    /**
     * GET OPTIONS
     *
     * OBTIENE OPCIONES PARA SELECTS ENTRE CATALOGOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        # SE PREPARAN OPCIONES DINAMICAS
        return [
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'banks' => $this->select_options('core_catalog_banks', 'id', 'name'),
            'document_types' => $this->select_options('core_catalog_document_types', 'id', 'name'),
            'shipping_carriers' => $this->select_options('core_catalog_shipping_carriers', 'id', 'name'),
            'shipping_methods' => $this->select_options('core_catalog_shipping_methods', 'id', 'name'),
            'fiscal_operation_types' => $this->select_options('core_catalog_fiscal_operation_types', 'id', 'name'),
            'sat_cfdi_uses' => $this->select_options('core_sat_cfdi_uses', 'code', 'name'),
            'sat_payment_forms' => $this->select_options('core_sat_payment_forms', 'code', 'name'),
            'sat_payment_methods' => $this->select_options('core_sat_payment_methods', 'code', 'name'),
            'sat_tax_regimes' => $this->select_options('core_sat_tax_regimes', 'code', 'name'),
            'sat_taxes' => $this->select_options('core_sat_taxes', 'code', 'name'),
            'sat_unit_keys' => $this->select_options('core_sat_unit_keys', 'code', 'name'),
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASICOS POR CATALOGO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats(array $definitions = null)
    {
        # SE INICIALIZAN CONTADORES
        $stats = [];
        $definitions = $definitions ?: $this->get_catalog_definitions();

        # SE RECORREN TABLAS
        foreach ($definitions as $key => $definition) {
            $stats[$key] = (int) \DB::count_records($definition['table']);
        }

        return $stats;
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
        # SE CONSULTAN REGISTROS ACTIVOS
        $rows = \DB::select($value_field, $label_field)
            ->from($table)
            ->where('active', '=', 1)
            ->order_by($label_field, 'asc')
            ->execute();

        # SE FORMATEAN OPCIONES
        $options = [];
        foreach ($rows as $row) {
            $options[] = [
                'value' => (string) $row[$value_field],
                'label' => (string) $row[$label_field],
            ];
        }

        return $options;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS DE CATALOGOS EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach ($this->get_catalog_definitions() as $definition) {
            if (!\DBUtil::table_exists($definition['table'])) {
                throw new \RuntimeException('Falta ejecutar migraciones de catalogos.');
            }
        }

        # SE VERIFICA LA BASE SAT OFICIAL PARA CATALOGOS RELACIONADOS
        foreach (['core_sat_taxes', 'core_sat_unit_keys', 'core_sat_cfdi_uses', 'core_sat_payment_forms', 'core_sat_payment_methods', 'core_sat_tax_regimes'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de catalogos SAT.');
            }
        }
    }

    /**
     * NORMALIZE CODE
     *
     * NORMALIZA CODIGOS INTERNOS DE CATALOGOS
     *
     * @access  protected
     * @return  String
     */
    protected function normalize_code($value)
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
