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
            'modules' => [
                'config' => $this->is_super_admin || \Auth::has_access('config.access[view]'),
                'web' => $this->is_super_admin || \Auth::has_access('web.access[view]'),
                'calendar' => $this->is_super_admin || \Auth::has_access('calendar.access[view]'),
            ],
        ];

        # SE CARGA LA VISTA
        $this->template->title = 'Dashboard';
        $this->template->content = \View::forge('admin/dashboard', $data);
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
}
