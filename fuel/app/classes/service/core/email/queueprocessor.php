<?php

class Service_Core_Email_QueueProcessor
{
    protected $locked_by;

    public function __construct($locked_by = null)
    {
        $this->locked_by = $locked_by ?: 'oil-'.getmypid();
    }

    public function process($limit = 50, $provider_filter = '', array $options = [])
    {
        $limit = max(1, min(500, (int) $limit));
        $summary = [
            'found' => 0,
            'sent' => 0,
            'simulated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'stale_processing_count' => 0,
            'oldest_processing_age' => 0,
            'recoverable_count' => 0,
            'recovered_pending' => 0,
            'recovered_failed' => 0,
            'errors' => [],
        ];

        if (!\DBUtil::table_exists('core_email_queue')) {
            $summary['errors'][] = 'Falta tabla core_email_queue.';
            return $summary;
        }

        $stale_minutes = max(1, min(1440, (int) \Arr::get($options, 'stale_minutes', 30)));
        $stale_stats = $this->stale_processing_stats($stale_minutes);
        $summary['stale_processing_count'] = $stale_stats['stale_processing_count'];
        $summary['oldest_processing_age'] = $stale_stats['oldest_processing_age'];
        $summary['recoverable_count'] = $stale_stats['recoverable_count'];

        if ((int) \Arr::get($options, 'recover_stale', 0) === 1) {
            $recovered = $this->recover_stale_processing($stale_minutes, $limit);
            $summary['recovered_pending'] = $recovered['recovered_pending'];
            $summary['recovered_failed'] = $recovered['recovered_failed'];
            $summary['errors'] = array_merge($summary['errors'], $recovered['errors']);
        }

        $rows = $this->pending_rows($limit, $provider_filter);
        $summary['found'] = count($rows);

        foreach ($rows as $row) {
            $result = $this->process_row($row);
            if (!empty($result['simulated'])) {
                $summary['simulated']++;
            } elseif (!empty($result['success'])) {
                $summary['sent']++;
            } elseif (!empty($result['skipped'])) {
                $summary['skipped']++;
            } else {
                $summary['failed']++;
                $summary['errors'][] = $result['message'];
            }
        }

        return $summary;
    }

    public function stale_processing_stats($stale_minutes = 30)
    {
        $stats = [
            'stale_processing_count' => 0,
            'oldest_processing_age' => 0,
            'recoverable_count' => 0,
        ];

        if (!\DBUtil::table_exists('core_email_queue')) {
            return $stats;
        }

        $cutoff = time() - (max(1, min(1440, (int) $stale_minutes)) * 60);
        $rows = $this->stale_processing_rows($cutoff, 500);
        $stats['stale_processing_count'] = count($rows);

        foreach ($rows as $row) {
            $locked_at = (int) \Arr::get($row, 'locked_at', 0);
            $age = $locked_at > 0 ? max(0, time() - $locked_at) : 0;
            $stats['oldest_processing_age'] = max($stats['oldest_processing_age'], $age);

            $attempts = (int) \Arr::get($row, 'attempts', 0);
            $max_attempts = max(1, (int) \Arr::get($row, 'max_attempts', 3));
            if ($attempts < $max_attempts) {
                $stats['recoverable_count']++;
            }
        }

        return $stats;
    }

    public function recover_stale_processing($stale_minutes = 30, $limit = 50)
    {
        $summary = [
            'recovered_pending' => 0,
            'recovered_failed' => 0,
            'errors' => [],
        ];

        if (!\DBUtil::table_exists('core_email_queue')) {
            $summary['errors'][] = 'Falta tabla core_email_queue.';
            return $summary;
        }

        $cutoff = time() - (max(1, min(1440, (int) $stale_minutes)) * 60);
        $rows = $this->stale_processing_rows($cutoff, max(1, min(500, (int) $limit)));

        foreach ($rows as $row) {
            $queue_id = (int) \Arr::get($row, 'id', 0);
            if ($queue_id <= 0) {
                continue;
            }

            $attempts = (int) \Arr::get($row, 'attempts', 0);
            $max_attempts = max(1, (int) \Arr::get($row, 'max_attempts', 3));
            if ($attempts >= $max_attempts) {
                \DB::update('core_email_queue')
                    ->set([
                        'status' => 'failed',
                        'last_error' => 'Fila recuperada desde processing: intentos agotados.',
                        'next_retry_at' => 0,
                        'locked_at' => 0,
                        'locked_by' => '',
                        'updated_at' => time(),
                    ])
                    ->where('id', '=', $queue_id)
                    ->where('status', '=', 'processing')
                    ->execute();
                $summary['recovered_failed']++;
                continue;
            }

            $next_retry_at = (int) \Arr::get($row, 'next_retry_at', 0);
            if ($next_retry_at <= time()) {
                $next_retry_at = $this->retry_at_for_recovered_row($attempts);
            }

            \DB::update('core_email_queue')
                ->set([
                    'status' => 'pending',
                    'last_error' => 'Fila recuperada desde processing; reintento programado.',
                    'next_retry_at' => $next_retry_at,
                    'locked_at' => 0,
                    'locked_by' => '',
                    'updated_at' => time(),
                ])
                ->where('id', '=', $queue_id)
                ->where('status', '=', 'processing')
                ->execute();
            $summary['recovered_pending']++;
        }

        if ($summary['recovered_pending'] > 0 || $summary['recovered_failed'] > 0) {
            \Log::warning('Email queue stale processing recuperado: '.json_encode($summary));
        }

        return $summary;
    }

    protected function stale_processing_rows($cutoff, $limit)
    {
        return \DB::select('id', 'attempts', 'max_attempts', 'locked_at', 'locked_by', 'next_retry_at')
            ->from('core_email_queue')
            ->where('status', '=', 'processing')
            ->where_open()
                ->where('locked_at', '=', 0)
                ->or_where('locked_at', '<=', (int) $cutoff)
            ->where_close()
            ->order_by('locked_at', 'asc')
            ->order_by('id', 'asc')
            ->limit((int) $limit)
            ->execute()
            ->as_array();
    }

    protected function pending_rows($limit, $provider_filter)
    {
        $query = \DB::select()
            ->from('core_email_queue')
            ->where('status', '=', 'pending')
            ->where('scheduled_at', '<=', time())
            ->where_open()
                ->where('next_retry_at', '=', 0)
                ->or_where('next_retry_at', '<=', time())
            ->where_close()
            ->order_by(\DB::expr("FIELD(priority, 'critical', 'high', 'normal', 'low', 'marketing')"), 'asc')
            ->order_by('id', 'asc')
            ->limit((int) $limit);

        if (trim((string) $provider_filter) !== '') {
            $query->where('provider_code', '=', trim((string) $provider_filter));
        }

        return $query->execute()->as_array();
    }

    protected function process_row(array $row)
    {
        $queue_id = (int) $row['id'];
        $this->lock_row($queue_id);

        $provider = Model_Core_Communication_Provider::active_by_code((string) $row['provider_code']);
        if (!$provider) {
            $provider = Model_Core_Communication_Provider::active_by_code('disabled_default');
        }

        if (!$provider) {
            return $this->mark_failed($row, 'No existe proveedor de comunicaciones activo.');
        }

        $transport = $this->transport_for($provider);
        $payload = json_decode((string) \Arr::get($row, 'payload_json', ''), true);
        $payload = is_array($payload) ? $payload : [];
        $from_email = $this->safe_email((string) \Arr::get($payload, 'from_email', ''));
        $from_name = $this->safe_text((string) \Arr::get($payload, 'from_name', ''));

        $message = [
            'to_email' => (string) $row['to_email'],
            'to_name' => (string) $row['to_name'],
            'subject' => (string) $row['subject'],
            'body' => (string) $row['body'],
            'from_email' => $from_email !== '' ? $from_email : (string) $provider->from_email,
            'from_name' => $from_name !== '' ? $from_name : (string) $provider->from_name,
            'reply_to_email' => (string) $provider->reply_to_email,
            'cc' => $this->safe_recipients((array) \Arr::get($payload, 'cc', [])),
            'bcc' => $this->safe_recipients((array) \Arr::get($payload, 'bcc', [])),
            'attachments' => $this->safe_attachments((array) \Arr::get($payload, 'attachments', [])),
        ];

        $attempt_number = (int) $row['attempts'] + 1;
        $result = $transport->send($message, $this->provider_settings($provider));
        $this->record_attempt($queue_id, $attempt_number, $provider, $result);

        if (!empty($result['success'])) {
            \DB::update('core_email_queue')
                ->set([
                    'status' => 'sent',
                    'attempts' => $attempt_number,
                    'sent_at' => time(),
                    'last_error' => null,
                    'provider_message_id' => (string) \Arr::get($result, 'provider_message_id', ''),
                    'error_json' => null,
                    'locked_at' => 0,
                    'locked_by' => '',
                    'updated_at' => time(),
                ])
                ->where('id', '=', $queue_id)
                ->execute();

            return [
                'success' => true,
                'simulated' => !empty($result['simulated']),
                'message' => (string) \Arr::get($result, 'message', 'Enviado.'),
            ];
        }

        return $this->mark_failed($row, (string) \Arr::get($result, 'message', 'Error enviando correo.'), $attempt_number, $result);
    }

    protected function lock_row($queue_id)
    {
        \DB::update('core_email_queue')
            ->set([
                'status' => 'processing',
                'locked_at' => time(),
                'locked_by' => $this->locked_by,
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $queue_id)
            ->where('status', '=', 'pending')
            ->execute();
    }

    protected function mark_failed(array $row, $message, $attempt_number = null, array $result = [])
    {
        $queue_id = (int) $row['id'];
        $attempt_number = $attempt_number ?: ((int) $row['attempts'] + 1);
        $max_attempts = max(1, (int) $row['max_attempts']);
        $final = $attempt_number >= $max_attempts;
        $next_retry = $final ? 0 : $this->next_retry_at($attempt_number);

        \DB::update('core_email_queue')
            ->set([
                'status' => $final ? 'failed' : 'pending',
                'attempts' => $attempt_number,
                'last_error' => $message,
                'next_retry_at' => $next_retry,
                'error_json' => json_encode($result),
                'locked_at' => 0,
                'locked_by' => '',
                'updated_at' => time(),
            ])
            ->where('id', '=', $queue_id)
            ->execute();

        return [
            'success' => false,
            'message' => $message,
        ];
    }

    protected function next_retry_at($attempt_number)
    {
        $delays = [
            1 => 300,
            2 => 900,
            3 => 3600,
            4 => 21600,
        ];

        return time() + (int) \Arr::get($delays, (int) $attempt_number, 21600);
    }

    protected function retry_at_for_recovered_row($attempts)
    {
        $next_attempt = max(1, (int) $attempts + 1);
        return $this->next_retry_at($next_attempt);
    }

    protected function record_attempt($queue_id, $attempt_number, Model_Core_Communication_Provider $provider, array $result)
    {
        if (!\DBUtil::table_exists('core_email_queue_attempts')) {
            return;
        }

        Model_Core_Email_QueueAttempt::forge([
            'queue_id' => (int) $queue_id,
            'attempt_number' => (int) $attempt_number,
            'transport' => (string) $provider->transport,
            'provider_code' => (string) $provider->code,
            'status' => !empty($result['success']) ? 'success' : 'error',
            'response_code' => (string) \Arr::get($result, 'response_code', ''),
            'response_message' => (string) \Arr::get($result, 'message', ''),
            'attempted_at' => time(),
        ])->save();
    }

    protected function transport_for(Model_Core_Communication_Provider $provider)
    {
        switch ((string) $provider->transport) {
            case 'php_mail':
                return new Service_Core_Email_Transports_PhpMail();
            case 'smtp':
                return new Service_Core_Email_Transports_Smtp();
            case 'api':
            case 'disabled':
            default:
                return new Service_Core_Email_Transports_Disabled();
        }
    }

    protected function provider_settings(Model_Core_Communication_Provider $provider)
    {
        return [
            'code' => (string) $provider->code,
            'host' => (string) $provider->host,
            'port' => (int) $provider->port,
            'username' => (string) $provider->username,
            'password' => $this->decode_secret((string) $provider->password_encrypted),
            'encryption' => (string) $provider->encryption,
            'timeout_seconds' => (int) $provider->timeout_seconds,
            'verify_tls' => (int) $provider->verify_tls,
            'simulation_mode' => (int) $provider->simulation_mode,
        ];
    }

    protected function decode_secret($value)
    {
        if (trim((string) $value) === '') {
            return '';
        }

        try {
            return \Crypt::decode((string) $value);
        } catch (\Exception $e) {
            \Log::warning('No se pudo decodificar secreto de proveedor de comunicaciones.');
            return '';
        }
    }

    protected function safe_recipients(array $items)
    {
        $safe = [];
        foreach ($items as $item) {
            $item = (array) $item;
            $email = $this->safe_email((string) \Arr::get($item, 'email', ''));
            if ($email === '') {
                continue;
            }
            $safe[] = [
                'email' => $email,
                'name' => $this->safe_text((string) \Arr::get($item, 'name', '')),
            ];
        }

        return $safe;
    }

    protected function safe_attachments(array $items)
    {
        $safe = [];
        foreach ($items as $item) {
            $item = (array) $item;
            $storage_ref = $this->safe_storage_ref((string) \Arr::get($item, 'storage_ref', ''));
            if ($storage_ref === '') {
                continue;
            }

            $safe[] = [
                'filename' => $this->safe_text((string) \Arr::get($item, 'filename', '')),
                'mime_type' => $this->safe_text((string) \Arr::get($item, 'mime_type', '')),
                'size_bytes' => max(0, (int) \Arr::get($item, 'size_bytes', 0)),
                'storage_ref' => $storage_ref,
                'content_hash' => substr(preg_replace('/[^a-fA-F0-9]/', '', (string) \Arr::get($item, 'content_hash', '')), 0, 64),
                'disposition' => $this->safe_text((string) \Arr::get($item, 'disposition', 'attachment')),
            ];
        }

        return $safe;
    }

    protected function safe_email($email)
    {
        $email = strtolower(trim((string) $email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? substr($email, 0, 180) : '';
    }

    protected function safe_text($value)
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        $value = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        return substr(trim($value), 0, 180);
    }

    protected function safe_storage_ref($value)
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/[\\\\\/:]/', $value) || preg_match('/(file_path|storage_path|DOCROOT|APPPATH)/i', $value)) {
            return '';
        }

        return substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $value), 0, 180);
    }
}
