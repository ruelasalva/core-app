<?php

/**
 * HELPER CORE_NOTIFICATION
 *
 * Centraliza la creacion, lectura y marcado de notificaciones internas.
 */
class Helper_Core_Notification
{
    /**
     * CREATE
     *
     * CREA UNA NOTIFICACION Y SUS DESTINATARIOS
     *
     * @access  public
     * @return  Model_Core_Notification|null
     */
    public static function create(array $data, array $user_ids)
    {
        # SI NO HAY USUARIOS, NO SE CREA NOTIFICACION
        if (empty($user_ids)) {
            return null;
        }

        # SE NORMALIZAN LOS DESTINATARIOS
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids))));
        if (empty($user_ids)) {
            return null;
        }

        # SE CREA LA NOTIFICACION MAESTRA
        $notification = Model_Core_Notification::forge([
            'event_code' => isset($data['event_code']) ? $data['event_code'] : '',
            'notification_type' => isset($data['notification_type']) ? $data['notification_type'] : 'event',
            'title' => isset($data['title']) ? $data['title'] : '',
            'message' => isset($data['message']) ? $data['message'] : '',
            'url' => isset($data['url']) ? $data['url'] : '',
            'icon' => isset($data['icon']) ? $data['icon'] : 'bi bi-bell',
            'priority' => isset($data['priority']) ? (int) $data['priority'] : 1,
            'payload_json' => !empty($data['payload']) ? json_encode($data['payload']) : null,
            'created_by' => isset($data['created_by']) ? (int) $data['created_by'] : null,
            'active' => 1,
        ]);
        $notification->save();

        # SE CREAN LOS DESTINATARIOS
        foreach ($user_ids as $user_id) {
            Model_Core_Notification_Recipient::forge([
                'notification_id' => (int) $notification->id,
                'user_id' => $user_id,
                'status' => 'unread',
            ])->save();
        }

        return $notification;
    }

    /**
     * UNREAD FOR USER
     *
     * OBTIENE LAS NOTIFICACIONES NO LEIDAS DE UN USUARIO
     *
     * @access  public
     * @return  Array
     */
    public static function unread_for_user($user_id, $limit = 10)
    {
        # SE CONSULTAN DESTINATARIOS NO LEIDOS
        $rows = Model_Core_Notification_Recipient::query()
            ->related('notification')
            ->where('user_id', (int) $user_id)
            ->where('status', 'unread')
            ->order_by('created_at', 'desc')
            ->limit((int) $limit)
            ->get();

        # SE FORMATEA LA RESPUESTA
        $items = [];
        foreach ($rows as $row) {
            if (!$row->notification) {
                continue;
            }

            $items[] = [
                'recipient_id' => (int) $row->id,
                'id' => (int) $row->notification->id,
                'title' => (string) $row->notification->title,
                'message' => (string) $row->notification->message,
                'url' => (string) $row->notification->url,
                'icon' => (string) $row->notification->icon,
                'priority' => (int) $row->notification->priority,
                'created_at' => $row->notification->created_at ? date('d/m/Y H:i', $row->notification->created_at) : '',
            ];
        }

        return $items;
    }

    /**
     * UNREAD COUNT
     *
     * CUENTA NOTIFICACIONES NO LEIDAS DE UN USUARIO
     *
     * @access  public
     * @return  Int
     */
    public static function unread_count($user_id)
    {
        return (int) Model_Core_Notification_Recipient::query()
            ->where('user_id', (int) $user_id)
            ->where('status', 'unread')
            ->count();
    }

    /**
     * MARK READ
     *
     * MARCA UNA NOTIFICACION COMO LEIDA
     *
     * @access  public
     * @return  Bool
     */
    public static function mark_read($recipient_id, $user_id)
    {
        # SE BUSCA EL DESTINATARIO
        $recipient = Model_Core_Notification_Recipient::query()
            ->where('id', (int) $recipient_id)
            ->where('user_id', (int) $user_id)
            ->get_one();

        if (!$recipient) {
            return false;
        }

        # SE ACTUALIZA EL ESTADO
        $recipient->status = 'read';
        $recipient->read_at = time();
        $recipient->save();

        return true;
    }
}
