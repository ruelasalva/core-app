<?php

/**
 * CONTROLADOR ADMIN_HELPDESK
 *
 * Administra tickets, conversaciones y seguimiento de soporte transversal.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Helpdesk extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE HELPDESK
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('helpdesk.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE HELPDESK
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Helpdesk';
        $this->template->content = View::forge('admin/helpdesk/index');
    }

    /**
     * DATA
     *
     * ENTREGA TICKETS, MENSAJES, OPCIONES Y ESTADISTICAS
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
                'tickets' => $this->get_tickets(),
                'messages' => $this->get_messages(),
                'documents' => $this->get_ticket_documents(),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando helpdesk: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar helpdesk.'], 500);
        }
    }

    /**
     * CREATE TICKET
     *
     * CREA UN TICKET DESDE ADMIN
     *
     * @access  public
     * @return  Response
     */
    public function post_create_ticket()
    {
        # VALIDAR PERMISO PARA CREAR
        $this->require_access('helpdesk.access[create]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN DATOS MINIMOS
            $subject = trim((string) \Arr::get($val, 'subject', ''));
            $description = trim((string) \Arr::get($val, 'description', ''));
            if ($subject === '' || $description === '') {
                return $this->json_response(['error' => 'Asunto y descripcion son obligatorios.'], 422);
            }

            # SE CREA EL TICKET
            $status = $this->status_by_code('nuevo');
            $schedule = $this->ticket_schedule_data($val);
            $ticket = Model_Core_Helpdesk_Ticket::forge([
                'folio' => $this->next_folio(),
                'source' => 'admin',
                'portal_code' => '',
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'requester_user_id' => $this->user_id,
                'assigned_user_id' => (int) \Arr::get($val, 'assigned_user_id', 0),
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'category_id' => (int) \Arr::get($val, 'category_id', 0),
                'status_id' => $status ? (int) $status->id : 0,
                'priority' => $this->codeify(\Arr::get($val, 'priority', 'normal')),
                'subject' => $subject,
                'description' => $description,
                'last_message_at' => time(),
                'due_at' => $schedule['due_at'],
                'scheduled_start_at' => $schedule['scheduled_start_at'],
                'scheduled_end_at' => $schedule['scheduled_end_at'],
                'active' => 1,
            ]);
            $ticket->save();

            # SE AGREGA MENSAJE INICIAL
            Model_Core_Helpdesk_Message::forge([
                'ticket_id' => (int) $ticket->id,
                'user_id' => $this->user_id,
                'author_type' => 'admin',
                'message' => $description,
                'is_internal' => 0,
                'active' => 1,
            ])->save();

            # SE NOTIFICA AL ASIGNADO SI EXISTE
            $this->notify_ticket($ticket, 'helpdesk.ticket_created', 'Nuevo ticket '.$ticket->folio, $subject, [(int) $ticket->assigned_user_id]);

            # SE AUDITA CREACION DEL TICKET
            Helper_Core_Audit::log([
                'module' => 'helpdesk',
                'action' => 'create_ticket',
                'business_event' => 'helpdesk.create_ticket',
                'entity_type' => 'helpdesk_ticket',
                'entity_id' => (int) $ticket->id,
                'table_name' => 'core_helpdesk_tickets',
                'summary' => 'Ticket creado '.$ticket->folio,
                'new_values' => $ticket->to_array(),
            ]);

            # SE REGRESA ESTADO ACTUALIZADO
            return $this->json_response([
                'status' => 'ok',
                'tickets' => $this->get_tickets(),
                'messages' => $this->get_messages(),
                'documents' => $this->get_ticket_documents(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creando ticket: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear el ticket.'], 400);
        }
    }

    /**
     * ACTION CREATE TICKET
     *
     * COMPATIBILIDAD DE RUTA PARA FUELPHP CUANDO NO RESUELVE METODOS POST_*
     *
     * @access  public
     * @return  Response
     */
    public function action_create_ticket()
    {
        return $this->post_create_ticket();
    }

    /**
     * REPLY
     *
     * AGREGA RESPUESTA O NOTA INTERNA AL TICKET
     *
     * @access  public
     * @return  Response
     */
    public function post_reply()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('helpdesk.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN DATOS MINIMOS
            $ticket_id = (int) \Arr::get($val, 'ticket_id', 0);
            $message = trim((string) \Arr::get($val, 'message', ''));
            $ticket = Model_Core_Helpdesk_Ticket::find($ticket_id);
            if (!$ticket || $message === '') {
                return $this->json_response(['error' => 'Ticket y mensaje son obligatorios.'], 422);
            }

            # SE CREA MENSAJE
            Model_Core_Helpdesk_Message::forge([
                'ticket_id' => $ticket_id,
                'user_id' => $this->user_id,
                'author_type' => 'admin',
                'message' => $message,
                'is_internal' => (int) (bool) \Arr::get($val, 'is_internal', false),
                'active' => 1,
            ])->save();

            # SE ACTUALIZA TICKET
            $ticket->last_message_at = time();
            $ticket->save();

            # SE NOTIFICA AL SOLICITANTE Y ASIGNADO
            $this->notify_ticket($ticket, 'helpdesk.ticket_replied', 'Respuesta en ticket '.$ticket->folio, $message, [(int) $ticket->requester_user_id, (int) $ticket->assigned_user_id]);

            return $this->json_response([
                'status' => 'ok',
                'tickets' => $this->get_tickets(),
                'messages' => $this->get_messages(),
                'documents' => $this->get_ticket_documents(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error respondiendo ticket: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo responder el ticket.'], 400);
        }
    }

    /**
     * ACTION REPLY
     *
     * COMPATIBILIDAD DE RUTA PARA FUELPHP CUANDO NO RESUELVE METODOS POST_*
     *
     * @access  public
     * @return  Response
     */
    public function action_reply()
    {
        return $this->post_reply();
    }

    /**
     * UPDATE TICKET
     *
     * ACTUALIZA ASIGNACION, ESTADO O PRIORIDAD DEL TICKET
     *
     * @access  public
     * @return  Response
     */
    public function post_update_ticket()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('helpdesk.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE BUSCA TICKET
            $ticket = Model_Core_Helpdesk_Ticket::find((int) \Arr::get($val, 'id', 0));
            if (!$ticket) {
                return $this->json_response(['error' => 'Ticket no encontrado.'], 404);
            }
            $old = $ticket->to_array();

            # SE ACTUALIZAN CAMPOS PERMITIDOS
            $was_closed = false;
            $previous_status = Model_Core_Helpdesk_Status::find((int) $ticket->status_id);
            if ($previous_status && (int) $previous_status->is_closed === 1) {
                $was_closed = true;
            }

            $ticket->assigned_user_id = (int) \Arr::get($val, 'assigned_user_id', $ticket->assigned_user_id);
            $ticket->department_id = (int) \Arr::get($val, 'department_id', $ticket->department_id);
            $ticket->category_id = (int) \Arr::get($val, 'category_id', $ticket->category_id);
            $ticket->status_id = (int) \Arr::get($val, 'status_id', $ticket->status_id);
            $ticket->priority = $this->codeify(\Arr::get($val, 'priority', $ticket->priority));
            $schedule = $this->ticket_schedule_data($val, $ticket);
            $ticket->due_at = $schedule['due_at'];
            $ticket->scheduled_start_at = $schedule['scheduled_start_at'];
            $ticket->scheduled_end_at = $schedule['scheduled_end_at'];

            $status = Model_Core_Helpdesk_Status::find((int) $ticket->status_id);
            if ($status && (int) $status->is_closed === 1 && (int) $ticket->closed_at === 0) {
                $ticket->closed_at = time();
            }
            if ($status && (int) $status->is_closed === 0) {
                $ticket->closed_at = 0;
            }

            $ticket->save();

            # SE AUDITA ACTUALIZACION DEL TICKET
            Helper_Core_Audit::log([
                'module' => 'helpdesk',
                'action' => 'update_ticket',
                'business_event' => 'helpdesk.update_ticket',
                'entity_type' => 'helpdesk_ticket',
                'entity_id' => (int) $ticket->id,
                'table_name' => 'core_helpdesk_tickets',
                'summary' => 'Ticket actualizado '.$ticket->folio,
                'old_values' => $old,
                'new_values' => $ticket->to_array(),
            ]);

            # SE NOTIFICA CIERRE CUANDO EL TICKET CAMBIA A ESTADO CERRADO
            if (!$was_closed && $status && (int) $status->is_closed === 1) {
                $this->notify_ticket($ticket, 'helpdesk.ticket_closed', 'Ticket cerrado '.$ticket->folio, $ticket->subject, [(int) $ticket->requester_user_id, (int) $ticket->assigned_user_id]);
            }

            return $this->json_response([
                'status' => 'ok',
                'tickets' => $this->get_tickets(),
                'messages' => $this->get_messages(),
                'documents' => $this->get_ticket_documents(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error actualizando ticket: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo actualizar el ticket.'], 400);
        }
    }

    /**
     * ACTION UPDATE TICKET
     *
     * COMPATIBILIDAD DE RUTA PARA FUELPHP CUANDO NO RESUELVE METODOS POST_*
     *
     * @access  public
     * @return  Response
     */
    public function action_update_ticket()
    {
        return $this->post_update_ticket();
    }

    /**
     * UPLOAD DOCUMENT
     *
     * ADJUNTA DOCUMENTO O EVIDENCIA A UN TICKET
     *
     * @access  public
     * @return  Response
     */
    public function post_upload_document()
    {
        # VALIDAR PERMISOS PARA EDITAR TICKET Y CREAR DOCUMENTOS
        $this->require_access('helpdesk.access[edit]');
        $this->require_access('documents.access[create]');

        try {
            # SE VALIDA ESTRUCTURA Y TICKET
            $this->assert_schema_ready();
            $ticket = Model_Core_Helpdesk_Ticket::find((int) \Input::post('ticket_id', 0));
            if (!$ticket) {
                return $this->json_response(['error' => 'Ticket no encontrado.'], 404);
            }

            # SE GUARDA DOCUMENTO TRANSVERSAL
            $document = $this->store_ticket_document($ticket, 'internal');

            # SE REGISTRA MENSAJE DE SISTEMA PARA TRAZABILIDAD
            Model_Core_Helpdesk_Message::forge([
                'ticket_id' => (int) $ticket->id,
                'user_id' => $this->user_id,
                'author_type' => 'admin',
                'message' => 'Adjunto agregado: '.$document->original_name,
                'is_internal' => 0,
                'active' => 1,
            ])->save();

            $ticket->last_message_at = time();
            $ticket->save();

            return $this->json_response([
                'status' => 'ok',
                'tickets' => $this->get_tickets(),
                'messages' => $this->get_messages(),
                'documents' => $this->get_ticket_documents(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error adjuntando documento a ticket: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * ACTION UPLOAD DOCUMENT
     *
     * COMPATIBILIDAD DE RUTA PARA FUELPHP CUANDO NO RESUELVE METODOS POST_*
     *
     * @access  public
     * @return  Response
     */
    public function action_upload_document()
    {
        return $this->post_upload_document();
    }

    protected function get_tickets()
    {
        $tickets = [];
        foreach (Model_Core_Helpdesk_Ticket::query()->order_by('id', 'desc')->limit(200)->get() as $ticket) {
            $row = $ticket->to_array();
            $row['created_at'] = $ticket->created_at ? date('d/m/Y H:i', $ticket->created_at) : '';
            $row['updated_at'] = $ticket->updated_at ? date('d/m/Y H:i', $ticket->updated_at) : '';
            $row['last_message_at'] = $ticket->last_message_at ? date('d/m/Y H:i', $ticket->last_message_at) : '';
            $row['closed_at'] = $ticket->closed_at ? date('d/m/Y H:i', $ticket->closed_at) : '';
            $row['due_at_label'] = $ticket->due_at ? date('d/m/Y H:i', $ticket->due_at) : '';
            $row['scheduled_start_at_label'] = $ticket->scheduled_start_at ? date('d/m/Y H:i', $ticket->scheduled_start_at) : '';
            $row['scheduled_end_at_label'] = $ticket->scheduled_end_at ? date('d/m/Y H:i', $ticket->scheduled_end_at) : '';
            $row['due_at_input'] = $ticket->due_at ? date('Y-m-d\TH:i', $ticket->due_at) : '';
            $row['scheduled_start_at_input'] = $ticket->scheduled_start_at ? date('Y-m-d\TH:i', $ticket->scheduled_start_at) : '';
            $row['scheduled_end_at_input'] = $ticket->scheduled_end_at ? date('Y-m-d\TH:i', $ticket->scheduled_end_at) : '';
            $tickets[] = $row;
        }
        return $tickets;
    }

    /**
     * TICKET SCHEDULE DATA
     *
     * NORMALIZA FECHAS DE VENCIMIENTO Y PROGRAMACION DEL TICKET
     *
     * @access  protected
     * @return  Array
     */
    protected function ticket_schedule_data(array $val, $ticket = null)
    {
        # SE TOMA VALOR EXISTENTE SI EL CAMPO NO VIENE EN EL PAYLOAD
        $due_at = array_key_exists('due_at_input', $val) || array_key_exists('due_at', $val)
            ? $this->normalize_datetime(\Arr::get($val, 'due_at_input', \Arr::get($val, 'due_at', '')))
            : ($ticket ? (int) $ticket->due_at : 0);

        $scheduled_start_at = array_key_exists('scheduled_start_at_input', $val) || array_key_exists('scheduled_start_at', $val)
            ? $this->normalize_datetime(\Arr::get($val, 'scheduled_start_at_input', \Arr::get($val, 'scheduled_start_at', '')))
            : ($ticket ? (int) $ticket->scheduled_start_at : 0);

        $scheduled_end_at = array_key_exists('scheduled_end_at_input', $val) || array_key_exists('scheduled_end_at', $val)
            ? $this->normalize_datetime(\Arr::get($val, 'scheduled_end_at_input', \Arr::get($val, 'scheduled_end_at', '')))
            : ($ticket ? (int) $ticket->scheduled_end_at : 0);

        # SI SOLO HAY PROGRAMACION, SE USA COMO VENCIMIENTO OPERATIVO
        if ($due_at < 1 && $scheduled_start_at > 0) {
            $due_at = $scheduled_start_at;
        }

        # SI SOLO HAY VENCIMIENTO, SE CREA UNA VENTANA VISUAL DE UNA HORA
        if ($scheduled_start_at < 1 && $due_at > 0) {
            $scheduled_start_at = $due_at;
        }

        if ($scheduled_end_at < 1 && $scheduled_start_at > 0) {
            $scheduled_end_at = strtotime('+1 hour', $scheduled_start_at);
        }

        if ($scheduled_start_at > 0 && $scheduled_end_at > 0 && $scheduled_end_at <= $scheduled_start_at) {
            throw new \InvalidArgumentException('La fecha fin debe ser mayor a la fecha inicio.');
        }

        return [
            'due_at' => (int) $due_at,
            'scheduled_start_at' => (int) $scheduled_start_at,
            'scheduled_end_at' => (int) $scheduled_end_at,
        ];
    }

    /**
     * NORMALIZE DATETIME
     *
     * CONVIERTE FECHA DE FORMULARIO A TIMESTAMP
     *
     * @access  protected
     * @return  Int
     */
    protected function normalize_datetime($value)
    {
        # SE PERMITE LIMPIAR LA FECHA ENVIANDO CADENA VACIA
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

    protected function get_messages()
    {
        $messages = [];
        foreach (Model_Core_Helpdesk_Message::query()->order_by('id', 'asc')->limit(500)->get() as $message) {
            $row = $message->to_array();
            $row['created_at'] = $message->created_at ? date('d/m/Y H:i', $message->created_at) : '';
            $row['updated_at'] = $message->updated_at ? date('d/m/Y H:i', $message->updated_at) : '';
            $messages[] = $row;
        }
        return $messages;
    }

    /**
     * GET TICKET DOCUMENTS
     *
     * FORMATEA DOCUMENTOS VINCULADOS A TICKETS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_ticket_documents()
    {
        $rows = \DB::select(
                ['d.id', 'id'],
                ['l.entity_id', 'ticket_id'],
                ['d.title', 'title'],
                ['d.original_name', 'original_name'],
                ['d.file_path', 'file_path'],
                ['d.file_extension', 'file_extension'],
                ['d.file_size', 'file_size'],
                ['d.visibility', 'visibility'],
                ['d.is_evidence', 'is_evidence'],
                ['d.created_at', 'created_at']
            )
            ->from(['core_document_links', 'l'])
            ->join(['core_documents', 'd'], 'INNER')
            ->on('d.id', '=', 'l.document_id')
            ->where('l.entity_type', '=', 'ticket')
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->order_by('d.id', 'desc')
            ->limit(500)
            ->execute();

        $documents = [];
        foreach ($rows as $row) {
            $documents[] = [
                'id' => (int) $row['id'],
                'ticket_id' => (int) $row['ticket_id'],
                'title' => (string) $row['title'],
                'original_name' => (string) $row['original_name'],
                'file_path' => (string) $row['file_path'],
                'file_extension' => (string) $row['file_extension'],
                'file_size' => (int) $row['file_size'],
                'visibility' => (string) $row['visibility'],
                'is_evidence' => (int) $row['is_evidence'],
                'created_at' => $row['created_at'] ? date('d/m/Y H:i', $row['created_at']) : '',
            ];
        }

        return $documents;
    }

    protected function get_options()
    {
        return [
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'departments' => $this->select_options('core_departments', 'id', 'name'),
            'categories' => $this->select_options('core_helpdesk_categories', 'id', 'name'),
            'statuses' => $this->status_options(),
            'users' => $this->user_options(),
            'priorities' => [
                ['value' => 'baja', 'label' => 'Baja'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'alta', 'label' => 'Alta'],
                ['value' => 'urgente', 'label' => 'Urgente'],
            ],
        ];
    }

    protected function get_stats()
    {
        $open_statuses = \DB::select('id')->from('core_helpdesk_statuses')->where('is_closed', '=', 0)->execute()->as_array();
        $open_ids = array_map(function ($row) { return (int) $row['id']; }, $open_statuses);

        return [
            'tickets' => (int) \DB::count_records('core_helpdesk_tickets'),
            'open' => !empty($open_ids) ? (int) \DB::select()->from('core_helpdesk_tickets')->where('status_id', 'in', $open_ids)->execute()->count() : 0,
            'assigned_to_me' => (int) \DB::select()->from('core_helpdesk_tickets')->where('assigned_user_id', '=', $this->user_id)->execute()->count(),
            'messages' => (int) \DB::count_records('core_helpdesk_messages'),
        ];
    }

    protected function select_options($table, $value_field, $label_field)
    {
        $rows = \DB::select($value_field, $label_field)->from($table)->where('active', '=', 1)->order_by($label_field, 'asc')->execute();
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $options;
    }

    /**
     * STATUS OPTIONS
     *
     * FORMATEA ESTADOS DE HELPDESK CON COLOR Y BANDERA DE CIERRE
     *
     * @access  protected
     * @return  Array
     */
    protected function status_options()
    {
        $rows = \DB::select('id', 'name', 'color', 'is_closed')
            ->from('core_helpdesk_statuses')
            ->where('active', '=', 1)
            ->order_by('sort_order', 'asc')
            ->execute();

        $options = [];
        foreach ($rows as $row) {
            $options[] = [
                'value' => (string) $row['id'],
                'label' => (string) $row['name'],
                'color' => (string) $row['color'],
                'is_closed' => (int) $row['is_closed'],
            ];
        }

        return $options;
    }

    protected function user_options()
    {
        $rows = \DB::select('id', 'username', 'email')->from('users')->order_by('username', 'asc')->execute();
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row['id'], 'label' => $row['username'].($row['email'] ? ' - '.$row['email'] : '')];
        }
        return $options;
    }

    protected function status_by_code($code)
    {
        return Model_Core_Helpdesk_Status::query()->where('code', $code)->where('active', 1)->get_one();
    }

    protected function next_folio()
    {
        return 'HD-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_helpdesk_tickets') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function notify_ticket($ticket, $event_code, $title, $message, array $user_ids)
    {
        Helper_Core_Notification::create([
            'event_code' => $event_code,
            'notification_type' => 'helpdesk',
            'title' => $title,
            'message' => function_exists('mb_substr') ? mb_substr(strip_tags($message), 0, 220) : substr(strip_tags($message), 0, 220),
            'url' => \Uri::create('admin/helpdesk'),
            'icon' => 'bi bi-life-preserver',
            'priority' => 2,
            'payload' => ['ticket_id' => (int) $ticket->id, 'folio' => (string) $ticket->folio],
            'created_by' => $this->user_id,
        ], $user_ids);
    }

    /**
     * STORE TICKET DOCUMENT
     *
     * GUARDA ARCHIVO Y CREA VINCULO DOCUMENTAL CONTRA EL TICKET
     *
     * @access  protected
     * @return  Model_Core_Document
     */
    protected function store_ticket_document($ticket, $visibility)
    {
        # SE OBTIENE EL ARCHIVO
        $file = \Input::file('file');
        if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Selecciona un archivo valido.');
        }

        # SE VALIDAN EXTENSION Y PESO
        $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
        $allowed = ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
        if (!in_array($extension, $allowed)) {
            throw new \RuntimeException('Tipo de archivo no permitido.');
        }

        if ((int) \Arr::get($file, 'size', 0) > 15728640) {
            throw new \RuntimeException('El archivo no puede superar 15 MB.');
        }

        # SE PREPARA DESTINO PUBLICO CONTROLADO
        $relative_dir = 'assets/uploads/documents/ticket/'.date('Y').'/'.date('m');
        $absolute_dir = DOCROOT.$relative_dir;
        if (!is_dir($absolute_dir)) {
            mkdir($absolute_dir, 0755, true);
        }

        # SE GENERA NOMBRE SEGURO
        $base_name = pathinfo((string) \Arr::get($file, 'name', 'documento'), PATHINFO_FILENAME);
        $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->codeify($base_name).'.'.$extension;
        $target = $absolute_dir.DS.$filename;

        if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        # SE CREA EL DOCUMENTO
        $path = str_replace('\\', '/', $relative_dir.'/'.$filename);
        $document = Model_Core_Document::forge([
            'document_type' => 'ticket',
            'title' => trim((string) \Input::post('title', '')) ?: $base_name,
            'description' => trim((string) \Input::post('description', '')),
            'file_path' => $path,
            'original_name' => (string) \Arr::get($file, 'name', ''),
            'mime_type' => (string) \Arr::get($file, 'type', ''),
            'file_extension' => $extension,
            'file_size' => (int) \Arr::get($file, 'size', 0),
            'checksum' => is_file($target) ? hash_file('sha256', $target) : '',
            'visibility' => $this->codeify($visibility),
            'is_evidence' => (int) (bool) \Input::post('is_evidence', true),
            'uploaded_by' => $this->user_id,
            'active' => 1,
        ]);
        $document->save();

        # SE CREA VINCULO CON TICKET
        Model_Core_Document_Link::forge([
            'document_id' => (int) $document->id,
            'entity_type' => 'ticket',
            'entity_id' => (int) $ticket->id,
            'relation_type' => 'attachment',
            'notes' => trim((string) \Input::post('notes', '')),
            'created_by' => $this->user_id,
            'active' => 1,
        ])->save();

        return $document;
    }

    protected function assert_schema_ready()
    {
        foreach (['core_helpdesk_categories', 'core_helpdesk_statuses', 'core_helpdesk_tickets', 'core_helpdesk_messages', 'core_documents', 'core_document_links'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de helpdesk.');
            }
        }
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
