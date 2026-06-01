<?php
namespace Fuel\Tasks;

/**
 * TAREA CANCELFISCALDRAFT
 *
 * Cancela logicamente polizas fiscales en borrador para permitir regeneracion.
 *
 * Uso:
 * php oil refine cancelfiscaldraft --rfc=SET180322811 --period=2026-05
 *
 * @package  app
 */
class Cancelfiscaldraft
{
    /**
     * RUN
     *
     * CANCELA POLIZAS FISCALES DRAFT SIN BORRAR PARTIDAS NI TOCAR POSTED.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        $options = $this->options();
        $rfc = isset($options['rfc']) ? $this->normalize_rfc($options['rfc']) : '';
        $period = isset($options['period']) ? $this->normalize_period($options['period']) : '';

        if ($rfc === '' || $period === '') {
            \Cli::write('Uso: php oil refine cancelfiscaldraft --rfc=RFCEMPRESA --period=2026-05');
            return;
        }

        try {
            $this->assert_schema_ready();
            $fiscal_period = $this->fiscal_period($rfc, $period);
            if (!$fiscal_period) {
                \Cli::write('OK: No existe periodo fiscal para '.$rfc.' '.$period.'. No hay borradores por cancelar.');
                $this->log_event($rfc, $period, 'skipped', 'No existe periodo fiscal; no hay borradores por cancelar.', ['reason' => 'period_not_found']);
                return;
            }

            $drafts = $this->draft_entries((int) $fiscal_period['id']);
            if (empty($drafts)) {
                \Cli::write('OK: No hay polizas fiscales en borrador para '.$rfc.' '.$period.'.');
                $this->log_event($rfc, $period, 'skipped', 'No hay polizas fiscales en borrador para cancelar.', ['fiscal_period_id' => (int) $fiscal_period['id']]);
                return;
            }

            $cancelled = 0;
            foreach ($drafts as $entry) {
                $this->cancel_entry($entry);
                $cancelled++;
                \Cli::write('Cancelada poliza '.$entry['folio'].' ID '.$entry['id']);
            }

            \Log::info('Cancelfiscaldraft: RFC='.$rfc.' periodo='.$period.' canceladas='.$cancelled);
            $this->log_event($rfc, $period, 'success', 'Polizas fiscales en borrador canceladas.', [
                'fiscal_period_id' => (int) $fiscal_period['id'],
                'cancelled_count' => $cancelled,
            ]);
            \Cli::write('OK: Polizas fiscales en borrador canceladas: '.$cancelled);
        } catch (\Exception $e) {
            \Log::error('Cancelfiscaldraft: '.$e->getMessage());
            $this->log_event($rfc, $period, 'error', 'Error cancelando borrador fiscal.', ['error' => $e->getMessage()]);
            \Cli::write('Error cancelando borrador fiscal: '.$e->getMessage());
        }
    }

    protected function log_event($rfc, $period, $status, $summary, array $details)
    {
        \Service_Core_Fiscal_EventLogger::log([
            'taxpayer_rfc' => $rfc,
            'fiscal_period' => $period,
            'event_type' => 'draft_cancellation',
            'event_status' => $status,
            'source_module' => 'fiscal',
            'source_entity_type' => 'accounting_journal_entry',
            'summary' => $summary,
            'details' => $details,
            'executed_by' => 0,
        ]);
    }

    protected function cancel_entry(array $entry)
    {
        if ((string) $entry['status'] !== 'draft') {
            return;
        }

        $description = trim((string) $entry['description']);
        $note = 'Borrador fiscal cancelado para regeneracion';
        if (strpos($description, $note) === false) {
            $description = trim($description.' | '.$note, ' |');
        }

        $set = [
            'status' => 'cancelled',
            'description' => $description,
            'updated_at' => time(),
        ];

        if (\DBUtil::field_exists('core_accounting_journal_entries', ['active'])) {
            $set['active'] = 0;
        }

        \DB::update('core_accounting_journal_entries')
            ->set($set)
            ->where('id', '=', (int) $entry['id'])
            ->where('status', '=', 'draft')
            ->where('source_module', '=', 'fiscal')
            ->where('source_entity_type', '=', 'fiscal_period')
            ->execute();
    }

    protected function draft_entries($fiscal_period_id)
    {
        $query = \DB::select()
            ->from('core_accounting_journal_entries')
            ->where('source_module', '=', 'fiscal')
            ->where('source_entity_type', '=', 'fiscal_period')
            ->where('source_entity_id', '=', (int) $fiscal_period_id)
            ->where('status', '=', 'draft')
            ->order_by('id', 'asc');

        if (\DBUtil::field_exists('core_accounting_journal_entries', ['active'])) {
            $query->where('active', '=', 1);
        }

        return $query->execute()->as_array();
    }

    protected function fiscal_period($rfc, $period)
    {
        return \DB::select('id', 'taxpayer_rfc', 'period_key', 'status')
            ->from('core_fiscal_periods')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('period_key', '=', $period)
            ->where('active', '=', 1)
            ->execute()
            ->current();
    }

    protected function assert_schema_ready()
    {
        foreach (['core_fiscal_periods', 'core_accounting_journal_entries', 'core_accounting_journal_lines'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta tabla requerida: '.$table.'. Ejecuta migraciones primero.');
            }
        }
    }

    protected function normalize_rfc($rfc)
    {
        $rfc = strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
        if ($rfc === '' || !preg_match('/^[A-Z&\x{00D1}]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
            return '';
        }
        return $rfc;
    }

    protected function normalize_period($period)
    {
        $period = trim((string) $period);
        return preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period) ? $period : '';
    }

    protected function options()
    {
        $options = [];
        $argv = isset($_SERVER['argv']) ? (array) $_SERVER['argv'] : [];

        foreach ($argv as $arg) {
            if (strpos($arg, '--') !== 0) {
                continue;
            }

            $arg = substr($arg, 2);
            $parts = explode('=', $arg, 2);
            $key = trim((string) $parts[0]);
            $value = isset($parts[1]) ? trim((string) $parts[1]) : '';
            if ($key !== '') {
                $options[$key] = $value;
            }
        }

        return $options;
    }
}
