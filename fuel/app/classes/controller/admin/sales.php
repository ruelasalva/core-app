<?php

/**
 * CONTROLADOR ADMIN_SALES
 *
 * Administra solicitudes comerciales generadas desde el frontend.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Sales extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * Valida sesion administrativa y permiso de lectura.
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y LA SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('sales.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE COTIZACIONES Y PEDIDOS BASE.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Ventas';
        $this->template->content = View::forge('admin/sales/index');
    }

    /**
     * DATA
     *
     * ENTREGA COTIZACIONES EN JSON.
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA ESQUEMA BASE
            $this->assert_schema_ready();

            return $this->json_response([
                'quotes' => $this->quotes(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando ventas: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar ventas.'], 500);
        }
    }

    /**
     * UPDATE STATUS
     *
     * ACTUALIZA EL ESTADO DE UNA COTIZACION.
     *
     * @access  public
     * @return  Response
     */
    public function post_update_status()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('sales.access[edit]');

        try {
            # SE OBTIENE PAYLOAD
            $val = (array) \Input::json();
            $quote = Model_Core_Sales_Quote::find((int) \Arr::get($val, 'id', 0));
            if (!$quote) {
                return $this->json_response(['error' => 'Cotizacion no encontrada.'], 404);
            }

            # SE VALIDA ESTADO PERMITIDO
            $status = trim((string) \Arr::get($val, 'status', ''));
            $allowed = ['requested', 'reviewed', 'approved', 'rejected', 'converted'];
            if (!in_array($status, $allowed, true)) {
                return $this->json_response(['error' => 'Estado no valido.'], 422);
            }

            # SE GUARDA CAMBIO
            $quote->status = $status;
            $quote->internal_notes = trim((string) \Arr::get($val, 'internal_notes', $quote->internal_notes));
            $quote->save();

            return $this->json_response(['status' => 'ok', 'quotes' => $this->quotes(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error actualizando cotizacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo actualizar la cotizacion.'], 400);
        }
    }

    /**
     * QUOTES
     *
     * FORMATEA COTIZACIONES RECIENTES.
     *
     * @access  protected
     * @return  Array
     */
    protected function quotes()
    {
        # SE CONSULTAN COTIZACIONES CON TERCERO
        $rows = \DB::select(
                array('q.id', 'id'),
                array('q.folio', 'folio'),
                array('q.source', 'source'),
                array('q.status', 'status'),
                array('q.currency_code', 'currency_code'),
                array('q.total', 'total'),
                array('q.customer_notes', 'customer_notes'),
                array('q.internal_notes', 'internal_notes'),
                array('q.created_at', 'created_at'),
                array('p.name', 'party_name'),
                array('p.email', 'party_email')
            )
            ->from(array('core_sales_quotes', 'q'))
            ->join(array('core_parties', 'p'), 'left')
                ->on('q.party_id', '=', 'p.id')
            ->order_by('q.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['items'] = $this->quote_items((int) $row['id']);
            $row['created_label'] = !empty($row['created_at']) ? date('Y-m-d H:i', (int) $row['created_at']) : '';
        }

        return $rows;
    }

    /**
     * QUOTE ITEMS
     *
     * OBTIENE RENGLONES DE UNA COTIZACION.
     *
     * @access  protected
     * @return  Array
     */
    protected function quote_items($quote_id)
    {
        # SE CONSULTAN RENGLONES
        return \DB::select('sku', 'name', 'quantity', 'unit_price', 'line_total')
            ->from('core_sales_quote_items')
            ->where('quote_id', '=', (int) $quote_id)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->execute()
            ->as_array();
    }

    /**
     * STATS
     *
     * OBTIENE CONTADORES BASICOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function stats()
    {
        # SE REGRESAN CONTADORES GENERALES
        return [
            'quotes' => (int) \DB::count_records('core_sales_quotes'),
            'requested' => (int) \DB::select()->from('core_sales_quotes')->where('status', '=', 'requested')->execute()->count(),
        ];
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA TABLAS REQUERIDAS.
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA
        foreach (['core_sales_quotes', 'core_sales_quote_items'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de ventas.');
            }
        }
    }
}
