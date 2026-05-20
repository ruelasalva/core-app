<?php

/**
 * CONTROLADOR ADMIN_DASHBOARD
 *
 * Muestra el panel principal del administrador.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Dashboard extends Controller_Adminbase
{
    /**
     * INDEX
     *
     * MUESTRA EL DASHBOARD ADMINISTRATIVO
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # REGISTRO DE ACTIVIDAD
        \Log::info('El administrador '.\Auth::get_screen_name().' entro al Dashboard.');

        # SE INICIALIZAN LAS VARIABLES PARA LA VISTA
        $data = [
            'title' => 'Panel de Control Principal',
            'assigned_dashboards' => $this->assigned_dashboards(),
            'modules' => [
                'config' => $this->is_super_admin || \Auth::has_access('config.access[view]'),
                'web' => $this->is_super_admin || \Auth::has_access('web.access[view]'),
                'calendar' => $this->is_super_admin || \Auth::has_access('calendar.access[view]'),
                'executive_dashboard' => $this->can_see_executive(),
            ],
        ];

        # SE CARGA LA VISTA
        $this->template->title = 'Dashboard';
        $this->template->content = \View::forge('admin/dashboard', $data);
    }

    public function action_data()
    {
        try {
            return $this->json_response([
                'dashboards' => $this->assigned_dashboards(),
                'executive' => $this->executive_data(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando dashboard ejecutivo: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar dashboard ejecutivo.'], 500);
        }
    }

    /**
     * CALENDAR DATA
     *
     * ENTREGA EVENTOS PENDIENTES DEL USUARIO ACTUAL PARA EL MINI CALENDARIO
     *
     * @access  public
     * @return  Response
     */
    public function action_calendar_data()
    {
        try {
            # VALIDAR PERMISO DEL MODULO CALENDARIO
            $this->require_access('calendar.access[view]');

            # SE VALIDA QUE LAS TABLAS EXISTAN
            if (!\DBUtil::table_exists('core_calendar_events')) {
                return $this->json_response(['events' => []]);
            }

            # SE REGRESAN EVENTOS DEL USUARIO
            return $this->json_response([
                'events' => $this->get_user_calendar_events(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando mini calendario dashboard: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar el calendario.'], 500);
        }
    }

    /**
     * GET USER CALENDAR EVENTS
     *
     * FORMATEA EVENTOS ASIGNADOS, ORGANIZADOS Y TICKETS CON FECHA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_user_calendar_events()
    {
        # SE PREPARA RANGO PROXIMO PARA DASHBOARD
        $now = strtotime(date('Y-m-d 00:00:00'));
        $limit = strtotime('+60 days 23:59:59');
        $events = [];

        # EVENTOS DEL CALENDARIO CENTRAL
        $rows = \DB::select()
            ->from('core_calendar_events')
            ->where('active', '=', 1)
            ->where('status', '!=', 'done')
            ->where('status', '!=', 'cancelled')
            ->where('start_at', '>=', $now)
            ->where('start_at', '<=', $limit)
            ->order_by('start_at', 'asc')
            ->limit(200)
            ->execute();

        foreach ($rows as $row) {
            if ((int) $row['assigned_user_id'] !== $this->user_id && (int) $row['organizer_user_id'] !== $this->user_id) {
                continue;
            }

            $events[] = [
                'id' => 'calendar-'.$row['id'],
                'title' => $row['title'],
                'start' => date('c', (int) $row['start_at']),
                'end' => date('c', (int) $row['end_at']),
                'color' => $row['color'] ?: '#007bff',
                'url' => \Uri::create('admin/calendar'),
                'extendedProps' => [
                    'source' => 'calendar',
                    'type' => $row['event_type'],
                    'status' => $row['status'],
                ],
            ];
        }

        # TICKETS CON FECHAS PREPARADAS PARA TAREAS FUTURAS
        if (\DBUtil::table_exists('core_helpdesk_tickets') && \DBUtil::field_exists('core_helpdesk_tickets', ['due_at'])) {
            $tickets = \DB::select('id', 'folio', 'subject', 'assigned_user_id', 'due_at', 'scheduled_start_at', 'scheduled_end_at')
                ->from('core_helpdesk_tickets')
                ->where('active', '=', 1)
                ->where('assigned_user_id', '=', $this->user_id)
                ->where('due_at', '>=', $now)
                ->where('due_at', '<=', $limit)
                ->order_by('due_at', 'asc')
                ->limit(100)
                ->execute();

            foreach ($tickets as $ticket) {
                $start = (int) ($ticket['scheduled_start_at'] ?: $ticket['due_at']);
                $end = (int) ($ticket['scheduled_end_at'] ?: strtotime('+1 hour', $start));
                if ($start < 1) {
                    continue;
                }

                $events[] = [
                    'id' => 'ticket-'.$ticket['id'],
                    'title' => $ticket['folio'].' '.$ticket['subject'],
                    'start' => date('c', $start),
                    'end' => date('c', $end),
                    'color' => '#ffc107',
                    'url' => \Uri::create('admin/helpdesk'),
                    'extendedProps' => [
                        'source' => 'helpdesk',
                        'type' => 'ticket',
                        'status' => 'pending',
                    ],
                ];
            }
        }

        return $events;
    }

    protected function assigned_dashboards()
    {
        if (!\DBUtil::table_exists('core_dashboards') || !\DBUtil::table_exists('core_dashboard_user_assignments')) {
            return [['code' => 'generic', 'name' => 'Dashboard generico', 'dashboard_type' => 'generic']];
        }

        $rows = \DB::select(['d.code', 'code'], ['d.name', 'name'], ['d.dashboard_type', 'dashboard_type'])
            ->from(['core_dashboard_user_assignments', 'a'])
            ->join(['core_dashboards', 'd'])->on('a.dashboard_id', '=', 'd.id')
            ->where('a.user_id', '=', (int) $this->user_id)
            ->where('a.active', '=', 1)
            ->where('d.active', '=', 1)
            ->order_by('a.is_default', 'desc')
            ->order_by('d.name', 'asc')
            ->execute()
            ->as_array();

        return $rows ?: [['code' => 'generic', 'name' => 'Dashboard generico', 'dashboard_type' => 'generic']];
    }

    protected function can_see_executive()
    {
        foreach ($this->assigned_dashboards() as $dashboard) {
            if ((string) $dashboard['dashboard_type'] === 'executive_commercial') {
                return true;
            }
        }
        return $this->is_super_admin;
    }

    protected function executive_data()
    {
        if (!$this->can_see_executive()) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'sales' => $this->sales_metrics(),
            'inventory' => $this->inventory_metrics(),
            'collections' => $this->collection_metrics(),
            'trends' => $this->trend_metrics(),
        ];
    }

    protected function sales_metrics()
    {
        $from = strtotime('-30 days 00:00:00');
        $total = $this->sum_table('core_sales_quotes', 'total', [['created_at', '>=', $from]]);
        $orders = $this->sum_table('core_sales_orders', 'total', [['created_at', '>=', $from], ['active', '=', 1]]);
        return [
            'quote_total_30d' => $total,
            'order_total_30d' => $orders,
            'by_channel' => $this->sales_by_channel($from),
            'by_product' => $this->sales_by_product($from),
            'by_zone' => $this->sales_by_zone($from),
        ];
    }

    protected function inventory_metrics()
    {
        if (!\DBUtil::table_exists('core_inventory_stock_balances')) {
            return ['low_stock' => [], 'negative_stock' => [], 'total_products' => 0];
        }

        $rows = \DB::select(['p.id', 'product_id'], ['p.sku', 'sku'], ['p.name', 'name'], ['w.name', 'warehouse_name'], ['b.quantity_on_hand', 'stock'], ['b.quantity_reserved', 'reserved'], ['p.stock_min', 'stock_min'])
            ->from(['core_inventory_stock_balances', 'b'])
            ->join(['core_commerce_products', 'p'])->on('b.product_id', '=', 'p.id')
            ->join(['core_inventory_warehouses', 'w'], 'left')->on('b.warehouse_id', '=', 'w.id')
            ->where('p.active', '=', 1)
            ->order_by(\DB::expr('(b.quantity_on_hand - b.quantity_reserved)'), 'asc')
            ->limit(50)
            ->execute()
            ->as_array();

        $low = [];
        $negative = [];
        foreach ($rows as $row) {
            $available = (float) $row['stock'] - (float) $row['reserved'];
            $row['available'] = $available;
            if ($available < 0) {
                $negative[] = $row;
            } elseif ((float) $row['stock_min'] > 0 && $available <= (float) $row['stock_min']) {
                $low[] = $row;
            }
        }

        return [
            'low_stock' => array_slice($low, 0, 12),
            'negative_stock' => array_slice($negative, 0, 12),
            'total_products' => \DB::select()->from('core_commerce_products')->where('active', '=', 1)->execute()->count(),
        ];
    }

    protected function collection_metrics()
    {
        if (!\DBUtil::table_exists('core_billing_invoices')) {
            return ['receivable_total' => 0, 'overdue_total' => 0, 'top_overdue' => []];
        }
        $today = date('Y-m-d');
        $receivable = $this->sum_table('core_billing_invoices', 'balance_due', [['invoice_type', '=', 'sale'], ['active', '=', 1], ['balance_due', '>', 0]]);
        $overdue = $this->sum_table('core_billing_invoices', 'balance_due', [['invoice_type', '=', 'sale'], ['active', '=', 1], ['balance_due', '>', 0], ['due_date', '<', $today]]);
        $top = \DB::select(['i.folio', 'folio'], ['i.due_date', 'due_date'], ['i.balance_due', 'balance_due'], ['p.name', 'party_name'], ['p.credit_days', 'credit_days'])
            ->from(['core_billing_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('i.balance_due', '>', 0)
            ->order_by('i.due_date', 'asc')
            ->limit(10)
            ->execute()
            ->as_array();
        return ['receivable_total' => $receivable, 'overdue_total' => $overdue, 'top_overdue' => $top];
    }

    protected function trend_metrics()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = strtotime(date('Y-m-01 00:00:00', strtotime('-'.$i.' months')));
            $end = strtotime(date('Y-m-t 23:59:59', $start));
            $months[] = [
                'label' => date('M Y', $start),
                'sales' => $this->sum_table('core_sales_quotes', 'total', [['created_at', '>=', $start], ['created_at', '<=', $end]]),
                'orders' => $this->sum_table('core_sales_orders', 'total', [['created_at', '>=', $start], ['created_at', '<=', $end], ['active', '=', 1]]),
            ];
        }
        $last = end($months);
        $prev = count($months) > 1 ? $months[count($months) - 2] : ['orders' => 0];
        $projection = (float) $last['orders'];
        if ((float) $prev['orders'] > 0) {
            $projection = round((float) $last['orders'] * (1 + (((float) $last['orders'] - (float) $prev['orders']) / max(1, (float) $prev['orders']))), 2);
        }
        return ['months' => $months, 'next_month_projection' => max(0, $projection)];
    }

    protected function sales_by_channel($from)
    {
        if (!\DBUtil::table_exists('core_sales_quotes')) {
            return [];
        }
        return \DB::select(['source', 'label'], [\DB::expr('COUNT(*)'), 'count'], [\DB::expr('COALESCE(SUM(total),0)'), 'total'])
            ->from('core_sales_quotes')
            ->where('created_at', '>=', $from)
            ->group_by('source')
            ->order_by('total', 'desc')
            ->execute()
            ->as_array();
    }

    protected function sales_by_product($from)
    {
        if (!\DBUtil::table_exists('core_sales_quote_items')) {
            return [];
        }
        return \DB::select(['name', 'label'], [\DB::expr('COALESCE(SUM(quantity),0)'), 'quantity'], [\DB::expr('COALESCE(SUM(line_total),0)'), 'total'])
            ->from('core_sales_quote_items')
            ->where('created_at', '>=', $from)
            ->group_by('name')
            ->order_by('total', 'desc')
            ->limit(8)
            ->execute()
            ->as_array();
    }

    protected function sales_by_zone($from)
    {
        if (!\DBUtil::table_exists('core_sales_quotes')) {
            return [];
        }
        return \DB::select([\DB::expr("COALESCE(NULLIF(a.city,''), NULLIF(a.state,''), 'Sin zona')"), 'label'], [\DB::expr('COALESCE(SUM(q.total),0)'), 'total'], [\DB::expr('COUNT(*)'), 'count'])
            ->from(['core_sales_quotes', 'q'])
            ->join(['core_parties', 'p'], 'left')->on('q.party_id', '=', 'p.id')
            ->join(['core_party_addresses', 'a'], 'left')->on('p.id', '=', 'a.party_id')->on('a.is_default', '=', \DB::expr('1'))
            ->where('q.created_at', '>=', $from)
            ->group_by(\DB::expr("COALESCE(NULLIF(a.city,''), NULLIF(a.state,''), 'Sin zona')"))
            ->order_by('total', 'desc')
            ->limit(8)
            ->execute()
            ->as_array();
    }

    protected function sum_table($table, $field, array $conditions = [])
    {
        if (!\DBUtil::table_exists($table)) {
            return 0;
        }
        $query = \DB::select([\DB::expr('COALESCE(SUM('.$field.'),0)'), 'total'])->from($table);
        foreach ($conditions as $condition) {
            $query->where($condition[0], $condition[1], $condition[2]);
        }
        $row = $query->execute()->current();
        return $row ? (float) $row['total'] : 0;
    }
}
