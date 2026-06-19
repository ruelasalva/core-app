<?php

/**
 * Administra conversaciones de comunicaciones sin ejecutar automatizaciones.
 */
class Service_Core_Communications_ConversationManager
{
    public function find_or_create_conversation(array $message)
    {
        $subject = trim((string) \Arr::get($message, 'subject', ''));
        $normalized = $this->normalize_subject($subject);
        $channel = trim((string) \Arr::get($message, 'channel_code', 'email')) ?: 'email';
        $thread = trim((string) \Arr::get($message, 'external_thread_id', ''));
        $entity_type = trim((string) \Arr::get($message, 'related_entity_type', ''));
        $entity_id = (int) \Arr::get($message, 'related_entity_id', 0);
        $party_id = (int) \Arr::get($message, 'related_party_id', 0);

        $query = Model_Core_Communication_Conversation::query()
            ->where('active', 1)
            ->where('channel_code', $channel);

        if ($thread !== '') {
            $conversation = $query->where('code', $this->conversation_code($channel, $thread))->get_one();
            if ($conversation) {
                return $conversation;
            }
        }

        if ($entity_type !== '' && $entity_id > 0) {
            $conversation = Model_Core_Communication_Conversation::query()
                ->where('active', 1)
                ->where('channel_code', $channel)
                ->where('related_entity_type', $entity_type)
                ->where('related_entity_id', $entity_id)
                ->get_one();
            if ($conversation) {
                return $conversation;
            }
        }

        $conversation = new Model_Core_Communication_Conversation();
        $conversation->code = $thread !== '' ? $this->conversation_code($channel, $thread) : $this->conversation_code($channel, uniqid('conv_', true));
        $conversation->channel_code = $channel;
        $conversation->subject = $subject;
        $conversation->normalized_subject = $normalized;
        $conversation->direction = $this->allowed((string) \Arr::get($message, 'direction', 'incoming'), ['incoming', 'outgoing', 'internal', 'draft', 'trash'], 'incoming');
        $conversation->status = 'open';
        $conversation->priority = max(1, min(5, (int) \Arr::get($message, 'priority', 1)));
        $conversation->owner_user_id = (int) \Arr::get($message, 'owner_user_id', 0);
        $conversation->assigned_user_id = (int) \Arr::get($message, 'assigned_user_id', 0);
        $conversation->assigned_group_id = (int) \Arr::get($message, 'assigned_group_id', 0);
        $conversation->related_entity_type = $entity_type;
        $conversation->related_entity_id = $entity_id;
        $conversation->related_party_id = $party_id;
        $conversation->last_message_at = (int) \Arr::get($message, 'received_at', 0) ?: (int) \Arr::get($message, 'sent_at', 0) ?: time();
        $conversation->message_count = 0;
        $conversation->unread_count = 0;
        $conversation->active = 1;
        $conversation->save();

        return $conversation;
    }

    public function update_counters($conversation_id)
    {
        $conversation = Model_Core_Communication_Conversation::find((int) $conversation_id);
        if (!$conversation) {
            return false;
        }

        $messages = \DB::select('id', 'direction', 'status', 'received_at', 'sent_at', 'created_at')
            ->from('core_communication_messages')
            ->where('conversation_id', '=', (int) $conversation_id)
            ->where('active', '=', 1)
            ->execute()
            ->as_array();

        $last = 0;
        $unread = 0;
        foreach ($messages as $message) {
            $last = max($last, (int) $message['received_at'], (int) $message['sent_at'], (int) $message['created_at']);
            if ((string) $message['direction'] === 'incoming' && (string) $message['status'] === 'new') {
                $unread++;
            }
        }

        $conversation->message_count = count($messages);
        $conversation->unread_count = $unread;
        $conversation->last_message_at = $last;
        $conversation->save();

        return true;
    }

    public function link_conversation_to_entity($conversation_id, $entity_type, $entity_id, $relation_type)
    {
        $conversation = Model_Core_Communication_Conversation::find((int) $conversation_id);
        if (!$conversation) {
            return false;
        }

        $conversation->related_entity_type = $this->safe_key($entity_type);
        $conversation->related_entity_id = (int) $entity_id;
        $conversation->save();

        return true;
    }

    public function assign($conversation_id, $user_id = null, $group_id = null)
    {
        $conversation = Model_Core_Communication_Conversation::find((int) $conversation_id);
        if (!$conversation) {
            return false;
        }

        $conversation->assigned_user_id = (int) $user_id;
        $conversation->assigned_group_id = (int) $group_id;
        $conversation->save();

        return true;
    }

    public function close($conversation_id)
    {
        return $this->set_status($conversation_id, 'closed');
    }

    public function reopen($conversation_id)
    {
        return $this->set_status($conversation_id, 'open');
    }

    protected function set_status($conversation_id, $status)
    {
        $conversation = Model_Core_Communication_Conversation::find((int) $conversation_id);
        if (!$conversation) {
            return false;
        }

        $conversation->status = $status;
        $conversation->save();

        return true;
    }

    protected function conversation_code($channel, $seed)
    {
        return substr($this->safe_key($channel).'_'.sha1((string) $seed), 0, 80);
    }

    protected function normalize_subject($subject)
    {
        $subject = trim((string) $subject);
        $subject = preg_replace('/^\s*(re|fw|fwd)\s*:\s*/i', '', $subject);
        $subject = preg_replace('/\s+/', ' ', $subject);
        return mb_strtolower(trim($subject), 'UTF-8');
    }

    protected function safe_key($value)
    {
        return substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $value), 0, 80);
    }

    protected function allowed($value, array $allowed, $default)
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
