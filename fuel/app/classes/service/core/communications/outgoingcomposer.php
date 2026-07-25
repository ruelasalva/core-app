<?php

class Service_Core_Communications_OutgoingComposer
{
    protected $access;
    protected $store;
    protected $email_manager;

    public function __construct()
    {
        $this->access = new Service_Core_Communications_MailboxAccess();
        $this->store = new Service_Core_Communications_MessageStore();
        $this->email_manager = new Service_Core_Email_Manager();
    }

    public function compose($user_id, array $payload, array $files = [])
    {
        $user_id = (int) $user_id;
        $account = $this->validated_sender_account($user_id, (int) \Arr::get($payload, 'account_id', 0));
        if (!$account['success']) {
            return $account;
        }

        if (!$this->message_store_ready()) {
            return $this->error('Falta preparar Message Store de comunicaciones.', ['message_store_not_ready'], 500);
        }

        $attachments = $this->prepare_attachments($files);
        if (empty($attachments['success'])) {
            return $attachments;
        }

        $to = $this->normalize_recipients(\Arr::get($payload, 'to', ''));
        $cc = $this->normalize_recipients(\Arr::get($payload, 'cc', ''));
        $bcc = $this->normalize_recipients(\Arr::get($payload, 'bcc', ''));
        if (!empty($to['errors'])) {
            return $this->error('Revisa los destinatarios principales.', $to['errors'], 422);
        }
        if (!empty($cc['errors']) || !empty($bcc['errors'])) {
            return $this->error('Revisa CC/BCC.', array_merge($cc['errors'], $bcc['errors']), 422);
        }
        if (empty($to['items'])) {
            return $this->error('Captura al menos un destinatario.', ['to_required'], 422);
        }

        $subject = trim((string) \Arr::get($payload, 'subject', ''));
        $body_text = trim((string) \Arr::get($payload, 'body_text', ''));
        $body_html = $this->sanitize_html((string) \Arr::get($payload, 'body_html', ''));
        if ($subject === '' || ($body_text === '' && trim(strip_tags($body_html)) === '')) {
            return $this->error('Captura asunto y mensaje.', ['subject_body_required'], 422);
        }

        $provider_code = $this->provider_code($account['data']['account']);
        $queue = $this->queue_messages($provider_code, $account['data']['account'], $to['items'], $cc['items'], $bcc['items'], $subject, $body_text, $body_html, [
            'event_code' => 'communications.compose',
            'related_entity_type' => $this->safe_key(\Arr::get($payload, 'related_entity_type', '')),
            'related_entity_id' => (int) \Arr::get($payload, 'related_entity_id', 0),
            'related_party_id' => (int) \Arr::get($payload, 'related_party_id', 0),
            'attachments' => $attachments['data']['attachments'],
        ]);

        if (empty($queue['success'])) {
            return $queue;
        }

        $message = $this->store_outgoing($account['data']['account'], [
            'subject' => $subject,
            'body_text' => $body_text,
            'body_html' => $body_html,
            'to' => $to['items'],
            'cc' => $cc['items'],
            'bcc' => $bcc['items'],
            'provider_code' => $provider_code,
            'queue_id' => (int) \Arr::get($queue, 'first_queue_id', 0),
            'related_entity_type' => $this->safe_key(\Arr::get($payload, 'related_entity_type', '')),
            'related_entity_id' => (int) \Arr::get($payload, 'related_entity_id', 0),
            'related_party_id' => (int) \Arr::get($payload, 'related_party_id', 0),
            'owner_user_id' => $user_id,
            'attachments' => $attachments['data']['attachments'],
        ]);

        if (empty($message['success'])) {
            return $message;
        }

        $stored = (array) \Arr::get($message, 'data', []);
        $stored_message = (array) \Arr::get($stored, 'message', []);

        \Log::info('Correo saliente encolado. conversation_id='.(int) \Arr::get($stored, 'conversation_id', 0).' queue_count='.(int) $queue['queued']);

        return [
            'success' => true,
            'message' => 'Correo encolado correctamente.',
            'status' => 200,
            'data' => [
                'conversation_id' => (int) \Arr::get($stored, 'conversation_id', 0),
                'message_id' => (int) \Arr::get($stored_message, 'id', 0),
                'queue_ids' => $queue['queue_ids'],
                'queued' => (int) $queue['queued'],
                'attachments' => $attachments['data']['public'],
            ],
            'errors' => [],
        ];
    }

    public function reply($user_id, array $payload, array $files = [])
    {
        $user_id = (int) $user_id;
        $conversation_id = (int) \Arr::get($payload, 'conversation_id', 0);
        if ($conversation_id <= 0) {
            return $this->error('Conversacion invalida.', ['invalid_conversation'], 422);
        }
        if (!$this->access->can_view_conversation($user_id, $conversation_id)) {
            return $this->error('No tienes permiso para ver esta conversacion.', ['conversation_permission_denied'], 403);
        }

        $account = $this->validated_sender_account($user_id, (int) \Arr::get($payload, 'account_id', 0));
        if (!$account['success']) {
            return $account;
        }

        if (!$this->message_store_ready()) {
            return $this->error('Falta preparar Message Store de comunicaciones.', ['message_store_not_ready'], 500);
        }

        $attachments = $this->prepare_attachments($files);
        if (empty($attachments['success'])) {
            return $attachments;
        }

        $conversation = Model_Core_Communication_Conversation::find($conversation_id);
        if (!$conversation || (int) $conversation->active !== 1) {
            return $this->error('Conversacion no encontrada.', ['conversation_not_found'], 404);
        }

        $last_incoming = $this->last_incoming_message($conversation_id, (int) $account['data']['account']['id']);
        if (empty($last_incoming)) {
            return $this->error('No se encontro un mensaje entrante para responder.', ['incoming_message_not_found'], 422);
        }

        $to = $this->reply_recipients($last_incoming, $account['data']['account']);
        if (empty($to)) {
            return $this->error('No se pudo resolver destinatario de respuesta.', ['reply_recipient_not_found'], 422);
        }

        $cc = $this->normalize_recipients(\Arr::get($payload, 'cc', ''));
        $bcc = $this->normalize_recipients(\Arr::get($payload, 'bcc', ''));
        if (!empty($cc['errors']) || !empty($bcc['errors'])) {
            return $this->error('Revisa CC/BCC.', array_merge($cc['errors'], $bcc['errors']), 422);
        }

        $body_text = trim((string) \Arr::get($payload, 'body_text', ''));
        $body_html = $this->sanitize_html((string) \Arr::get($payload, 'body_html', ''));
        if ($body_text === '' && trim(strip_tags($body_html)) === '') {
            return $this->error('Captura el mensaje de respuesta.', ['body_required'], 422);
        }

        $subject = $this->reply_subject((string) $conversation->subject);
        $provider_code = $this->provider_code($account['data']['account']);
        $queue = $this->queue_messages($provider_code, $account['data']['account'], $to, $cc['items'], $bcc['items'], $subject, $body_text, $body_html, [
            'event_code' => 'communications.reply',
            'conversation_id' => $conversation_id,
            'in_reply_to' => (string) \Arr::get($last_incoming, 'external_message_id', ''),
            'related_entity_type' => (string) $conversation->related_entity_type,
            'related_entity_id' => (int) $conversation->related_entity_id,
            'related_party_id' => (int) $conversation->related_party_id,
            'attachments' => $attachments['data']['attachments'],
        ]);

        if (empty($queue['success'])) {
            return $queue;
        }

        $message = $this->store_outgoing($account['data']['account'], [
            'conversation_id' => $conversation_id,
            'subject' => $subject,
            'body_text' => $body_text,
            'body_html' => $body_html,
            'to' => $to,
            'cc' => $cc['items'],
            'bcc' => $bcc['items'],
            'provider_code' => $provider_code,
            'queue_id' => (int) \Arr::get($queue, 'first_queue_id', 0),
            'in_reply_to' => (string) \Arr::get($last_incoming, 'external_message_id', ''),
            'references' => (string) \Arr::get($last_incoming, 'external_message_id', ''),
            'related_entity_type' => (string) $conversation->related_entity_type,
            'related_entity_id' => (int) $conversation->related_entity_id,
            'related_party_id' => (int) $conversation->related_party_id,
            'owner_user_id' => $user_id,
            'attachments' => $attachments['data']['attachments'],
        ]);

        if (empty($message['success'])) {
            return $message;
        }

        $stored = (array) \Arr::get($message, 'data', []);
        $stored_message = (array) \Arr::get($stored, 'message', []);

        \Log::info('Respuesta de conversacion encolada. conversation_id='.$conversation_id.' queue_count='.(int) $queue['queued']);

        return [
            'success' => true,
            'message' => 'Respuesta encolada correctamente.',
            'status' => 200,
            'data' => [
                'conversation_id' => $conversation_id,
                'message_id' => (int) \Arr::get($stored_message, 'id', 0),
                'queue_ids' => $queue['queue_ids'],
                'queued' => (int) $queue['queued'],
                'attachments' => $attachments['data']['public'],
            ],
            'errors' => [],
        ];
    }

    protected function validated_sender_account($user_id, $account_id)
    {
        if ($account_id <= 0) {
            return $this->error('Selecciona una cuenta de correo para enviar.', ['account_required'], 422);
        }
        if (!$this->access->can_send_from_account((int) $user_id, (int) $account_id)) {
            return $this->error('No tienes permiso para enviar desde esta cuenta.', ['send_permission_denied'], 403);
        }

        $account = \DB::select('id', 'code', 'name', 'email_address', 'provider_code', 'smtp_provider_code')
            ->from('core_communication_accounts')
            ->where('id', '=', (int) $account_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$account || !filter_var((string) \Arr::get($account, 'email_address', ''), FILTER_VALIDATE_EMAIL)) {
            return $this->error('La cuenta de envio no esta activa o no tiene correo valido.', ['invalid_sender_account'], 422);
        }

        return [
            'success' => true,
            'message' => '',
            'status' => 200,
            'data' => ['account' => $account],
            'errors' => [],
        ];
    }

    protected function queue_messages($provider_code, array $account, array $to, array $cc, array $bcc, $subject, $body_text, $body_html, array $meta)
    {
        $queue_ids = [];
        $errors = [];
        $body = trim((string) $body_html) !== '' ? $body_html : nl2br(e($body_text));
        foreach ($to as $index => $recipient) {
            $email = (string) \Arr::get($recipient, 'email', '');
            $result = $this->email_manager->queue([
                'event_code' => (string) \Arr::get($meta, 'event_code', 'communications.outgoing'),
                'template_code' => '',
                'email_role' => 'communications',
                'provider_code' => $provider_code,
                'to_email' => $email,
                'to_name' => (string) \Arr::get($recipient, 'name', ''),
                'subject' => $subject,
                'body' => $body,
                'priority' => 'normal',
                'payload' => $this->safe_payload([
                    'communication_account_id' => (int) \Arr::get($account, 'id', 0),
                    'from_email' => (string) \Arr::get($account, 'email_address', ''),
                    'from_name' => (string) \Arr::get($account, 'name', ''),
                    'cc' => (int) $index === 0 ? $cc : [],
                    'bcc' => (int) $index === 0 ? $bcc : [],
                    'related_entity_type' => \Arr::get($meta, 'related_entity_type', ''),
                    'related_entity_id' => (int) \Arr::get($meta, 'related_entity_id', 0),
                    'related_party_id' => (int) \Arr::get($meta, 'related_party_id', 0),
                    'conversation_id' => (int) \Arr::get($meta, 'conversation_id', 0),
                    'in_reply_to' => (string) \Arr::get($meta, 'in_reply_to', ''),
                    'attachments' => (int) $index === 0 ? (array) \Arr::get($meta, 'attachments', []) : [],
                ]),
            ]);

            if (!empty($result['success'])) {
                $queue_ids[] = (int) \Arr::get($result, 'queue_id', 0);
            } else {
                $errors[] = (string) \Arr::get($result, 'message', 'No se pudo encolar correo.');
            }
        }

        if (!empty($errors)) {
            return $this->error('No se pudieron encolar todos los correos.', $errors, 400);
        }

        return [
            'success' => true,
            'message' => 'Correos encolados.',
            'status' => 200,
            'queued' => count($queue_ids),
            'queue_ids' => $queue_ids,
            'first_queue_id' => empty($queue_ids) ? 0 : (int) $queue_ids[0],
            'errors' => [],
        ];
    }

    protected function store_outgoing(array $account, array $message)
    {
        $data = array_merge($message, [
            'account_id' => (int) \Arr::get($account, 'id', 0),
            'channel_code' => 'email',
            'message_type' => 'email',
            'direction' => 'outgoing',
            'external_message_id' => $this->local_message_id(),
            'from_email' => (string) \Arr::get($account, 'email_address', ''),
            'from_name' => (string) \Arr::get($account, 'name', ''),
            'sent_at' => 0,
            'status' => 'queued',
            'attachments' => (array) \Arr::get($message, 'attachments', []),
        ]);

        return $this->store->store_outgoing($data);
    }

    protected function prepare_attachments(array $files)
    {
        $items = $this->normalize_uploaded_files($files);
        if (empty($items)) {
            return [
                'success' => true,
                'message' => '',
                'status' => 200,
                'data' => ['attachments' => [], 'public' => []],
                'errors' => [],
            ];
        }

        if (count($items) > 10) {
            return $this->error('Solo se permiten hasta 10 adjuntos por mensaje.', ['attachments_limit_exceeded'], 422);
        }

        if (!\DBUtil::table_exists('core_communication_message_attachments')) {
            return $this->error('Falta preparar la tabla de metadatos de adjuntos.', ['attachment_metadata_table_missing'], 500);
        }

        $attachments = [];
        $public = [];
        $errors = [];
        foreach ($items as $file) {
            $validated = $this->validate_attachment($file);
            if (!empty($validated['errors'])) {
                $errors = array_merge($errors, $validated['errors']);
                continue;
            }
            if (empty($validated['attachment'])) {
                continue;
            }

            $attachments[] = $validated['attachment'];
            $public[] = [
                'filename' => $validated['attachment']['filename'],
                'mime_type' => $validated['attachment']['mime_type'],
                'size_bytes' => $validated['attachment']['size_bytes'],
                'disposition' => $validated['attachment']['disposition'],
            ];
        }

        if (!empty($errors)) {
            return $this->error('Revisa los adjuntos antes de enviar.', $errors, 422);
        }

        return [
            'success' => true,
            'message' => '',
            'status' => 200,
            'data' => ['attachments' => $attachments, 'public' => $public],
            'errors' => [],
        ];
    }

    protected function normalize_uploaded_files(array $files)
    {
        if (empty($files)) {
            return [];
        }

        if (isset($files['name']) && is_array($files['name'])) {
            $items = [];
            foreach ($files['name'] as $index => $name) {
                $items[] = [
                    'name' => $name,
                    'type' => isset($files['type'][$index]) ? $files['type'][$index] : '',
                    'tmp_name' => isset($files['tmp_name'][$index]) ? $files['tmp_name'][$index] : '',
                    'error' => isset($files['error'][$index]) ? $files['error'][$index] : UPLOAD_ERR_NO_FILE,
                    'size' => isset($files['size'][$index]) ? $files['size'][$index] : 0,
                ];
            }
            return $items;
        }

        if (isset($files['name'])) {
            return [$files];
        }

        $items = [];
        foreach ($files as $file) {
            if (is_array($file)) {
                $items = array_merge($items, $this->normalize_uploaded_files($file));
            }
        }

        return $items;
    }

    protected function validate_attachment(array $file)
    {
        $original = trim((string) \Arr::get($file, 'name', ''));
        $error = (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE || $original === '') {
            return ['errors' => []];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return ['errors' => ['No se pudo recibir el archivo '.$this->safe_text($original, 120).'.']];
        }

        $size = (int) \Arr::get($file, 'size', 0);
        $max_size = 5 * 1024 * 1024;
        if ($size <= 0 || $size > $max_size) {
            return ['errors' => ['El archivo '.$this->safe_text($original, 120).' supera el limite de 5 MB o esta vacio.']];
        }

        $tmp = (string) \Arr::get($file, 'tmp_name', '');
        if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
            return ['errors' => ['El archivo '.$this->safe_text($original, 120).' no pudo validarse.']];
        }

        $filename = $this->safe_filename($original);
        if ($filename === '') {
            return ['errors' => ['Nombre de archivo invalido.']];
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $blocked = ['php', 'phtml', 'phar', 'js', 'mjs', 'html', 'htm', 'svg', 'exe', 'bat', 'cmd', 'com', 'msi', 'sh', 'ps1', 'jar'];
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx'];
        if (in_array($extension, $blocked, true) || !in_array($extension, $allowed, true)) {
            return ['errors' => ['Tipo de archivo no permitido: '.$filename.'.']];
        }

        $mime = $this->detect_mime($tmp, (string) \Arr::get($file, 'type', ''));
        $blocked_mimes = ['text/html', 'application/javascript', 'text/javascript', 'application/x-php', 'image/svg+xml', 'application/x-msdownload'];
        if (in_array(strtolower($mime), $blocked_mimes, true)) {
            return ['errors' => ['Contenido de archivo no permitido: '.$filename.'.']];
        }

        $allowed_mimes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'txt' => ['text/plain'],
            'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
            'doc' => ['application/msword', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        ];
        if (!in_array(strtolower($mime), (array) \Arr::get($allowed_mimes, $extension, []), true)) {
            return ['errors' => ['El tipo MIME no coincide con la extension de '.$filename.'.']];
        }

        $hash = hash_file('sha256', $tmp);
        $storage_ref = 'out_att_'.substr($hash, 0, 24).'_'.substr(sha1($filename.microtime(true)), 0, 10).'.'.$extension;

        return [
            'errors' => [],
            'attachment' => [
                'filename' => $filename,
                'mime_type' => $mime,
                'size_bytes' => $size,
                'storage_ref' => $storage_ref,
                'content_hash' => $hash,
                'disposition' => 'attachment',
            ],
        ];
    }

    protected function detect_mime($tmp, $fallback)
    {
        $mime = '';
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($tmp);
        }

        if ($mime === '') {
            $mime = trim((string) $fallback);
        }

        return strtolower($mime);
    }

    protected function safe_filename($filename)
    {
        $filename = basename((string) $filename);
        $filename = preg_replace('/[^\w\.\-]+/u', '_', $filename);
        $filename = trim($filename, '._- ');
        if ($filename === '' || strpos($filename, '..') !== false || preg_match('/[\\\\\/:]/', $filename)) {
            return '';
        }

        return substr($filename, 0, 160);
    }

    protected function last_incoming_message($conversation_id, $account_id)
    {
        $query = \DB::select()
            ->from('core_communication_messages')
            ->where('conversation_id', '=', (int) $conversation_id)
            ->where('direction', '=', 'incoming')
            ->where('active', '=', 1);

        if ((int) $account_id > 0) {
            $query->where('account_id', '=', (int) $account_id);
        }

        $row = $query
            ->order_by(\DB::expr('COALESCE(received_at, created_at)'), 'desc')
            ->order_by('id', 'desc')
            ->limit(1)
            ->execute()
            ->current();

        return $row ?: [];
    }

    protected function reply_recipients(array $message, array $account)
    {
        $sender = strtolower(trim((string) \Arr::get($message, 'from_email', '')));
        $account_email = strtolower(trim((string) \Arr::get($account, 'email_address', '')));
        if ($sender !== '' && $sender !== $account_email && filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            return [[
                'email' => $sender,
                'name' => (string) \Arr::get($message, 'from_name', ''),
            ]];
        }

        $items = json_decode((string) \Arr::get($message, 'to_json', ''), true);
        $items = is_array($items) ? $items : [];
        $resolved = [];
        foreach ($items as $item) {
            $email = strtolower(trim((string) \Arr::get((array) $item, 'email', '')));
            if ($email !== '' && $email !== $account_email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $resolved[] = [
                    'email' => $email,
                    'name' => (string) \Arr::get((array) $item, 'name', ''),
                ];
            }
        }

        return $resolved;
    }

    protected function normalize_recipients($raw)
    {
        $values = [];
        if (is_array($raw)) {
            $is_assoc = array_keys($raw) !== range(0, count($raw) - 1);
            $values = $is_assoc ? [$raw] : $raw;
        } else {
            $values = preg_split('/[;,]+/', (string) $raw);
        }

        $items = [];
        $errors = [];
        foreach ($values as $value) {
            $name = '';
            $email = '';
            if (is_array($value)) {
                $name = trim((string) \Arr::get($value, 'name', ''));
                $email = trim((string) \Arr::get($value, 'email', ''));
            } else {
                $email = trim((string) $value);
            }

            if ($email === '') {
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Correo invalido: '.$email;
                continue;
            }

            $key = strtolower($email);
            if (!isset($items[$key])) {
                $items[$key] = [
                    'email' => $key,
                    'name' => $this->safe_text($name, 180),
                ];
            }
        }

        return [
            'items' => array_values($items),
            'errors' => $errors,
        ];
    }

    protected function provider_code(array $account)
    {
        $provider = trim((string) \Arr::get($account, 'smtp_provider_code', ''));
        if ($provider === '') {
            $provider = trim((string) \Arr::get($account, 'provider_code', ''));
        }
        return $provider === '' ? 'disabled_default' : $this->safe_key($provider, 'disabled_default');
    }

    protected function reply_subject($subject)
    {
        $subject = trim((string) $subject);
        if ($subject === '') {
            return 'Re: (Sin asunto)';
        }
        return preg_match('/^\s*re\s*:/i', $subject) ? $subject : 'Re: '.$subject;
    }

    protected function sanitize_html($html)
    {
        $html = (string) $html;
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/<\?|\?>/i', '', $html);
        $html = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $html);
        $html = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $html);
        return $html;
    }

    protected function safe_payload(array $payload)
    {
        foreach ($payload as $key => $value) {
            if (preg_match('/(password|token|secret|api[_-]?key|file_path|storage_path)/i', (string) $key)) {
                unset($payload[$key]);
                continue;
            }
            if (is_array($value)) {
                $payload[$key] = $this->safe_payload($value);
            } elseif (is_string($value)) {
                $payload[$key] = $this->safe_text($value, 500);
            }
        }
        return $payload;
    }

    protected function safe_text($value, $length = 255)
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        $value = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        return substr(trim($value), 0, (int) $length);
    }

    protected function safe_key($value, $default = '')
    {
        $value = substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $value), 0, 80);
        return $value === '' ? $default : $value;
    }

    protected function local_message_id()
    {
        return '<core-app-'.sha1(uniqid('', true).mt_rand()).'@local>';
    }

    protected function message_store_ready()
    {
        foreach (['core_communication_conversations', 'core_communication_messages'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                return false;
            }
        }

        return true;
    }

    protected function error($message, array $errors, $status)
    {
        return [
            'success' => false,
            'message' => $message,
            'status' => (int) $status,
            'data' => [],
            'errors' => $errors,
        ];
    }
}
