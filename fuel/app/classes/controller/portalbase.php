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
            \Log::warning('PORTAL ACCESS: sesion no autenticada para portal '.$this->portal_code.' uri='.\Uri::string());
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
            \Log::warning('PORTAL ACCESS: usuario '.$this->user_id.' sin vinculo activo para portal '.$this->portal_code.' uri='.\Uri::string());
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
     * PORTAL VIEW
     *
     * RESUELVE PRIMERO LA VISTA MVC DEL PORTAL Y USA UNA VISTA COMUN COMO RESPALDO.
     *
     * @access  protected
     * @return  View
     */
    protected function portal_view($section, $fallback, array $data = [])
    {
        $candidate = $this->portal_code.'/'.$section.'/index';
        $candidate_path = APPPATH.'views'.DS.str_replace('/', DS, $candidate).'.php';
        $view = is_file($candidate_path) ? $candidate : $fallback;

        return View::forge($view, $data);
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
        $this->template->content = $this->portal_view('helpdesk', 'portales/helpdesk/index', [
            'portal_code' => $this->portal_code,
            'party' => $this->party,
        ]);
    }

    /**
     * PERFIL
     *
     * MUESTRA DATOS OPERATIVOS DEL TERCERO EN EL PORTAL ACTUAL.
     *
     * @access  public
     * @return  Void
     */
    public function action_perfil()
    {
        $this->template->title = 'Mi cuenta';
        $this->template->content = $this->portal_view('perfil', 'portales/perfil/index', [
            'portal_code' => $this->portal_code,
            'party' => $this->party,
        ]);
    }

    /**
     * CFDI
     *
     * VISTA GENERICA DE CFDI PARA PORTALES SIN IMPLEMENTACION ESPECIFICA.
     *
     * @access  public
     * @return  Void
     */
    public function action_cfdi()
    {
        $this->template->title = 'CFDI';
        $this->template->content = $this->portal_view('cfdi', 'portales/cfdi/index', [
            'portal_code' => $this->portal_code,
            'portal_direction' => $this->portal_code,
            'portal_title' => 'CFDI del portal',
        ]);
    }

    /**
     * CFDI DATA
     *
     * RESPUESTA SEGURA PARA PORTALES QUE TODAVIA NO TIENEN REGLA CFDI PROPIA.
     *
     * @access  public
     * @return  Response
     */
    public function action_cfdi_data()
    {
        return $this->json_response([
            'items' => [],
            'message' => 'Este portal aun no tiene CFDI configurados.',
        ]);
    }

    /**
     * PERFIL DATA
     *
     * ENTREGA DATOS, DIRECCIONES, CONTACTOS Y DOCUMENTOS DEL TERCERO.
     *
     * @access  public
     * @return  Response
     */
    public function action_perfil_data()
    {
        try {
            return $this->json_response($this->portal_profile_payload());
        } catch (\Exception $e) {
            \Log::error('Error cargando perfil portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar Mi cuenta.'], 500);
        }
    }

    /**
     * PERFIL SAVE
     *
     * ACTUALIZA DATOS BASICOS CONTROLADOS POR EL PORTAL.
     *
     * @access  public
     * @return  Response
     */
    public function post_perfil_save()
    {
        $val = (array) \Input::json();

        try {
            if (!$this->party) {
                return $this->json_response(['error' => 'Socio comercial no encontrado.'], 404);
            }

            $this->party->name = trim((string) \Arr::get($val, 'name', $this->party->name));
            $this->party->legal_name = trim((string) \Arr::get($val, 'legal_name', $this->party->legal_name));
            $this->party->email = trim((string) \Arr::get($val, 'email', $this->party->email));
            $this->party->phone = trim((string) \Arr::get($val, 'phone', $this->party->phone));
            $this->party->sat_tax_regime_code = trim((string) \Arr::get($val, 'sat_tax_regime_code', $this->party->sat_tax_regime_code));
            $this->party->notes = trim((string) \Arr::get($val, 'notes', $this->party->notes));
            $this->party->save();

            Helper_Core_Audit::log([
                'module' => 'portals',
                'action' => 'portal_profile_save',
                'business_event' => 'portals.profile_save',
                'entity_type' => 'party',
                'entity_id' => (int) $this->party->id,
                'table_name' => 'core_parties',
                'portal_code' => $this->portal_code,
                'backend' => 'portal',
                'summary' => 'Perfil actualizado desde portal '.$this->portal_code,
                'new_values' => $this->party->to_array(),
            ]);

            return $this->json_response($this->portal_profile_payload(['status' => 'ok', 'message' => 'Datos guardados.']));
        } catch (\Exception $e) {
            \Log::error('Error guardando perfil portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el perfil.'], 400);
        }
    }

    public function action_perfil_save()
    {
        return $this->post_perfil_save();
    }

    /**
     * PERFIL ADDRESS
     *
     * CREA O ACTUALIZA DIRECCIONES DEL TERCERO.
     *
     * @access  public
     * @return  Response
     */
    public function post_perfil_address()
    {
        $val = (array) \Input::json();

        try {
            $party_id = (int) $this->portal_link->party_id;
            $id = (int) \Arr::get($val, 'id', 0);
            $address = $id > 0 ? Model_Core_Party_Address::query()->where('id', $id)->where('party_id', $party_id)->get_one() : null;
            if (!$address) {
                $address = Model_Core_Party_Address::forge(['party_id' => $party_id, 'active' => 1]);
            }

            $address->address_type = $this->profile_address_type((string) \Arr::get($val, 'address_type', 'delivery'));
            $address->name = trim((string) \Arr::get($val, 'name', ''));
            $address->street = trim((string) \Arr::get($val, 'street', ''));
            $address->exterior_number = trim((string) \Arr::get($val, 'exterior_number', ''));
            $address->interior_number = trim((string) \Arr::get($val, 'interior_number', ''));
            $address->neighborhood = trim((string) \Arr::get($val, 'neighborhood', ''));
            $address->city = trim((string) \Arr::get($val, 'city', ''));
            $address->state = trim((string) \Arr::get($val, 'state', ''));
            $address->country_code = strtoupper(trim((string) \Arr::get($val, 'country_code', 'MX'))) ?: 'MX';
            $address->postal_code = trim((string) \Arr::get($val, 'postal_code', ''));
            $address->is_default = (int) \Arr::get($val, 'is_default', 0);
            $address->active = 1;
            $address->save();

            return $this->json_response($this->portal_profile_payload(['status' => 'ok', 'message' => 'Direccion guardada.']));
        } catch (\Exception $e) {
            \Log::error('Error guardando direccion portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la direccion.'], 400);
        }
    }

    public function action_perfil_address()
    {
        return $this->post_perfil_address();
    }

    /**
     * PERFIL CONTACT
     *
     * CREA O ACTUALIZA CONTACTOS DEL TERCERO.
     *
     * @access  public
     * @return  Response
     */
    public function post_perfil_contact()
    {
        $val = (array) \Input::json();

        try {
            $party_id = (int) $this->portal_link->party_id;
            $id = (int) \Arr::get($val, 'id', 0);
            $contact = $id > 0 ? Model_Core_Party_Contact::query()->where('id', $id)->where('party_id', $party_id)->get_one() : null;
            if (!$contact) {
                $contact = Model_Core_Party_Contact::forge(['party_id' => $party_id, 'active' => 1]);
            }

            $contact->name = trim((string) \Arr::get($val, 'name', ''));
            $contact->position = trim((string) \Arr::get($val, 'position', ''));
            $contact->email = trim((string) \Arr::get($val, 'email', ''));
            $contact->phone = trim((string) \Arr::get($val, 'phone', ''));
            $contact->receives_notifications = (int) \Arr::get($val, 'receives_notifications', 1);
            $contact->active = 1;
            $contact->save();

            return $this->json_response($this->portal_profile_payload(['status' => 'ok', 'message' => 'Contacto guardado.']));
        } catch (\Exception $e) {
            \Log::error('Error guardando contacto portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el contacto.'], 400);
        }
    }

    public function action_perfil_contact()
    {
        return $this->post_perfil_contact();
    }

    /**
     * PERFIL UPLOAD
     *
     * CARGA DOCUMENTOS DEL TERCERO DESDE SU PORTAL.
     *
     * @access  public
     * @return  Response
     */
    public function post_perfil_upload()
    {
        try {
            $document = $this->store_portal_party_document();
            Helper_Core_Audit::log([
                'module' => 'portals',
                'action' => 'portal_profile_upload',
                'business_event' => 'portals.profile_upload',
                'entity_type' => 'document',
                'entity_id' => (int) $document->id,
                'portal_code' => $this->portal_code,
                'backend' => 'portal',
                'summary' => 'Documento de perfil cargado desde portal '.$this->portal_code,
                'new_values' => $document->to_array(),
            ]);

            return $this->json_response($this->portal_profile_payload(['status' => 'ok', 'message' => 'Documento cargado.']));
        } catch (\Exception $e) {
            \Log::error('Error cargando documento perfil portal '.$this->portal_code.': '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_perfil_upload()
    {
        return $this->post_perfil_upload();
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
     * PORTAL PROFILE PAYLOAD
     *
     * ARMA LA RESPUESTA OPERATIVA DEL PERFIL DEL PORTAL.
     *
     * @access  protected
     * @return  Array
     */
    protected function portal_profile_payload(array $extra = [])
    {
        $party_id = (int) $this->portal_link->party_id;

        return array_merge([
            'party' => $this->party ? $this->party->to_array() : [],
            'addresses' => $this->portal_party_addresses($party_id),
            'contacts' => $this->portal_party_contacts($party_id),
            'documents' => $this->portal_party_documents($party_id),
            'reseller_customers' => $this->portal_code === 'revendedores' ? $this->reseller_customers($party_id) : [],
            'features' => [
                'supplier' => $this->portal_code === 'proveedores',
                'customer' => $this->portal_code === 'clientes',
                'reseller' => $this->portal_code === 'revendedores',
            ],
            'options' => [
                'sat_tax_regimes' => Helper_Core_Sat_Catalog::options('core_sat_tax_regimes'),
            ],
            'labels' => $this->portal_profile_labels(),
        ], $extra);
    }

    protected function portal_profile_labels()
    {
        if ($this->portal_code === 'proveedores') {
            return [
                'profile' => 'Datos de proveedor',
                'addresses' => 'Bodegas y lugares de entrega',
                'contacts' => 'Contactos administrativos',
                'documents' => 'Constancia, opinion de cumplimiento y evidencias',
                'credit' => 'Dias de credito pactados',
            ];
        }

        if ($this->portal_code === 'revendedores') {
            return [
                'profile' => 'Datos del revendedor',
                'addresses' => 'Direcciones operativas',
                'contacts' => 'Contactos del equipo',
                'documents' => 'Documentos comerciales',
                'credit' => 'Condiciones comerciales',
            ];
        }

        return [
            'profile' => 'Datos del cliente',
            'addresses' => 'Direcciones de entrega',
            'contacts' => 'Personas autorizadas para recibir',
            'documents' => 'Documentos del cliente',
            'credit' => 'Credito autorizado',
        ];
    }

    protected function portal_party_addresses($party_id)
    {
        if (!\DBUtil::table_exists('core_party_addresses')) {
            return [];
        }

        return \DB::select()
            ->from('core_party_addresses')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('is_default', 'desc')
            ->order_by('id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function portal_party_contacts($party_id)
    {
        if (!\DBUtil::table_exists('core_party_contacts')) {
            return [];
        }

        return \DB::select()
            ->from('core_party_contacts')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function portal_party_documents($party_id)
    {
        if (!\DBUtil::table_exists('core_documents') || !\DBUtil::table_exists('core_document_links')) {
            return [];
        }

        return \DB::select(
                ['d.id', 'id'], ['d.document_type', 'document_type'], ['d.title', 'title'],
                ['d.original_name', 'original_name'], ['d.file_path', 'file_path'],
                ['d.file_extension', 'file_extension'], ['d.file_size', 'file_size'],
                ['d.created_at', 'created_at'], ['l.relation_type', 'relation_type'], ['l.notes', 'notes']
            )
            ->from(['core_document_links', 'l'])
            ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
            ->where('l.entity_type', '=', 'party')
            ->where('l.entity_id', '=', (int) $party_id)
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->order_by('d.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function reseller_customers($party_id)
    {
        if ($this->portal_code !== 'revendedores' || !\DBUtil::table_exists('core_parties')) {
            return [];
        }

        return \DB::select('id', 'code', 'name', 'legal_name', 'rfc', 'email', 'phone', 'credit_days', 'active', 'created_at')
            ->from('core_parties')
            ->where('party_type', '=', 'customer')
            ->where('notes', 'like', '%Revendedor party_id='.$party_id.'%')
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function profile_address_type($value)
    {
        $value = $this->codeify($value);
        $allowed = ['billing', 'delivery', 'warehouse', 'pickup'];
        return in_array($value, $allowed, true) ? $value : 'delivery';
    }

    protected function profile_document_type($value)
    {
        $value = $this->codeify($value);
        $allowed = ['constancia_fiscal', 'opinion_cumplimiento', 'contrato', 'identificacion', 'evidencia', 'otro'];
        return in_array($value, $allowed, true) ? $value : 'otro';
    }

    /**
     * STORE PORTAL PARTY DOCUMENT
     *
     * GUARDA DOCUMENTO GENERAL DE PERFIL DEL TERCERO.
     *
     * @access  protected
     * @return  Model_Core_Document
     */
    protected function store_portal_party_document()
    {
        $file = \Input::file('file');
        if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Selecciona un archivo valido.');
        }

        $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
        $allowed = ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Tipo de archivo no permitido.');
        }

        if ((int) \Arr::get($file, 'size', 0) > 15728640) {
            throw new \RuntimeException('El archivo no puede superar 15 MB.');
        }

        $relative_dir = 'assets/uploads/documents/portal/'.$this->portal_code.'/'.date('Y').'/'.date('m');
        $absolute_dir = DOCROOT.$relative_dir;
        if (!is_dir($absolute_dir)) {
            mkdir($absolute_dir, 0755, true);
        }

        $base_name = pathinfo((string) \Arr::get($file, 'name', 'documento'), PATHINFO_FILENAME);
        $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->codeify($base_name).'.'.$extension;
        $target = $absolute_dir.DS.$filename;
        if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        $document_type = $this->profile_document_type((string) \Input::post('document_type', 'otro'));
        $path = str_replace('\\', '/', $relative_dir.'/'.$filename);
        $document = Model_Core_Document::forge([
            'document_type' => $document_type,
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

        Model_Core_Document_Link::forge([
            'document_id' => (int) $document->id,
            'entity_type' => 'party',
            'entity_id' => (int) $this->portal_link->party_id,
            'relation_type' => $document_type,
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
