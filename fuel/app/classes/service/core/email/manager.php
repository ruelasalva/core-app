<?php

class Service_Core_Email_Manager
{
    protected $template_renderer;
    protected $layout_renderer;

    public function __construct(
        Service_Core_Email_TemplateRenderer $template_renderer = null,
        Service_Core_Email_LayoutRenderer $layout_renderer = null
    ) {
        $this->template_renderer = $template_renderer ?: new Service_Core_Email_TemplateRenderer();
        $this->layout_renderer = $layout_renderer ?: new Service_Core_Email_LayoutRenderer();
    }

    public function queue(array $message)
    {
        $to_email = trim((string) \Arr::get($message, 'to_email', ''));
        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Correo destinatario invalido.',
                'queue_id' => 0,
            ];
        }

        $provider = $this->resolve_provider((string) \Arr::get($message, 'provider_code', ''));
        $provider_code = $provider ? (string) $provider->code : 'disabled_default';
        $simulation = $provider ? (int) $provider->simulation_mode : 1;

        $queue = Model_Core_Email_Queue::forge([
            'event_code' => (string) \Arr::get($message, 'event_code', 'manual.test'),
            'template_code' => (string) \Arr::get($message, 'template_code', ''),
            'email_role' => (string) \Arr::get($message, 'email_role', 'system'),
            'to_email' => $to_email,
            'to_name' => (string) \Arr::get($message, 'to_name', ''),
            'subject' => (string) \Arr::get($message, 'subject', 'Prueba de comunicaciones'),
            'body' => (string) \Arr::get($message, 'body', ''),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => (int) \Arr::get($message, 'max_attempts', 3),
            'last_error' => null,
            'scheduled_at' => (int) \Arr::get($message, 'scheduled_at', time()),
            'sent_at' => null,
            'provider_code' => $provider_code,
            'channel_code' => (string) \Arr::get($message, 'channel_code', 'email'),
            'priority' => (string) \Arr::get($message, 'priority', 'normal'),
            'locked_at' => 0,
            'locked_by' => '',
            'next_retry_at' => 0,
            'provider_message_id' => '',
            'payload_json' => json_encode((array) \Arr::get($message, 'payload', [])),
            'error_json' => null,
            'simulation_mode' => $simulation,
        ]);
        $queue->save();

        return [
            'success' => true,
            'message' => 'Correo agregado a la cola.',
            'queue_id' => (int) $queue->id,
        ];
    }

    public function queue_from_event(Model_Core_Notification_Event $event, array $payload = [], array $user_ids = [], array $meta = [])
    {
        $template = Model_Core_Email_Template::query()
            ->where('code', $event->email_template_code)
            ->where('active', 1)
            ->get_one();

        if (!$template) {
            return [
                'success' => false,
                'message' => 'No existe plantilla activa para el evento.',
                'queued' => 0,
            ];
        }

        $queued = 0;
        $queue_ids = [];
        $errors = [];
        $manual_emails = (array) \Arr::get($meta, 'manual_emails', []);
        foreach ($this->resolve_users($user_ids) as $user) {
            $rendered = $this->render($template, $payload);
            $result = $this->queue([
                'event_code' => $event->code,
                'template_code' => $template->code,
                'email_role' => $event->email_role ?: $template->email_role,
                'to_email' => $user['email'],
                'to_name' => $user['name'],
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'payload' => $payload,
            ]);

            if (!empty($result['success'])) {
                $queued++;
                if (!empty($result['queue_id'])) {
                    $queue_ids[] = (int) $result['queue_id'];
                }
            } else {
                $errors[] = $result['message'];
            }
        }

        foreach ($manual_emails as $email) {
            $email = strtolower(trim((string) $email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $rendered = $this->render($template, $payload);
            $result = $this->queue([
                'event_code' => $event->code,
                'template_code' => $template->code,
                'email_role' => $event->email_role ?: $template->email_role,
                'to_email' => $email,
                'to_name' => '',
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'payload' => $payload,
            ]);

            if (!empty($result['success'])) {
                $queued++;
                if (!empty($result['queue_id'])) {
                    $queue_ids[] = (int) $result['queue_id'];
                }
            } else {
                $errors[] = $result['message'];
            }
        }

        return [
            'success' => $queued > 0 || empty($errors),
            'message' => 'Correos preparados: '.$queued,
            'queued' => $queued,
            'queue_ids' => $queue_ids,
            'errors' => $errors,
        ];
    }

    public function render(Model_Core_Email_Template $template, array $variables = [], $layout_code = 'base_erp_email')
    {
        $subject = $this->template_renderer->render((string) $template->subject, $variables);
        $body = $this->template_renderer->render((string) $template->content, $variables);
        $body = $this->layout_renderer->render_html($layout_code, $body, [
            'subject' => $subject,
        ]);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    public function test_send($provider_code, $to_email, $subject, $message)
    {
        $provider = $this->resolve_provider($provider_code);
        $result = $this->queue([
            'event_code' => 'system.test',
            'template_code' => 'queue_test',
            'email_role' => 'system',
            'provider_code' => $provider_code,
            'to_email' => $to_email,
            'subject' => $subject,
            'body' => $message,
            'priority' => 'normal',
        ]);

        if (!empty($result['success'])) {
            $processor = new Service_Core_Email_QueueProcessor();
            $processed = $processor->process(1, $provider_code);
            $result['processed'] = $processed;

            if (!empty($processed['failed'])) {
                $result['success'] = false;
                $result['message'] = 'La prueba se encolo, pero el envio fallo.';
                $result['errors'] = (array) \Arr::get($processed, 'errors', []);
            } elseif (!empty($processed['simulated'])) {
                $result['message'] = 'Prueba simulada correctamente.';
            } elseif (!empty($processed['sent'])) {
                $result['message'] = 'Prueba enviada correctamente.';
            }
        }

        if ($provider) {
            $provider->last_test_at = time();
            $provider->last_test_status = !empty($result['success']) ? 'success' : 'error';
            $provider->save();
        }

        return $result;
    }

    public function resolve_provider($provider_code = '')
    {
        $provider_code = trim((string) $provider_code);
        if ($provider_code !== '') {
            $provider = Model_Core_Communication_Provider::active_by_code($provider_code);
            if ($provider) {
                return $provider;
            }
        }

        return Model_Core_Communication_Provider::active_by_code('disabled_default');
    }

    public function resolve_role($role_code = 'system')
    {
        return Model_Core_Email_Role::query()
            ->where('code', trim((string) $role_code))
            ->where('active', 1)
            ->get_one();
    }

    protected function resolve_users(array $user_ids)
    {
        $users = [];
        foreach (array_unique(array_filter(array_map('intval', $user_ids))) as $user_id) {
            $user = \Auth\Model\Auth_User::find($user_id);
            if (!$user || empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $users[] = [
                'id' => (int) $user_id,
                'email' => (string) $user->email,
                'name' => (string) $user->username,
            ];
        }

        return $users;
    }
}
