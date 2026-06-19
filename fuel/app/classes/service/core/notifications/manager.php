<?php

class Service_Core_Notifications_Manager
{
    public function create_from_event(Model_Core_Notification_Event $event, array $payload = [], array $user_ids = [], array $meta = [])
    {
        $title = Helper_Core_Event::parse((string) $event->title_template, $payload);
        $message = Helper_Core_Event::parse((string) $event->message_template, $payload);
        $url = Helper_Core_Event::parse((string) $event->url_template, $payload);

        $notification = Helper_Core_Notification::create([
            'event_code' => $event->code,
            'title' => $title ?: $event->name,
            'message' => $message,
            'url' => $url,
            'icon' => $event->icon ?: 'bi bi-bell',
            'priority' => (int) $event->priority,
            'payload' => $payload,
            'created_by' => isset($meta['created_by']) ? $meta['created_by'] : null,
        ], $user_ids);

        return [
            'success' => (bool) $notification,
            'message' => $notification ? 'Notificacion interna creada.' : 'No se pudo crear la notificacion interna.',
            'notification_id' => $notification ? (int) $notification->id : 0,
            'recipients_count' => count(array_unique(array_filter(array_map('intval', $user_ids)))),
        ];
    }
}
