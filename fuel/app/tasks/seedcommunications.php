<?php
namespace Fuel\Tasks;

class Seedcommunications
{
    protected $created = 0;
    protected $updated = 0;

    public function run()
    {
        try {
            $this->assert_schema();
            $this->seed_permissions();
            $this->seed_providers();
            $this->seed_accounts();
            $this->seed_account_assignments();
            $this->seed_channels();
            $this->seed_layouts();
            $this->seed_roles();
            $this->seed_templates();
            $this->seed_events();
            $this->seed_recipient_rules();

            \Cli::write('Seed de comunicaciones terminado.');
            \Cli::write('Creados: '.$this->created);
            \Cli::write('Actualizados/existentes: '.$this->updated);
            \Log::info('Seedcommunications ejecutado. creados='.$this->created.' actualizados='.$this->updated);
        } catch (\Exception $e) {
            \Log::error('Seedcommunications: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema()
    {
        foreach ([
            'core_communication_providers',
            'core_communication_accounts',
            'core_communication_channels',
            'core_email_layouts',
            'core_email_roles',
            'core_email_templates',
            'core_email_queue',
            'core_email_queue_attempts',
            'core_communication_event_recipients',
        ] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta: php oil refine migrate');
            }
        }
    }

    protected function seed_permissions()
    {
        if (!\DBUtil::table_exists('users_permissions')) {
            return;
        }

        $row = \DB::select('id', 'actions')
            ->from('users_permissions')
            ->where('area', '=', 'communications')
            ->where('permission', '=', 'access')
            ->execute()
            ->current();

        $actions = $this->auth_actions(['view', 'edit', 'test', 'process', 'create']);
        if (!$row) {
            \DB::insert('users_permissions')->set([
                'area' => 'communications',
                'permission' => 'access',
                'description' => 'Acceso al centro de comunicaciones',
                'actions' => serialize($actions),
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
            $this->created++;
            return;
        }

        $current = @unserialize($row['actions']);
        $current = is_array($current) ? $current : [];
        $merged = array_merge($current, $actions);
        \DB::update('users_permissions')
            ->set([
                'actions' => serialize($merged),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $row['id'])
            ->execute();
        $this->updated++;
    }

    protected function seed_providers()
    {
        $this->upsert('core_communication_providers', 'code', 'disabled_default', [
            'code' => 'disabled_default',
            'name' => 'Deshabilitado / simulacion',
            'type' => 'disabled',
            'transport' => 'disabled',
            'simulation_mode' => 1,
            'active' => 1,
        ]);

        $this->upsert('core_communication_providers', 'code', 'php_mail_default', [
            'code' => 'php_mail_default',
            'name' => 'PHP mail',
            'type' => 'php_mail',
            'transport' => 'php_mail',
            'simulation_mode' => 1,
            'active' => 1,
        ]);

        $this->upsert('core_communication_providers', 'code', 'smtp_default', [
            'code' => 'smtp_default',
            'name' => 'SMTP principal',
            'type' => 'smtp',
            'transport' => 'smtp',
            'port' => 587,
            'timeout_seconds' => 20,
            'verify_tls' => 1,
            'simulation_mode' => 1,
            'active' => 0,
        ]);
    }

    protected function seed_channels()
    {
        $channels = [
            ['internal', 'Interno', 'internal', 'Notificaciones dentro del ERP'],
            ['email', 'Correo electronico', 'email', 'Correos transaccionales'],
            ['marketing', 'Marketing', 'marketing', 'Correos de marketing futuro'],
            ['whatsapp', 'WhatsApp', 'whatsapp', 'Canal futuro'],
            ['sms', 'SMS', 'sms', 'Canal futuro'],
            ['push', 'Push', 'push', 'Canal futuro'],
        ];

        foreach ($channels as $channel) {
            $this->upsert('core_communication_channels', 'code', $channel[0], [
                'code' => $channel[0],
                'name' => $channel[1],
                'type' => $channel[2],
                'description' => $channel[3],
                'active' => 1,
            ]);
        }
    }

    protected function seed_accounts()
    {
        $this->upsert('core_communication_accounts', 'code', 'support_inbox', [
            'code' => 'support_inbox',
            'name' => 'Bandeja de soporte',
            'email_address' => 'soporte@example.com',
            'account_type' => 'support',
            'provider_code' => 'smtp_default',
            'smtp_provider_code' => 'smtp_default',
            'imap_provider_code' => 'imap_default',
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => 'soporte@example.com',
            'imap_folder_inbox' => 'INBOX',
            'imap_folder_sent' => 'Sent',
            'imap_folder_drafts' => 'Drafts',
            'imap_folder_trash' => 'Trash',
            'sync_inbox' => 1,
            'sync_sent' => 0,
            'sync_drafts' => 0,
            'sync_trash' => 0,
            'append_sent' => 0,
            'sync_enabled' => 0,
            'last_sync_status' => 'never',
            'active' => 0,
        ]);
    }

    protected function seed_account_assignments()
    {
        if (!\DBUtil::table_exists('core_communication_account_assignments')) {
            \Cli::write('[WARN] Falta core_communication_account_assignments; se omite asignacion de buzones.');
            return;
        }

        $account = \DB::select('id')
            ->from('core_communication_accounts')
            ->where('code', '=', 'support_inbox')
            ->execute()
            ->current();

        if (!$account) {
            \Cli::write('[WARN] Cuenta support_inbox no encontrada; se omite asignacion a Sistemas.');
            return;
        }

        $sistemas_group = $this->find_group_by_name('sistemas');
        if ($sistemas_group <= 0) {
            \Cli::write('[WARN] Grupo Sistemas no encontrado; support_inbox queda sin asignacion de grupo.');
            return;
        }

        $this->upsert_account_assignment([
            'account_id' => (int) $account['id'],
            'assignment_type' => 'group',
            'assignment_value' => (string) $sistemas_group,
            'access_level' => 'delegate',
            'can_send' => 0,
            'can_receive' => 1,
            'can_sync' => 0,
            'can_manage' => 0,
            'default_sender' => 0,
            'active' => 1,
        ]);

        \Cli::write('Cuenta support_inbox asignada al grupo Sistemas: '.$sistemas_group);
    }

    protected function seed_layouts()
    {
        $html = '<!doctype html><html><body style="font-family:Arial,sans-serif;background:#f6f7fb;padding:24px;"><div style="max-width:720px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;padding:24px;"><h2 style="margin-top:0;">{{subject}}</h2><div>{{body}}</div><hr><p style="color:#6b7280;font-size:12px;">CORE-APP ERP</p></div></body></html>';
        $text = "{{subject}}\n\n{{body}}\n\nCORE-APP ERP";

        $this->upsert('core_email_layouts', 'code', 'base_erp_email', [
            'code' => 'base_erp_email',
            'name' => 'Layout base ERP',
            'description' => 'Layout transaccional base para CORE-APP.',
            'html_layout' => $html,
            'text_layout' => $text,
            'version' => 1,
            'active' => 1,
        ]);
    }

    protected function seed_roles()
    {
        foreach ([
            'system' => 'Sistema',
            'sales' => 'Ventas',
            'purchases' => 'Compras',
            'billing' => 'Facturacion',
            'cfdi' => 'CFDI',
            'portal' => 'Portal',
            'support' => 'Soporte',
            'marketing' => 'Marketing',
        ] as $code => $name) {
            $this->upsert('core_email_roles', 'code', $code, [
                'code' => $code,
                'name' => $name,
                'from_email' => 'no-reply@coreapp.local',
                'from_name' => 'CORE-APP ERP',
                'active' => 1,
            ]);
        }
    }

    protected function seed_templates()
    {
        $system = [
            'code' => 'system_test',
            'email_role' => 'system',
            'subject' => 'Prueba del Centro de Comunicaciones',
            'content' => '<p>Esta es una prueba de comunicaciones para {{app_name}}.</p>',
            'active' => 1,
        ];

        $queue = [
            'code' => 'queue_test',
            'email_role' => 'system',
            'subject' => '{{subject}}',
            'content' => '<p>{{message}}</p>',
            'active' => 1,
        ];

        if (\DBUtil::field_exists('core_email_templates', ['name'])) {
            $system['name'] = 'Prueba del Centro de Comunicaciones';
            $queue['name'] = 'Prueba de cola de correo';
        }

        if (\DBUtil::field_exists('core_email_templates', ['body_text'])) {
            $system['body_text'] = 'Esta es una prueba de comunicaciones para {{app_name}}.';
            $queue['body_text'] = '{{message}}';
        }

        $this->upsert('core_email_templates', 'code', 'system_test', $system);
        $this->upsert('core_email_templates', 'code', 'queue_test', $queue);

        $helpdesk = [
            'code' => 'helpdesk_ticket_created',
            'email_role' => 'support',
            'subject' => 'Nuevo ticket recibido: {{ticket_folio}}',
            'content' => '<p>Ticket: {{ticket_folio}}</p><p>Asunto: {{subject}}</p><p>Portal: {{portal_code}}</p><p>Cliente/proveedor: {{party_id}}</p><p>Fecha: {{current_date}}</p>',
            'active' => 1,
        ];

        if (\DBUtil::field_exists('core_email_templates', ['name'])) {
            $helpdesk['name'] = 'Ticket de soporte creado';
        }

        if (\DBUtil::field_exists('core_email_templates', ['body_text'])) {
            $helpdesk['body_text'] = "Ticket: {{ticket_folio}}\nAsunto: {{subject}}\nPortal: {{portal_code}}\nCliente/proveedor: {{party_id}}\nFecha: {{current_date}}";
        }

        $this->upsert('core_email_templates', 'code', 'helpdesk_ticket_created', $helpdesk);
    }

    protected function seed_recipient_rules()
    {
        $this->upsert_rule([
            'event_code' => 'system.test',
            'channel_code' => 'internal',
            'recipient_type' => 'group',
            'recipient_value' => '100',
            'mode' => 'include',
            'active' => 1,
        ]);

        $this->upsert_rule([
            'event_code' => 'system.test',
            'channel_code' => 'email',
            'recipient_type' => 'role',
            'recipient_value' => 'system',
            'mode' => 'include',
            'active' => 1,
        ]);

        $sistemas_group = $this->find_group_by_name('sistemas');
        if ($sistemas_group > 0) {
            $this->upsert_rule([
                'event_code' => 'helpdesk.ticket.created',
                'channel_code' => 'internal',
                'recipient_type' => 'group',
                'recipient_value' => (string) $sistemas_group,
                'mode' => 'include',
                'active' => 1,
            ]);
            \Cli::write('Grupo Sistemas detectado para Helpdesk: '.$sistemas_group);
        } else {
            \Cli::write('[WARN] Grupo Sistemas no encontrado; no se activo ruteo interno de helpdesk.ticket.created.');
        }
    }

    protected function seed_events()
    {
        if (!\DBUtil::table_exists('core_notification_events')) {
            \Cli::write('[WARN] Falta core_notification_events; se omite seed de eventos.');
            return;
        }

        $events = [
            'contact.web.message' => [
                'code' => 'contact.web.message',
                'name' => 'Mensaje web recibido',
                'description' => 'Mensaje recibido desde formulario publico de contacto.',
                'title_template' => 'Nuevo mensaje web de {{name}}',
                'message_template' => '{{name}} envio un mensaje desde {{origin}}.',
                'url_template' => 'admin/communications',
                'icon' => 'bi bi-envelope',
                'priority' => 2,
                'notify_internal' => 1,
                'notify_email' => 0,
                'email_role' => 'sales',
                'email_template_code' => '',
                'active' => 1,
            ],
            'helpdesk.ticket.created' => [
                'code' => 'helpdesk.ticket.created',
                'name' => 'Ticket de soporte creado',
                'description' => 'Ticket creado desde admin o portal.',
                'title_template' => 'Nuevo ticket {{folio}}',
                'message_template' => '{{subject}}',
                'url_template' => 'admin/helpdesk',
                'icon' => 'bi bi-life-preserver',
                'priority' => 3,
                'notify_internal' => 1,
                'notify_email' => 0,
                'email_role' => 'support',
                'email_template_code' => 'helpdesk_ticket_created',
                'active' => 1,
            ],
            'sales.quote.created' => [
                'code' => 'sales.quote.created',
                'name' => 'Cotizacion creada',
                'description' => 'Cotizacion creada desde un flujo comercial.',
                'title_template' => 'Nueva cotizacion {{folio}}',
                'message_template' => 'Cotizacion {{folio}} creada desde {{source}}.',
                'url_template' => 'admin/sales?view=quotes',
                'icon' => 'bi bi-file-earmark-text',
                'priority' => 2,
                'notify_internal' => 1,
                'notify_email' => 0,
                'email_role' => 'sales',
                'email_template_code' => '',
                'active' => 1,
            ],
        ];

        foreach ($events as $code => $event) {
            $this->upsert('core_notification_events', 'code', $code, $event);
        }
    }

    protected function upsert_rule(array $data)
    {
        $row = \DB::select('id')
            ->from('core_communication_event_recipients')
            ->where('event_code', '=', $data['event_code'])
            ->where('channel_code', '=', $data['channel_code'])
            ->where('recipient_type', '=', $data['recipient_type'])
            ->where('recipient_value', '=', $data['recipient_value'])
            ->where('mode', '=', $data['mode'])
            ->execute()
            ->current();

        $data['updated_at'] = time();
        if ($row) {
            \DB::update('core_communication_event_recipients')
                ->set($data)
                ->where('id', '=', (int) $row['id'])
                ->execute();
            $this->updated++;
            return;
        }

        $data['created_at'] = time();
        \DB::insert('core_communication_event_recipients')->set($data)->execute();
        $this->created++;
    }

    protected function upsert_account_assignment(array $data)
    {
        $row = \DB::select('id')
            ->from('core_communication_account_assignments')
            ->where('account_id', '=', (int) $data['account_id'])
            ->where('assignment_type', '=', $data['assignment_type'])
            ->where('assignment_value', '=', $data['assignment_value'])
            ->execute()
            ->current();

        $data['updated_at'] = time();
        if ($row) {
            \DB::update('core_communication_account_assignments')
                ->set($data)
                ->where('id', '=', (int) $row['id'])
                ->execute();
            $this->updated++;
            return;
        }

        $data['created_at'] = time();
        \DB::insert('core_communication_account_assignments')->set($data)->execute();
        $this->created++;
    }

    protected function find_group_by_name($needle)
    {
        if (!\DBUtil::table_exists('users_groups')) {
            return 0;
        }

        $rows = \DB::select('id', 'name')
            ->from('users_groups')
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            if (stripos((string) $row['name'], (string) $needle) !== false) {
                return (int) $row['id'];
            }
        }

        return 0;
    }

    protected function upsert($table, $key, $value, array $data)
    {
        $row = \DB::select('id')->from($table)->where($key, '=', $value)->execute()->current();
        $data['updated_at'] = time();

        if ($row) {
            \DB::update($table)->set($data)->where('id', '=', (int) $row['id'])->execute();
            $this->updated++;
            return;
        }

        $data['created_at'] = time();
        \DB::insert($table)->set($data)->execute();
        $this->created++;
    }

    protected function auth_actions(array $actions)
    {
        $map = [];
        foreach ($actions as $action) {
            $map[$action] = $action;
        }

        return $map;
    }
}
