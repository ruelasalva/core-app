<?php

/**
 * CONTROLADOR ADMIN_FISCAL
 *
 * Muestra el dashboard fiscal de solo lectura.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Fiscal extends Controller_Adminbase
{
    protected $taxpayer_rfc_source = 'no configurado';

    /**
     * INDEX
     *
     * MUESTRA EL DASHBOARD FISCAL MENSUAL.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();

        # SE CARGAN DATOS SOLO LECTURA
        $dashboard = $this->dashboard_data($rfc, $period);

        # SE CARGA LA VISTA
        $this->template->title = 'Dashboard Fiscal';
        $this->template->content = \View::forge('admin/fiscal/index', [
            'title' => 'Dashboard Fiscal',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'dashboard' => $dashboard,
            'dashboard_json' => json_encode($dashboard, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
        ]);
    }

    /**
     * VAT
     *
     * MUESTRA IVA MENSUAL DETALLADO DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function action_vat()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();

        # SE CARGAN DATOS SOLO LECTURA
        $detail = $this->vat_detail_data($rfc, $period);

        # SE CARGA LA VISTA
        $this->template->title = 'IVA Mensual';
        $this->template->content = \View::forge('admin/fiscal/vat', [
            'title' => 'IVA Mensual Detallado',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'detail' => $detail,
        ]);
    }

    /**
     * DIOT
     *
     * MUESTRA PREPARACION DIOT DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function action_diot()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();

        # SE CARGAN DATOS SOLO LECTURA
        $preview = $this->diot_preview_data($rfc, $period);

        # SE CARGA LA VISTA
        $this->template->title = 'Preparacion DIOT';
        $this->template->content = \View::forge('admin/fiscal/diot', [
            'title' => 'Preparacion DIOT',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'preview' => $preview,
        ]);
    }

    /**
     * DATA
     *
     * ENTREGA DATOS JSON PARA EL DASHBOARD FISCAL.
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();

            return $this->json_response([
                'success' => true,
                'dashboard' => $this->dashboard_data($rfc, $period),
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard fiscal data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar el dashboard fiscal.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * VAT DATA
     *
     * ENTREGA DATOS JSON PARA IVA MENSUAL DETALLADO.
     *
     * @access  public
     * @return  Response
     */
    public function action_vat_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();

            return $this->json_response([
                'success' => true,
                'detail' => $this->vat_detail_data($rfc, $period),
            ]);
        } catch (\Exception $e) {
            \Log::error('IVA mensual data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar el IVA mensual.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * DIOT DATA
     *
     * ENTREGA DATOS JSON PARA PREPARACION DIOT.
     *
     * @access  public
     * @return  Response
     */
    public function action_diot_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();

            return $this->json_response([
                'success' => true,
                'preview' => $this->diot_preview_data($rfc, $period),
            ]);
        } catch (\Exception $e) {
            \Log::error('Preparacion DIOT data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar la preparacion DIOT.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * DASHBOARD DATA
     *
     * CONSOLIDA PERIODO, ULTIMA CONSTRUCCION Y RESUMEN IVA.
     *
     * @access  protected
     * @return  Array
     */
    protected function dashboard_data($rfc, $period)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'period_status' => 'sin periodo',
            'period_status_label' => 'Sin periodo',
            'last_build' => null,
            'ledger_lines_count' => 0,
            'issued_vat_transferred' => 0,
            'received_vat_transferred' => 0,
            'vat_retained' => 0,
            'isr_retained' => 0,
            'preliminary_vat_payable' => 0,
            'validation' => [
                'cfdi_count' => 0,
                'detail_count' => 0,
                'ledger_lines' => 0,
                'issued_vat_transferred' => 0,
                'received_vat_transferred' => 0,
                'vat_retained' => 0,
                'isr_retained' => 0,
                'warning_count' => 0,
                'error_count' => 0,
                'warnings' => [],
                'errors' => [],
            ],
            'commands' => [
                'build' => '',
                'validate' => '',
                'close' => '',
            ],
            'warnings' => [],
        ];

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('Dashboard fiscal sin RFC configurado usuario='.(int) $this->user_id);
            return $data;
        }

        $data['commands'] = $this->fiscal_commands($rfc, $period);

        try {
            $period_service = new \Service_Core_Fiscal_PeriodService();
            $period_row = $period_service->find($rfc, $period);
            if ($period_row) {
                $data['period_status'] = (string) $period_row['status'];
                $data['period_status_label'] = $this->period_status_label((string) $period_row['status']);
                $data['period_id'] = (int) $period_row['id'];
            }

            $summary = (new \Service_Core_Fiscal_VatSummary())->calculate($rfc, $period);
            $data['ledger_lines_count'] = (int) $summary['ledger_rows'];
            $data['issued_vat_transferred'] = (float) $summary['issued_vat_transferred'];
            $data['received_vat_transferred'] = (float) $summary['received_vat_transferred'];
            $data['vat_retained'] = round((float) $summary['vat_retained_by_customers'] + (float) $summary['vat_retained_from_suppliers'], 6);
            $data['isr_retained'] = (float) $summary['isr_retained_from_suppliers'];
            $data['preliminary_vat_payable'] = (float) $summary['preliminary_vat_payable'];
            $data['warnings'] = (array) $summary['warnings'];
            $data['last_build'] = $this->last_ledger_build($rfc, $period);
            $data['validation'] = $this->validation_summary($data, $summary);
        } catch (\Exception $e) {
            \Log::error('Dashboard fiscal: '.$e->getMessage());
            $data['warnings'][] = 'No se pudo cargar el dashboard fiscal: '.$e->getMessage();
            $data['validation']['errors'][] = $e->getMessage();
            $data['validation']['error_count'] = count($data['validation']['errors']);
        }

        \Log::info('Dashboard fiscal consultado RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id);

        return $data;
    }

    /**
     * VAT DETAIL DATA
     *
     * CONSOLIDA EL DETALLE IVA DEL PERIODO.
     *
     * @access  protected
     * @return  Array
     */
    protected function vat_detail_data($rfc, $period)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'ledger_rows' => 0,
            'sales' => [
                'taxed_base' => 0,
                'vat_transferred' => 0,
                'zero_base' => 0,
                'exempt_base' => 0,
            ],
            'purchases' => [
                'taxed_base' => 0,
                'vat_creditable' => 0,
                'zero_base' => 0,
                'exempt_base' => 0,
            ],
            'withholdings' => [
                'vat_retained' => 0,
                'vat_retained_by_customers' => 0,
                'vat_retained_from_suppliers' => 0,
                'isr_retained' => 0,
            ],
            'result' => [
                'vat_caused' => 0,
                'vat_creditable' => 0,
                'preliminary_vat_payable' => 0,
            ],
            'warnings' => [],
        ];

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('IVA mensual sin RFC configurado usuario='.(int) $this->user_id);
            return $data;
        }

        try {
            $detail = (new \Service_Core_Fiscal_VatDetail())->calculate($rfc, $period);
            $data = array_replace_recursive($data, $detail);
            $data['rfc_source'] = $this->taxpayer_rfc_source;
            $data['rfc_source_label'] = $this->rfc_source_label($this->taxpayer_rfc_source);
        } catch (\Exception $e) {
            \Log::error('IVA mensual: '.$e->getMessage());
            $data['warnings'][] = 'No se pudo cargar el IVA mensual: '.$e->getMessage();
        }

        \Log::info('IVA mensual consultado RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id);

        return $data;
    }

    /**
     * DIOT PREVIEW DATA
     *
     * CONSOLIDA LA PREPARACION DIOT DEL PERIODO.
     *
     * @access  protected
     * @return  Array
     */
    protected function diot_preview_data($rfc, $period)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'items' => [],
            'totals' => [
                'taxed_base' => 0,
                'creditable_vat' => 0,
                'vat_retained' => 0,
                'isr_retained' => 0,
                'movement_count' => 0,
                'cfdi_count' => 0,
                'warning_count' => 0,
            ],
            'warnings' => [],
        ];

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('Preparacion DIOT sin RFC configurado usuario='.(int) $this->user_id);
            return $data;
        }

        try {
            $preview = (new \Service_Core_Fiscal_DiotPreview())->calculate($rfc, $period);
            $data = array_replace_recursive($data, $preview);
            $data['rfc_source'] = $this->taxpayer_rfc_source;
            $data['rfc_source_label'] = $this->rfc_source_label($this->taxpayer_rfc_source);
        } catch (\Exception $e) {
            \Log::error('Preparacion DIOT: '.$e->getMessage());
            $data['warnings'][] = 'No se pudo cargar la preparacion DIOT: '.$e->getMessage();
        }

        \Log::info('Preparacion DIOT consultada RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id);

        return $data;
    }

    /**
     * VALIDATION SUMMARY
     *
     * PREPARA INDICADORES DE VALIDACION DE SOLO LECTURA.
     *
     * @access  protected
     * @return  Array
     */
    protected function validation_summary(array $data, array $summary)
    {
        $last_build = (array) \Arr::get($data, 'last_build', []);
        $warnings = (array) \Arr::get($summary, 'warnings', []);
        $errors = [];

        if (empty($last_build)) {
            $warnings[] = 'No hay construccion registrada del libro fiscal para este periodo.';
        } elseif ((int) \Arr::get($last_build, 'error_count', 0) > 0) {
            $errors[] = 'La ultima construccion registro errores. Ejecuta la validacion del libro fiscal.';
        }

        if ((int) \Arr::get($summary, 'ledger_rows', 0) === 0) {
            $warnings[] = 'No hay lineas fiscales para el RFC y periodo consultado.';
        }

        return [
            'cfdi_count' => (int) \Arr::get($last_build, 'cfdi_count', 0),
            'detail_count' => (int) \Arr::get($last_build, 'detail_count', 0),
            'ledger_lines' => (int) \Arr::get($summary, 'ledger_rows', 0),
            'issued_vat_transferred' => (float) \Arr::get($summary, 'issued_vat_transferred', 0),
            'received_vat_transferred' => (float) \Arr::get($summary, 'received_vat_transferred', 0),
            'vat_retained' => round((float) \Arr::get($summary, 'vat_retained_by_customers', 0) + (float) \Arr::get($summary, 'vat_retained_from_suppliers', 0), 6),
            'isr_retained' => (float) \Arr::get($summary, 'isr_retained_from_suppliers', 0),
            'warning_count' => count($warnings),
            'error_count' => count($errors),
            'warnings' => array_values(array_unique($warnings)),
            'errors' => $errors,
        ];
    }

    /**
     * FISCAL COMMANDS
     *
     * GENERA COMANDOS INFORMATIVOS PARA OPERACION MANUAL.
     *
     * @access  protected
     * @return  Array
     */
    protected function fiscal_commands($rfc, $period)
    {
        return [
            'build' => 'php oil refine buildfiscalledger --rfc='.$rfc.' --period='.$period,
            'validate' => 'php oil refine validatefiscalledger --rfc='.$rfc.' --period='.$period,
            'close' => 'php oil refine closefiscalperiod --rfc='.$rfc.' --period='.$period,
        ];
    }

    /**
     * RFC SOURCE LABEL
     *
     * TRADUCE LA FUENTE INTERNA DEL RFC.
     *
     * @access  protected
     * @return  String
     */
    protected function rfc_source_label($source)
    {
        $source = (string) $source;
        if (strpos($source, 'core_settings') === 0 || $source === 'core_companies activo') {
            return 'Configuracion de empresa';
        }
        if ($source === 'credencial SAT activa') {
            return 'Credencial SAT';
        }
        if ($source === 'parametro de depuracion') {
            return 'Parametro debug';
        }
        return 'No configurado';
    }

    /**
     * PERIOD STATUS LABEL
     *
     * TRADUCE EL ESTADO DEL PERIODO FISCAL.
     *
     * @access  protected
     * @return  String
     */
    protected function period_status_label($status)
    {
        $labels = [
            'open' => 'Abierto',
            'locked' => 'Bloqueado',
            'closed' => 'Cerrado',
        ];

        return isset($labels[$status]) ? $labels[$status] : 'Sin periodo';
    }

    /**
     * LAST LEDGER BUILD
     *
     * OBTIENE LA ULTIMA CONSTRUCCION DEL LIBRO FISCAL PARA EL PERIODO.
     *
     * @access  protected
     * @return  Array|null
     */
    protected function last_ledger_build($rfc, $period)
    {
        if (!\DBUtil::table_exists('core_fiscal_ledger_builds')) {
            return null;
        }

        $dates = $this->period_dates($period);
        $row = \DB::select()
            ->from('core_fiscal_ledger_builds')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('date_from', '=', $dates['from'])
            ->where('date_to', '=', $dates['to'])
            ->where('active', '=', 1)
            ->order_by('started_at', 'desc')
            ->order_by('id', 'desc')
            ->execute()
            ->current();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'status' => (string) $row['status'],
            'cfdi_count' => (int) $row['cfdi_count'],
            'detail_count' => (int) $row['detail_count'],
            'line_count' => (int) $row['line_count'],
            'error_count' => (int) $row['error_count'],
            'started_at' => (int) $row['started_at'],
            'finished_at' => (int) $row['finished_at'],
        ];
    }

    /**
     * RESOLVE TAXPAYER RFC
     *
     * RESUELVE EL RFC FISCAL ACTIVO DEL ERP.
     *
     * @access  protected
     * @return  String
     */
    protected function resolve_taxpayer_rfc()
    {
        # 1. CONFIGURACION GENERAL / EMPRESA
        $rfc = $this->company_config_rfc();
        if ($rfc !== '') {
            return $rfc;
        }

        # 2. CREDENCIAL SAT ACTIVA
        $rfc = $this->active_sat_credential_rfc();
        if ($rfc !== '') {
            return $rfc;
        }

        # 3. PARAMETRO MANUAL SOLO PARA DEPURACION
        $rfc = $this->normalize_rfc((string) \Input::get('rfc', ''));
        if ($rfc !== '') {
            $this->taxpayer_rfc_source = 'parametro de depuracion';
            return $rfc;
        }

        $this->taxpayer_rfc_source = 'no configurado';
        return '';
    }

    /**
     * COMPANY CONFIG RFC
     *
     * BUSCA RFC EN CONFIGURACION CENTRAL Y EMPRESA ACTIVA.
     *
     * @access  protected
     * @return  String
     */
    protected function company_config_rfc()
    {
        if (\DBUtil::table_exists('core_settings')) {
            $candidates = [
                ['company', 'rfc'],
                ['company', 'taxpayer_rfc'],
                ['fiscal', 'rfc'],
                ['fiscal', 'taxpayer_rfc'],
            ];

            foreach ($candidates as $candidate) {
                $row = \DB::select('value')
                    ->from('core_settings')
                    ->where('setting_group', '=', $candidate[0])
                    ->where('setting_key', '=', $candidate[1])
                    ->execute()
                    ->current();

                $rfc = $row ? $this->normalize_rfc((string) $row['value']) : '';
                if ($rfc !== '') {
                    $this->taxpayer_rfc_source = 'core_settings '.$candidate[0].'.'.$candidate[1];
                    return $rfc;
                }
            }
        }

        if (\DBUtil::table_exists('core_companies')) {
            $row = \DB::select('rfc')
                ->from('core_companies')
                ->where('active', '=', 1)
                ->order_by('id', 'asc')
                ->execute()
                ->current();

            $rfc = $row ? $this->normalize_rfc((string) $row['rfc']) : '';
            if ($rfc !== '') {
                $this->taxpayer_rfc_source = 'core_companies activo';
                return $rfc;
            }
        }

        return '';
    }

    /**
     * ACTIVE SAT CREDENTIAL RFC
     *
     * BUSCA RFC EN CREDENCIALES SAT ACTIVAS.
     *
     * @access  protected
     * @return  String
     */
    protected function active_sat_credential_rfc()
    {
        if (!\DBUtil::table_exists('core_sat_credentials')) {
            return '';
        }

        $row = \DB::select('rfc')
            ->from('core_sat_credentials')
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        $rfc = $row ? $this->normalize_rfc((string) $row['rfc']) : '';
        if ($rfc !== '') {
            $this->taxpayer_rfc_source = 'credencial SAT activa';
        }

        return $rfc;
    }

    /**
     * NORMALIZE RFC
     *
     * NORMALIZA RFC A MAYUSCULAS SIN ESPACIOS.
     *
     * @access  protected
     * @return  String
     */
    protected function normalize_rfc($rfc)
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
    }

    /**
     * REQUESTED PERIOD
     *
     * OBTIENE PERIODO SOLICITADO O MES ACTUAL.
     *
     * @access  protected
     * @return  String
     */
    protected function requested_period()
    {
        $period = trim((string) \Input::get('period', date('Y-m')));
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period)) {
            return date('Y-m');
        }

        return $period;
    }

    protected function period_dates($period)
    {
        $from = $period.'-01';
        return [
            'from' => $from,
            'to' => date('Y-m-t', strtotime($from)),
        ];
    }
}
