<?php

/**
 * CONTROLADOR ADMIN_COMMUNICATIONS
 *
 * Administra eventos, correos y tablero basico de comunicaciones.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Communications extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE COMUNICACIONES
     *
     * @return  Void
     */
    public function before()
    {
        if ($this->request && $this->is_json_action($this->request->action)) {
            $this->auto_render = false;
            if (\Auth::check()) {
                $this->setup_authenticated_context();
            }
            return;
        }

        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('communications.access[view]');
    }

    protected function is_json_action($action)
    {
        return in_array((string) $action, [
            'data',
            'send_notification',
            'test_email',
            'save_provider',
            'save_account',
            'save_account_assignment',
            'revoke_account_assignment',
            'test_imap_account',
            'sync_imap_account',
            'my_mailbox',
            'my_mailbox_detail',
            'entity_conversations',
            'entity_conversation_detail',
            'compose_message',
            'reply_conversation',
            'conversationlist',
            'conversationdetail',
            'conversation_center',
            'conversation_detail',
            'save_template',
            'save_layout',
            'preview_template',
            'process_queue',
            'save_recipient_rule',
            'toggle_recipient_rule',
            'preview_recipients',
            'test_event',
        ], true);
    }

    protected function setup_authenticated_context()
    {
        $user_id_data = \Auth::get_user_id();
        $this->user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;

        $groups = \Auth::get_groups();
        if (!empty($groups)) {
            $group_data = $groups[0][1];
            $this->user_group = is_object($group_data) ? (int) $group_data->id : (int) $group_data;
        }

        $this->is_super_admin = ($this->user_group === 100);
    }

    protected function deny_json_if_no_access($permission)
    {
        if ($response = $this->communications_json_guard($permission)) {
            return $response;
        }

        return null;
    }

    protected function communications_json_guard($permission = 'communications.access[view]')
    {
        if (!\Auth::check()) {
            return $this->json_response([
                'success' => false,
                'message' => 'Sesión requerida.',
                'data' => [],
                'errors' => ['auth_required'],
            ], 401);
        }

        if ($this->user_id <= 0) {
            $this->setup_authenticated_context();
        }

        if ((new \Service_Core_Auth_PasswordPolicy())->must_change($this->user_id)) {
            return $this->json_response([
                'success' => false,
                'message' => 'Debes cambiar tu contraseña antes de continuar.',
                'data' => [],
                'errors' => ['password_change_required'],
            ], 403);
        }

        if (!$this->is_super_admin && !\Auth::has_access($permission)) {
            return $this->json_response([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta operación.',
                'data' => [],
                'errors' => ['permission_denied'],
            ], 403);
        }

        return null;
    }

    protected function require_explicit_json_csrf_token()
    {
        $key = \Config::get('security.csrf_token_key', 'fuel_csrf_token');
        $payload = (array) \Input::json();
        $token = (string) \Input::headers('X-CSRF-Token', '');

        if ($token === '') {
            $token = (string) \Arr::get($payload, $key, '');
        }

        if ($token === '') {
            $token = (string) \Input::post($key, '');
        }

        $expected = (string) \Security::fetch_token();
        $valid = function_exists('hash_equals')
            ? hash_equals($expected, $token)
            : $expected === $token;

        if ($token === '' || !$valid) {
            return $this->json_response([
                'success' => false,
                'message' => 'Token de seguridad inválido o expirado.',
                'data' => [],
                'errors' => ['csrf_invalid'],
            ], 403);
        }

        return null;
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE COMUNICACIONES
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Comunicaciones';
        $this->template->content = View::forge('admin/communications/index');
    }

    /**
     * DATA
     *
     * ENTREGA EVENTOS Y ESTADISTICAS EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        if ($response = $this->communications_json_guard()) {
            return $response;
        }

        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response([
                'events' => $this->get_events(),
                'users' => $this->get_users(),
                'groups' => $this->get_groups(),
                'roles' => $this->get_roles(),
                'departments' => $this->get_departments(),
                'recipient_rules' => $this->get_recipient_rules(),
                'providers' => $this->get_providers(),
                'accounts' => $this->get_accounts(),
                'account_assignments' => $this->get_account_assignments(),
                'my_accounts' => $this->get_my_accounts(),
                'conversations' => $this->get_conversations(),
                'imap_defaults' => (new Service_Core_Communications_ImapManager())->get_default_folders(),
                'imap_capabilities' => (new Service_Core_Communications_ImapManager())->capabilities(),
                'templates' => $this->get_templates(),
                'layouts' => $this->get_layouts(),
                'variables' => $this->get_variable_helper(),
                'recent_attempts' => $this->get_recent_attempts(),
                'queue' => $this->get_recent_queue(),
                'queue_summary' => $this->get_queue_summary(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando comunicaciones: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar comunicaciones.'], 500);
        }
    }

    /**
     * SEND NOTIFICATION
     *
     * ENVIA UNA NOTIFICACION INTERNA MANUAL A USUARIOS SELECCIONADOS
     *
     * @access  public
     * @return  Response
     */
    public function post_send_notification()
    {
        # VALIDAR PERMISO PARA CREAR
        if ($response = $this->deny_json_if_no_access('communications.access[create]')) {
            return $response;
        }

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();
        $title = trim((string) \Arr::get($val, 'title', ''));
        $message = trim((string) \Arr::get($val, 'message', ''));
        $url = trim((string) \Arr::get($val, 'url', 'admin'));
        $event_code = trim((string) \Arr::get($val, 'event_code', 'manual.admin.notification'));
        $user_ids = (array) \Arr::get($val, 'user_ids', []);
        $department_ids = (array) \Arr::get($val, 'department_ids', []);

        # SE COMPLEMENTA CON DATOS DEL EVENTO SI FUE SELECCIONADO
        $event = Model_Core_Notification_Event::active_by_code($event_code);
        if ($event) {
            if ($title === '') {
                $title = (string) $event->name;
            }
            if ($url === '') {
                $url = (string) $event->url_template;
            }
        }

        # VALIDACIONES MINIMAS
        if ($title === '' || $message === '') {
            return $this->json_response(['error' => 'Titulo y mensaje son obligatorios.'], 422);
        }

        $recipients = $this->resolve_recipients($user_ids, $department_ids);
        if (empty($recipients)) {
            return $this->json_response(['error' => 'Selecciona al menos un destinatario.'], 422);
        }

        # SE CREA LA NOTIFICACION
        $notification = Helper_Core_Notification::create([
            'event_code' => $event_code,
            'notification_type' => 'manual',
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'icon' => $event ? (string) $event->icon : 'bi bi-megaphone',
            'priority' => (int) \Arr::get($val, 'priority', $event ? (int) $event->priority : 1),
            'created_by' => $this->user_id,
        ], $recipients);

        if (!$notification) {
            return $this->json_response(['error' => 'No se pudo crear la notificacion.'], 400);
        }

        return $this->json_response([
            'status' => 'ok',
            'stats' => $this->get_stats(),
        ]);
    }

    /**
     * TEST EMAIL
     *
     * CREA Y PROCESA UNA PRUEBA DE CORREO CON UN PROVEEDOR SELECCIONADO.
     *
     * @access  public
     * @return  Response
     */
    public function post_test_email()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[test]')) {
            return $response;
        }

        $val = (array) \Input::json();
        $provider = trim((string) \Arr::get($val, 'provider_code', 'disabled_default'));
        $to = trim((string) \Arr::get($val, 'to_email', ''));
        $subject = trim((string) \Arr::get($val, 'subject', 'Prueba del Centro de Comunicaciones'));
        $message = trim((string) \Arr::get($val, 'message', 'Mensaje de prueba.'));

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->json_response([
                'success' => false,
                'message' => 'Captura un correo destinatario valido.',
                'errors' => ['Correo destinatario invalido.'],
            ], 422);
        }

        try {
            $manager = new Service_Core_Email_Manager();
            $result = $manager->test_send($provider, $to, $subject, $message);

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', 'Prueba ejecutada.'),
                'data' => [
                    'result' => $result,
                    'stats' => $this->get_stats(),
                    'providers' => $this->get_providers(),
                    'queue_summary' => $this->get_queue_summary(),
                    'recent_attempts' => $this->get_recent_attempts(),
                    'queue' => $this->get_recent_queue(),
                ],
                'errors' => !empty($result['errors']) ? $result['errors'] : [],
            ], !empty($result['success']) ? 200 : 400);
        } catch (\Exception $e) {
            \Log::error('Error ejecutando prueba de correo: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo ejecutar la prueba de correo.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * SAVE PROVIDER
     *
     * ACTUALIZA CONFIGURACION SEGURA DE UN PROVEEDOR SIN DEVOLVER SECRETOS.
     *
     * @access  public
     * @return  Response
     */
    public function post_save_provider()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $this->assert_schema_ready();
            $val = (array) \Input::json();
            $provider = Model_Core_Communication_Provider::find((int) \Arr::get($val, 'id', 0));

            if (!$provider) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Proveedor no encontrado.',
                    'errors' => ['No existe el proveedor seleccionado.'],
                ], 404);
            }

            $provider->name = trim((string) \Arr::get($val, 'name', $provider->name));
            $provider->type = $this->allowed_value(\Arr::get($val, 'type', $provider->type), ['smtp', 'php_mail', 'api', 'disabled'], 'disabled');
            $provider->transport = $this->allowed_value(\Arr::get($val, 'transport', $provider->transport), ['smtp', 'php_mail', 'api', 'disabled'], 'disabled');
            $provider->host = trim((string) \Arr::get($val, 'host', ''));
            $provider->port = max(0, (int) \Arr::get($val, 'port', 0));
            $provider->username = trim((string) \Arr::get($val, 'username', ''));
            $provider->api_base_url = trim((string) \Arr::get($val, 'api_base_url', ''));
            $provider->encryption = $this->allowed_value(\Arr::get($val, 'encryption', ''), ['', 'ssl', 'tls'], '');
            $provider->timeout_seconds = max(1, min(120, (int) \Arr::get($val, 'timeout_seconds', 20)));
            $provider->verify_tls = (int) \Arr::get($val, 'verify_tls', 1) === 1 ? 1 : 0;
            $provider->from_email = trim((string) \Arr::get($val, 'from_email', ''));
            $provider->from_name = trim((string) \Arr::get($val, 'from_name', ''));
            $provider->reply_to_email = trim((string) \Arr::get($val, 'reply_to_email', ''));
            $provider->daily_limit = max(0, (int) \Arr::get($val, 'daily_limit', 0));
            $provider->hourly_limit = max(0, (int) \Arr::get($val, 'hourly_limit', 0));
            $provider->simulation_mode = (int) \Arr::get($val, 'simulation_mode', 1) === 1 ? 1 : 0;
            $provider->active = (int) \Arr::get($val, 'active', 1) === 1 ? 1 : 0;

            $new_password = (string) \Arr::get($val, 'new_password', '');
            if (trim($new_password) !== '') {
                $provider->password_encrypted = $this->encode_secret($new_password);
            }

            $new_api_key = (string) \Arr::get($val, 'new_api_key', '');
            if (trim($new_api_key) !== '') {
                $provider->api_key_encrypted = $this->encode_secret($new_api_key);
            }

            $errors = $this->validate_provider($provider);
            if (!empty($errors)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Revisa la configuracion del proveedor.',
                    'errors' => $errors,
                ], 422);
            }

            $provider->save();
            \Log::info('Proveedor de comunicaciones actualizado: '.$provider->code);

            return $this->json_response([
                'success' => true,
                'message' => 'Proveedor guardado correctamente.',
                'data' => [
                    'providers' => $this->get_providers(),
                    'stats' => $this->get_stats(),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando proveedor de comunicaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar el proveedor.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function post_save_account()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $this->assert_accounts_ready();
            $val = (array) \Input::json();
            $id = (int) \Arr::get($val, 'id', 0);
            $account = $id > 0 ? Model_Core_Communication_Account::find($id) : null;

            if (!$account) {
                $account = new Model_Core_Communication_Account();
            }

            $account->code = trim((string) \Arr::get($val, 'code', $account->code));
            $account->name = trim((string) \Arr::get($val, 'name', $account->name));
            $account->email_address = trim((string) \Arr::get($val, 'email_address', $account->email_address));
            $account->account_type = $this->allowed_value(\Arr::get($val, 'account_type', $account->account_type), ['support', 'sales', 'purchases', 'billing', 'system', 'other'], 'support');
            if (\DBUtil::field_exists('core_communication_accounts', ['owner_user_id'])) {
                $account->owner_user_id = max(0, (int) \Arr::get($val, 'owner_user_id', $account->owner_user_id));
            }
            if (\DBUtil::field_exists('core_communication_accounts', ['owner_group_id'])) {
                $account->owner_group_id = max(0, (int) \Arr::get($val, 'owner_group_id', $account->owner_group_id));
            }
            if (\DBUtil::field_exists('core_communication_accounts', ['mailbox_scope'])) {
                $account->mailbox_scope = $this->allowed_value(\Arr::get($val, 'mailbox_scope', $account->mailbox_scope), ['system', 'personal', 'shared', 'department'], 'system');
            }
            $account->provider_code = trim((string) \Arr::get($val, 'provider_code', $account->provider_code));
            $account->smtp_provider_code = trim((string) \Arr::get($val, 'smtp_provider_code', $account->smtp_provider_code));
            $account->imap_provider_code = trim((string) \Arr::get($val, 'imap_provider_code', $account->imap_provider_code));
            $account->imap_host = trim((string) \Arr::get($val, 'imap_host', $account->imap_host));
            $account->imap_port = max(0, min(65535, (int) \Arr::get($val, 'imap_port', $account->imap_port ?: 993)));
            $account->imap_encryption = $this->allowed_value(\Arr::get($val, 'imap_encryption', $account->imap_encryption), ['', 'ssl', 'tls', 'none'], 'ssl');
            $account->imap_username = trim((string) \Arr::get($val, 'imap_username', $account->imap_username));
            $account->imap_folder_inbox = trim((string) \Arr::get($val, 'imap_folder_inbox', $account->imap_folder_inbox ?: 'INBOX'));
            $account->imap_folder_sent = trim((string) \Arr::get($val, 'imap_folder_sent', $account->imap_folder_sent ?: 'Sent'));
            $account->imap_folder_drafts = trim((string) \Arr::get($val, 'imap_folder_drafts', $account->imap_folder_drafts ?: 'Drafts'));
            $account->imap_folder_trash = trim((string) \Arr::get($val, 'imap_folder_trash', $account->imap_folder_trash ?: 'Trash'));
            $account->sync_inbox = (int) \Arr::get($val, 'sync_inbox', 1) === 1 ? 1 : 0;
            $account->sync_sent = (int) \Arr::get($val, 'sync_sent', 0) === 1 ? 1 : 0;
            $account->sync_drafts = (int) \Arr::get($val, 'sync_drafts', 0) === 1 ? 1 : 0;
            $account->sync_trash = (int) \Arr::get($val, 'sync_trash', 0) === 1 ? 1 : 0;
            $account->append_sent = (int) \Arr::get($val, 'append_sent', 0) === 1 ? 1 : 0;
            $account->sync_enabled = (int) \Arr::get($val, 'sync_enabled', 0) === 1 ? 1 : 0;
            $account->active = (int) \Arr::get($val, 'active', 0) === 1 ? 1 : 0;

            $new_password = (string) \Arr::get($val, 'new_imap_password', '');
            if (trim($new_password) !== '') {
                $account->imap_password_encrypted = $this->encode_secret($new_password);
            }

            if ((int) $account->id === 0 && trim((string) $account->imap_password_encrypted) === '') {
                $account->imap_password_encrypted = '';
            }

            $manager = new Service_Core_Communications_ImapManager();
            try {
                $this->assert_unique_account_code($account);
            } catch (\RuntimeException $e) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Revisa la cuenta de correo.',
                    'errors' => [$e->getMessage()],
                ], 422);
            }

            $errors = $manager->validate_account_config($this->account_to_array($account))['errors'];
            if (!empty($errors)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Revisa la cuenta de correo.',
                    'errors' => $errors,
                ], 422);
            }

            $account->save();
            \Log::info('Cuenta de correo de comunicaciones guardada: '.$account->code);

            return $this->json_response([
                'success' => true,
                'message' => 'Cuenta de correo guardada correctamente.',
                'data' => [
                    'accounts' => $this->get_accounts(),
                    'account_assignments' => $this->get_account_assignments(),
                    'my_accounts' => $this->get_my_accounts(),
                    'stats' => $this->get_stats(),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando cuenta de correo: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar la cuenta de correo.',
                'errors' => ['Error interno al guardar la cuenta.'],
            ], 500);
        }
    }

    public function post_test_imap_account()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[test]')) {
            return $response;
        }

        try {
            $this->assert_accounts_ready();
            $val = (array) \Input::json();
            $account = Model_Core_Communication_Account::find((int) \Arr::get($val, 'id', 0));

            if (!$account) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Cuenta de correo no encontrada.',
                    'errors' => ['No existe la cuenta seleccionada.'],
                ], 404);
            }

            $manager = new Service_Core_Communications_ImapManager();
            $result = $manager->test_connection($this->account_to_array($account));

            $account->last_sync_at = time();
            $account->last_sync_status = !empty($result['success']) ? 'ok' : 'test_failed';
            $account->last_sync_error = !empty($result['errors']) ? substr(implode(', ', (array) $result['errors']), 0, 255) : '';
            $account->save();

            \Log::info('Prueba IMAP ejecutada para cuenta '.$account->code.' resultado='.$account->last_sync_status);

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', 'Prueba IMAP ejecutada.'),
                'data' => [
                    'result' => $result,
                    'accounts' => $this->get_accounts(),
                ],
                'errors' => (array) \Arr::get($result, 'errors', []),
            ], !empty($result['success']) ? 200 : 400);
        } catch (\Exception $e) {
            \Log::error('Error probando cuenta IMAP: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo probar la cuenta IMAP.',
                'errors' => ['Error interno al probar la cuenta.'],
            ], 500);
        }
    }

    public function post_save_account_assignment()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $this->assert_account_assignments_ready();
            $val = (array) \Input::json();
            $account_id = (int) \Arr::get($val, 'account_id', 0);

            $service = new Service_Core_Communications_MailboxAccess();
            $result = $service->assign_account($account_id, $val);

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', 'Asignacion procesada.'),
                'data' => [
                    'account_assignments' => $this->get_account_assignments(),
                    'my_accounts' => $this->get_my_accounts(),
                    'accounts' => $this->get_accounts(),
                ],
                'errors' => (array) \Arr::get($result, 'errors', []),
            ], !empty($result['success']) ? 200 : 422);
        } catch (\Exception $e) {
            \Log::error('Error guardando asignacion de buzon: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar la asignacion del buzon.',
                'errors' => ['Error interno al guardar la asignacion.'],
            ], 500);
        }
    }

    public function post_revoke_account_assignment()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $this->assert_account_assignments_ready();
            $val = (array) \Input::json();
            $service = new Service_Core_Communications_MailboxAccess();
            $result = $service->revoke_assignment((int) \Arr::get($val, 'id', 0));

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', 'Asignacion procesada.'),
                'data' => [
                    'account_assignments' => $this->get_account_assignments(),
                    'my_accounts' => $this->get_my_accounts(),
                    'accounts' => $this->get_accounts(),
                ],
                'errors' => (array) \Arr::get($result, 'errors', []),
            ], !empty($result['success']) ? 200 : 422);
        } catch (\Exception $e) {
            \Log::error('Error revocando asignacion de buzon: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo desactivar la asignacion del buzon.',
                'errors' => ['Error interno al desactivar la asignacion.'],
            ], 500);
        }
    }

    public function post_sync_imap_account()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[process]')) {
            return $response;
        }

        try {
            $this->assert_accounts_ready();
            $val = (array) \Input::json();
            $account = Model_Core_Communication_Account::find((int) \Arr::get($val, 'id', 0));

            if (!$account) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Cuenta de correo no encontrada.',
                    'errors' => ['No existe la cuenta seleccionada.'],
                ], 404);
            }

            $manager = new Service_Core_Communications_ImapManager();
            $result = $manager->sync_account((int) $account->id, [
                'limit' => max(1, min(100, (int) \Arr::get($val, 'limit', 20))),
            ]);

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', 'Sincronizacion IMAP procesada.'),
                'data' => [
                    'result' => $result,
                    'accounts' => $this->get_accounts(),
                    'conversations' => $this->get_conversations(),
                    'stats' => $this->get_stats(),
                ],
                'errors' => (array) \Arr::get($result, 'errors', []),
            ], !empty($result['success']) ? 200 : 400);
        } catch (\Exception $e) {
            \Log::error('Error sincronizando cuenta IMAP: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo sincronizar la cuenta IMAP.',
                'errors' => ['Error interno al sincronizar IMAP.'],
            ], 500);
        }
    }

    public function action_my_mailbox()
    {
        if ($response = $this->communications_json_guard('communications.access[view]')) {
            return $response;
        }

        try {
            $this->assert_conversation_tables_ready();
            $this->assert_accounts_ready();

            $service = new Service_Core_Communications_MailboxAccess();
            $accounts = $service->accounts_for_user((int) $this->user_id);
            $account_id = (int) \Input::get('account_id', 0);

            if ($account_id > 0 && !$service->can_view_account((int) $this->user_id, $account_id)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta cuenta.',
                    'data' => [],
                    'errors' => ['mailbox_permission_denied'],
                ], 403);
            }

            if (!$this->is_super_admin && empty($accounts)) {
                return $this->json_response([
                    'success' => true,
                    'message' => 'No tienes cuentas de correo asignadas.',
                    'data' => [
                        'accounts' => [],
                        'items' => [],
                        'pagination' => ['page' => 1, 'per_page' => 15, 'total' => 0, 'pages' => 1],
                        'filters' => [],
                        'channels' => [],
                    ],
                    'errors' => [],
                ]);
            }

            $filters = [
                'folder' => trim((string) \Input::get('folder', 'inbox')),
                'q' => trim((string) \Input::get('q', '')),
                'unread' => (int) \Input::get('unread', 0),
                'assigned' => (int) \Input::get('assigned', 0),
                'channel' => trim((string) \Input::get('channel', '')),
                'account_id' => $account_id,
                'date_from' => trim((string) \Input::get('date_from', '')),
                'date_to' => trim((string) \Input::get('date_to', '')),
                'page' => max(1, (int) \Input::get('page', 1)),
                'per_page' => max(5, min(50, (int) \Input::get('per_page', 15))),
            ];

            $result = $this->get_conversation_center($filters);
            $result['accounts'] = $accounts;

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => $result,
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando bandeja asignada: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar la bandeja asignada.',
                'data' => [],
                'errors' => ['my_mailbox_error'],
            ], 500);
        }
    }

    public function action_my_mailbox_detail($id = null)
    {
        if ($response = $this->communications_json_guard('communications.access[view]')) {
            return $response;
        }

        try {
            $this->assert_conversation_tables_ready();
            $conversation_id = (int) $id;
            if ($conversation_id <= 0) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Conversacion invalida.',
                    'data' => [],
                    'errors' => ['invalid_conversation'],
                ], 422);
            }

            if (!$this->conversation_exists($conversation_id)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Conversacion no encontrada.',
                    'data' => [],
                    'errors' => ['conversation_not_found'],
                ], 404);
            }

            if (!$this->can_view_conversation($conversation_id)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta conversacion.',
                    'data' => [],
                    'errors' => ['conversation_permission_denied'],
                ], 403);
            }

            $detail = $this->get_conversation_detail($conversation_id);

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => $detail,
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando detalle de bandeja asignada: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar la conversacion.',
                'data' => [],
                'errors' => ['my_mailbox_detail_error'],
            ], 500);
        }
    }

    public function action_entity_conversations()
    {
        if ($response = $this->communications_json_guard('communications.access[view]')) {
            return $response;
        }

        try {
            $service = new Service_Core_Communications_EmbeddedPanel();
            $result = $service->conversations((int) $this->user_id, [
                'entity_type' => trim((string) \Input::get('entity_type', '')),
                'entity_id' => (int) \Input::get('entity_id', 0),
                'party_id' => (int) \Input::get('party_id', 0),
                'limit' => (int) \Input::get('limit', 10),
            ]);

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', ''),
                'data' => (array) \Arr::get($result, 'data', ['conversations' => [], 'total' => 0]),
                'errors' => (array) \Arr::get($result, 'errors', []),
            ], (int) \Arr::get($result, 'status', 200));
        } catch (\Exception $e) {
            \Log::error('Error cargando comunicaciones embebidas: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudieron cargar las comunicaciones relacionadas.',
                'data' => ['conversations' => [], 'total' => 0],
                'errors' => ['embedded_conversations_error'],
            ], 500);
        }
    }

    public function action_entity_conversation_detail($id = null)
    {
        if ($response = $this->communications_json_guard('communications.access[view]')) {
            return $response;
        }

        try {
            $conversation_id = (int) $id;
            if ($conversation_id <= 0) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Conversacion invalida.',
                    'data' => [],
                    'errors' => ['invalid_conversation'],
                ], 422);
            }

            $service = new Service_Core_Communications_EmbeddedPanel();
            if (!$service->can_view_conversation((int) $this->user_id, $conversation_id)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta conversacion.',
                    'data' => [],
                    'errors' => ['conversation_permission_denied'],
                ], 403);
            }

            $detail = $service->detail((int) $this->user_id, $conversation_id);
            if (empty($detail)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Conversacion no encontrada.',
                    'data' => [],
                    'errors' => ['conversation_not_found'],
                ], 404);
            }

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => $detail,
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando detalle embebido de comunicaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar la conversacion.',
                'data' => [],
                'errors' => ['embedded_conversation_detail_error'],
            ], 500);
        }
    }

    public function post_compose_message()
    {
        if ($response = $this->communications_json_guard('communications.access[view]')) {
            return $response;
        }
        if ($response = $this->require_explicit_json_csrf_token()) {
            return $response;
        }

        try {
            $service = new Service_Core_Communications_OutgoingComposer();
            $result = $service->compose((int) $this->user_id, (array) \Input::json());

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', ''),
                'data' => (array) \Arr::get($result, 'data', []),
                'errors' => (array) \Arr::get($result, 'errors', []),
            ], (int) \Arr::get($result, 'status', !empty($result['success']) ? 200 : 400));
        } catch (\Exception $e) {
            \Log::error('Error componiendo correo: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo encolar el correo.',
                'data' => [],
                'errors' => ['compose_message_error'],
            ], 500);
        }
    }

    public function post_reply_conversation()
    {
        if ($response = $this->communications_json_guard('communications.access[view]')) {
            return $response;
        }
        if ($response = $this->require_explicit_json_csrf_token()) {
            return $response;
        }

        try {
            $service = new Service_Core_Communications_OutgoingComposer();
            $result = $service->reply((int) $this->user_id, (array) \Input::json());

            return $this->json_response([
                'success' => !empty($result['success']),
                'message' => (string) \Arr::get($result, 'message', ''),
                'data' => (array) \Arr::get($result, 'data', []),
                'errors' => (array) \Arr::get($result, 'errors', []),
            ], (int) \Arr::get($result, 'status', !empty($result['success']) ? 200 : 400));
        } catch (\Exception $e) {
            \Log::error('Error respondiendo conversacion: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo encolar la respuesta.',
                'data' => [],
                'errors' => ['reply_conversation_error'],
            ], 500);
        }
    }

    public function action_conversation_center()
    {
        return $this->action_conversations();
    }

    public function action_conversations()
    {
        return $this->action_conversationlist();
    }

    public function action_conversationlist()
    {
        if ($response = $this->communications_json_guard()) {
            return $response;
        }

        try {
            $this->assert_conversation_tables_ready();
            $filters = [
                'folder' => trim((string) \Input::get('folder', 'inbox')),
                'q' => trim((string) \Input::get('q', '')),
                'unread' => (int) \Input::get('unread', 0),
                'assigned' => (int) \Input::get('assigned', 0),
                'channel' => trim((string) \Input::get('channel', '')),
                'account_id' => (int) \Input::get('account_id', 0),
                'date_from' => trim((string) \Input::get('date_from', '')),
                'date_to' => trim((string) \Input::get('date_to', '')),
                'page' => max(1, (int) \Input::get('page', 1)),
                'per_page' => max(5, min(50, (int) \Input::get('per_page', 15))),
            ];

            $result = $this->get_conversation_center($filters);

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => $result,
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando centro de conversaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar el centro de conversaciones.',
                'data' => [],
                'errors' => ['conversation_center_error'],
            ], 500);
        }
    }

    public function action_conversation_detail($id = null)
    {
        return $this->action_conversationdetail($id);
    }

    public function action_conversationdetail($id = null)
    {
        if ($response = $this->communications_json_guard()) {
            return $response;
        }

        try {
            $this->assert_conversation_tables_ready();
            $conversation_id = (int) $id;
            if ($conversation_id <= 0) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Conversacion invalida.',
                    'data' => [],
                    'errors' => ['invalid_conversation'],
                ], 422);
            }

            $detail = $this->get_conversation_detail($conversation_id);
            if (empty($detail)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Conversacion no encontrada.',
                    'data' => [],
                    'errors' => ['conversation_not_found'],
                ], 404);
            }

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => $detail,
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando detalle de conversacion: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cargar la conversacion.',
                'data' => [],
                'errors' => ['conversation_detail_error'],
            ], 500);
        }
    }

    /**
     * SAVE TEMPLATE
     *
     * GUARDA UNA PLANTILLA EDITABLE SIN PERMITIR CODIGO EJECUTABLE.
     *
     * @access  public
     * @return  Response
     */
    public function post_save_template()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $this->assert_schema_ready();
            $this->assert_template_editor_ready();

            $val = (array) \Input::json();
            $template = Model_Core_Email_Template::find((int) \Arr::get($val, 'id', 0));

            if (!$template) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Plantilla no encontrada.',
                    'errors' => ['No existe la plantilla seleccionada.'],
                ], 404);
            }

            $template->name = trim((string) \Arr::get($val, 'name', $template->name));
            $template->subject = trim((string) \Arr::get($val, 'subject', $template->subject));
            $template->content = (string) \Arr::get($val, 'content', $template->content);
            $template->body_text = (string) \Arr::get($val, 'body_text', $template->body_text);
            $template->active = (int) \Arr::get($val, 'active', 1) === 1 ? 1 : 0;

            $errors = $this->validate_template($template);
            if (!empty($errors)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Revisa la plantilla.',
                    'errors' => $errors,
                ], 422);
            }

            $template->save();
            \Log::info('Plantilla de comunicaciones actualizada: '.$template->code);

            return $this->json_response([
                'success' => true,
                'message' => 'Plantilla guardada correctamente.',
                'data' => [
                    'templates' => $this->get_templates(),
                    'variables' => $this->get_variable_helper(),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando plantilla de comunicaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar la plantilla.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * SAVE LAYOUT
     *
     * GUARDA LAYOUTS DE CORREO SIN PERMITIR CODIGO EJECUTABLE.
     *
     * @access  public
     * @return  Response
     */
    public function post_save_layout()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $this->assert_schema_ready();
            $val = (array) \Input::json();
            $layout = Model_Core_Email_Layout::find((int) \Arr::get($val, 'id', 0));

            if (!$layout) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Layout no encontrado.',
                    'errors' => ['No existe el layout seleccionado.'],
                ], 404);
            }

            $layout->name = trim((string) \Arr::get($val, 'name', $layout->name));
            $layout->description = trim((string) \Arr::get($val, 'description', $layout->description));
            $layout->html_layout = (string) \Arr::get($val, 'html_layout', $layout->html_layout);
            $layout->text_layout = (string) \Arr::get($val, 'text_layout', $layout->text_layout);
            $layout->active = (int) \Arr::get($val, 'active', 1) === 1 ? 1 : 0;

            $errors = $this->validate_layout($layout);
            if (!empty($errors)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Revisa el layout.',
                    'errors' => $errors,
                ], 422);
            }

            $layout->save();
            \Log::info('Layout de comunicaciones actualizado: '.$layout->code);

            return $this->json_response([
                'success' => true,
                'message' => 'Layout guardado correctamente.',
                'data' => [
                    'layouts' => $this->get_layouts(),
                    'variables' => $this->get_variable_helper(),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando layout de comunicaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar el layout.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * PREVIEW TEMPLATE
     *
     * RENDERIZA PLANTILLA Y LAYOUT SIN ENVIAR CORREO.
     *
     * @access  public
     * @return  Response
     */
    public function post_preview_template()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[test]')) {
            return $response;
        }

        try {
            $this->assert_schema_ready();
            $this->assert_template_editor_ready();

            $val = (array) \Input::json();
            $template = Model_Core_Email_Template::find((int) \Arr::get($val, 'template_id', 0));
            $layout_code = trim((string) \Arr::get($val, 'layout_code', ''));

            if (!$template) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Selecciona una plantilla valida.',
                    'errors' => ['Plantilla no encontrada.'],
                ], 404);
            }

            $unsafe_errors = [];
            $unsafe_errors = array_merge($unsafe_errors, $this->validate_safe_content((string) $template->subject, 'asunto'));
            $unsafe_errors = array_merge($unsafe_errors, $this->validate_safe_content((string) $template->content, 'cuerpo HTML'));
            $unsafe_errors = array_merge($unsafe_errors, $this->validate_safe_content((string) $template->body_text, 'cuerpo texto'));

            if ($layout_code !== '') {
                $layout = Model_Core_Email_Layout::query()
                    ->where('code', $layout_code)
                    ->get_one();
                if ($layout) {
                    $unsafe_errors = array_merge($unsafe_errors, $this->validate_safe_content((string) $layout->html_layout, 'layout HTML'));
                    $unsafe_errors = array_merge($unsafe_errors, $this->validate_safe_content((string) $layout->text_layout, 'layout texto'));
                }
            }

            if (!empty($unsafe_errors)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'La vista previa fue bloqueada por seguridad.',
                    'errors' => $unsafe_errors,
                ], 422);
            }

            $variables = $this->preview_variables(\Arr::get($val, 'variables', []));
            $warnings = [];
            $renderer = new Service_Core_Email_TemplateRenderer();
            $layout_renderer = new Service_Core_Email_LayoutRenderer();

            $subject = $renderer->render((string) $template->subject, $variables, $warnings);
            $body_html = $renderer->render((string) $template->content, $variables, $warnings);
            $body_text = trim((string) $template->body_text) !== ''
                ? $renderer->render((string) $template->body_text, $variables, $warnings)
                : trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", (string) $body_html)));

            $render_variables = array_merge($variables, [
                'subject' => $subject,
            ]);

            $html = $layout_renderer->render_html($layout_code, $body_html, $render_variables, $warnings);
            $text = $layout_renderer->render_text($layout_code, $body_text, $render_variables, $warnings);

            return $this->json_response([
                'success' => true,
                'message' => 'Vista previa generada. No se envio ningun correo.',
                'data' => [
                    'subject' => $subject,
                    'html' => $html,
                    'text' => $text,
                    'warnings' => array_values(array_unique($warnings)),
                    'variables_used' => $this->template_variables($template),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generando preview de comunicaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo generar la vista previa.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * PROCESS QUEUE
     *
     * PROCESA MANUALMENTE LA COLA DE CORREO.
     *
     * @access  public
     * @return  Response
     */
    public function post_process_queue()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[process]')) {
            return $response;
        }

        try {
            $val = (array) \Input::json();
            $limit = max(1, min(100, (int) \Arr::get($val, 'limit', 10)));
            $processor = new Service_Core_Email_QueueProcessor('admin-'.$this->user_id);
            $summary = $processor->process($limit);

            return $this->json_response([
                'success' => true,
                'message' => 'Cola procesada.',
                'data' => [
                    'summary' => $summary,
                    'stats' => $this->get_stats(),
                    'queue_summary' => $this->get_queue_summary(),
                    'queue' => $this->get_recent_queue(),
                    'recent_attempts' => $this->get_recent_attempts(),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error procesando cola de comunicaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo procesar la cola.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * SAVE RECIPIENT RULE
     *
     * CREA O ACTUALIZA UNA REGLA DE DESTINATARIOS POR EVENTO/CANAL.
     *
     * @access  public
     * @return  Response
     */
    public function post_save_recipient_rule()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $this->assert_schema_ready();
            $val = (array) \Input::json();
            $id = (int) \Arr::get($val, 'id', 0);
            $rule = $id > 0 ? Model_Core_Communication_EventRecipient::find($id) : Model_Core_Communication_EventRecipient::forge();

            if (!$rule) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Regla no encontrada.',
                    'errors' => ['No existe la regla seleccionada.'],
                ], 404);
            }

            $rule->event_code = trim((string) \Arr::get($val, 'event_code', ''));
            $rule->channel_code = $this->allowed_value(\Arr::get($val, 'channel_code', 'internal'), ['internal', 'email'], 'internal');
            $rule->recipient_type = $this->allowed_value(\Arr::get($val, 'recipient_type', 'user'), ['user', 'group', 'role', 'email'], 'user');
            $rule->recipient_value = trim((string) \Arr::get($val, 'recipient_value', ''));
            $rule->mode = $this->allowed_value(\Arr::get($val, 'mode', 'include'), ['include', 'exclude'], 'include');
            $rule->active = (int) \Arr::get($val, 'active', 1) === 1 ? 1 : 0;

            $errors = $this->validate_recipient_rule($rule);
            if (!empty($errors)) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Revisa la regla de destinatarios.',
                    'errors' => $errors,
                ], 422);
            }

            $rule->save();
            \Log::info('Regla de destinatarios guardada: '.$rule->event_code.' '.$rule->channel_code.' '.$rule->recipient_type);

            return $this->json_response([
                'success' => true,
                'message' => 'Regla guardada correctamente.',
                'data' => [
                    'recipient_rules' => $this->get_recipient_rules(),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando regla de destinatarios: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar la regla.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function post_toggle_recipient_rule()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[edit]')) {
            return $response;
        }

        try {
            $val = (array) \Input::json();
            $rule = Model_Core_Communication_EventRecipient::find((int) \Arr::get($val, 'id', 0));
            if (!$rule) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Regla no encontrada.',
                    'errors' => ['No existe la regla seleccionada.'],
                ], 404);
            }

            $rule->active = (int) \Arr::get($val, 'active', 0) === 1 ? 1 : 0;
            $rule->save();

            return $this->json_response([
                'success' => true,
                'message' => 'Regla actualizada.',
                'data' => [
                    'recipient_rules' => $this->get_recipient_rules(),
                ],
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cambiando regla de destinatarios: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo actualizar la regla.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function post_preview_recipients()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[test]')) {
            return $response;
        }

        try {
            $val = (array) \Input::json();
            $event_code = trim((string) \Arr::get($val, 'event_code', ''));
            $channel_code = $this->allowed_value(\Arr::get($val, 'channel_code', 'internal'), ['internal', 'email'], 'internal');
            $preview = $this->recipient_preview($event_code, $channel_code);

            return $this->json_response([
                'success' => true,
                'message' => 'Destinatarios resueltos.',
                'data' => $preview,
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error previsualizando destinatarios: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudieron resolver los destinatarios.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function post_test_event()
    {
        if ($response = $this->deny_json_if_no_access('communications.access[test]')) {
            return $response;
        }

        try {
            $val = (array) \Input::json();
            $event_code = trim((string) \Arr::get($val, 'event_code', ''));
            $channel_code = $this->allowed_value(\Arr::get($val, 'channel_code', 'internal'), ['internal', 'email'], 'internal');
            $provider_code = trim((string) \Arr::get($val, 'provider_code', 'disabled_default'));
            $preview = $this->recipient_preview($event_code, $channel_code);

            if ($event_code === '') {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Selecciona un evento.',
                    'errors' => ['Evento obligatorio.'],
                ], 422);
            }

            if ($channel_code === 'internal') {
                if (empty($preview['users'])) {
                    return $this->json_response([
                        'success' => false,
                        'message' => 'No hay usuarios internos resueltos para este evento.',
                        'errors' => ['Agrega reglas de usuario o grupo.'],
                    ], 422);
                }

                Helper_Core_Notification::create([
                    'event_code' => $event_code,
                    'notification_type' => 'communications_test',
                    'title' => 'Prueba de comunicacion: '.$event_code,
                    'message' => 'Mensaje de prueba generado desde Centro de Comunicaciones.',
                    'url' => 'admin/communications',
                    'icon' => 'bi bi-envelope-check',
                    'priority' => 1,
                    'created_by' => $this->user_id,
                ], $preview['users']);

                return $this->json_response([
                    'success' => true,
                    'message' => 'Notificacion interna de prueba creada.',
                    'data' => [
                        'preview' => $preview,
                        'stats' => $this->get_stats(),
                    ],
                    'errors' => [],
                ]);
            }

            if (empty($preview['emails'])) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'No hay correos resueltos para este evento.',
                    'errors' => ['Agrega reglas de email, usuario, grupo o rol con correos validos.'],
                ], 422);
            }

            $manager = new Service_Core_Email_Manager();
            $queued = 0;
            $errors = [];
            foreach ($preview['emails'] as $email) {
                $result = $manager->queue([
                    'event_code' => $event_code,
                    'template_code' => 'queue_test',
                    'email_role' => 'system',
                    'provider_code' => $provider_code,
                    'to_email' => $email,
                    'subject' => 'Prueba de comunicacion: '.$event_code,
                    'body' => 'Mensaje de prueba generado desde Centro de Comunicaciones.',
                    'priority' => 'normal',
                ]);

                if (!empty($result['success'])) {
                    $queued++;
                } else {
                    $errors[] = (string) \Arr::get($result, 'message', 'Error encolando correo.');
                }
            }

            $processor = new Service_Core_Email_QueueProcessor('admin-'.$this->user_id);
            $processed = $processor->process($queued, $provider_code);

            return $this->json_response([
                'success' => empty($errors),
                'message' => 'Prueba de email procesada.',
                'data' => [
                    'queued' => $queued,
                    'processed' => $processed,
                    'preview' => $preview,
                    'stats' => $this->get_stats(),
                    'queue_summary' => $this->get_queue_summary(),
                    'recent_attempts' => $this->get_recent_attempts(),
                    'queue' => $this->get_recent_queue(),
                ],
                'errors' => $errors,
            ], empty($errors) ? 200 : 400);
        } catch (\Exception $e) {
            \Log::error('Error probando evento de comunicaciones: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo ejecutar la prueba del evento.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * GET EVENTS
     *
     * FORMATEA EVENTOS PARA LA VISTA ADMINISTRATIVA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_events()
    {
        # SE INICIALIZA EL ARREGLO DE RESPUESTA
        $items = [];

        # SE RECORREN LOS EVENTOS
        foreach (Model_Core_Notification_Event::list_for_admin() as $event) {
            $items[] = [
                'id' => (int) $event->id,
                'code' => (string) $event->code,
                'name' => (string) $event->name,
                'description' => (string) $event->description,
                'notify_internal' => (int) $event->notify_internal,
                'notify_email' => (int) $event->notify_email,
                'active' => (int) $event->active,
            ];
        }

        return $items;
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASICOS DEL MODULO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE REGRESAN CONTADORES AGREGADOS
        return [
            'events' => (int) \DB::count_records('core_notification_events'),
            'notifications' => (int) \DB::count_records('core_notifications'),
            'unread' => (int) \DB::select()->from('core_notification_recipients')->where('status', '=', 'unread')->execute()->count(),
            'emails_pending' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'pending')->execute()->count(),
            'emails_failed' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'failed')->execute()->count(),
            'emails_sent' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'sent')->execute()->count(),
            'emails_processing' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'processing')->execute()->count(),
            'providers' => \DBUtil::table_exists('core_communication_providers') ? (int) \DB::count_records('core_communication_providers') : 0,
            'accounts' => \DBUtil::table_exists('core_communication_accounts') ? (int) \DB::count_records('core_communication_accounts') : 0,
            'conversations' => \DBUtil::table_exists('core_communication_conversations') ? (int) \DB::count_records('core_communication_conversations') : 0,
            'attempts' => \DBUtil::table_exists('core_email_queue_attempts') ? (int) \DB::count_records('core_email_queue_attempts') : 0,
        ];
    }

    protected function get_accounts()
    {
        if (!\DBUtil::table_exists('core_communication_accounts')) {
            return [];
        }

        $fields = $this->account_select_fields();
        $rows = \DB::select_array($fields)
            ->from('core_communication_accounts')
            ->order_by('code', 'asc')
            ->execute()
            ->as_array();

        $manager = new Service_Core_Communications_ImapManager();
        $items = [];
        foreach ($rows as $row) {
            $items[] = $manager->mask_account($row);
        }

        return $items;
    }

    protected function get_account_assignments()
    {
        return (new Service_Core_Communications_MailboxAccess())->assignments();
    }

    protected function get_my_accounts()
    {
        return (new Service_Core_Communications_MailboxAccess())->accounts_for_user((int) $this->user_id);
    }

    protected function get_conversations()
    {
        if (!\DBUtil::table_exists('core_communication_conversations')) {
            return [];
        }

        $query = \DB::select(
                'id',
                'code',
                'channel_code',
                'subject',
                'direction',
                'status',
                'priority',
                'assigned_user_id',
                'assigned_group_id',
                'related_entity_type',
                'related_entity_id',
                'related_party_id',
                'last_message_at',
                'message_count',
                'unread_count',
                'active'
            )
            ->from('core_communication_conversations')
            ->where('active', '=', 1);

        $allowed_conversations = (new Service_Core_Communications_MailboxAccess())->conversation_ids_for_user((int) $this->user_id);
        if (is_array($allowed_conversations)) {
            if (empty($allowed_conversations)) {
                $query->where('id', '=', 0);
            } else {
                $query->where('id', 'in', $allowed_conversations);
            }
        }

        return $query->order_by('last_message_at', 'desc')
            ->limit(20)
            ->execute()
            ->as_array();
    }

    protected function get_conversation_center(array $filters)
    {
        $message_ids = $this->conversation_ids_matching_messages((string) $filters['q']);
        $total_query = \DB::select([\DB::expr('COUNT(*)'), 'total'])
            ->from('core_communication_conversations');
        $this->apply_conversation_filters($total_query, $filters, $message_ids);
        $total_row = $total_query->execute()->current();
        $total = (int) (isset($total_row['total']) ? $total_row['total'] : 0);

        $page = (int) $filters['page'];
        $per_page = (int) $filters['per_page'];
        $rows_query = \DB::select(
                'id',
                'code',
                'channel_code',
                'subject',
                'direction',
                'status',
                'priority',
                'assigned_user_id',
                'assigned_group_id',
                'related_entity_type',
                'related_entity_id',
                'related_party_id',
                'last_message_at',
                'message_count',
                'unread_count',
                'active'
            )
            ->from('core_communication_conversations');
        $this->apply_conversation_filters($rows_query, $filters, $message_ids);
        $rows = $rows_query
            ->order_by('last_message_at', 'desc')
            ->order_by('id', 'desc')
            ->limit($per_page)
            ->offset(($page - 1) * $per_page)
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->format_conversation_row($row);
        }

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'pages' => $per_page > 0 ? (int) ceil($total / $per_page) : 1,
            ],
            'filters' => $filters,
            'folders' => $this->conversation_folders(),
            'channels' => $this->conversation_channels(),
        ];
    }

    protected function get_conversation_detail($conversation_id)
    {
        if (!$this->can_view_conversation((int) $conversation_id)) {
            return [];
        }

        $conversation = \DB::select(
                'id',
                'code',
                'channel_code',
                'subject',
                'direction',
                'status',
                'priority',
                'assigned_user_id',
                'assigned_group_id',
                'related_entity_type',
                'related_entity_id',
                'related_party_id',
                'last_message_at',
                'message_count',
                'unread_count',
                'active',
                'created_at',
                'updated_at'
            )
            ->from('core_communication_conversations')
            ->where('id', '=', (int) $conversation_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$conversation) {
            return [];
        }

        $messages = [];
        $message_rows = \DB::select(
                'id',
                'conversation_id',
                'account_id',
                'channel_code',
                'direction',
                'message_type',
                'from_email',
                'from_name',
                'to_json',
                'cc_json',
                'bcc_json',
                'subject',
                'body_text',
                'body_html_sanitized',
                'snippet',
                'received_at',
                'sent_at',
                'status',
                'provider_code',
                'related_entity_type',
                'related_entity_id',
                'related_party_id',
                'has_attachments',
                'attachment_count',
                'created_at'
            )
            ->from('core_communication_messages')
            ->where('conversation_id', '=', (int) $conversation_id)
            ->where('active', '=', 1);

        $visible_account_ids = $this->visible_mailbox_account_ids();
        if (is_array($visible_account_ids)) {
            if (empty($visible_account_ids)) {
                $message_rows->where('id', '=', 0);
            } else {
                $message_rows->where('account_id', 'in', $visible_account_ids);
            }
        }

        $message_rows = $message_rows
            ->order_by(\DB::expr('COALESCE(received_at, sent_at, created_at)'), 'asc')
            ->order_by('id', 'asc')
            ->limit(100)
            ->execute()
            ->as_array();

        foreach ($message_rows as $message) {
            $messages[] = $this->format_message_row($message);
        }

        return [
            'conversation' => $this->format_conversation_row($conversation),
            'messages' => $messages,
            'related_summary' => $this->conversation_related_summary($conversation),
        ];
    }

    protected function apply_conversation_filters($query, array $filters, array $message_ids)
    {
        $query->where('active', '=', 1);

        $allowed_conversations = (new Service_Core_Communications_MailboxAccess())->conversation_ids_for_user((int) $this->user_id);
        if (is_array($allowed_conversations)) {
            if (empty($allowed_conversations)) {
                $query->where('id', '=', 0);
            } else {
                $query->where('id', 'in', $allowed_conversations);
            }
        }

        $folder = (string) \Arr::get($filters, 'folder', 'inbox');
        if ($folder === 'inbox') {
            $query->where('direction', '=', 'incoming');
        } elseif ($folder === 'sent') {
            $query->where('direction', '=', 'outgoing');
        } elseif ($folder === 'drafts') {
            $query->where('direction', '=', 'draft');
        } elseif ($folder === 'trash') {
            $query->where('direction', '=', 'trash');
        } elseif ($folder === 'assigned') {
            $query->where_open()
                ->where('assigned_user_id', '=', (int) $this->user_id)
                ->or_where('assigned_group_id', '=', (int) $this->user_group)
                ->where_close();
        } elseif ($folder === 'favorites') {
            $query->where('id', '=', 0);
        }

        if ((int) \Arr::get($filters, 'unread', 0) === 1) {
            $query->where('unread_count', '>', 0);
        }

        if ((int) \Arr::get($filters, 'assigned', 0) === 1) {
            $query->where_open()
                ->where('assigned_user_id', '>', 0)
                ->or_where('assigned_group_id', '>', 0)
                ->where_close();
        }

        $channel = trim((string) \Arr::get($filters, 'channel', ''));
        if ($channel !== '') {
            $query->where('channel_code', '=', $channel);
        }

        $account_id = (int) \Arr::get($filters, 'account_id', 0);
        if ($account_id > 0) {
            $account_conversations = $this->conversation_ids_for_account($account_id);
            if (empty($account_conversations)) {
                $query->where('id', '=', 0);
            } else {
                $query->where('id', 'in', $account_conversations);
            }
        }

        $date_from = $this->date_to_timestamp(\Arr::get($filters, 'date_from', ''), false);
        if ($date_from > 0) {
            $query->where('last_message_at', '>=', $date_from);
        }

        $date_to = $this->date_to_timestamp(\Arr::get($filters, 'date_to', ''), true);
        if ($date_to > 0) {
            $query->where('last_message_at', '<=', $date_to);
        }

        $q = trim((string) \Arr::get($filters, 'q', ''));
        if ($q !== '') {
            $query->where_open()
                ->where('subject', 'LIKE', '%'.$q.'%');
            if (!empty($message_ids)) {
                $query->or_where('id', 'IN', $message_ids);
            }
            $query->where_close();
        }
    }

    protected function conversation_ids_matching_messages($q)
    {
        $q = trim((string) $q);
        if ($q === '' || !\DBUtil::table_exists('core_communication_messages')) {
            return [];
        }

        $query = \DB::select('conversation_id')
            ->from('core_communication_messages')
            ->where('active', '=', 1)
            ->where_open()
                ->where('from_email', 'LIKE', '%'.$q.'%')
                ->or_where('from_name', 'LIKE', '%'.$q.'%')
                ->or_where('subject', 'LIKE', '%'.$q.'%')
                ->or_where('snippet', 'LIKE', '%'.$q.'%')
            ->where_close();

        $visible_account_ids = $this->visible_mailbox_account_ids();
        if (is_array($visible_account_ids)) {
            if (empty($visible_account_ids)) {
                $query->where('id', '=', 0);
            } else {
                $query->where('account_id', 'in', $visible_account_ids);
            }
        }

        $rows = $query
            ->group_by('conversation_id')
            ->limit(500)
            ->execute()
            ->as_array();

        return array_values(array_filter(array_map(function ($row) {
            return (int) \Arr::get($row, 'conversation_id', 0);
        }, $rows)));
    }

    protected function conversation_ids_for_account($account_id)
    {
        $account_id = (int) $account_id;
        if ($account_id <= 0 || !\DBUtil::table_exists('core_communication_messages')) {
            return [];
        }

        $access = new Service_Core_Communications_MailboxAccess();
        if (!$access->can_view_account((int) $this->user_id, $account_id)) {
            return [];
        }

        $rows = \DB::select('conversation_id')
            ->from('core_communication_messages')
            ->where('active', '=', 1)
            ->where('account_id', '=', $account_id)
            ->group_by('conversation_id')
            ->execute()
            ->as_array();

        return array_values(array_filter(array_map(function ($row) {
            return (int) \Arr::get($row, 'conversation_id', 0);
        }, $rows)));
    }

    protected function can_view_conversation($conversation_id)
    {
        return (new Service_Core_Communications_MailboxAccess())->can_view_conversation((int) $this->user_id, (int) $conversation_id);
    }

    protected function conversation_exists($conversation_id)
    {
        if (!\DBUtil::table_exists('core_communication_conversations')) {
            return false;
        }

        $row = \DB::select('id')
            ->from('core_communication_conversations')
            ->where('id', '=', (int) $conversation_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return (bool) $row;
    }

    protected function format_conversation_row(array $row)
    {
        $latest = $this->latest_message_for_conversation((int) $row['id']);
        $participants = $this->conversation_participants((int) $row['id'], $latest);
        $account = $this->account_public_summary((int) \Arr::get($latest, 'account_id', 0));

        return [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'channel_code' => (string) $row['channel_code'],
            'channel_label' => $this->channel_label((string) $row['channel_code']),
            'channel_icon' => $this->channel_icon((string) $row['channel_code']),
            'subject' => (string) $row['subject'],
            'direction' => (string) $row['direction'],
            'status' => (string) $row['status'],
            'priority' => (int) $row['priority'],
            'assigned_user_id' => (int) $row['assigned_user_id'],
            'assigned_group_id' => (int) $row['assigned_group_id'],
            'related_entity_type' => (string) $row['related_entity_type'],
            'related_entity_id' => (int) $row['related_entity_id'],
            'related_party_id' => (int) $row['related_party_id'],
            'related_label' => $this->related_label($row),
            'last_message_at' => (int) $row['last_message_at'],
            'message_count' => (int) $row['message_count'],
            'unread_count' => (int) $row['unread_count'],
            'participants' => $participants,
            'snippet' => (string) \Arr::get($latest, 'snippet', ''),
            'account_id' => (int) \Arr::get($latest, 'account_id', 0),
            'account_code' => (string) \Arr::get($account, 'code', ''),
            'account_name' => (string) \Arr::get($account, 'name', ''),
            'account_email' => (string) \Arr::get($account, 'email_address', ''),
        ];
    }

    protected function format_message_row(array $row)
    {
        $body_html = (string) \Arr::get($row, 'body_html_sanitized', '');
        $body_text = (string) \Arr::get($row, 'body_text', '');
        $message_id = (int) $row['id'];
        $account = $this->account_public_summary((int) \Arr::get($row, 'account_id', 0));

        return [
            'id' => $message_id,
            'account_id' => (int) \Arr::get($row, 'account_id', 0),
            'account_email' => (string) \Arr::get($account, 'email_address', ''),
            'account_name' => (string) \Arr::get($account, 'name', ''),
            'channel_code' => (string) $row['channel_code'],
            'direction' => (string) $row['direction'],
            'message_type' => (string) $row['message_type'],
            'from_email' => (string) $row['from_email'],
            'from_name' => (string) $row['from_name'],
            'to' => $this->decode_json_list(\Arr::get($row, 'to_json', '')),
            'cc' => $this->decode_json_list(\Arr::get($row, 'cc_json', '')),
            'bcc_count' => count($this->decode_json_list(\Arr::get($row, 'bcc_json', ''))),
            'subject' => (string) $row['subject'],
            'body_text' => $this->safe_text($body_text),
            'body_html_sanitized' => $this->safe_html($body_html),
            'snippet' => $this->safe_text((string) $row['snippet']),
            'date' => (int) ($row['received_at'] ?: ($row['sent_at'] ?: $row['created_at'])),
            'status' => (string) $row['status'],
            'provider_code' => (string) $row['provider_code'],
            'related_entity_type' => (string) $row['related_entity_type'],
            'related_entity_id' => (int) $row['related_entity_id'],
            'related_party_id' => (int) $row['related_party_id'],
            'has_attachments' => (int) $row['has_attachments'],
            'attachment_count' => (int) $row['attachment_count'],
            'attachments' => $this->message_attachments($message_id),
        ];
    }

    protected function latest_message_for_conversation($conversation_id)
    {
        if (!\DBUtil::table_exists('core_communication_messages')) {
            return [];
        }

        $query = \DB::select('account_id', 'from_email', 'from_name', 'to_json', 'snippet')
            ->from('core_communication_messages')
            ->where('conversation_id', '=', (int) $conversation_id)
            ->where('active', '=', 1);

        $visible_account_ids = $this->visible_mailbox_account_ids();
        if (is_array($visible_account_ids)) {
            if (empty($visible_account_ids)) {
                $query->where('id', '=', 0);
            } else {
                $query->where('account_id', 'in', $visible_account_ids);
            }
        }

        $row = $query
            ->order_by(\DB::expr('COALESCE(received_at, sent_at, created_at)'), 'desc')
            ->order_by('id', 'desc')
            ->limit(1)
            ->execute()
            ->current();

        return $row ?: [];
    }

    protected function account_public_summary($account_id)
    {
        $account_id = (int) $account_id;
        if ($account_id <= 0 || !\DBUtil::table_exists('core_communication_accounts')) {
            return [];
        }

        $row = \DB::select('id', 'code', 'name', 'email_address')
            ->from('core_communication_accounts')
            ->where('id', '=', $account_id)
            ->execute()
            ->current();

        return $row ?: [];
    }

    protected function visible_mailbox_account_ids()
    {
        if ($this->is_super_admin) {
            return null;
        }

        return (new Service_Core_Communications_MailboxAccess())->account_ids_for_user((int) $this->user_id);
    }

    protected function conversation_participants($conversation_id, array $latest)
    {
        $items = [];
        $from = trim((string) \Arr::get($latest, 'from_name', ''));
        $from_email = trim((string) \Arr::get($latest, 'from_email', ''));
        if ($from !== '' || $from_email !== '') {
            $items[] = $from !== '' ? $from : $from_email;
        }

        foreach ($this->decode_json_list(\Arr::get($latest, 'to_json', '')) as $to) {
            $label = trim((string) \Arr::get($to, 'name', ''));
            $email = trim((string) \Arr::get($to, 'email', ''));
            if ($label !== '' || $email !== '') {
                $items[] = $label !== '' ? $label : $email;
            }
        }

        $items = array_values(array_unique(array_filter($items)));
        return empty($items) ? ['Sin participantes'] : array_slice($items, 0, 3);
    }

    protected function message_attachments($message_id)
    {
        if (!\DBUtil::table_exists('core_communication_message_attachments')) {
            return [];
        }

        $rows = \DB::select('id', 'filename', 'mime_type', 'size_bytes', 'disposition')
            ->from('core_communication_message_attachments')
            ->where('message_id', '=', (int) $message_id)
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'filename' => $this->safe_text((string) $row['filename']),
                'mime_type' => $this->safe_text((string) $row['mime_type']),
                'size_bytes' => (int) $row['size_bytes'],
                'disposition' => $this->safe_text((string) $row['disposition']),
            ];
        }

        return $items;
    }

    protected function decode_json_list($json)
    {
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function safe_text($value)
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        $value = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        return trim($value);
    }

    protected function safe_html($html)
    {
        $html = (string) $html;
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $html);
        $html = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $html);
        return $html;
    }

    protected function conversation_related_summary(array $conversation)
    {
        $type = trim((string) \Arr::get($conversation, 'related_entity_type', ''));
        $id = (int) \Arr::get($conversation, 'related_entity_id', 0);

        return [
            'type' => $type,
            'id' => $id,
            'label' => $this->related_label($conversation),
            'party_id' => (int) \Arr::get($conversation, 'related_party_id', 0),
        ];
    }

    protected function related_label(array $row)
    {
        $type = trim((string) \Arr::get($row, 'related_entity_type', ''));
        $id = (int) \Arr::get($row, 'related_entity_id', 0);
        return $type !== '' && $id > 0 ? $type.' #'.$id : '';
    }

    protected function channel_label($channel)
    {
        $labels = [
            'email' => 'Email',
            'internal' => 'Interno',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'push' => 'Push',
        ];

        return isset($labels[$channel]) ? $labels[$channel] : ($channel !== '' ? $channel : 'Canal');
    }

    protected function channel_icon($channel)
    {
        $icons = [
            'email' => 'bi bi-envelope',
            'internal' => 'bi bi-bell',
            'whatsapp' => 'bi bi-whatsapp',
            'sms' => 'bi bi-chat-dots',
            'push' => 'bi bi-phone',
        ];

        return isset($icons[$channel]) ? $icons[$channel] : 'bi bi-chat-left-text';
    }

    protected function conversation_channels()
    {
        if (!\DBUtil::table_exists('core_communication_conversations')) {
            return [];
        }

        $rows = \DB::select('channel_code')
            ->from('core_communication_conversations')
            ->where('active', '=', 1)
            ->group_by('channel_code')
            ->order_by('channel_code', 'asc')
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $code = (string) $row['channel_code'];
            if ($code !== '') {
                $items[] = ['code' => $code, 'label' => $this->channel_label($code)];
            }
        }

        return $items;
    }

    protected function conversation_folders()
    {
        return [
            ['code' => 'inbox', 'label' => 'Inbox', 'icon' => 'bi bi-inbox'],
            ['code' => 'sent', 'label' => 'Sent', 'icon' => 'bi bi-send'],
            ['code' => 'drafts', 'label' => 'Drafts', 'icon' => 'bi bi-file-earmark'],
            ['code' => 'trash', 'label' => 'Trash', 'icon' => 'bi bi-trash'],
            ['code' => 'assigned', 'label' => 'Asignadas', 'icon' => 'bi bi-person-check'],
            ['code' => 'favorites', 'label' => 'Favoritas', 'icon' => 'bi bi-star'],
        ];
    }

    protected function date_to_timestamp($date, $end_of_day = false)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return 0;
        }

        $time = strtotime($date.($end_of_day ? ' 23:59:59' : ' 00:00:00'));
        return $time ? (int) $time : 0;
    }

    protected function assert_conversation_tables_ready()
    {
        foreach (['core_communication_conversations', 'core_communication_messages', 'core_communication_message_attachments'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Tabla requerida no disponible: '.$table);
            }
        }
    }

    /**
     * GET PROVIDERS
     *
     * LISTA PROVEEDORES SIN EXPONER SECRETOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_providers()
    {
        if (!\DBUtil::table_exists('core_communication_providers')) {
            return [];
        }

        $rows = \DB::select(
                'id',
                'code',
                'name',
                'type',
                'transport',
                'host',
                'port',
                'username',
                'api_base_url',
                'encryption',
                'timeout_seconds',
                'verify_tls',
                'from_email',
                'from_name',
                'reply_to_email',
                'daily_limit',
                'hourly_limit',
                'simulation_mode',
                'active',
                'last_test_at',
                'last_test_status',
                'password_encrypted',
                'api_key_encrypted'
            )
            ->from('core_communication_providers')
            ->order_by('code', 'asc')
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'type' => (string) $row['type'],
                'transport' => (string) $row['transport'],
                'host' => (string) $row['host'],
                'port' => (int) $row['port'],
                'username' => (string) $row['username'],
                'api_base_url' => (string) $row['api_base_url'],
                'encryption' => (string) $row['encryption'],
                'timeout_seconds' => (int) $row['timeout_seconds'],
                'verify_tls' => (int) $row['verify_tls'],
                'from_email' => (string) $row['from_email'],
                'from_name' => (string) $row['from_name'],
                'reply_to_email' => (string) $row['reply_to_email'],
                'daily_limit' => (int) $row['daily_limit'],
                'hourly_limit' => (int) $row['hourly_limit'],
                'simulation_mode' => (int) $row['simulation_mode'],
                'active' => (int) $row['active'],
                'last_test_at' => (int) $row['last_test_at'],
                'last_test_status' => (string) $row['last_test_status'],
                'password_configured' => trim((string) $row['password_encrypted']) !== '',
                'api_key_configured' => trim((string) $row['api_key_encrypted']) !== '',
            ];
        }

        return $items;
    }

    protected function get_templates()
    {
        if (!\DBUtil::table_exists('core_email_templates')) {
            return [];
        }

        $fields = ['id', 'code', 'email_role', 'subject', 'view_path', 'content', 'active', 'created_at', 'updated_at'];
        if (\DBUtil::field_exists('core_email_templates', ['name'])) {
            $fields[] = 'name';
        }
        if (\DBUtil::field_exists('core_email_templates', ['body_text'])) {
            $fields[] = 'body_text';
        }

        $rows = \DB::select_array($fields)
            ->from('core_email_templates')
            ->order_by('code', 'asc')
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $name = array_key_exists('name', $row) ? (string) $row['name'] : '';
            $items[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => $name !== '' ? $name : (string) $row['code'],
                'email_role' => (string) $row['email_role'],
                'subject' => (string) $row['subject'],
                'view_path' => (string) $row['view_path'],
                'content' => (string) $row['content'],
                'body_text' => array_key_exists('body_text', $row) ? (string) $row['body_text'] : '',
                'active' => (int) $row['active'],
                'created_at' => (int) $row['created_at'],
                'updated_at' => (int) $row['updated_at'],
                'variables' => $this->extract_template_variables($row),
            ];
        }

        return $items;
    }

    protected function get_layouts()
    {
        if (!\DBUtil::table_exists('core_email_layouts')) {
            return [];
        }

        $rows = \DB::select('id', 'code', 'name', 'description', 'html_layout', 'text_layout', 'active', 'version', 'created_at', 'updated_at')
            ->from('core_email_layouts')
            ->order_by('code', 'asc')
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'description' => (string) $row['description'],
                'html_layout' => (string) $row['html_layout'],
                'text_layout' => (string) $row['text_layout'],
                'active' => (int) $row['active'],
                'version' => (int) $row['version'],
                'created_at' => (int) $row['created_at'],
                'updated_at' => (int) $row['updated_at'],
            ];
        }

        return $items;
    }

    protected function get_variable_helper()
    {
        $renderer = new Service_Core_Email_TemplateRenderer();

        return [
            'global' => $renderer->available_global_variables(),
            'sample' => $this->preview_variables([]),
        ];
    }

    protected function validate_template(Model_Core_Email_Template $template)
    {
        $errors = [];

        if (trim((string) $template->name) === '') {
            $errors[] = 'El nombre de la plantilla es obligatorio.';
        }

        if (trim((string) $template->subject) === '') {
            $errors[] = 'El asunto es obligatorio.';
        }

        $errors = array_merge($errors, $this->validate_safe_content((string) $template->subject, 'asunto'));
        $errors = array_merge($errors, $this->validate_safe_content((string) $template->content, 'cuerpo HTML'));
        $errors = array_merge($errors, $this->validate_safe_content((string) $template->body_text, 'cuerpo texto'));

        return $errors;
    }

    protected function validate_layout(Model_Core_Email_Layout $layout)
    {
        $errors = [];

        if (trim((string) $layout->name) === '') {
            $errors[] = 'El nombre del layout es obligatorio.';
        }

        if (trim((string) $layout->html_layout) === '') {
            $errors[] = 'El layout HTML es obligatorio.';
        }

        $errors = array_merge($errors, $this->validate_safe_content((string) $layout->html_layout, 'layout HTML'));
        $errors = array_merge($errors, $this->validate_safe_content((string) $layout->text_layout, 'layout texto'));

        return $errors;
    }

    protected function validate_safe_content($content, $label)
    {
        $errors = [];
        $content = (string) $content;

        if (preg_match('/<\s*script\b/i', $content)) {
            $errors[] = 'No se permiten etiquetas script en '.$label.'.';
        }

        if (preg_match('/\son[a-z]+\s*=/i', $content) || preg_match('/javascript\s*:/i', $content)) {
            $errors[] = 'No se permite JavaScript embebido en '.$label.'.';
        }

        if (preg_match('/<\?/i', $content) || preg_match('/\?>/i', $content)) {
            $errors[] = 'No se permite codigo PHP en '.$label.'.';
        }

        if (preg_match('/(password_encrypted|api_key_encrypted|secret_value|smtp_password|api_secret)/i', $content)) {
            $errors[] = 'No se permiten variables o referencias a secretos en '.$label.'.';
        }

        return $errors;
    }

    protected function preview_variables($raw)
    {
        $renderer = new Service_Core_Email_TemplateRenderer();
        $variables = $renderer->global_variables();

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (is_array($raw)) {
            foreach ($raw as $key => $value) {
                if (!is_scalar($value)) {
                    continue;
                }

                $safe_key = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', (string) $key);
                if ($safe_key === '' || preg_match('/(password|api_key|secret|token)/i', $safe_key)) {
                    continue;
                }

                $variables[$safe_key] = (string) $value;
            }
        }

        if (trim((string) $variables['user_name']) === '') {
            $variables['user_name'] = 'Usuario de prueba';
        }

        return $variables;
    }

    protected function template_variables(Model_Core_Email_Template $template)
    {
        return $this->extract_template_variables([
            'subject' => (string) $template->subject,
            'content' => (string) $template->content,
            'body_text' => (string) $template->body_text,
        ]);
    }

    protected function extract_template_variables(array $row)
    {
        $renderer = new Service_Core_Email_TemplateRenderer();
        $content = implode("\n", [
            (string) \Arr::get($row, 'subject', ''),
            (string) \Arr::get($row, 'content', ''),
            (string) \Arr::get($row, 'body_text', ''),
        ]);

        return $renderer->extract_variables($content);
    }

    protected function get_recent_attempts()
    {
        if (!\DBUtil::table_exists('core_email_queue_attempts')) {
            return [];
        }

        $rows = \DB::select('queue_id', 'attempt_number', 'transport', 'provider_code', 'status', 'response_code', 'response_message', 'attempted_at')
            ->from('core_email_queue_attempts')
            ->order_by('id', 'desc')
            ->limit(10)
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['response_message'] = substr((string) $row['response_message'], 0, 160);
        }

        return $rows;
    }

    protected function get_recent_queue()
    {
        $fields = ['id', 'event_code', 'template_code', 'email_role', 'to_email', 'subject', 'status', 'attempts', 'scheduled_at', 'sent_at'];
        if (\DBUtil::field_exists('core_email_queue', ['provider_code'])) {
            $fields[] = 'provider_code';
        }
        if (\DBUtil::field_exists('core_email_queue', ['priority'])) {
            $fields[] = 'priority';
        }

        return \DB::select_array($fields)
            ->from('core_email_queue')
            ->order_by('id', 'desc')
            ->limit(10)
            ->execute()
            ->as_array();
    }

    protected function get_queue_summary()
    {
        if (!\DBUtil::table_exists('core_email_queue')) {
            return [
                'pending' => 0,
                'sent' => 0,
                'failed' => 0,
                'simulated' => 0,
                'processing' => 0,
                'last_errors' => 0,
            ];
        }

        $summary = [
            'pending' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'pending')->execute()->count(),
            'sent' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'sent')->execute()->count(),
            'failed' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'failed')->execute()->count(),
            'simulated' => 0,
            'processing' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'processing')->execute()->count(),
            'last_errors' => (int) \DB::select()->from('core_email_queue')->where('last_error', '!=', '')->execute()->count(),
        ];

        if (\DBUtil::field_exists('core_email_queue', ['simulation_mode'])) {
            $summary['simulated'] = (int) \DB::select()
                ->from('core_email_queue')
                ->where('status', '=', 'sent')
                ->where('simulation_mode', '=', 1)
                ->execute()
                ->count();
        }

        return $summary;
    }

    protected function validate_provider(Model_Core_Communication_Provider $provider)
    {
        $errors = [];

        if (trim((string) $provider->name) === '') {
            $errors[] = 'El nombre del proveedor es obligatorio.';
        }

        if ((string) $provider->from_email !== '' && !filter_var((string) $provider->from_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo remitente no es valido.';
        }

        if ((string) $provider->reply_to_email !== '' && !filter_var((string) $provider->reply_to_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo de respuesta no es valido.';
        }

        if ((string) $provider->transport === 'smtp' && (int) $provider->active === 1 && (int) $provider->simulation_mode === 0) {
            if (trim((string) $provider->host) === '') {
                $errors[] = 'El host SMTP es obligatorio para envio real.';
            }
            if ((int) $provider->port <= 0) {
                $errors[] = 'El puerto SMTP es obligatorio para envio real.';
            }
        }

        return $errors;
    }

    protected function account_to_array(Model_Core_Communication_Account $account)
    {
        $data = [];
        foreach ($this->account_select_fields() as $name) {
            $data[$name] = isset($account->{$name}) ? $account->{$name} : '';
        }

        return $data;
    }

    protected function account_select_fields()
    {
        $fields = [
            'id',
            'code',
            'name',
            'email_address',
            'account_type',
            'provider_code',
            'smtp_provider_code',
            'imap_provider_code',
            'imap_host',
            'imap_port',
            'imap_encryption',
            'imap_username',
            'imap_password_encrypted',
            'imap_folder_inbox',
            'imap_folder_sent',
            'imap_folder_drafts',
            'imap_folder_trash',
            'sync_inbox',
            'sync_sent',
            'sync_drafts',
            'sync_trash',
            'append_sent',
            'sync_enabled',
            'last_sync_at',
            'last_sync_status',
            'last_sync_error',
            'active',
            'created_at',
            'updated_at',
        ];

        foreach (['owner_user_id', 'owner_group_id', 'mailbox_scope'] as $field) {
            if (\DBUtil::field_exists('core_communication_accounts', [$field])) {
                $offset = array_search('account_type', $fields, true);
                array_splice($fields, $offset + 1, 0, [$field]);
            }
        }

        return array_values(array_unique($fields));
    }

    protected function assert_unique_account_code(Model_Core_Communication_Account $account)
    {
        $row = \DB::select('id')
            ->from('core_communication_accounts')
            ->where('code', '=', trim((string) $account->code))
            ->execute()
            ->current();

        if ($row && (int) $row['id'] !== (int) $account->id) {
            throw new \RuntimeException('Ya existe una cuenta de correo con ese codigo.');
        }
    }

    protected function allowed_value($value, array $allowed, $default)
    {
        $value = trim((string) $value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    protected function encode_secret($value)
    {
        try {
            return \Crypt::encode((string) $value);
        } catch (\Exception $e) {
            \Log::error('No se pudo cifrar secreto de comunicaciones: '.$e->getMessage());
            throw new \RuntimeException('No se pudo cifrar el secreto.');
        }
    }

    /**
     * GET USERS
     *
     * OBTIENE USUARIOS ACTIVOS PARA DESTINATARIOS INTERNOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_users()
    {
        # SE CONSULTAN USUARIOS DEL SISTEMA
        if (!\DBUtil::table_exists('users')) {
            return [];
        }

        $rows = \DB::select('id', 'username', 'email', 'group_id')
            ->from('users')
            ->order_by('username', 'asc')
            ->execute()
            ->as_array();

        $users = [];
        foreach ($rows as $row) {
            $users[] = [
                'id' => (int) $row['id'],
                'label' => trim((string) ($row['username'] ?: $row['email'])),
                'email' => (string) $row['email'],
                'group_id' => (int) $row['group_id'],
            ];
        }

        return $users;
    }

    protected function get_groups()
    {
        if (!\DBUtil::table_exists('users_groups')) {
            return [];
        }

        $rows = \DB::select('id', 'name')
            ->from('users_groups')
            ->order_by('name', 'asc')
            ->execute()
            ->as_array();

        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $groups;
    }

    protected function get_roles()
    {
        if (!\DBUtil::table_exists('core_email_roles')) {
            return [];
        }

        $rows = \DB::select('code', 'name')
            ->from('core_email_roles')
            ->where('active', '=', 1)
            ->order_by('code', 'asc')
            ->execute()
            ->as_array();

        $roles = [];
        foreach ($rows as $row) {
            $roles[] = [
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
            ];
        }

        return $roles;
    }

    protected function get_recipient_rules()
    {
        if (!\DBUtil::table_exists('core_communication_event_recipients')) {
            return [];
        }

        $rows = \DB::select()
            ->from('core_communication_event_recipients')
            ->order_by('event_code', 'asc')
            ->order_by('channel_code', 'asc')
            ->order_by('id', 'asc')
            ->execute()
            ->as_array();

        $rules = [];
        foreach ($rows as $row) {
            $rules[] = [
                'id' => (int) $row['id'],
                'event_code' => (string) $row['event_code'],
                'channel_code' => (string) $row['channel_code'],
                'recipient_type' => (string) $row['recipient_type'],
                'recipient_value' => (string) $row['recipient_value'],
                'mode' => (string) $row['mode'],
                'active' => (int) $row['active'],
                'label' => $this->recipient_label((string) $row['recipient_type'], (string) $row['recipient_value']),
            ];
        }

        return $rules;
    }

    protected function recipient_label($type, $value)
    {
        if ($type === 'user' && \DBUtil::table_exists('users')) {
            $row = \DB::select('username', 'email')->from('users')->where('id', '=', (int) $value)->execute()->current();
            if ($row) {
                return trim((string) ($row['username'] ?: $row['email']));
            }
        }

        if ($type === 'group' && \DBUtil::table_exists('users_groups')) {
            $row = \DB::select('name')->from('users_groups')->where('id', '=', (int) $value)->execute()->current();
            if ($row) {
                return (string) $row['name'];
            }
        }

        if ($type === 'role') {
            return 'Rol: '.$value;
        }

        return $value;
    }

    protected function validate_recipient_rule(Model_Core_Communication_EventRecipient $rule)
    {
        $errors = [];

        if (trim((string) $rule->event_code) === '') {
            $errors[] = 'El evento es obligatorio.';
        }

        if (!in_array((string) $rule->channel_code, ['internal', 'email'], true)) {
            $errors[] = 'Canal invalido.';
        }

        if (!in_array((string) $rule->recipient_type, ['user', 'group', 'role', 'email'], true)) {
            $errors[] = 'Tipo de destinatario invalido.';
        }

        if (trim((string) $rule->recipient_value) === '') {
            $errors[] = 'El valor del destinatario es obligatorio.';
        }

        if ((string) $rule->recipient_type === 'email' && !filter_var((string) $rule->recipient_value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo manual no es valido.';
        }

        if (!in_array((string) $rule->mode, ['include', 'exclude'], true)) {
            $errors[] = 'Modo invalido.';
        }

        return $errors;
    }

    protected function recipient_preview($event_code, $channel_code)
    {
        $resolver = new Service_Core_Communications_RecipientResolver();
        $resolved = $resolver->resolve($event_code, $channel_code);

        return [
            'event_code' => (string) $event_code,
            'channel_code' => (string) $channel_code,
            'users' => $resolved['users'],
            'emails' => $resolved['emails'],
            'groups' => $resolved['groups'],
            'excluded_count' => (int) $resolved['excluded_count'],
            'user_labels' => $this->user_labels($resolved['users']),
            'group_labels' => $this->group_labels($resolved['groups']),
        ];
    }

    protected function user_labels(array $user_ids)
    {
        if (empty($user_ids) || !\DBUtil::table_exists('users')) {
            return [];
        }

        $rows = \DB::select('id', 'username', 'email')
            ->from('users')
            ->where('id', 'in', array_map('intval', $user_ids))
            ->execute()
            ->as_array();

        $labels = [];
        foreach ($rows as $row) {
            $labels[] = [
                'id' => (int) $row['id'],
                'label' => trim((string) ($row['username'] ?: $row['email'])),
                'email' => (string) $row['email'],
            ];
        }

        return $labels;
    }

    protected function group_labels(array $group_ids)
    {
        if (empty($group_ids) || !\DBUtil::table_exists('users_groups')) {
            return [];
        }

        $rows = \DB::select('id', 'name')
            ->from('users_groups')
            ->where('id', 'in', array_map('intval', $group_ids))
            ->execute()
            ->as_array();

        $labels = [];
        foreach ($rows as $row) {
            $labels[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $labels;
    }

    /**
     * GET DEPARTMENTS
     *
     * OBTIENE DEPARTAMENTOS ACTIVOS PARA DESTINATARIOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function get_departments()
    {
        # SE CONSULTAN DEPARTAMENTOS ACTIVOS
        if (!\DBUtil::table_exists('core_departments')) {
            return [];
        }

        $rows = \DB::select('id', 'name')
            ->from('core_departments')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $items[] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return $items;
    }

    /**
     * RESOLVE RECIPIENTS
     *
     * UNE USUARIOS DIRECTOS Y USUARIOS ASIGNADOS A DEPARTAMENTOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function resolve_recipients(array $user_ids, array $department_ids)
    {
        # SE NORMALIZAN USUARIOS DIRECTOS
        $recipients = array_filter(array_map('intval', $user_ids));

        # SE AGREGAN USUARIOS VINCULADOS A EMPLEADOS POR DEPARTAMENTO
        $department_ids = array_filter(array_map('intval', $department_ids));
        if (!empty($department_ids) && \DBUtil::table_exists('core_employees')) {
            $rows = \DB::select('user_id')
                ->from('core_employees')
                ->where('department_id', 'in', $department_ids)
                ->where('user_id', '>', 0)
                ->where('active', '=', 1)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $recipients[] = (int) $row['user_id'];
            }
        }

        return array_values(array_unique(array_filter($recipients)));
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS DE COMUNICACIONES EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach (['core_notifications', 'core_notification_recipients', 'core_email_queue', 'core_communication_event_recipients'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de comunicaciones.');
            }
        }
    }

    protected function assert_template_editor_ready()
    {
        if (!\DBUtil::table_exists('core_email_templates') || !\DBUtil::table_exists('core_email_layouts')) {
            throw new \RuntimeException('Falta ejecutar migraciones de comunicaciones.');
        }

        if (!\DBUtil::field_exists('core_email_templates', ['name']) || !\DBUtil::field_exists('core_email_templates', ['body_text'])) {
            throw new \RuntimeException('Falta ejecutar migracion 077 para editar plantillas.');
        }
    }

    protected function assert_accounts_ready()
    {
        if (!\DBUtil::table_exists('core_communication_accounts')) {
            throw new \RuntimeException('Falta ejecutar migracion 078 para cuentas de correo.');
        }
    }

    protected function assert_account_assignments_ready()
    {
        $this->assert_accounts_ready();
        if (!\DBUtil::table_exists('core_communication_account_assignments')) {
            throw new \RuntimeException('Falta ejecutar migracion 080 para asignaciones de cuentas.');
        }
    }
}
