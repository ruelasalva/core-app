<?php

class Service_Core_Communications_Dispatcher
{
    protected $manager;
    protected $recipient_resolver;

    public function __construct(
        Service_Core_Communications_Manager $manager = null,
        Service_Core_Communications_RecipientResolver $recipient_resolver = null
    )
    {
        $this->manager = $manager ?: new Service_Core_Communications_Manager();
        $this->recipient_resolver = $recipient_resolver ?: new Service_Core_Communications_RecipientResolver();
    }

    public function fire($event_code, array $payload = [], array $user_ids = [], array $meta = [])
    {
        $event = Model_Core_Notification_Event::active_by_code($event_code);
        if (!$event) {
            \Log::warning('Communications Dispatcher: evento no configurado '.$event_code);
            return [
                'success' => false,
                'message' => 'Evento no configurado.',
                'errors' => ['No existe un evento activo con codigo '.$event_code],
            ];
        }

        if (empty($user_ids)) {
            $meta['configured_recipients'] = [
                'internal' => $this->recipient_resolver->resolve($event_code, 'internal'),
                'email' => $this->recipient_resolver->resolve($event_code, 'email'),
            ];
        }

        return $this->manager->dispatch($event, $payload, $user_ids, $meta);
    }
}
