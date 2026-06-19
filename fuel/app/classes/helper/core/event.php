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
     * @return  Array
     */
    public static function fire($event_code, array $payload = [], array $user_ids = [], array $meta = [])
    {
        $event_code = trim((string) $event_code);
        $payload = self::safe_payload($payload);
        $meta = self::safe_meta($meta);
        $result = self::empty_result($event_code);

        try {
            if (class_exists('Service_Core_Communications_Dispatcher')) {
                $dispatcher = new Service_Core_Communications_Dispatcher();
                $dispatch_result = $dispatcher->fire($event_code, $payload, $user_ids, $meta);
                if (!empty($dispatch_result['success'])) {
                    return self::normalize_result($event_code, $dispatch_result);
                }

                \Log::warning('Event Bus dispatcher no proceso evento '.$event_code.': '.json_encode((array) \Arr::get($dispatch_result, 'errors', [])));
                if ((string) \Arr::get($dispatch_result, 'message', '') !== 'Evento no configurado.') {
                    return self::normalize_result($event_code, $dispatch_result);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Event Bus dispatcher fallo evento '.$event_code.': '.$e->getMessage());
            $result['errors'][] = 'Dispatcher no disponible.';
        }

        # SE BUSCA LA CONFIGURACION DEL EVENTO
        $event = Model_Core_Notification_Event::active_by_code($event_code);
        if (!$event) {
            \Log::warning('Evento no configurado: '.$event_code);
            $result['success'] = false;
            $result['errors'][] = 'Evento no configurado.';
            return $result;
        }

        # SE PARSEAN TEXTOS
        $title = self::parse((string) $event->title_template, $payload);
        $message = self::parse((string) $event->message_template, $payload);
        $url = self::parse((string) $event->url_template, $payload);

        # SE CREA NOTIFICACION INTERNA SI APLICA
        if ((int) $event->notify_internal === 1 && empty($meta['skip_internal_notification'])) {
            $notification = Helper_Core_Notification::create([
                'event_code' => $event_code,
                'title' => $title ?: $event->name,
                'message' => $message,
                'url' => $url,
                'icon' => $event->icon ?: 'bi bi-bell',
                'priority' => (int) $event->priority,
                'payload' => $payload,
                'created_by' => isset($meta['created_by']) ? $meta['created_by'] : null,
            ], $user_ids);
            $result['channels']['internal'] = [
                'success' => (bool) $notification,
                'notification_id' => $notification ? (int) $notification->id : 0,
            ];
            $result['notified'] = $notification ? 1 : 0;
        } elseif ((int) $event->notify_internal === 1) {
            $result['channels']['internal'] = [
                'success' => true,
                'skipped' => true,
                'message' => 'Notificacion interna omitida por compatibilidad legacy.',
            ];
        }

        # SE PREPARA CORREO SI APLICA
        if ((int) $event->notify_email === 1) {
            self::queue_email($event, $payload, $user_ids);
            $result['channels']['email'] = [
                'success' => true,
                'queued' => 0,
            ];
        }

        $result['success'] = empty($result['errors']);
        return $result;
    }

    public static function empty_result($event_code)
    {
        return [
            'success' => true,
            'event_code' => trim((string) $event_code),
            'channels' => [],
            'queued' => 0,
            'notified' => 0,
            'errors' => [],
        ];
    }

    public static function normalize_result($event_code, array $result)
    {
        $safe = self::empty_result($event_code);
        $safe['success'] = !empty($result['success']);
        $safe['channels'] = (array) \Arr::get($result, 'channels', []);
        $safe['errors'] = (array) \Arr::get($result, 'errors', []);

        foreach ($safe['channels'] as $channel) {
            if (isset($channel['queued'])) {
                $safe['queued'] += (int) $channel['queued'];
            }
            if (!empty($channel['queue_id'])) {
                $safe['queued']++;
            }
            if (!empty($channel['notification_id'])) {
                $safe['notified']++;
            }
        }

        return $safe;
    }

    public static function safe_payload(array $payload)
    {
        return self::sanitize_event_data($payload);
    }

    protected static function safe_meta(array $meta)
    {
        return self::sanitize_event_data($meta);
    }

    protected static function sanitize_event_data(array $data)
    {
        $safe = [];
        foreach ($data as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', (string) $key);
            if ($key === '' || self::is_forbidden_key($key)) {
                continue;
            }

            if (is_array($value)) {
                $safe[$key] = self::sanitize_event_data($value);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $safe[$key] = self::safe_scalar_value($key, $value);
            }
        }

        return $safe;
    }

    protected static function is_forbidden_key($key)
    {
        return (bool) preg_match('/(password|passwd|pwd|salt|token|secret|api[_-]?key|credential|certificate|certificado|private[_-]?key|file[_-]?path|storage[_-]?path|physical[_-]?path|xml|raw[_-]?sql|sql|stack[_-]?trace|trace|smtp|pac|sat[_-]?key)/i', (string) $key);
    }

    protected static function safe_scalar_value($key, $value)
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if (self::looks_like_forbidden_value($value)) {
            return '[redacted]';
        }

        if (strlen($value) > 500) {
            $value = substr($value, 0, 500);
        }

        return $value;
    }

    protected static function looks_like_forbidden_value($value)
    {
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/(<\\?xml|-----BEGIN |file_path|storage_path|password|api_key|secret|token|SELECT\\s+|INSERT\\s+|UPDATE\\s+|DELETE\\s+|STACK TRACE|#\\d+\\s+)/i', $value);
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
