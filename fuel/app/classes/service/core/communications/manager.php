<?php

class Service_Core_Communications_Manager
{
    protected $email_manager;
    protected $notification_manager;

    public function __construct(
        Service_Core_Email_Manager $email_manager = null,
        Service_Core_Notifications_Manager $notification_manager = null
    ) {
        $this->email_manager = $email_manager ?: new Service_Core_Email_Manager();
        $this->notification_manager = $notification_manager ?: new Service_Core_Notifications_Manager();
    }

    public function dispatch(Model_Core_Notification_Event $event, array $payload = [], array $user_ids = [], array $meta = [])
    {
        $result = [
            'success' => true,
            'message' => 'Evento procesado.',
            'channels' => [],
            'queued' => 0,
            'notified' => 0,
            'errors' => [],
        ];

        if ((int) $event->notify_internal === 1) {
            $internal_user_ids = $user_ids;
            if (empty($internal_user_ids) && isset($meta['configured_recipients']['internal']['users'])) {
                $internal_user_ids = (array) $meta['configured_recipients']['internal']['users'];
            }

            if (!empty($meta['skip_internal_notification'])) {
                $internal = [
                    'success' => true,
                    'message' => 'Notificacion interna omitida por compatibilidad legacy.',
                    'skipped' => true,
                    'notification_id' => 0,
                    'recipients_count' => count(array_unique(array_filter(array_map('intval', $internal_user_ids)))),
                ];
            } elseif (empty($internal_user_ids)) {
                $internal = [
                    'success' => true,
                    'message' => 'No hay destinatarios internos configurados.',
                    'skipped' => true,
                    'notification_id' => 0,
                    'recipients_count' => 0,
                ];
            } else {
                $internal = $this->notification_manager->create_from_event($event, $payload, $internal_user_ids, $meta);
            }
            $result['channels']['internal'] = $internal;
            if (!empty($internal['notification_id'])) {
                $result['notified']++;
            }
            if (empty($internal['success'])) {
                $result['success'] = false;
                $result['errors'][] = $internal['message'];
            }
        }

        if ((int) $event->notify_email === 1) {
            $email_user_ids = $user_ids;
            $manual_emails = [];
            if (empty($email_user_ids) && isset($meta['configured_recipients']['email'])) {
                $email_user_ids = (array) \Arr::get($meta['configured_recipients']['email'], 'users', []);
                $manual_emails = (array) \Arr::get($meta['configured_recipients']['email'], 'emails', []);
            }

            $email = $this->email_manager->queue_from_event($event, $payload, $email_user_ids, array_merge($meta, [
                'manual_emails' => $manual_emails,
            ]));
            $result['channels']['email'] = $email;
            $result['queued'] += (int) \Arr::get($email, 'queued', 0);
            if (empty($email['success'])) {
                $result['success'] = false;
                $result['errors'][] = $email['message'];
            }
        }

        $this->trace_event($event, $payload, $user_ids, $meta, $result);

        return $result;
    }

    protected function trace_event(Model_Core_Notification_Event $event, array $payload, array $user_ids, array $meta, array $result)
    {
        $internal_recipients = $user_ids;
        if (empty($internal_recipients) && isset($meta['configured_recipients']['internal']['users'])) {
            $internal_recipients = (array) $meta['configured_recipients']['internal']['users'];
        }

        $email_recipients = $user_ids;
        $manual_emails = [];
        if (empty($email_recipients) && isset($meta['configured_recipients']['email'])) {
            $email_recipients = (array) \Arr::get($meta['configured_recipients']['email'], 'users', []);
            $manual_emails = (array) \Arr::get($meta['configured_recipients']['email'], 'emails', []);
        }

        $queue_ids = [];
        if (isset($result['channels']['email']['queue_ids'])) {
            $queue_ids = array_values(array_filter(array_map('intval', (array) $result['channels']['email']['queue_ids'])));
        }

        \Log::info('Event Bus trace: '.json_encode([
            'event_code' => (string) $event->code,
            'source_module' => (string) \Arr::get($meta, 'source_module', ''),
            'entity_type' => (string) \Arr::get($payload, 'entity_type', ''),
            'entity_id' => (int) \Arr::get($payload, 'entity_id', 0),
            'channels_attempted' => array_keys((array) \Arr::get($result, 'channels', [])),
            'internal_recipients_count' => count(array_unique(array_filter(array_map('intval', $internal_recipients)))),
            'email_recipients_count' => count(array_unique(array_filter(array_map('intval', $email_recipients)))) + count(array_unique(array_filter($manual_emails))),
            'notification_id' => (int) \Arr::get($result, 'channels.internal.notification_id', 0),
            'queue_ids' => $queue_ids,
            'queued' => (int) \Arr::get($result, 'queued', 0),
            'notified' => (int) \Arr::get($result, 'notified', 0),
        ]));
    }
}
