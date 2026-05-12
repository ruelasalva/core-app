<?php

/**
 * HELPER CORE_EVENT
 *
 * Orquestador basico de eventos, notificaciones internas y cola de correos.
 */
class Helper_Core_Event
{
    /**
     * FIRE
     *
     * DISPARA UN EVENTO CONFIGURADO
     *
     * @access  public
     * @return  Bool
     */
    public static function fire($event_code, array $payload = [], array $user_ids = [], array $meta = [])
    {
        # SE BUSCA LA CONFIGURACION DEL EVENTO
        $event = Model_Core_Notification_Event::active_by_code($event_code);
        if (!$event) {
            \Log::warning('Evento no configurado: '.$event_code);
            return false;
        }

        # SE PARSEAN TEXTOS
        $title = self::parse((string) $event->title_template, $payload);
        $message = self::parse((string) $event->message_template, $payload);
        $url = self::parse((string) $event->url_template, $payload);

        # SE CREA NOTIFICACION INTERNA SI APLICA
        if ((int) $event->notify_internal === 1) {
            Helper_Core_Notification::create([
                'event_code' => $event_code,
                'title' => $title ?: $event->name,
                'message' => $message,
                'url' => $url,
                'icon' => $event->icon ?: 'bi bi-bell',
                'priority' => (int) $event->priority,
                'payload' => $payload,
                'created_by' => isset($meta['created_by']) ? $meta['created_by'] : null,
            ], $user_ids);
        }

        # SE PREPARA CORREO SI APLICA
        if ((int) $event->notify_email === 1) {
            self::queue_email($event, $payload, $user_ids);
        }

        return true;
    }

    /**
     * PARSE
     *
     * REEMPLAZA PLACEHOLDERS {{key}} EN TEXTO
     *
     * @access  public
     * @return  String
     */
    public static function parse($text, array $payload)
    {
        # SE REEMPLAZAN VARIABLES SIMPLES
        foreach ($payload as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace('{{'.$key.'}}', (string) $value, $text);
            }
        }

        return $text;
    }

    /**
     * QUEUE EMAIL
     *
     * PREPARA CORREOS EN COLA PARA LOS USUARIOS DEL EVENTO
     *
     * @access  protected
     * @return  Void
     */
    protected static function queue_email(Model_Core_Notification_Event $event, array $payload, array $user_ids)
    {
        # SE BUSCA LA PLANTILLA
        $template = Model_Core_Email_Template::query()
            ->where('code', $event->email_template_code)
            ->where('active', 1)
            ->get_one();

        if (!$template) {
            return;
        }

        # SE PREPARA ASUNTO Y CUERPO
        $subject = self::parse((string) $template->subject, $payload);
        $body = self::parse((string) $template->content, $payload);

        # SE RECORREN USUARIOS DESTINATARIOS
        foreach (array_unique(array_filter(array_map('intval', $user_ids))) as $user_id) {
            $user = \Auth\Model\Auth_User::find($user_id);
            if (!$user || empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            Model_Core_Email_Queue::forge([
                'event_code' => $event->code,
                'template_code' => $template->code,
                'email_role' => $event->email_role,
                'to_email' => $user->email,
                'to_name' => $user->username,
                'subject' => $subject,
                'body' => $body,
                'status' => 'pending',
                'scheduled_at' => time(),
            ])->save();
        }
    }
}
