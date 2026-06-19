<?php
namespace Fuel\Tasks;

class Testeventbus
{
    public function run()
    {
        $event = trim((string) \Cli::option('event', 'system.test'));
        $no_dedupe_option = \Cli::option('no-dedupe', false);
        $no_dedupe = !($no_dedupe_option === false || $no_dedupe_option === null || $no_dedupe_option === '0');

        try {
            $before = $this->counts();
            $payload = $this->sample_payload($event);
            $meta = [
                'source_module' => 'diagnostics',
                'source_action' => 'testeventbus',
                'triggered_by_user_id' => 0,
            ];
            if ($event === 'helpdesk.ticket.created' && !$no_dedupe) {
                $meta['skip_internal_notification'] = 1;
                $meta['dedupe_reason'] = 'diagnostic_default_legacy_helpdesk_dedupe';
            }

            $recipients = $this->resolved_recipients($event);
            $result = \Helper_Core_Event::fire($event, $payload, [], $meta);

            $after = $this->counts();

            \Cli::write('Prueba Event Bus');
            \Cli::write('Evento: '.$event);
            \Cli::write('Dedupe interno: '.(($event === 'helpdesk.ticket.created' && !$no_dedupe) ? 'activo' : 'inactivo'));
            \Cli::write('Destinatarios internos resueltos: usuarios='.$recipients['internal_users'].' grupos='.$recipients['internal_groups']);
            \Cli::write('Destinatarios email resueltos: usuarios='.$recipients['email_users'].' correos='.$recipients['email_emails']);
            \Cli::write('Resultado: '.(!empty($result['success']) ? 'OK' : 'ERROR'));
            \Cli::write('Canales: '.implode(', ', array_keys((array) \Arr::get($result, 'channels', []))));
            \Cli::write('Notificaciones nuevas: '.max(0, $after['notifications'] - $before['notifications']));
            \Cli::write('Correos en cola nuevos: '.max(0, $after['queue'] - $before['queue']));
            \Cli::write('Resultado sanitizado: '.json_encode($this->safe_result($result), JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            \Log::error('Testeventbus: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function sample_payload($event)
    {
        $base = [
            'entity_type' => 'diagnostic_event',
            'entity_id' => 0,
            'module' => 'diagnostics',
            'source' => 'oil_task',
        ];

        if ($event === 'contact.web.message') {
            return array_merge($base, [
                'entity_type' => 'web_contact',
                'name' => 'Contacto de prueba',
                'email' => 'prueba@example.com',
                'phone' => '5555555555',
                'message_summary' => 'Mensaje de prueba seguro.',
                'origin' => 'diagnostics/testeventbus',
            ]);
        }

        if ($event === 'helpdesk.ticket.created') {
            return array_merge($base, [
                'entity_type' => 'helpdesk_ticket',
                'ticket_id' => 0,
                'folio' => 'TEST-HELPDESK',
                'ticket_folio' => 'TEST-HELPDESK',
                'subject' => 'Ticket de prueba seguro',
                'portal_code' => 'diagnostics',
                'party_id' => 0,
                'current_date' => date('Y-m-d H:i:s'),
                'admin_url' => 'admin/helpdesk',
            ]);
        }

        if ($event === 'sales.quote.created') {
            return array_merge($base, [
                'entity_type' => 'sales_quote',
                'quote_id' => 0,
                'folio' => 'TEST-QUOTE',
                'status' => 'requested',
                'currency_code' => 'MXN',
                'total' => 0,
                'admin_url' => 'admin/sales?view=quotes',
            ]);
        }

        return array_merge($base, [
            'title' => 'Prueba Event Bus',
            'message' => 'Payload seguro de diagnostico.',
        ]);
    }

    protected function counts()
    {
        return [
            'notifications' => \DBUtil::table_exists('core_notifications') ? (int) \DB::count_records('core_notifications') : 0,
            'queue' => \DBUtil::table_exists('core_email_queue') ? (int) \DB::count_records('core_email_queue') : 0,
        ];
    }

    protected function resolved_recipients($event)
    {
        if (!class_exists('Service_Core_Communications_RecipientResolver')) {
            return [
                'internal_users' => 0,
                'internal_groups' => 0,
                'email_users' => 0,
                'email_emails' => 0,
            ];
        }

        $resolver = new \Service_Core_Communications_RecipientResolver();
        $internal = $resolver->resolve($event, 'internal');
        $email = $resolver->resolve($event, 'email');

        return [
            'internal_users' => count((array) \Arr::get($internal, 'users', [])),
            'internal_groups' => count((array) \Arr::get($internal, 'groups', [])),
            'email_users' => count((array) \Arr::get($email, 'users', [])),
            'email_emails' => count((array) \Arr::get($email, 'emails', [])),
        ];
    }

    protected function safe_result(array $result)
    {
        return [
            'success' => !empty($result['success']),
            'event_code' => (string) \Arr::get($result, 'event_code', ''),
            'channels' => array_keys((array) \Arr::get($result, 'channels', [])),
            'queued' => (int) \Arr::get($result, 'queued', 0),
            'notified' => (int) \Arr::get($result, 'notified', 0),
            'errors' => (array) \Arr::get($result, 'errors', []),
        ];
    }
}
