<?php

/**
 * Servicio de paneles embebidos de comunicaciones.
 *
 * Resuelve conversaciones relacionadas con entidades ERP sin exponer secretos,
 * rutas fisicas ni mensajes de cuentas no asignadas al usuario actual.
 */
class Service_Core_Communications_EmbeddedPanel
{
    protected $entity_aliases = [
        'customer' => ['customer', 'party'],
        'party' => ['party', 'customer'],
        'helpdesk_ticket' => ['helpdesk_ticket'],
    ];

    public function allowed_entity_types()
    {
        return array_keys($this->entity_aliases);
    }

    public function conversations($user_id, array $filters)
    {
        $this->assert_tables_ready();

        $entity_type = $this->normalize_entity_type(\Arr::get($filters, 'entity_type', ''));
        $entity_id = (int) \Arr::get($filters, 'entity_id', 0);
        $party_id = (int) \Arr::get($filters, 'party_id', 0);
        $limit = max(1, min(50, (int) \Arr::get($filters, 'limit', 10)));

        if ($entity_type === '') {
            return [
                'success' => false,
                'message' => 'Tipo de entidad no permitido.',
                'data' => ['conversations' => [], 'total' => 0],
                'errors' => ['invalid_entity_type'],
                'status' => 422,
            ];
        }

        if ($entity_id <= 0 && $party_id <= 0) {
            return [
                'success' => false,
                'message' => 'Indica una entidad o tercero valido.',
                'data' => ['conversations' => [], 'total' => 0],
                'errors' => ['invalid_entity_id'],
                'status' => 422,
            ];
        }

        $ids = $this->candidate_conversation_ids($entity_type, $entity_id, $party_id);
        $visible = $this->visible_conversation_ids((int) $user_id, $ids);
        if (empty($visible)) {
            return [
                'success' => true,
                'message' => '',
                'data' => ['conversations' => [], 'total' => 0],
                'errors' => [],
                'status' => 200,
            ];
        }

        $rows = \DB::select(
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
            ->where('active', '=', 1)
            ->where('id', 'in', $visible)
            ->order_by('last_message_at', 'desc')
            ->order_by('id', 'desc')
            ->limit($limit)
            ->execute()
            ->as_array();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->format_conversation_row($row, (int) $user_id);
        }

        return [
            'success' => true,
            'message' => '',
            'data' => [
                'conversations' => $items,
                'total' => count($visible),
            ],
            'errors' => [],
            'status' => 200,
        ];
    }

    public function can_view_conversation($user_id, $conversation_id)
    {
        return (new Service_Core_Communications_MailboxAccess())->can_view_conversation((int) $user_id, (int) $conversation_id);
    }

    public function detail($user_id, $conversation_id)
    {
        $this->assert_tables_ready();

        $conversation_id = (int) $conversation_id;
        if ($conversation_id <= 0) {
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
            ->where('id', '=', $conversation_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$conversation) {
            return [];
        }

        $query = \DB::select(
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
            ->where('conversation_id', '=', $conversation_id)
            ->where('active', '=', 1);

        $visible_account_ids = $this->visible_account_ids((int) $user_id);
        if (is_array($visible_account_ids)) {
            if (empty($visible_account_ids)) {
                $query->where('id', '=', 0);
            } else {
                $query->where('account_id', 'in', $visible_account_ids);
            }
        }

        $messages = [];
        foreach ($query->order_by(\DB::expr('COALESCE(received_at, sent_at, created_at)'), 'asc')
            ->order_by('id', 'asc')
            ->limit(100)
            ->execute()
            ->as_array() as $message) {
            $messages[] = $this->format_message_row($message);
        }

        return [
            'conversation' => $this->format_conversation_row($conversation, (int) $user_id),
            'messages' => $messages,
        ];
    }

    protected function candidate_conversation_ids($entity_type, $entity_id, $party_id)
    {
        $aliases = $this->entity_aliases[$entity_type];
        $ids = [];

        if ($entity_id > 0 && \DBUtil::table_exists('core_communication_message_links')) {
            $rows = \DB::select('conversation_id')
                ->from('core_communication_message_links')
                ->where('active', '=', 1)
                ->where('entity_type', 'in', $aliases)
                ->where('entity_id', '=', $entity_id)
                ->group_by('conversation_id')
                ->execute()
                ->as_array();
            $ids = array_merge($ids, $this->ids_from_rows($rows, 'conversation_id'));
        }

        if ($entity_id > 0) {
            $rows = \DB::select('id')
                ->from('core_communication_conversations')
                ->where('active', '=', 1)
                ->where('related_entity_type', 'in', $aliases)
                ->where('related_entity_id', '=', $entity_id)
                ->execute()
                ->as_array();
            $ids = array_merge($ids, $this->ids_from_rows($rows, 'id'));

            $rows = \DB::select('conversation_id')
                ->from('core_communication_messages')
                ->where('active', '=', 1)
                ->where('related_entity_type', 'in', $aliases)
                ->where('related_entity_id', '=', $entity_id)
                ->group_by('conversation_id')
                ->execute()
                ->as_array();
            $ids = array_merge($ids, $this->ids_from_rows($rows, 'conversation_id'));
        }

        if ($party_id > 0) {
            $rows = \DB::select('id')
                ->from('core_communication_conversations')
                ->where('active', '=', 1)
                ->where('related_party_id', '=', $party_id)
                ->execute()
                ->as_array();
            $ids = array_merge($ids, $this->ids_from_rows($rows, 'id'));

            $rows = \DB::select('conversation_id')
                ->from('core_communication_messages')
                ->where('active', '=', 1)
                ->where('related_party_id', '=', $party_id)
                ->group_by('conversation_id')
                ->execute()
                ->as_array();
            $ids = array_merge($ids, $this->ids_from_rows($rows, 'conversation_id'));
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    protected function visible_conversation_ids($user_id, array $candidate_ids)
    {
        if (empty($candidate_ids)) {
            return [];
        }

        $access = new Service_Core_Communications_MailboxAccess();
        $visible = [];
        foreach ($candidate_ids as $id) {
            if ($access->can_view_conversation((int) $user_id, (int) $id)) {
                $visible[] = (int) $id;
            }
        }

        return array_values(array_unique($visible));
    }

    protected function format_conversation_row(array $row, $user_id)
    {
        $latest = $this->latest_message_for_conversation((int) $row['id'], (int) $user_id);
        $account = $this->account_public_summary((int) \Arr::get($latest, 'account_id', 0));

        return [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'channel_code' => (string) $row['channel_code'],
            'channel_label' => $this->channel_label((string) $row['channel_code']),
            'subject' => $this->safe_text((string) $row['subject']),
            'direction' => (string) $row['direction'],
            'status' => (string) $row['status'],
            'priority' => (int) $row['priority'],
            'related_entity_type' => (string) $row['related_entity_type'],
            'related_entity_id' => (int) $row['related_entity_id'],
            'related_party_id' => (int) $row['related_party_id'],
            'last_message_at' => (int) $row['last_message_at'],
            'message_count' => (int) $row['message_count'],
            'unread_count' => (int) $row['unread_count'],
            'participants' => $this->conversation_participants($latest),
            'snippet' => $this->safe_text((string) \Arr::get($latest, 'snippet', '')),
            'account_id' => (int) \Arr::get($latest, 'account_id', 0),
            'account_name' => (string) \Arr::get($account, 'name', ''),
            'account_email' => (string) \Arr::get($account, 'email_address', ''),
        ];
    }

    protected function format_message_row(array $row)
    {
        $account = $this->account_public_summary((int) \Arr::get($row, 'account_id', 0));

        return [
            'id' => (int) $row['id'],
            'account_id' => (int) \Arr::get($row, 'account_id', 0),
            'account_email' => (string) \Arr::get($account, 'email_address', ''),
            'account_name' => (string) \Arr::get($account, 'name', ''),
            'channel_code' => (string) $row['channel_code'],
            'direction' => (string) $row['direction'],
            'message_type' => (string) $row['message_type'],
            'from_email' => $this->safe_text((string) $row['from_email']),
            'from_name' => $this->safe_text((string) $row['from_name']),
            'to' => $this->decode_json_list(\Arr::get($row, 'to_json', '')),
            'cc' => $this->decode_json_list(\Arr::get($row, 'cc_json', '')),
            'bcc_count' => count($this->decode_json_list(\Arr::get($row, 'bcc_json', ''))),
            'subject' => $this->safe_text((string) $row['subject']),
            'body_text' => $this->safe_text((string) $row['body_text']),
            'body_html_sanitized' => $this->safe_html((string) $row['body_html_sanitized']),
            'snippet' => $this->safe_text((string) $row['snippet']),
            'date' => (int) ($row['received_at'] ?: ($row['sent_at'] ?: $row['created_at'])),
            'status' => (string) $row['status'],
            'has_attachments' => (int) $row['has_attachments'],
            'attachment_count' => (int) $row['attachment_count'],
            'attachments' => $this->message_attachments((int) $row['id']),
        ];
    }

    protected function latest_message_for_conversation($conversation_id, $user_id)
    {
        $query = \DB::select('account_id', 'from_email', 'from_name', 'to_json', 'snippet')
            ->from('core_communication_messages')
            ->where('conversation_id', '=', (int) $conversation_id)
            ->where('active', '=', 1);

        $visible_account_ids = $this->visible_account_ids((int) $user_id);
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

    protected function visible_account_ids($user_id)
    {
        $access = new Service_Core_Communications_MailboxAccess();
        if ($access->conversation_ids_for_user((int) $user_id) === null) {
            return null;
        }

        return $access->account_ids_for_user((int) $user_id);
    }

    protected function account_public_summary($account_id)
    {
        $account_id = (int) $account_id;
        if ($account_id <= 0) {
            return [];
        }

        $row = \DB::select('id', 'code', 'name', 'email_address')
            ->from('core_communication_accounts')
            ->where('id', '=', $account_id)
            ->execute()
            ->current();

        return $row ?: [];
    }

    protected function conversation_participants(array $latest)
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

    protected function ids_from_rows(array $rows, $field)
    {
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) \Arr::get($row, $field, 0);
        }

        return $ids;
    }

    protected function normalize_entity_type($type)
    {
        $type = trim((string) $type);
        return isset($this->entity_aliases[$type]) ? $type : '';
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
        $html = preg_replace('#<(script|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $html);
        $html = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $html);
        return $html;
    }

    protected function assert_tables_ready()
    {
        foreach (['core_communication_conversations', 'core_communication_messages', 'core_communication_accounts'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Tabla requerida no disponible: '.$table);
            }
        }
    }
}
