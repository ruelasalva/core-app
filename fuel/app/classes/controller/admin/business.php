<?php

/**
 * CONTROLADOR ADMIN_BUSINESS
 *
 * Capa operativa read-only para Administracion Comercial.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Business extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE ADMINISTRACION COMERCIAL.
     *
     * @return  Void
     */
    public function before()
    {
        parent::before();
        $this->require_access('business.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA DASHBOARD OPERATIVO READ-ONLY.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Administración Comercial';
        $this->template->content = \View::forge('admin/business/index');
    }

    /**
     * DATA
     *
     * ENTREGA KPIS Y RESUMENES READ-ONLY.
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            $filters = $this->period_filters();
            $context = [
                'user_id' => (int) $this->user_id,
                'is_super_admin' => (bool) $this->is_super_admin,
            ];

            $kpis = new \Service_Core_Business_KpiService($context);
            $summary = new \Service_Core_Business_CommercialSummary($context);
            $customer_360 = new \Service_Core_Business_Customer360($context);

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => [
                    'filters' => $filters,
                    'kpis' => $kpis->dashboard($filters),
                    'commercial_summary' => $summary->dashboard($filters),
                    'customer_360' => $customer_360->base($filters),
                    'warnings' => array_values(array_unique(array_merge(
                        $kpis->warnings(),
                        $summary->warnings(),
                        $customer_360->warnings()
                    ))),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Administracion Comercial read-only error: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar Administración Comercial.',
                'data' => [],
                'errors' => ['No se pudo cargar Administración Comercial.'],
            ], 500);
        }
    }
}
