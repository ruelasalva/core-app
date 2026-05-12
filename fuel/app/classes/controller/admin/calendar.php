<?php

/**
 * CONTROLADOR ADMIN_CALENDAR
 *
 * Administra calendario transversal, sala de juntas y eventos operativos.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Calendar extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE CALENDARIO
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('calendar.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE CALENDARIO Y SALA DE JUNTAS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Calendario';
        $this->template->content = View::forge('admin/calendar/index');
    }

    /**
     * DATA
     *
     * ENTREGA EVENTOS, RECURSOS, OPCIONES Y ESTADISTICAS
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
                'events' => $this->get_events(),
                'resources' => $this->get_resources(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando calendario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar calendario.'], 500);
        }
    }

    /**
     * EVENTS FEED
     *
     * ENTREGA EVENTOS EN FORMATO COMPATIBLE CON FULLCALENDAR
     *
     * @access  public
     * @return  Response
     */
    public function action_events_feed()
    {
        try {
            # SE FORMATEAN EVENTOS PARA LIBRERIAS DE CALENDARIO
            $feed = [];
            foreach ($this->get_events() as $event) {
                $feed[] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_iso'],
                    'end' => $event['end_iso'],
                    'allDay' => (bool) $event['all_day'],
                    'color' => $event['color'],
                    'extendedProps' => $event,
                ];
            }

            return $this->json_response($feed);
        } catch (\Exception $e) {
            \Log::error('Error generando feed de calendario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo generar feed.'], 500);
        }
    }

    /**
     * SAVE EVENT
     *
     * CREA O ACTUALIZA EVENTOS DEL CALENDARIO
     *
     * @access  public
     * @return  Response
     */
    public function action_save_event()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('calendar.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN CAMPOS BASE
            $title = trim((string) \Arr::get($val, 'title', ''));
            $start_at = $this->normalize_datetime(\Arr::get($val, 'start_at', ''));
            $end_at = $this->normalize_datetime(\Arr::get($val, 'end_at', ''));
            if ($title === '' || $start_at < 1 || $end_at < 1 || $end_at <= $start_at) {
                return $this->json_response(['error' => 'Titulo, inicio y fin validos son obligatorios.'], 422);
            }

            # SE PREPARAN DATOS NORMALIZADOS
            $resource_id = (int) \Arr::get($val, 'resource_id', 0);
            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'title' => $title,
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'event_type' => $this->codeify(\Arr::get($val, 'event_type', 'general')),
                'resource_id' => $resource_id,
                'assigned_user_id' => (int) \Arr::get($val, 'assigned_user_id', 0),
                'organizer_user_id' => (int) \Arr::get($val, 'organizer_user_id', $this->user_id),
                'related_entity_type' => $this->codeify(\Arr::get($val, 'related_entity_type', '')),
                'related_entity_id' => (int) \Arr::get($val, 'related_entity_id', 0),
                'start_at' => $start_at,
                'end_at' => $end_at,
                'all_day' => (int) (bool) \Arr::get($val, 'all_day', false),
                'status' => $this->codeify(\Arr::get($val, 'status', 'scheduled')),
                'visibility' => $this->codeify(\Arr::get($val, 'visibility', 'internal')),
                'color' => trim((string) \Arr::get($val, 'color', '#007bff')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE EVITA DOBLE RESERVA DEL MISMO RECURSO
            if ($resource_id > 0 && $data['active'] === 1 && $data['status'] !== 'cancelled' && $this->has_resource_conflict($resource_id, $start_at, $end_at, $id)) {
                return $this->json_response(['error' => 'El recurso ya esta reservado en ese horario.'], 409);
            }

            # SE CREA O ACTUALIZA EVENTO
            if ($id > 0) {
                $event = Model_Core_Calendar_Event::find($id);
                if (!$event) {
                    return $this->json_response(['error' => 'Evento no encontrado.'], 404);
                }
                $old = $event->to_array();
                $event->set($data);
            } else {
                $old = [];
                $event = Model_Core_Calendar_Event::forge($data);
            }
            $event->save();

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'calendar',
                'action' => $id > 0 ? 'update_event' : 'create_event',
                'business_event' => $id > 0 ? 'calendar.update_event' : 'calendar.create_event',
                'entity_type' => 'calendar_event',
                'entity_id' => (int) $event->id,
                'table_name' => 'core_calendar_events',
                'summary' => 'Evento de calendario: '.$event->title,
                'old_values' => $old,
                'new_values' => $event->to_array(),
            ]);

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->json_response(['status' => 'ok', 'events' => $this->get_events(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando evento: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el evento.'], 400);
        }
    }

    /**
     * SAVE RESOURCE
     *
     * CREA O ACTUALIZA RECURSOS RESERVABLES COMO SALAS
     *
     * @access  public
     * @return  Response
     */
    public function action_save_resource()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('calendar.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN DATOS MINIMOS
            $name = trim((string) \Arr::get($val, 'name', ''));
            if ($name === '') {
                return $this->json_response(['error' => 'Nombre del recurso es obligatorio.'], 422);
            }

            # SE PREPARAN DATOS
            $code = $this->codeify(\Arr::get($val, 'code', ''));
            if ($code === '') {
                $code = $this->codeify($name);
            }

            $data = [
                'code' => $code,
                'name' => $name,
                'resource_type' => $this->codeify(\Arr::get($val, 'resource_type', 'meeting_room')),
                'location' => trim((string) \Arr::get($val, 'location', '')),
                'capacity' => (int) \Arr::get($val, 'capacity', 0),
                'color' => trim((string) \Arr::get($val, 'color', '#007bff')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE CREA O ACTUALIZA RECURSO
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $resource = Model_Core_Calendar_Resource::find($id);
                if (!$resource) {
                    return $this->json_response(['error' => 'Recurso no encontrado.'], 404);
                }
                $old = $resource->to_array();
                $resource->set($data);
            } else {
                $old = [];
                $resource = Model_Core_Calendar_Resource::forge($data);
            }
            $resource->save();

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'calendar',
                'action' => $id > 0 ? 'update_resource' : 'create_resource',
                'business_event' => $id > 0 ? 'calendar.update_resource' : 'calendar.create_resource',
                'entity_type' => 'calendar_resource',
                'entity_id' => (int) $resource->id,
                'table_name' => 'core_calendar_resources',
                'summary' => 'Recurso de calendario: '.$resource->name,
                'old_values' => $old,
                'new_values' => $resource->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'resources' => $this->get_resources(), 'options' => $this->get_options(), 'stats' => $this->get_stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando recurso de calendario: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el recurso.'], 400);
        }
    }

    /**
     * GET EVENTS
     *
     * FORMATEA EVENTOS DEL CALENDARIO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_events()
    {
        # SE PREPARAN RANGOS DE CONSULTA
        $date_from = trim((string) \Input::get('date_from', date('Y-m-01')));
        $date_to = trim((string) \Input::get('date_to', date('Y-m-t')));
        $from = strtotime($date_from.' 00:00:00');
        $to = strtotime($date_to.' 23:59:59');

        # SE PREPARA CONSULTA PRINCIPAL
        $query = Model_Core_Calendar_Event::query()
            ->where('active', '=', 1)
            ->where('start_at', '<=', $to)
            ->where('end_at', '>=', $from)
            ->order_by('start_at', 'asc')
            ->limit(500);

        # FILTROS OPERATIVOS
        $type = trim((string) \Input::get('event_type', ''));
        if ($type !== '') {
            $query->where('event_type', '=', $this->codeify($type));
        }

        $resource_id = (int) \Input::get('resource_id', 0);
        if ($resource_id > 0) {
            $query->where('resource_id', '=', $resource_id);
        }

        $assigned_user_id = (int) \Input::get('assigned_user_id', 0);
        if ($assigned_user_id > 0) {
            $query->where('assigned_user_id', '=', $assigned_user_id);
        }

        # SE FORMATEA RESPUESTA
        $items = [];
        foreach ($query->get() as $event) {
            $row = $event->to_array();
            $row['start_label'] = $event->start_at ? date('d/m/Y H:i', $event->start_at) : '';
            $row['end_label'] = $event->end_at ? date('d/m/Y H:i', $event->end_at) : '';
            $row['start_iso'] = $event->start_at ? date('c', $event->start_at) : '';
            $row['end_iso'] = $event->end_at ? date('c', $event->end_at) : '';
            $row['start_input'] = $event->start_at ? date('Y-m-d\TH:i', $event->start_at) : '';
            $row['end_input'] = $event->end_at ? date('Y-m-d\TH:i', $event->end_at) : '';
            $row['day_key'] = $event->start_at ? date('Y-m-d', $event->start_at) : '';
            $items[] = $row;
        }

        return $items;
    }

    /**
     * GET RESOURCES
     *
     * FORMATEA RECURSOS RESERVABLES
     *
     * @access  protected
     * @return  Array
     */
    protected function get_resources()
    {
        # SE OBTIENEN RECURSOS ACTIVOS E INACTIVOS PARA ADMIN
        $items = [];
        foreach (Model_Core_Calendar_Resource::query()->order_by('name', 'asc')->get() as $resource) {
            $items[] = $resource->to_array();
        }
        return $items;
    }

    /**
     * GET OPTIONS
     *
     * OBTIENE OPCIONES PARA FORMULARIOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        # USUARIOS ORM AUTH
        $users = [];
        foreach (\DB::select('id', 'username', 'email')->from('users')->order_by('username', 'asc')->execute() as $user) {
            $label = $user['username'] ?: $user['email'];
            $users[] = ['value' => (int) $user['id'], 'label' => $label];
        }

        # RECURSOS ACTIVOS PARA SELECT
        $resources = [];
        foreach (Model_Core_Calendar_Resource::query()->where('active', '=', 1)->order_by('name', 'asc')->get() as $resource) {
            $resources[] = ['value' => (int) $resource->id, 'label' => $resource->name];
        }

        return [
            'users' => $users,
            'resources' => $resources,
            'types' => [
                ['value' => 'meeting', 'label' => 'Reunion'],
                ['value' => 'task', 'label' => 'Tarea'],
                ['value' => 'helpdesk', 'label' => 'Helpdesk'],
                ['value' => 'reminder', 'label' => 'Recordatorio'],
                ['value' => 'general', 'label' => 'General'],
            ],
            'statuses' => [
                ['value' => 'scheduled', 'label' => 'Programado'],
                ['value' => 'in_progress', 'label' => 'En proceso'],
                ['value' => 'done', 'label' => 'Terminado'],
                ['value' => 'cancelled', 'label' => 'Cancelado'],
            ],
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES DEL CALENDARIO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE CALCULAN CONTADORES BASE
        $now = time();
        $next_week = strtotime('+7 days');

        return [
            'events' => (int) \DB::select()->from('core_calendar_events')->where('active', '=', 1)->execute()->count(),
            'resources' => (int) \DB::select()->from('core_calendar_resources')->where('active', '=', 1)->execute()->count(),
            'next_7_days' => (int) \DB::select()->from('core_calendar_events')->where('active', '=', 1)->where('start_at', '>=', $now)->where('start_at', '<=', $next_week)->execute()->count(),
            'pending' => (int) \DB::select()->from('core_calendar_events')->where('active', '=', 1)->where('status', '=', 'scheduled')->where('start_at', '>=', $now)->execute()->count(),
        ];
    }

    /**
     * HAS RESOURCE CONFLICT
     *
     * VALIDA TRASLAPES DE RESERVA PARA UN MISMO RECURSO
     *
     * @access  protected
     * @return  Bool
     */
    protected function has_resource_conflict($resource_id, $start_at, $end_at, $ignore_id = 0)
    {
        # SE BUSCA SI EXISTE OTRO EVENTO ACTIVO EN EL MISMO HORARIO
        $query = Model_Core_Calendar_Event::query()
            ->where('resource_id', '=', (int) $resource_id)
            ->where('active', '=', 1)
            ->where('status', '!=', 'cancelled')
            ->where('start_at', '<', (int) $end_at)
            ->where('end_at', '>', (int) $start_at);

        if ((int) $ignore_id > 0) {
            $query->where('id', '!=', (int) $ignore_id);
        }

        return $query->count() > 0;
    }

    /**
     * NORMALIZE DATETIME
     *
     * CONVIERTE FECHA DE FORMULARIO EN TIMESTAMP
     *
     * @access  protected
     * @return  Int
     */
    protected function normalize_datetime($value)
    {
        # SE PERMITE TIMESTAMP O CADENA DE FECHA
        if (is_numeric($value)) {
            return (int) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        $time = strtotime($value);
        return $time ? (int) $time : 0;
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS MIGRACIONES DE CALENDARIO ESTEN EJECUTADAS
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # TABLAS BASE DEL MODULO
        foreach (['core_calendar_resources', 'core_calendar_events'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de calendario.');
            }
        }

        # CAMPOS PREPARADOS PARA HELPDESK/TAREAS FUTURAS
        if (!\DBUtil::field_exists('core_helpdesk_tickets', ['due_at', 'scheduled_start_at', 'scheduled_end_at'])) {
            throw new \RuntimeException('Falta ejecutar migraciones de calendario para helpdesk.');
        }
    }

    /**
     * CODEIFY
     *
     * NORMALIZA TEXTOS PARA CODIGOS INTERNOS
     *
     * @access  protected
     * @return  String
     */
    protected function codeify($value)
    {
        # SE LIMPIA TEXTO A FORMATO CODIGO
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
