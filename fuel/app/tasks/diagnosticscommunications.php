<?php
namespace Fuel\Tasks;

class Diagnosticscommunications
{
    public function run()
    {
        try {
            \Cli::write('Diagnostico de comunicaciones');
            \Cli::write('current_user: not_applicable_cli');
            \Cli::write('');

            $this->write_count('providers_count', 'core_communication_providers');
            $this->write_count('accounts_count', 'core_communication_accounts');
            $this->write_count('assignments_count', 'core_communication_account_assignments');
            $this->write_count('conversations_count', 'core_communication_conversations');
            $this->write_count('messages_count', 'core_communication_messages');
            $this->write_count('queue_total', 'core_email_queue');
            $this->write_queue_statuses();
            $this->write_stale_processing();
            $this->write_last_attempts();

            \Cli::write('');
            \Cli::write('No se muestran passwords, API keys, tokens, payloads, cuerpos de correo ni rutas fisicas.');
        } catch (\Exception $e) {
            \Log::error('Diagnosticscommunications: '.$e->getMessage());
            \Cli::write('[ERROR] '.$this->safe_text($e->getMessage()));
        }
    }

    protected function write_count($label, $table)
    {
        if (!\DBUtil::table_exists($table)) {
            \Cli::write($label.': missing_table');
            return;
        }

        \Cli::write($label.': '.(int) \DB::count_records($table));
    }

    protected function write_queue_statuses()
    {
        if (!\DBUtil::table_exists('core_email_queue')) {
            \Cli::write('queues_pending: missing_table');
            \Cli::write('queues_sent: missing_table');
            \Cli::write('queues_simulated: missing_table');
            \Cli::write('queues_failed: missing_table');
            return;
        }

        \Cli::write('queues_pending: '.$this->count_queue(['status' => 'pending']));
        \Cli::write('queues_sent: '.$this->count_queue(['status' => 'sent']));
        \Cli::write('queues_simulated: '.$this->count_queue(['status' => 'sent', 'simulation_mode' => 1]));
        \Cli::write('queues_failed: '.$this->count_queue(['status' => 'failed']));
            \Cli::write('queues_processing: '.$this->count_queue(['status' => 'processing']));
    }

    protected function write_stale_processing()
    {
        \Cli::write('');
        \Cli::write('stale_processing:');

        if (!\DBUtil::table_exists('core_email_queue')) {
            \Cli::write('stale_processing_count: missing_table');
            \Cli::write('oldest_processing_age: missing_table');
            \Cli::write('recoverable_count: missing_table');
            return;
        }

        $processor = new \Service_Core_Email_QueueProcessor();
        $stats = $processor->stale_processing_stats(30);
        \Cli::write('stale_processing_count: '.$stats['stale_processing_count']);
        \Cli::write('oldest_processing_age: '.$stats['oldest_processing_age']);
        \Cli::write('recoverable_count: '.$stats['recoverable_count']);
    }

    protected function count_queue(array $where)
    {
        $query = \DB::select(\DB::expr('COUNT(*) AS total'))->from('core_email_queue');
        foreach ($where as $field => $value) {
            if ($field === 'simulation_mode' && !\DBUtil::field_exists('core_email_queue', ['simulation_mode'])) {
                return 0;
            }
            $query->where($field, '=', $value);
        }

        $row = $query->execute()->current();
        return (int) \Arr::get($row, 'total', 0);
    }

    protected function write_last_attempts()
    {
        \Cli::write('');
        \Cli::write('last_queue_attempts:');

        if (!\DBUtil::table_exists('core_email_queue_attempts')) {
            \Cli::write(' - missing_table');
            return;
        }

        $rows = \DB::select('id', 'queue_id', 'provider_code', 'transport', 'status', 'response_code', 'attempted_at')
            ->from('core_email_queue_attempts')
            ->order_by('id', 'desc')
            ->limit(5)
            ->execute()
            ->as_array();

        if (empty($rows)) {
            \Cli::write(' - none');
            return;
        }

        foreach ($rows as $row) {
            \Cli::write(
                ' - id='.(int) \Arr::get($row, 'id', 0).
                ' queue_id='.(int) \Arr::get($row, 'queue_id', 0).
                ' provider='.$this->safe_key(\Arr::get($row, 'provider_code', '')).
                ' transport='.$this->safe_key(\Arr::get($row, 'transport', '')).
                ' status='.$this->safe_key(\Arr::get($row, 'status', '')).
                ' response_code='.$this->safe_key(\Arr::get($row, 'response_code', '')).
                ' attempted_at='.(int) \Arr::get($row, 'attempted_at', 0)
            );
        }
    }

    protected function safe_key($value)
    {
        return substr(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $value), 0, 80);
    }

    protected function safe_text($value)
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        $value = preg_replace('/(file_path|storage_path)\s*[:=]\s*\S+/i', '$1=[redacted]', $value);
        return substr(trim($value), 0, 180);
    }
}
