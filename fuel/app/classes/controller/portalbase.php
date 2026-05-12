<?php

/**
 * CONTROLADOR PORTALBASE
 *
 * Base comun para portales externos vinculados a terceros.
 *
 * @package  app
 * @extends  Controller_Template
 */
class Controller_Portalbase extends Controller_Template
{
    public $template = 'portal/template';
    protected $portal_code = '';
    protected $user_id = 0;
    protected $portal_link = null;
    protected $party = null;
    protected $branding = null;

    /**
     * BEFORE
     *
     * VALIDA SESION Y VINCULO USUARIO-TERCERO-PORTAL
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING
        parent::before();

        # VALIDAR SESION
        if (!\Auth::check()) {
            \Response::redirect($this->portal_code.'/login');
        }

        # SE OBTIENE USUARIO ACTUAL
        $user_id_data = \Auth::get_user_id();
        $this->user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;

        # SE VALIDA PORTAL CONFIGURADO
        if ($this->portal_code === '') {
            throw new \HttpNotFoundException;
        }

        # SE OBTIENE VINCULO ACTIVO
        $this->portal_link = Model_Core_Party_User_Link::query()
            ->where('user_id', $this->user_id)
            ->where('portal_code', $this->portal_code)
            ->where('active', 1)
            ->get_one();

        if (!$this->portal_link) {
            \Response::redirect($this->portal_code.'/login');
        }

        # SE OBTIENE TERCERO Y BRANDING
        $this->party = Model_Core_Party::find((int) $this->portal_link->party_id);
        $this->branding = Model_Core_Party_Branding::query()
            ->where('party_id', (int) $this->portal_link->party_id)
            ->where('portal_code', $this->portal_code)
            ->where('active', 1)
            ->get_one();

        # SE ASIGNAN VARIABLES GLOBALES AL TEMPLATE
        $this->template->portal_code = $this->portal_code;
        $this->template->portal_name = $this->portal_title();
        $this->template->party = $this->party;
        $this->template->branding = $this->branding;
        $this->template->user_name = \Auth::get_screen_name();
    }

    /**
     * PORTAL TITLE
     *
     * OBTIENE EL NOMBRE CONFIGURADO DEL PORTAL
     *
     * @access  protected
     * @return  String
     */
    protected function portal_title()
    {
        $profile = Model_Core_Portal_Profile::query()
            ->where('code', $this->portal_code)
            ->where('active', 1)
            ->get_one();

        return $profile ? (string) $profile->name : ucfirst($this->portal_code);
    }

    /**
     * HELPDESK
     *
     * MUESTRA LOS TICKETS DEL TERCERO EN EL PORTAL ACTUAL
     *
     * @access  public
     * @return  Void
     */
    public function action_helpdesk()
    {
        # SE CARGA LA VISTA COMUN DE HELPDESK PARA PORTALES
        $this->template->title = 'Helpdesk';
        $this->template->content = View::forge('portal/helpdesk', [
            'portal_code' => $this->portal_code,
            'party' => $this->party,
        ]);
    }

    /**
     * HELPDESK DATA
     *
     * ENTREGA TICKETS Y OPCIONES DEL TERCERO LOGUEADO
     *
     * @access  public
     * @return  Response
     */
    public function action_helpdesk_data()
    {
        try {
            # SE VALIDA QUE LAS TABLAS EXISTAN
            $this->assert_helpdesk_schema_ready();

            # SE REGRESA INFORMACION CONTROLADA POR TERCERO Y PORTAL
            return $this->json_response([
                'tickets' => $this->portal_tickets(),
                'messages' => $this->portal_messages(),
                'documents' => $this->portal_ticket_documents(),
                'options' => $this->portal_helpdesk_options(),
                'stats' => $this->portal_helpdesk_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando helpdesk portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar helpdesk.'], 500);
        }
    }

    /**
     * HELPDESK CREATE
     *
     * CREA UN TICKET DESDE EL PORTAL EXTERNO
     *
     * @access  public
     * @return  Response
     */
    public function post_helpdesk_create()
    {
        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDAN DATOS MINIMOS
            $subject = trim((string) \Arr::get($val, 'subject', ''));
            $description = trim((string) \Arr::get($val, 'description', ''));
            if ($subject === '' || $description === '') {
                return $this->json_response(['error' => 'Asunto y descripcion son obligatorios.'], 422);
            }

            # SE CREA EL TICKET AMARRADO AL TERCERO Y PORTAL ACTUAL
            $status = $this->helpdesk_status_by_code('nuevo');
            $ticket = Model_Core_Helpdesk_Ticket::forge([
                'folio' => $this->next_helpdesk_folio(),
                'source' => 'portal',
                'portal_code' => $this->portal_code,
                'party_id' => (int) $this->portal_link->party_id,
                'requester_user_id' => $this->user_id,
                'assigned_user_id' => 0,
                'department_id' => 0,
                'category_id' => (int) \Arr::get($val, 'category_id', 0),
                'status_id' => $status ? (int) $status->id : 0,
                'priority' => $this->codeify(\Arr::get($val, 'priority', 'normal')),
                'subject' => $subject,
                'description' => $description,
                'last_message_at' => time(),
                'active' => 1,
            ]);
            $ticket->save();

            # SE AGREGA EL MENSAJE INICIAL VISIBLE
            Model_Core_Helpdesk_Message::forge([
                'ticket_id' => (int) $ticket->id,
                'user_id' => $this->user_id,
                'author_type' => $this->portal_code,
                'message' => $description,
                'is_internal' => 0,
                'active' => 1,
            ])->save();

            # SE NOTIFICA AL EQUIPO ADMINISTRATIVO
            $this->notify_helpdesk_admins($ticket, 'helpdesk.ticket_created', 'Nuevo ticket '.$ticket->folio, $subject);

            return $this->json_response([
                'status' => 'ok',
                'tickets' => $this->portal_tickets(),
                'messages' => $this->portal_messages(),
                'documents' => $this->portal_ticket_documents(),
                'stats' => $this->portal_helpdesk_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creando ticket portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear el ticket.'], 400);
        }
    }

    /**
     * ACTION HELPDESK CREATE
     *
     * COMPATIBILIDAD DE RUTA PARA FUELPHP CUANDO NO RESUELVE METODOS POST_*
     *
     * @access  public
     * @return  Response
     */
    public function action_helpdesk_create()
    {
        return $this->post_helpdesk_create();
    }

    /**
     * HELPDESK REPLY
     *
     * AGREGA RESPUESTA EXTERNA A UN TICKET DEL MISMO TERCERO Y PORTAL
     *
     * @access  public
     * @return  Response
     */
    public function post_helpdesk_reply()
    {
        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # SE VALIDA QUE EL TICKET PERTENEZCA AL TERCERO Y PORTAL ACTUAL
            $ticket_id = (int) \Arr::get($val, 'ticket_id', 0);
            $message = trim((string) \Arr::get($val, 'message', ''));
            $ticket = $this->portal_ticket_by_id($ticket_id);
            if (!$ticket || $message === '') {
                return $this->json_response(['error' => 'Ticket y mensaje son obligatorios.'], 422);
            }

            # SE CREA RESPUESTA VISIBLE PARA ADMIN
            Model_Core_Helpdesk_Message::forge([
                'ticket_id' => (int) $ticket->id,
                'user_id' => $this->user_id,
                'author_type' => $this->portal_code,
                'message' => $message,
                'is_internal' => 0,
                'active' => 1,
            ])->save();

            # SE ACTUALIZA ULTIMA ACTIVIDAD
            $ticket->last_message_at = time();
            $ticket->save();

            # SE NOTIFICA A RESPONSABLE O ADMINISTRADORES
            $user_ids = [(int) $ticket->assigned_user_id];
            if ((int) $ticket->assigned_user_id < 1) {
                $user_ids = $this->admin_user_ids();
            }
            $this->notify_helpdesk_users($ticket, 'helpdesk.ticket_replied', 'Respuesta en ticket '.$ticket->folio, $message, $user_ids);

            return $this->json_response([
                'status' => 'ok',
                'tickets' => $this->portal_tickets(),
                'messages' => $this->portal_messages(),
                'documents' => $this->portal_ticket_documents(),
                'stats' => $this->portal_helpdesk_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error respondiendo ticket portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo responder el ticket.'], 400);
        }
    }

    /**
     * ACTION HELPDESK REPLY
     *
     * COMPATIBILIDAD DE RUTA PARA FUELPHP CUANDO NO RESUELVE METODOS POST_*
     *
     * @access  public
     * @return  Response
     */
    public function action_helpdesk_reply()
    {
        return $this->post_helpdesk_reply();
    }

    /**
     * HELPDESK UPLOAD
     *
     * ADJUNTA DOCUMENTO A UN TICKET DEL PORTAL ACTUAL
     *
     * @access  public
     * @return  Response
     */
    public function post_helpdesk_upload()
    {
        try {
            # SE VALIDA QUE EL TICKET PERTENEZCA AL TERCERO Y PORTAL ACTUAL
            $ticket = $this->portal_ticket_by_id((int) \Input::post('ticket_id', 0));
            if (!$ticket) {
                return $this->json_response(['error' => 'Ticket no encontrado.'], 404);
            }

            # SE GUARDA DOCUMENTO CON VISIBILIDAD DE PORTAL
            $document = $this->store_portal_ticket_document($ticket);

            # SE REGISTRA MENSAJE VISIBLE PARA EL HISTORIAL
            Model_Core_Helpdesk_Message::forge([
                'ticket_id' => (int) $ticket->id,
                'user_id' => $this->user_id,
                'author_type' => $this->portal_code,
                'message' => 'Adjunto agregado: '.$document->original_name,
                'is_internal' => 0,
                'active' => 1,
            ])->save();

            $ticket->last_message_at = time();
            $ticket->save();

            # SE NOTIFICA A RESPONSABLE O ADMINISTRADORES
            $user_ids = [(int) $ticket->assigned_user_id];
            if ((int) $ticket->assigned_user_id < 1) {
                $user_ids = $this->admin_user_ids();
            }
            $this->notify_helpdesk_users($ticket, 'helpdesk.ticket_replied', 'Adjunto en ticket '.$ticket->folio, $document->original_name, $user_ids);

            return $this->json_response([
                'status' => 'ok',
                'tickets' => $this->portal_tickets(),
                'messages' => $this->portal_messages(),
                'documents' => $this->portal_ticket_documents(),
                'stats' => $this->portal_helpdesk_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error adjuntando documento portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * ACTION HELPDESK UPLOAD
     *
     * COMPATIBILIDAD DE RUTA PARA FUELPHP CUANDO NO RESUELVE METODOS POST_*
     *
     * @access  public
     * @return  Response
     */
    public function action_helpdesk_upload()
    {
        return $this->post_helpdesk_upload();
    }

    /**
     * PORTAL TICKETS
     *
     * FORMATEA LOS TICKETS VISIBLES PARA EL TERCERO ACTUAL
     *
     * @access  protected
     * @return  Array
     */
    protected function portal_tickets()
    {
        $rows = Model_Core_Helpdesk_Ticket::query()
            ->where('party_id', (int) $this->portal_link->party_id)
            ->where('portal_code', $this->portal_code)
            ->where('active', 1)
            ->order_by('id', 'desc')
            ->limit(100)
            ->get();

        $tickets = [];
        foreach ($rows as $ticket) {
            $row = $ticket->to_array();
            $row['created_at'] = $ticket->created_at ? date('d/m/Y H:i', $ticket->created_at) : '';
            $row['last_message_at'] = $ticket->last_message_at ? date('d/m/Y H:i', $ticket->last_message_at) : '';
            $row['closed_at'] = $ticket->closed_at ? date('d/m/Y H:i', $ticket->closed_at) : '';
            $tickets[] = $row;
        }

        return $tickets;
    }

    /**
     * PORTAL MESSAGES
     *
     * FORMATEA MENSAJES VISIBLES PARA LOS TICKETS DEL PORTAL
     *
     * @access  protected
     * @return  Array
     */
    protected function portal_messages()
    {
        $tickets = $this->portal_tickets();
        $ticket_ids = array_map(function ($ticket) {
            return (int) $ticket['id'];
        }, $tickets);

        if (empty($ticket_ids)) {
            return [];
        }

        $rows = Model_Core_Helpdesk_Message::query()
            ->where('ticket_id', 'in', $ticket_ids)
            ->where('is_internal', 0)
            ->where('active', 1)
            ->order_by('id', 'asc')
            ->limit(500)
            ->get();

        $messages = [];
        foreach ($rows as $message) {
            $row = $message->to_array();
            $row['created_at'] = $message->created_at ? date('d/m/Y H:i', $message->created_at) : '';
            $messages[] = $row;
        }

        return $messages;
    }

    /**
     * PORTAL TICKET DOCUMENTS
     *
     * FORMATEA DOCUMENTOS VISIBLES DE TICKETS DEL PORTAL
     *
     * @access  protected
     * @return  Array
     */
    protected function portal_ticket_documents()
    {
        $tickets = $this->portal_tickets();
        $ticket_ids = array_map(function ($ticket) {
            return (int) $ticket['id'];
        }, $tickets);

        if (empty($ticket_ids)) {
            return [];
        }

        $rows = \DB::select(
                ['d.id', 'id'],
                ['l.entity_id', 'ticket_id'],
                ['d.title', 'title'],
                ['d.original_name', 'original_name'],
                ['d.file_path', 'file_path'],
                ['d.file_extension', 'file_extension'],
                ['d.file_size', 'file_size'],
                ['d.visibility', 'visibility'],
                ['d.created_at', 'created_at']
            )
            ->from(['core_document_links', 'l'])
            ->join(['core_documents', 'd'], 'INNER')
            ->on('d.id', '=', 'l.document_id')
            ->where('l.entity_type', '=', 'ticket')
            ->where('l.entity_id', 'in', $ticket_ids)
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->where('d.visibility', 'in', ['portal', 'public'])
            ->order_by('d.id', 'desc')
            ->limit(300)
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
                'created_at' => $row['created_at'] ? date('d/m/Y H:i', $row['created_at']) : '',
            ];
        }

        return $documents;
    }

    /**
     * PORTAL HELPDESK OPTIONS
     *
     * OBTIENE CATEGORIAS, ESTADOS Y PRIORIDADES PARA EL PORTAL
     *
     * @access  protected
     * @return  Array
     */
    protected function portal_helpdesk_options()
    {
        return [
            'categories' => $this->select_helpdesk_options('core_helpdesk_categories', 'id', 'name'),
            'statuses' => $this->status_helpdesk_options(),
            'priorities' => [
                ['value' => 'baja', 'label' => 'Baja'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'alta', 'label' => 'Alta'],
                ['value' => 'urgente', 'label' => 'Urgente'],
            ],
        ];
    }

    /**
     * PORTAL HELPDESK STATS
     *
     * CALCULA CONTADORES DEL PORTAL ACTUAL
     *
     * @access  protected
     * @return  Array
     */
    protected function portal_helpdesk_stats()
    {
        $open_statuses = \DB::select('id')->from('core_helpdesk_statuses')->where('is_closed', '=', 0)->execute()->as_array();
        $open_ids = array_map(function ($row) { return (int) $row['id']; }, $open_statuses);

        $base = \DB::select()->from('core_helpdesk_tickets')
            ->where('party_id', '=', (int) $this->portal_link->party_id)
            ->where('portal_code', '=', $this->portal_code)
            ->where('active', '=', 1);

        $open = 0;
        if (!empty($open_ids)) {
            $open = \DB::select()->from('core_helpdesk_tickets')
                ->where('party_id', '=', (int) $this->portal_link->party_id)
                ->where('portal_code', '=', $this->portal_code)
                ->where('active', '=', 1)
                ->where('status_id', 'in', $open_ids)
                ->execute()
                ->count();
        }

        return [
            'tickets' => (int) $base->execute()->count(),
            'open' => (int) $open,
        ];
    }

    /**
     * PORTAL TICKET BY ID
     *
     * BUSCA UN TICKET ASEGURANDO TENENCIA POR TERCERO Y PORTAL
     *
     * @access  protected
     * @return  Model_Core_Helpdesk_Ticket|null
     */
    protected function portal_ticket_by_id($ticket_id)
    {
        return Model_Core_Helpdesk_Ticket::query()
            ->where('id', (int) $ticket_id)
            ->where('party_id', (int) $this->portal_link->party_id)
            ->where('portal_code', $this->portal_code)
            ->where('active', 1)
            ->get_one();
    }

    /**
     * SELECT HELPDESK OPTIONS
     *
     * FORMATEA OPCIONES ACTIVAS DE TABLAS BASE
     *
     * @access  protected
     * @return  Array
     */
    protected function select_helpdesk_options($table, $value_field, $label_field)
    {
        $rows = \DB::select($value_field, $label_field)
            ->from($table)
            ->where('active', '=', 1)
            ->order_by($label_field, 'asc')
            ->execute();

        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }

        return $options;
    }

    /**
     * STATUS HELPDESK OPTIONS
     *
     * FORMATEA ESTADOS CON COLOR PARA BADGES
     *
     * @access  protected
     * @return  Array
     */
    protected function status_helpdesk_options()
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

    /**
     * HELPDESK STATUS BY CODE
     *
     * OBTIENE UN ESTADO POR CODIGO
     *
     * @access  protected
     * @return  Model_Core_Helpdesk_Status|null
     */
    protected function helpdesk_status_by_code($code)
    {
        return Model_Core_Helpdesk_Status::query()->where('code', $code)->where('active', 1)->get_one();
    }

    /**
     * NEXT HELPDESK FOLIO
     *
     * GENERA FOLIO SIMPLE PARA TICKETS
     *
     * @access  protected
     * @return  String
     */
    protected function next_helpdesk_folio()
    {
        return 'HD-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_helpdesk_tickets') + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * NOTIFY HELPDESK ADMINS
     *
     * ENVIA NOTIFICACION A ADMINISTRADORES GENERALES
     *
     * @access  protected
     * @return  Void
     */
    protected function notify_helpdesk_admins($ticket, $event_code, $title, $message)
    {
        $this->notify_helpdesk_users($ticket, $event_code, $title, $message, $this->admin_user_ids());
    }

    /**
     * NOTIFY HELPDESK USERS
     *
     * CREA NOTIFICACION INTERNA PARA SEGUIMIENTO DE TICKET
     *
     * @access  protected
     * @return  Void
     */
    protected function notify_helpdesk_users($ticket, $event_code, $title, $message, array $user_ids)
    {
        Helper_Core_Notification::create([
            'event_code' => $event_code,
            'notification_type' => 'helpdesk',
            'title' => $title,
            'message' => function_exists('mb_substr') ? mb_substr(strip_tags($message), 0, 220) : substr(strip_tags($message), 0, 220),
            'url' => \Uri::create('admin/helpdesk'),
            'icon' => 'bi bi-life-preserver',
            'priority' => 2,
            'payload' => ['ticket_id' => (int) $ticket->id, 'folio' => (string) $ticket->folio, 'portal_code' => $this->portal_code],
            'created_by' => $this->user_id,
        ], $user_ids);
    }

    /**
     * STORE PORTAL TICKET DOCUMENT
     *
     * GUARDA ARCHIVO DEL PORTAL Y LO VINCULA AL TICKET
     *
     * @access  protected
     * @return  Model_Core_Document
     */
    protected function store_portal_ticket_document($ticket)
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

        # SE CREA DOCUMENTO DE PORTAL
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
            'visibility' => 'portal',
            'is_evidence' => 1,
            'uploaded_by' => $this->user_id,
            'active' => 1,
        ]);
        $document->save();

        # SE CREA VINCULO CONTRA TICKET
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

    /**
     * ADMIN USER IDS
     *
     * OBTIENE USUARIOS ADMINISTRATIVOS PARA NOTIFICACION INICIAL
     *
     * @access  protected
     * @return  Array
     */
    protected function admin_user_ids()
    {
        $rows = \DB::select('id')
            ->from('users')
            ->where('group_id', '>=', 90)
            ->execute();

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }

    /**
     * ASSERT HELPDESK SCHEMA READY
     *
     * VALIDA TABLAS BASE DE HELPDESK
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_helpdesk_schema_ready()
    {
        foreach (['core_helpdesk_categories', 'core_helpdesk_statuses', 'core_helpdesk_tickets', 'core_helpdesk_messages', 'core_documents', 'core_document_links'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de helpdesk.');
            }
        }
    }

    /**
     * JSON RESPONSE
     *
     * GENERA RESPUESTA JSON ESTANDAR PARA PORTALES
     *
     * @access  protected
     * @return  Response
     */
    protected function json_response(array $data, $status = 200)
    {
        return \Response::forge(
            json_encode($data),
            $status,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * CODEIFY
     *
     * NORMALIZA CODIGOS INTERNOS
     *
     * @access  protected
     * @return  String
     */
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
