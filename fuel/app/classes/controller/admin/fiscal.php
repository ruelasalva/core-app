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
     * LEDGER
     *
     * MUESTRA EL LIBRO FISCAL DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function action_ledger()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();
        $filters = $this->ledger_filters();

        # SE CARGAN DATOS SOLO LECTURA
        $ledger = $this->ledger_detail_data($rfc, $period, $filters);

        # SE CARGA LA VISTA
        $this->template->title = 'Libro Fiscal';
        $this->template->content = \View::forge('admin/fiscal/ledger', [
            'title' => 'Libro Fiscal',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'filters' => $filters,
            'ledger' => $ledger,
        ]);
    }

    /**
     * VALIDATIONS
     *
     * MUESTRA EL HISTORIAL DE VALIDACIONES FISCALES.
     *
     * @access  public
     * @return  Void
     */
    public function action_validations()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();

        # SE CARGAN DATOS SOLO LECTURA
        $validations = $this->fiscal_validations_data($rfc, $period);

        # SE CARGA LA VISTA
        $this->template->title = 'Validaciones Fiscales';
        $this->template->content = \View::forge('admin/fiscal/validations', [
            'title' => 'Validaciones Fiscales',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'validations' => $validations,
        ]);
    }

    /**
     * EVENTS
     *
     * MUESTRA LA BITACORA FISCAL DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function action_events()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();
        $filters = $this->event_filters();

        # SE CARGAN DATOS SOLO LECTURA
        $events = $this->fiscal_events_data($rfc, $period, $filters);

        # SE CARGA LA VISTA
        $this->template->title = 'Bitacora Fiscal';
        $this->template->content = \View::forge('admin/fiscal/events', [
            'title' => 'Bitacora Fiscal',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'filters' => $filters,
            'events' => $events,
        ]);
    }

    /**
     * REP AUDIT
     *
     * MUESTRA LA AUDITORIA REP/PPD DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function action_rep_audit()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();
        $filters = $this->rep_audit_filters();

        # SE CARGAN DATOS SOLO LECTURA
        $audit = $this->rep_audit_data($rfc, $period, $filters);

        # SE CARGA LA VISTA
        $this->template->title = 'Auditoria REP/PPD';
        $this->template->content = \View::forge('admin/fiscal/rep_audit', [
            'title' => 'Auditoria REP/PPD',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'filters' => $filters,
            'audit' => $audit,
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
     * RECONCILIATION
     *
     * MUESTRA LA CONCILIACION FISCAL-CONTABLE DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function action_reconciliation()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();

        # SE CARGAN DATOS SOLO LECTURA
        $reconciliation = $this->accounting_reconciliation_data($rfc, $period);

        # SE CARGA LA VISTA
        $this->template->title = 'Conciliación Fiscal-Contable';
        $this->template->content = \View::forge('admin/fiscal/reconciliation', [
            'title' => 'Conciliación Fiscal-Contable',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'reconciliation' => $reconciliation,
        ]);
    }

    /**
     * CLOSING
     *
     * MUESTRA EL CENTRO DE CIERRE FISCAL DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function action_closing()
    {
        # VALIDAR PERMISO DEL MODULO FISCAL
        $this->require_access('fiscal.access');

        # SE PREPARAN FILTROS DE CONSULTA
        $rfc = $this->resolve_taxpayer_rfc();
        $period = $this->requested_period();

        # SE CARGAN DATOS SOLO LECTURA
        $closing = $this->closing_data($rfc, $period);

        # SE CARGA LA VISTA
        $this->template->title = 'Centro de Cierre Fiscal';
        $this->template->content = \View::forge('admin/fiscal/closing', [
            'title' => 'Centro de Cierre Fiscal',
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'period' => $period,
            'closing' => $closing,
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
     * LEDGER DATA
     *
     * ENTREGA DATOS JSON PARA EL LIBRO FISCAL.
     *
     * @access  public
     * @return  Response
     */
    public function action_ledger_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();
            $filters = $this->ledger_filters();

            return $this->json_response([
                'success' => true,
                'ledger' => $this->ledger_detail_data($rfc, $period, $filters),
            ]);
        } catch (\Exception $e) {
            \Log::error('Libro fiscal data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar el libro fiscal.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * VALIDATIONS DATA
     *
     * ENTREGA DATOS JSON DEL HISTORIAL DE VALIDACIONES FISCALES.
     *
     * @access  public
     * @return  Response
     */
    public function action_validations_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();

            return $this->json_response([
                'success' => true,
                'validations' => $this->fiscal_validations_data($rfc, $period),
            ]);
        } catch (\Exception $e) {
            \Log::error('Validaciones fiscales data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudieron cargar las validaciones fiscales.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * EVENTS DATA
     *
     * ENTREGA DATOS JSON DE LA BITACORA FISCAL.
     *
     * @access  public
     * @return  Response
     */
    public function action_events_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();
            $filters = $this->event_filters();

            return $this->json_response([
                'success' => true,
                'events' => $this->fiscal_events_data($rfc, $period, $filters),
            ]);
        } catch (\Exception $e) {
            \Log::error('Bitacora fiscal data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar la bitacora fiscal.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * REP AUDIT DATA
     *
     * ENTREGA DATOS JSON PARA AUDITORIA REP/PPD.
     *
     * @access  public
     * @return  Response
     */
    public function action_rep_audit_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();
            $filters = $this->rep_audit_filters();

            return $this->json_response([
                'success' => true,
                'audit' => $this->rep_audit_data($rfc, $period, $filters),
            ]);
        } catch (\Exception $e) {
            \Log::error('Auditoria REP/PPD data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar la auditoria REP/PPD.',
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
     * RECONCILIATION DATA
     *
     * ENTREGA DATOS JSON PARA CONCILIACION FISCAL-CONTABLE.
     *
     * @access  public
     * @return  Response
     */
    public function action_reconciliation_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();

            return $this->json_response([
                'success' => true,
                'reconciliation' => $this->accounting_reconciliation_data($rfc, $period),
            ]);
        } catch (\Exception $e) {
            \Log::error('Conciliacion fiscal-contable data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar la conciliación fiscal-contable.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * CLOSING DATA
     *
     * ENTREGA DATOS JSON PARA EL CENTRO DE CIERRE FISCAL.
     *
     * @access  public
     * @return  Response
     */
    public function action_closing_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO FISCAL
            $this->require_access('fiscal.access');

            # SE PREPARAN FILTROS DE CONSULTA
            $rfc = $this->resolve_taxpayer_rfc();
            $period = $this->requested_period();

            return $this->json_response([
                'success' => true,
                'closing' => $this->closing_data($rfc, $period),
            ]);
        } catch (\Exception $e) {
            \Log::error('Centro de cierre fiscal data: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'error' => 'No se pudo cargar el centro de cierre fiscal.',
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
     * LEDGER DETAIL DATA
     *
     * CONSULTA EL LIBRO FISCAL DE SOLO LECTURA.
     *
     * @access  protected
     * @return  Array
     */
    protected function ledger_detail_data($rfc, $period, array $filters)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'filters' => $filters,
            'items' => [],
            'total' => 0,
            'shown' => 0,
            'limit' => 500,
            'has_more' => false,
            'warnings' => [],
        ];

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('Libro fiscal sin RFC configurado usuario='.(int) $this->user_id);
            return $data;
        }

        try {
            $ledger = (new \Service_Core_Fiscal_LedgerDetail())->search($rfc, $period, $filters, 500);
            $data = array_replace_recursive($data, $ledger);
            $data['rfc_source'] = $this->taxpayer_rfc_source;
            $data['rfc_source_label'] = $this->rfc_source_label($this->taxpayer_rfc_source);
        } catch (\Exception $e) {
            \Log::error('Libro fiscal: '.$e->getMessage());
            $data['warnings'][] = 'No se pudo cargar el libro fiscal: '.$e->getMessage();
        }

        \Log::info('Libro fiscal consultado RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id.' total='.(int) $data['total']);

        return $data;
    }

    /**
     * FISCAL VALIDATIONS DATA
     *
     * CONSULTA EL HISTORIAL DE VALIDACIONES FISCALES PERSISTIDAS.
     *
     * @access  protected
     * @return  Array
     */
    protected function fiscal_validations_data($rfc, $period)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'items' => [],
            'latest' => null,
            'warnings' => [],
        ];

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('Validaciones fiscales sin RFC configurado usuario='.(int) $this->user_id);
            return $data;
        }

        if (!\DBUtil::table_exists('core_fiscal_validations')) {
            $data['warnings'][] = 'Falta ejecutar migracion 066 para consultar validaciones fiscales.';
            \Log::warning('Validaciones fiscales sin tabla core_fiscal_validations usuario='.(int) $this->user_id);
            return $data;
        }

        $rows = \DB::select()
            ->from('core_fiscal_validations')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('fiscal_period', '=', $period)
            ->where('active', '=', 1)
            ->order_by('executed_at', 'desc')
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $data['items'][] = $this->format_fiscal_validation($row);
        }

        $data['latest'] = count($data['items']) > 0 ? $data['items'][0] : null;
        if (count($data['items']) === 0) {
            $data['warnings'][] = 'No hay validaciones fiscales persistidas para el RFC y periodo seleccionado. Ejecuta validatefiscalledger despues de aplicar la migracion 066.';
        }

        \Log::info('Validaciones fiscales consultadas RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id.' registros='.count($data['items']));

        return $data;
    }

    /**
     * FISCAL EVENTS DATA
     *
     * CONSULTA LA BITACORA FISCAL DE SOLO LECTURA.
     *
     * @access  protected
     * @return  Array
     */
    protected function fiscal_events_data($rfc, $period, array $filters)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'filters' => $filters,
            'items' => [],
            'warnings' => [],
        ];

        if (!\DBUtil::table_exists('core_fiscal_events')) {
            $data['warnings'][] = 'Falta ejecutar migracion 067 para consultar la bitacora fiscal.';
            \Log::warning('Bitacora fiscal sin tabla core_fiscal_events usuario='.(int) $this->user_id);
            return $data;
        }

        $query = \DB::select()
            ->from('core_fiscal_events')
            ->where('active', '=', 1);

        if ($rfc !== '') {
            $query->where('taxpayer_rfc', '=', $rfc);
        }

        if ($period !== '') {
            $query->where('fiscal_period', '=', $period);
        }

        $event_type = trim((string) \Arr::get($filters, 'event_type', ''));
        if ($event_type !== '') {
            $query->where('event_type', '=', $event_type);
        }

        $event_status = trim((string) \Arr::get($filters, 'event_status', ''));
        if ($event_status !== '') {
            $query->where('event_status', '=', $event_status);
        }

        $rows = $query
            ->order_by('executed_at', 'desc')
            ->order_by('id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $data['items'][] = $this->format_fiscal_event($row);
        }

        if (count($data['items']) === 0) {
            $data['warnings'][] = 'No hay eventos fiscales para los filtros seleccionados.';
        }

        \Log::info('Bitacora fiscal consultada RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id.' registros='.count($data['items']));

        return $data;
    }

    /**
     * REP AUDIT DATA
     *
     * CONSULTA LA AUDITORIA REP/PPD EN SERVICIO SEPARADO.
     *
     * @access  protected
     * @return  Array
     */
    protected function rep_audit_data($rfc, $period, array $filters)
    {
        try {
            $service = new \Service_Core_Fiscal_RepAudit();
            return $service->audit($rfc, $period, $filters, $this->rfc_source_label($this->taxpayer_rfc_source));
        } catch (\Exception $e) {
            \Log::error('Auditoria REP/PPD: '.$e->getMessage());
            return [
                'rfc' => $rfc,
                'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
                'period' => $period,
                'filters' => $filters,
                'summary' => [
                    'ppd_issued' => 0,
                    'ppd_received' => 0,
                    'ppd_without_rep' => 0,
                    'related_rep' => 0,
                    'cancelled_rep' => 0,
                    'rep_without_xml' => 0,
                    'duplicate_rep' => 0,
                    'internal_payments_without_rep' => 0,
                    'rep_without_internal_payment' => 0,
                    'pending_balance' => 0,
                ],
                'items' => [],
                'rep_items' => [],
                'internal_payment_items' => [],
                'warnings' => ['No se pudo cargar la auditoria REP/PPD: '.$e->getMessage()],
            ];
        }
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
     * ACCOUNTING RECONCILIATION DATA
     *
     * CONSOLIDA LA CONCILIACION ENTRE LIBRO FISCAL Y CONTABILIDAD.
     *
     * @access  protected
     * @return  Array
     */
    protected function accounting_reconciliation_data($rfc, $period)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'items' => [],
            'totals' => [
                'fiscal_amount' => 0,
                'accounting_amount' => 0,
                'difference' => 0,
            ],
            'warnings' => [],
        ];

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('Conciliacion fiscal-contable sin RFC configurado usuario='.(int) $this->user_id);
            return $data;
        }

        try {
            $reconciliation = (new \Service_Core_Fiscal_AccountingReconciliation())->calculate($rfc, $period);
            $data = array_replace_recursive($data, $reconciliation);
            $data['rfc_source'] = $this->taxpayer_rfc_source;
            $data['rfc_source_label'] = $this->rfc_source_label($this->taxpayer_rfc_source);
        } catch (\Exception $e) {
            \Log::error('Conciliacion fiscal-contable: '.$e->getMessage());
            $data['warnings'][] = 'No se pudo cargar la conciliación fiscal-contable: '.$e->getMessage();
        }

        \Log::info('Conciliacion fiscal-contable consultada RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id);

        return $data;
    }

    /**
     * CLOSING DATA
     *
     * CONSOLIDA EL AVANCE DE CIERRE FISCAL SIN EJECUTAR ACCIONES.
     *
     * @access  protected
     * @return  Array
     */
    protected function closing_data($rfc, $period)
    {
        $data = [
            'rfc' => $rfc,
            'rfc_source' => $this->taxpayer_rfc_source,
            'rfc_source_label' => $this->rfc_source_label($this->taxpayer_rfc_source),
            'period' => $period,
            'period_status' => 'sin periodo',
            'period_status_label' => 'Sin periodo',
            'steps' => [],
            'warnings' => [],
        ];

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('Centro de cierre fiscal sin RFC configurado usuario='.(int) $this->user_id);
            $data['steps'] = $this->empty_closing_steps($period, $rfc);
            return $data;
        }

        try {
            $dashboard = $this->dashboard_data($rfc, $period);
            $vat_detail = $this->vat_detail_data($rfc, $period);
            $reconciliation = $this->accounting_reconciliation_data($rfc, $period);
            $diot = $this->diot_preview_data($rfc, $period);
            $period_row = null;
            $period_id = (int) \Arr::get($dashboard, 'period_id', 0);

            if ($period_id > 0 && \DBUtil::table_exists('core_fiscal_periods')) {
                $period_row = \DB::select()->from('core_fiscal_periods')->where('id', '=', $period_id)->execute()->current();
            }

            $draft_entry = $this->latest_fiscal_entry($period_id, 'draft');
            $posted_entry = $this->latest_fiscal_entry($period_id, 'posted');
            $validation_record = $this->latest_fiscal_validation($rfc, $period);

            $data['period_status'] = (string) \Arr::get($dashboard, 'period_status', 'sin periodo');
            $data['period_status_label'] = (string) \Arr::get($dashboard, 'period_status_label', 'Sin periodo');
            $data['latest_validation'] = $validation_record;
            $data['steps'] = $this->build_closing_steps($period, $rfc, $dashboard, $vat_detail, $reconciliation, $diot, $period_row, $draft_entry, $posted_entry, $validation_record);
            $data['warnings'] = array_values(array_unique(array_merge(
                (array) \Arr::get($dashboard, 'warnings', []),
                (array) \Arr::get($vat_detail, 'warnings', []),
                (array) \Arr::get($reconciliation, 'warnings', []),
                (array) \Arr::get($diot, 'warnings', [])
            )));
        } catch (\Exception $e) {
            \Log::error('Centro de cierre fiscal: '.$e->getMessage());
            $data['warnings'][] = 'No se pudo cargar el centro de cierre fiscal: '.$e->getMessage();
            $data['steps'] = $this->empty_closing_steps($period, $rfc);
        }

        \Log::info('Centro de cierre fiscal consultado RFC='.$rfc.' periodo='.$period.' usuario='.(int) $this->user_id);

        return $data;
    }

    /**
     * BUILD CLOSING STEPS
     *
     * CALCULA LOS ESTADOS DEL FLUJO DE CIERRE.
     *
     * @access  protected
     * @return  Array
     */
    protected function build_closing_steps($period, $rfc, array $dashboard, array $vat_detail, array $reconciliation, array $diot, $period_row, $draft_entry, $posted_entry, $validation_record = null)
    {
        $last_build = (array) \Arr::get($dashboard, 'last_build', []);
        $ledger_lines = (int) \Arr::get($dashboard, 'ledger_lines_count', 0);
        $steps = [];

        if (empty($last_build)) {
            $steps[] = $this->closing_step(1, 'Libro fiscal generado', 'pendiente', $period, $rfc, 0, 0, 'Ejecuta el comando de construccion del libro fiscal.');
        } elseif ((int) \Arr::get($last_build, 'error_count', 0) > 0) {
            $steps[] = $this->closing_step(1, 'Libro fiscal generado', 'error', $period, $rfc, (int) \Arr::get($last_build, 'finished_at', 0), (int) \Arr::get($last_build, 'created_by', 0), 'La ultima construccion registro errores.');
        } elseif ($ledger_lines > 0) {
            $steps[] = $this->closing_step(1, 'Libro fiscal generado', 'completado', $period, $rfc, (int) \Arr::get($last_build, 'finished_at', 0), (int) \Arr::get($last_build, 'created_by', 0), 'Libro fiscal disponible con '.$ledger_lines.' lineas.');
        } else {
            $steps[] = $this->closing_step(1, 'Libro fiscal generado', 'advertencia', $period, $rfc, (int) \Arr::get($last_build, 'finished_at', 0), (int) \Arr::get($last_build, 'created_by', 0), 'La construccion termino sin lineas fiscales.');
        }

        if ($ledger_lines <= 0) {
            $steps[] = $this->closing_step(2, 'Libro fiscal validado', 'pendiente', $period, $rfc, 0, 0, 'Primero construye el libro fiscal.');
        } elseif (empty($validation_record)) {
            $steps[] = $this->closing_step(2, 'Libro fiscal validado', 'pendiente', $period, $rfc, 0, 0, 'No hay validacion fiscal persistida. Ejecuta validatefiscalledger para este RFC y periodo.');
        } elseif ((string) \Arr::get($validation_record, 'status', '') === 'error') {
            $steps[] = $this->closing_step(2, 'Libro fiscal validado', 'error', $period, $rfc, (int) \Arr::get($validation_record, 'executed_at', 0), (int) \Arr::get($validation_record, 'executed_by', 0), 'La ultima validacion persistida tiene errores.');
        } elseif ((string) \Arr::get($validation_record, 'status', '') === 'warning') {
            $steps[] = $this->closing_step(2, 'Libro fiscal validado', 'advertencia', $period, $rfc, (int) \Arr::get($validation_record, 'executed_at', 0), (int) \Arr::get($validation_record, 'executed_by', 0), 'La ultima validacion persistida tiene advertencias por revisar.');
        } else {
            $steps[] = $this->closing_step(2, 'Libro fiscal validado', 'completado', $period, $rfc, (int) \Arr::get($validation_record, 'executed_at', 0), (int) \Arr::get($validation_record, 'executed_by', 0), 'Ultima validacion persistida sin advertencias ni errores.');
        }

        if ((int) \Arr::get($vat_detail, 'ledger_rows', 0) > 0) {
            $vat_status = count((array) \Arr::get($vat_detail, 'warnings', [])) > 0 ? 'advertencia' : 'completado';
            $steps[] = $this->closing_step(3, 'IVA mensual calculado', $vat_status, $period, $rfc, time(), (int) $this->user_id, 'Consulta calculada desde el libro fiscal.');
        } else {
            $steps[] = $this->closing_step(3, 'IVA mensual calculado', 'pendiente', $period, $rfc, 0, 0, 'Sin lineas fiscales para calcular IVA mensual.');
        }

        $reconciliation_items = (array) \Arr::get($reconciliation, 'items', []);
        $reconciliation_diff = abs((float) \Arr::get((array) \Arr::get($reconciliation, 'totals', []), 'difference', 0));
        if (empty($reconciliation_items)) {
            $steps[] = $this->closing_step(4, 'Conciliacion fiscal-contable', 'pendiente', $period, $rfc, 0, 0, 'Sin datos de conciliacion para el periodo.');
        } elseif ($this->reconciliation_has_errors($reconciliation_items)) {
            $steps[] = $this->closing_step(4, 'Conciliacion fiscal-contable', 'error', $period, $rfc, time(), (int) $this->user_id, 'Existen conceptos sin cuenta configurada.');
        } elseif ($reconciliation_diff > 0.01) {
            $steps[] = $this->closing_step(4, 'Conciliacion fiscal-contable', 'advertencia', $period, $rfc, time(), (int) $this->user_id, 'Existe diferencia fiscal-contable por revisar.');
        } else {
            $steps[] = $this->closing_step(4, 'Conciliacion fiscal-contable', 'completado', $period, $rfc, time(), (int) $this->user_id, 'Importes fiscales y contables conciliados.');
        }

        if ($draft_entry) {
            $difference = round((float) $draft_entry['total_debit'] - (float) $draft_entry['total_credit'], 2);
            $status = abs($difference) > 0.01 ? 'advertencia' : 'completado';
            $steps[] = $this->closing_step(5, 'Borrador de poliza generado', $status, $period, $rfc, (int) $draft_entry['created_at'], (int) $draft_entry['created_by'], 'Poliza '.$draft_entry['folio'].' en estado borrador.');
        } else {
            $steps[] = $this->closing_step(5, 'Borrador de poliza generado', 'pendiente', $period, $rfc, 0, 0, 'Genera el borrador fiscal desde terminal cuando las validaciones esten listas.');
        }

        if ($posted_entry) {
            $steps[] = $this->closing_step(6, 'Poliza contabilizada', 'completado', $period, $rfc, (int) $posted_entry['posted_at'], (int) $posted_entry['posted_by'], 'Poliza '.$posted_entry['folio'].' contabilizada.');
        } else {
            $steps[] = $this->closing_step(6, 'Poliza contabilizada', 'pendiente', $period, $rfc, 0, 0, 'Revisa y contabiliza manualmente la poliza fiscal.');
        }

        $diot_items = (array) \Arr::get($diot, 'items', []);
        $diot_note = empty($diot_items) ? 'Sin informacion DIOT para preparar.' : 'La preparacion DIOT tiene datos, pero aun no existe generacion oficial de archivo.';
        $steps[] = $this->closing_step(7, 'DIOT generada', 'pendiente', $period, $rfc, 0, 0, $diot_note);

        return $steps;
    }

    /**
     * EMPTY CLOSING STEPS
     *
     * DEVUELVE PASOS PENDIENTES CUANDO NO HAY CONTEXTO.
     *
     * @access  protected
     * @return  Array
     */
    protected function empty_closing_steps($period, $rfc)
    {
        $titles = [
            1 => 'Libro fiscal generado',
            2 => 'Libro fiscal validado',
            3 => 'IVA mensual calculado',
            4 => 'Conciliacion fiscal-contable',
            5 => 'Borrador de poliza generado',
            6 => 'Poliza contabilizada',
            7 => 'DIOT generada',
        ];

        $steps = [];
        foreach ($titles as $number => $title) {
            $steps[] = $this->closing_step($number, $title, 'pendiente', $period, $rfc, 0, 0, 'Pendiente de informacion fiscal.');
        }

        return $steps;
    }

    /**
     * CLOSING STEP
     *
     * FORMATEA UN PASO DEL CENTRO DE CIERRE.
     *
     * @access  protected
     * @return  Array
     */
    protected function closing_step($number, $title, $status, $period, $rfc, $last_run_at, $user_id, $notes)
    {
        return [
            'number' => (int) $number,
            'title' => (string) $title,
            'status' => (string) $status,
            'status_label' => $this->closing_status_label($status),
            'period' => (string) $period,
            'rfc' => (string) $rfc,
            'last_run_at' => (int) $last_run_at,
            'last_run_label' => $last_run_at > 0 ? date('Y-m-d H:i:s', (int) $last_run_at) : 'Sin ejecucion',
            'user' => $user_id > 0 ? 'Usuario #'.(int) $user_id : 'No disponible',
            'notes' => (string) $notes,
        ];
    }

    /**
     * CLOSING STATUS LABEL
     *
     * TRADUCE ESTADOS INTERNOS A ETIQUETAS.
     *
     * @access  protected
     * @return  String
     */
    protected function closing_status_label($status)
    {
        $labels = [
            'pendiente' => 'Pendiente',
            'completado' => 'Completado',
            'advertencia' => 'Advertencia',
            'error' => 'Error',
        ];

        return isset($labels[$status]) ? $labels[$status] : 'Pendiente';
    }

    /**
     * LATEST FISCAL VALIDATION
     *
     * BUSCA LA ULTIMA VALIDACION PERSISTIDA DEL RFC/PERIODO.
     *
     * @access  protected
     * @return  Array|null
     */
    protected function latest_fiscal_validation($rfc, $period)
    {
        if (!\DBUtil::table_exists('core_fiscal_validations')) {
            return null;
        }

        $row = \DB::select()
            ->from('core_fiscal_validations')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('fiscal_period', '=', $period)
            ->where('active', '=', 1)
            ->order_by('executed_at', 'desc')
            ->order_by('id', 'desc')
            ->execute()
            ->current();

        return $row ? $this->format_fiscal_validation($row) : null;
    }

    /**
     * FORMAT FISCAL VALIDATION
     *
     * NORMALIZA UNA VALIDACION PERSISTIDA PARA JSON/VISTAS.
     *
     * @access  protected
     * @return  Array
     */
    protected function format_fiscal_validation(array $row)
    {
        $summary = [];
        if (trim((string) \Arr::get($row, 'summary_json', '')) !== '') {
            $decoded = json_decode((string) $row['summary_json'], true);
            $summary = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => (int) $row['id'],
            'company_id' => (int) $row['company_id'],
            'taxpayer_rfc' => (string) $row['taxpayer_rfc'],
            'fiscal_period' => (string) $row['fiscal_period'],
            'validation_type' => (string) $row['validation_type'],
            'validation_type_label' => $this->validation_type_label((string) $row['validation_type']),
            'status' => (string) $row['status'],
            'status_label' => $this->validation_status_label((string) $row['status']),
            'warnings_count' => (int) $row['warnings_count'],
            'errors_count' => (int) $row['errors_count'],
            'summary' => $summary,
            'executed_by' => (int) $row['executed_by'],
            'executed_by_label' => (int) $row['executed_by'] > 0 ? 'Usuario #'.(int) $row['executed_by'] : 'Sistema',
            'executed_at' => (int) $row['executed_at'],
            'executed_at_label' => (int) $row['executed_at'] > 0 ? date('Y-m-d H:i:s', (int) $row['executed_at']) : 'Sin fecha',
            'active' => (int) $row['active'],
        ];
    }

    protected function validation_type_label($type)
    {
        return $type === 'ledger_integrity' ? 'Integridad del libro fiscal' : $type;
    }

    protected function validation_status_label($status)
    {
        $labels = [
            'ok' => 'Correcta',
            'warning' => 'Con advertencias',
            'error' => 'Con errores',
        ];

        return isset($labels[$status]) ? $labels[$status] : 'No disponible';
    }

    /**
     * FORMAT FISCAL EVENT
     *
     * NORMALIZA UN EVENTO FISCAL PARA JSON/VISTAS.
     *
     * @access  protected
     * @return  Array
     */
    protected function format_fiscal_event(array $row)
    {
        $details = [];
        if (trim((string) \Arr::get($row, 'details_json', '')) !== '') {
            $decoded = json_decode((string) $row['details_json'], true);
            $details = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => (int) $row['id'],
            'company_id' => (int) $row['company_id'],
            'taxpayer_rfc' => (string) $row['taxpayer_rfc'],
            'fiscal_period' => (string) $row['fiscal_period'],
            'event_type' => (string) $row['event_type'],
            'event_type_label' => $this->event_type_label((string) $row['event_type']),
            'event_status' => (string) $row['event_status'],
            'event_status_label' => $this->event_status_label((string) $row['event_status']),
            'source_module' => (string) $row['source_module'],
            'source_entity_type' => (string) $row['source_entity_type'],
            'source_entity_id' => (int) $row['source_entity_id'],
            'summary' => (string) $row['summary'],
            'details' => $details,
            'executed_by' => (int) $row['executed_by'],
            'executed_by_label' => (int) $row['executed_by'] > 0 ? 'Usuario #'.(int) $row['executed_by'] : 'Sistema',
            'executed_at' => (int) $row['executed_at'],
            'executed_at_label' => (int) $row['executed_at'] > 0 ? date('Y-m-d H:i:s', (int) $row['executed_at']) : 'Sin fecha',
            'active' => (int) $row['active'],
        ];
    }

    protected function event_type_label($type)
    {
        $labels = [
            'ledger_build' => 'Construccion de libro fiscal',
            'ledger_validation' => 'Validacion de libro fiscal',
            'draft_generation' => 'Generacion de borrador fiscal',
            'draft_cancellation' => 'Cancelacion de borrador fiscal',
            'period_lock' => 'Bloqueo de periodo fiscal',
            'period_open' => 'Apertura de periodo fiscal',
            'period_close' => 'Cierre de periodo fiscal',
            'fiscal_accounts_repair' => 'Reparacion de cuentas fiscales',
        ];

        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    protected function event_status_label($status)
    {
        $labels = [
            'success' => 'Correcto',
            'warning' => 'Advertencia',
            'error' => 'Error',
            'skipped' => 'Omitido',
        ];

        return isset($labels[$status]) ? $labels[$status] : 'No disponible';
    }

    /**
     * LATEST FISCAL ENTRY
     *
     * BUSCA LA ULTIMA POLIZA FISCAL DEL PERIODO.
     *
     * @access  protected
     * @return  Array|null
     */
    protected function latest_fiscal_entry($period_id, $status)
    {
        if ((int) $period_id <= 0 || !\DBUtil::table_exists('core_accounting_journal_entries')) {
            return null;
        }

        $row = \DB::select()
            ->from('core_accounting_journal_entries')
            ->where('source_module', '=', 'fiscal')
            ->where('source_entity_type', '=', 'fiscal_period')
            ->where('source_entity_id', '=', (int) $period_id)
            ->where('status', '=', (string) $status)
            ->where('active', '=', 1)
            ->order_by('updated_at', 'desc')
            ->order_by('id', 'desc')
            ->execute()
            ->current();

        return $row ?: null;
    }

    /**
     * RECONCILIATION HAS ERRORS
     *
     * DETECTA CONCILIACIONES SIN CUENTA CONFIGURADA.
     *
     * @access  protected
     * @return  Bool
     */
    protected function reconciliation_has_errors(array $items)
    {
        foreach ($items as $item) {
            if ((string) \Arr::get((array) $item, 'status', '') === 'Sin cuenta configurada') {
                return true;
            }
        }

        return false;
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
            'created_by' => (int) $row['created_by'],
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

    /**
     * LEDGER FILTERS
     *
     * OBTIENE FILTROS DE CONSULTA DEL LIBRO FISCAL.
     *
     * @access  protected
     * @return  Array
     */
    protected function ledger_filters()
    {
        return [
            'uuid' => strtoupper(trim((string) \Input::get('uuid', ''))),
            'rfc' => strtoupper(preg_replace('/\s+/', '', trim((string) \Input::get('rfc_filter', '')))),
            'direction' => trim((string) \Input::get('direction', '')),
            'tax_code' => trim((string) \Input::get('tax_code', '')),
            'cfdi_type' => strtoupper(trim((string) \Input::get('cfdi_type', ''))),
            'sat_status' => trim((string) \Input::get('sat_status', '')),
        ];
    }

    /**
     * EVENT FILTERS
     *
     * OBTIENE FILTROS DE CONSULTA DE LA BITACORA FISCAL.
     *
     * @access  protected
     * @return  Array
     */
    protected function event_filters()
    {
        return [
            'event_type' => trim((string) \Input::get('event_type', '')),
            'event_status' => trim((string) \Input::get('event_status', '')),
        ];
    }

    /**
     * REP AUDIT FILTERS
     *
     * OBTIENE FILTROS DE CONSULTA DE AUDITORIA REP/PPD.
     *
     * @access  protected
     * @return  Array
     */
    protected function rep_audit_filters()
    {
        $type = trim((string) \Input::get('type', 'all'));
        if (!in_array($type, ['all', 'issued', 'received'], true)) {
            $type = 'all';
        }

        return [
            'type' => $type,
        ];
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
