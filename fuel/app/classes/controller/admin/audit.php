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
        $query = Model_Core_Audit_Log::query()->order_by('id', 'desc')->limit(200);

        $module = trim((string) \Input::get('module', ''));
        if ($module !== '') {
            $query->where('module', $module);
        }

        $entity_type = trim((string) \Input::get('entity_type', ''));
        if ($entity_type !== '') {
            $query->where('entity_type', $entity_type);
        }

        $items = [];
        foreach ($query->get() as $row) {
            $item = $row->to_array();
            $item['created_at'] = $row->created_at ? date('d/m/Y H:i', $row->created_at) : '';
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
        return [
            'total' => (int) \DB::count_records('core_audit_logs'),
            'today' => (int) \DB::select()->from('core_audit_logs')->where('created_at', '>=', strtotime(date('Y-m-d 00:00:00')))->execute()->count(),
        ];
    }
}
