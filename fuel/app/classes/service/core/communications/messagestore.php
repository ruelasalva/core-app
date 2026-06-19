<?php

/**
 * Persistencia segura de mensajes de comunicacion.
 */
class Service_Core_Communications_MessageStore
{
    public function normalize_subject($subject)
    {
        $subject = trim((string) $subject);
        $subject = preg_replace('/^\s*(re|fw|fwd)\s*:\s*/i', '', $subject);
        $subject = preg_replace('/\s+/', ' ', $subject);
        return mb_strtolower(trim($subject), 'UTF-8');
    }

    public function build_snippet($text, $html = '')
    {
        $source = trim((string) $text);
        if ($source === '') {
            $source = strip_tags((string) $html);
        }

        $source = preg_replace('/\s+/', ' ', html_entity_decode($source, ENT_QUOTES, 'UTF-8'));
        return mb_substr(trim($source), 0, 240, 'UTF-8');
    }

    public function sanitize_html($html)
    {
        $html = (string) $html;
        $html = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/<\?|\?>/i', '', $html);
        return $html;
    }

    public function calculate_content_hash(array $message)
    {
        $parts = [
            trim((string) \Arr::get($message, 'external_message_id', '')),
            trim((string) \Arr::get($message, 'from_email', '')),
            trim((string) \Arr::get($message, 'subject', '')),
            trim((string) \Arr::get($message, 'body_text', '')),
            trim(strip_tags((string) \Arr::get($message, 'body_html', ''))),
        ];

        return hash('sha256', implode('|', $parts));
    }

    public function store_message(array $message)
    {
        $this->assert_ready();

        $account_id = (int) \Arr::get($message, 'account_id', 0);
        $external_id = trim((string) \Arr::get($message, 'external_message_id', ''));
        $existing = $this->find_existing_message($external_id, $account_id);
        $content_hash = $this->calculate_content_hash($message);
        if (!$existing) {
            $existing = $this->find_existing_message_by_fallback(
                $content_hash,
                $account_id,
                (int) \Arr::get($message, 'received_at', 0),
                (int) \Arr::get($message, 'sent_at', 0),
                (string) \Arr::get($message, 'from_email', ''),
                (string) \Arr::get($message, 'subject', '')
            );
        }

        if ($existing) {
            return [
                'success' => true,
                'message' => 'Mensaje existente reutilizado.',
                'data' => [
                    'message' => $this->mask_message($this->model_to_array($existing)),
                    'duplicate' => true,
                ],
                'errors' => [],
            ];
        }

        $manager = new Service_Core_Communications_ConversationManager();
        $conversation_id = (int) \Arr::get($message, 'conversation_id', 0);
        $conversation = $conversation_id > 0 ? Model_Core_Communication_Conversation::find($conversation_id) : null;
        if (!$conversation || (int) $conversation->active !== 1) {
            $conversation = $manager->find_or_create_conversation($message);
        }

        $record = new Model_Core_Communication_Message();
        $record->conversation_id = (int) $conversation->id;
        $record->account_id = $account_id;
        $record->channel_code = $this->safe_key(\Arr::get($message, 'channel_code', 'email'), 'email');
        $record->direction = $this->allowed(\Arr::get($message, 'direction', 'incoming'), ['incoming', 'outgoing', 'internal', 'draft', 'trash'], 'incoming');
        $record->message_type = $this->safe_key(\Arr::get($message, 'message_type', 'email'), 'email');
        $record->external_message_id = $external_id;
        $record->external_thread_id = substr(trim((string) \Arr::get($message, 'external_thread_id', '')), 0, 255);
        $record->in_reply_to = substr(trim((string) \Arr::get($message, 'in_reply_to', '')), 0, 255);
        $record->references_hash = $this->references_hash(\Arr::get($message, 'references', ''));
        $record->from_email = $this->safe_email(\Arr::get($message, 'from_email', ''));
        $record->from_name = substr(trim((string) \Arr::get($message, 'from_name', '')), 0, 180);
        $record->to_json = $this->safe_json(\Arr::get($message, 'to', []));
        $record->cc_json = $this->safe_json(\Arr::get($message, 'cc', []));
        $record->bcc_json = $this->safe_json(\Arr::get($message, 'bcc', []));
        $record->subject = substr(trim((string) \Arr::get($message, 'subject', '')), 0, 255);
        $record->body_text = $this->strip_sensitive((string) \Arr::get($message, 'body_text', ''));
        $record->body_html_sanitized = $this->sanitize_html($this->strip_sensitive((string) \Arr::get($message, 'body_html', '')));
        $record->snippet = $this->build_snippet($record->body_text, $record->body_html_sanitized);
        $record->received_at = (int) \Arr::get($message, 'received_at', 0);
        $record->sent_at = (int) \Arr::get($message, 'sent_at', 0);
        $record->status = $this->allowed(\Arr::get($message, 'status', 'new'), ['new', 'read', 'sent', 'queued', 'failed', 'archived', 'draft', 'trash'], 'new');
        $record->provider_code = $this->safe_key(\Arr::get($message, 'provider_code', ''), '');
        $record->queue_id = (int) \Arr::get($message, 'queue_id', 0);
        $record->related_entity_type = $this->safe_key(\Arr::get($message, 'related_entity_type', ''), '');
        $record->related_entity_id = (int) \Arr::get($message, 'related_entity_id', 0);
        $record->related_party_id = (int) \Arr::get($message, 'related_party_id', 0);
        $record->raw_headers_json = $this->safe_headers_json(\Arr::get($message, 'raw_headers', []));
        $attachments = (array) \Arr::get($message, 'attachments', []);
        $record->has_attachments = empty($attachments) ? 0 : 1;
        $record->attachment_count = count($attachments);
        $record->content_hash = $content_hash;
        $record->active = 1;
        $record->save();

        foreach ($attachments as $attachment) {
            $this->store_attachment_metadata((int) $record->id, (array) $attachment);
        }

        if ($record->related_entity_type !== '' && $record->related_entity_id > 0) {
            $this->link_message((int) $record->id, (int) $conversation->id, $record->related_entity_type, (int) $record->related_entity_id, 'related');
        }

        $manager->update_counters((int) $conversation->id);

        return [
            'success' => true,
            'message' => 'Mensaje almacenado correctamente.',
            'data' => [
                'message' => $this->mask_message($this->model_to_array($record)),
                'conversation_id' => (int) $conversation->id,
                'duplicate' => false,
            ],
            'errors' => [],
        ];
    }

    public function store_attachment_metadata($message_id, array $attachment)
    {
        $record = new Model_Core_Communication_MessageAttachment();
        $record->message_id = (int) $message_id;
        $record->filename = substr(basename((string) \Arr::get($attachment, 'filename', '')), 0, 180);
        $record->mime_type = substr(trim((string) \Arr::get($attachment, 'mime_type', '')), 0, 120);
        $record->size_bytes = max(0, (int) \Arr::get($attachment, 'size_bytes', 0));
        $record->storage_ref = $this->safe_storage_ref(\Arr::get($attachment, 'storage_ref', ''));
        $record->content_hash = substr(preg_replace('/[^a-fA-F0-9]/', '', (string) \Arr::get($attachment, 'content_hash', '')), 0, 64);
        $record->disposition = $this->allowed(\Arr::get($attachment, 'disposition', 'attachment'), ['attachment', 'inline'], 'attachment');
        $record->active = 1;
        $record->save();

        return $record;
    }

    public function link_message($message_id, $conversation_id, $entity_type, $entity_id, $relation_type)
    {
        $entity_type = $this->safe_key($entity_type, '');
        $entity_id = (int) $entity_id;
        if ((int) $message_id <= 0 || $entity_type === '' || $entity_id <= 0) {
            return false;
        }

        $existing = \DB::select('id')
            ->from('core_communication_message_links')
            ->where('message_id', '=', (int) $message_id)
            ->where('entity_type', '=', $entity_type)
            ->where('entity_id', '=', $entity_id)
            ->where('relation_type', '=', $this->safe_key($relation_type, 'related'))
            ->execute()
            ->current();

        if ($existing) {
            return (int) $existing['id'];
        }

        $link = new Model_Core_Communication_MessageLink();
        $link->message_id = (int) $message_id;
        $link->conversation_id = (int) $conversation_id;
        $link->entity_type = $entity_type;
        $link->entity_id = $entity_id;
        $link->relation_type = $this->safe_key($relation_type, 'related');
        $link->active = 1;
        $link->save();

        return (int) $link->id;
    }

    public function find_existing_message($external_message_id, $account_id)
    {
        $external_message_id = trim((string) $external_message_id);
        $account_id = (int) $account_id;
        if ($external_message_id === '' || $account_id <= 0) {
            return null;
        }

        return Model_Core_Communication_Message::query()
            ->where('external_message_id', $external_message_id)
            ->where('account_id', $account_id)
            ->where('active', 1)
            ->get_one();
    }

    public function find_existing_message_by_fallback($content_hash, $account_id, $received_at, $sent_at, $from_email, $subject)
    {
        $content_hash = trim((string) $content_hash);
        $account_id = (int) $account_id;
        if ($content_hash === '' || $account_id <= 0) {
            return null;
        }

        $query = Model_Core_Communication_Message::query()
            ->where('content_hash', $content_hash)
            ->where('account_id', $account_id)
            ->where('from_email', $this->safe_email($from_email))
            ->where('subject', substr(trim((string) $subject), 0, 255))
            ->where('active', 1);

        $timestamp = (int) $received_at ?: (int) $sent_at;
        if ($timestamp > 0) {
            $query->and_where_open()
                ->where('received_at', $timestamp)
                ->or_where('sent_at', $timestamp)
                ->and_where_close();
        }

        return $query->get_one();
    }

    public function mask_message(array $message)
    {
        foreach (['raw_source', 'password', 'token', 'secret', 'api_key', 'file_path', 'storage_path'] as $key) {
            unset($message[$key]);
        }

        if (isset($message['raw_headers_json'])) {
            $message['raw_headers_json'] = $this->safe_headers_json(json_decode((string) $message['raw_headers_json'], true) ?: []);
        }

        return $message;
    }

    public function store_outgoing(array $message, array $context = [])
    {
        $message['direction'] = 'outgoing';
        return $this->store_message(array_merge($context, $message));
    }

    public function store_incoming(array $message, array $context = [])
    {
        $message['direction'] = 'incoming';
        return $this->store_message(array_merge($context, $message));
    }

    protected function assert_ready()
    {
        foreach (['core_communication_messages', 'core_communication_conversations'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migracion de Message Store.');
            }
        }
    }

    protected function safe_json($value)
    {
        $value = is_array($value) ? $value : [];
        return json_encode($this->strip_sensitive_array($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function safe_headers_json($headers)
    {
        $headers = is_array($headers) ? $headers : [];
        foreach ($headers as $key => $value) {
            if (preg_match('/(authorization|cookie|password|token|secret|api-key|x-api-key)/i', (string) $key)) {
                unset($headers[$key]);
            }
        }

        return json_encode($headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function strip_sensitive_array(array $items)
    {
        foreach ($items as $key => $value) {
            if (preg_match('/(password|token|secret|api_key|file_path|storage_path)/i', (string) $key)) {
                unset($items[$key]);
                continue;
            }
            if (is_array($value)) {
                $items[$key] = $this->strip_sensitive_array($value);
            }
        }

        return $items;
    }

    protected function strip_sensitive($value)
    {
        $value = (string) $value;
        $value = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        $value = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        return $value;
    }

    protected function references_hash($references)
    {
        if (is_array($references)) {
            $references = implode(' ', $references);
        }

        $references = trim((string) $references);
        return $references === '' ? '' : hash('sha256', $references);
    }

    protected function safe_email($email)
    {
        $email = trim((string) $email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? substr($email, 0, 180) : '';
    }

    protected function safe_key($value, $default)
    {
        $value = substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $value), 0, 80);
        return $value === '' ? $default : $value;
    }

    protected function safe_storage_ref($value)
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/[\\\\\/:]/', $value) || preg_match('/(file_path|storage_path|DOCROOT|APPPATH)/i', $value)) {
            return '';
        }

        return substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $value), 0, 180);
    }

    protected function allowed($value, array $allowed, $default)
    {
        $value = trim((string) $value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    protected function model_to_array($model)
    {
        return $model ? $model->to_array() : [];
    }
}
