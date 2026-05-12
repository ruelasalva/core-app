<?php

/**
 * CONTROLADOR ADMIN_AUDIT
 *
 * Consulta eventos de auditoria funcional del ERP.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Audit extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE AUDITORIA
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('audit.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE AUDITORIA
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Auditoria';
        $this->template->content = View::forge('admin/audit/index');
    }

    /**
     * DATA
     *
     * ENTREGA LOGS DE AUDITORIA EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            if (!\DBUtil::table_exists('core_audit_logs')) {
                throw new \RuntimeException('Falta ejecutar migraciones de auditoria.');
            }

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'items' => $this->get_items(),
                'stats' => $this->get_stats(),
                'filters' => $this->get_filter_options(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando auditoria: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar auditoria.'], 500);
        }
    }

    /**
     * GET ITEMS
     *
     * FORMATEA REGISTROS DE AUDITORIA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_items()
    {
        # SE PREPARA CONSULTA PRINCIPAL
        $query = Model_Core_Audit_Log::query()->order_by('id', 'desc')->limit(300);

        # FILTRO POR MODULO
        $module = trim((string) \Input::get('module', ''));
        if ($module !== '') {
            $query->where('module', $module);
        }

        # FILTRO POR ENTIDAD
        $entity_type = trim((string) \Input::get('entity_type', ''));
        if ($entity_type !== '') {
            $query->where('entity_type', $entity_type);
        }

        # FILTRO POR TABLA
        $table_name = trim((string) \Input::get('table_name', ''));
        if ($table_name !== '') {
            $query->where('table_name', $table_name);
        }

        # FILTRO POR REGISTRO
        $record_pk = trim((string) \Input::get('record_pk', ''));
        if ($record_pk !== '') {
            $query->where('record_pk', $record_pk);
        }

        # FILTRO POR OPERACION
        $operation = trim((string) \Input::get('operation', ''));
        if ($operation !== '') {
            $query->where('operation', $operation);
        }

        # FILTRO POR SEVERIDAD
        $severity = trim((string) \Input::get('severity', ''));
        if ($severity !== '') {
            $query->where('severity', $severity);
        }

        # FILTRO POR PORTAL
        $portal_code = trim((string) \Input::get('portal_code', ''));
        if ($portal_code !== '') {
            $query->where('portal_code', $portal_code);
        }

        # FILTRO POR FECHA INICIO
        $date_from = trim((string) \Input::get('date_from', ''));
        if ($date_from !== '') {
            $query->where('created_at', '>=', strtotime($date_from.' 00:00:00'));
        }

        # FILTRO POR FECHA FIN
        $date_to = trim((string) \Input::get('date_to', ''));
        if ($date_to !== '') {
            $query->where('created_at', '<=', strtotime($date_to.' 23:59:59'));
        }

        # SE FORMATEA RESPUESTA
        $items = [];
        foreach ($query->get() as $row) {
            $item = $row->to_array();
            $item['created_at'] = $row->created_at ? date('d/m/Y H:i', $row->created_at) : '';
            $item['changed_fields'] = $this->json_decode_list($row->changed_fields_json);
            $item['old_values'] = $this->json_decode_object($row->old_values_json);
            $item['new_values'] = $this->json_decode_object($row->new_values_json);
            $item['metadata'] = $this->json_decode_object($row->metadata_json);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES DE AUDITORIA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE CALCULAN CONTADORES BASE
        return [
            'total' => (int) \DB::count_records('core_audit_logs'),
            'today' => (int) \DB::select()->from('core_audit_logs')->where('created_at', '>=', strtotime(date('Y-m-d 00:00:00')))->execute()->count(),
            'warnings' => (int) \DB::select()->from('core_audit_logs')->where('severity', '=', 'warning')->execute()->count(),
            'danger' => (int) \DB::select()->from('core_audit_logs')->where('severity', '=', 'danger')->execute()->count(),
        ];
    }

    /**
     * GET FILTER OPTIONS
     *
     * OBTIENE OPCIONES UNICAS PARA FILTROS DE AUDITORIA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_filter_options()
    {
        return [
            'modules' => $this->distinct_values('module'),
            'tables' => $this->distinct_values('table_name'),
            'operations' => $this->distinct_values('operation'),
            'severities' => $this->distinct_values('severity'),
            'portals' => $this->distinct_values('portal_code'),
        ];
    }

    protected function distinct_values($field)
    {
        $rows = \DB::select($field)
            ->from('core_audit_logs')
            ->where($field, '!=', '')
            ->group_by($field)
            ->order_by($field, 'asc')
            ->execute();

        $values = [];
        foreach ($rows as $row) {
            $values[] = (string) $row[$field];
        }
        return $values;
    }

    protected function json_decode_object($json)
    {
        if (!$json) {
            return new \stdClass();
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : new \stdClass();
    }

    protected function json_decode_list($json)
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
}
