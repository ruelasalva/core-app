<?php
namespace Fuel\Tasks;

/**
 * TAREA OPENFISCALPERIOD
 *
 * Abre o reabre un periodo fiscal.
 *
 * Uso:
 * php oil refine openfiscalperiod --rfc=SET180322811 --period=2026-05
 *
 * @package  app
 */
class Openfiscalperiod
{
    /**
     * RUN
     *
     * ABRE PERIODO FISCAL.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        $options = $this->options();
        $rfc = isset($options['rfc']) ? $options['rfc'] : '';
        $period = isset($options['period']) ? $options['period'] : '';

        if ($rfc === '' || $period === '') {
            \Cli::write('Uso: php oil refine openfiscalperiod --rfc=SET180322811 --period=2026-05');
            return;
        }

        try {
            $result = (new \Service_Core_Fiscal_PeriodService())->open($rfc, $period, 0);
            \Cli::write('Periodo fiscal abierto');
            \Cli::write(' - ID: '.$result['id']);
            \Cli::write(' - RFC: '.$result['rfc']);
            \Cli::write(' - Periodo: '.$result['period']);
            \Cli::write(' - Estado: '.$result['status']);
            $this->log_event($result['rfc'], $result['period'], 'success', 'Periodo fiscal abierto.', $result);
        } catch (\Exception $e) {
            \Log::error('Openfiscalperiod: '.$e->getMessage());
            $this->log_event($rfc, $period, 'error', 'Error abriendo periodo fiscal.', ['error' => $e->getMessage()]);
            \Cli::write('Error abriendo periodo fiscal: '.$e->getMessage());
        }
    }

    protected function log_event($rfc, $period, $status, $summary, array $details)
    {
        \Service_Core_Fiscal_EventLogger::log([
            'taxpayer_rfc' => $rfc,
            'fiscal_period' => $period,
            'event_type' => 'period_open',
            'event_status' => $status,
            'source_module' => 'fiscal',
            'source_entity_type' => 'fiscal_period',
            'source_entity_id' => (int) \Arr::get($details, 'id', 0),
            'summary' => $summary,
            'details' => $details,
            'executed_by' => 0,
        ]);
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
