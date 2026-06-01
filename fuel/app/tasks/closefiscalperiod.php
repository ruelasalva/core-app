<?php
namespace Fuel\Tasks;

/**
 * TAREA CLOSEFISCALPERIOD
 *
 * Cierra un periodo fiscal.
 *
 * Uso:
 * php oil refine closefiscalperiod --rfc=SET180322811 --period=2026-05
 *
 * @package  app
 */
class Closefiscalperiod
{
    /**
     * RUN
     *
     * CIERRA PERIODO FISCAL.
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
            \Cli::write('Uso: php oil refine closefiscalperiod --rfc=SET180322811 --period=2026-05');
            return;
        }

        try {
            $result = (new \Service_Core_Fiscal_PeriodService())->close($rfc, $period, 0);
            \Cli::write('Periodo fiscal cerrado');
            \Cli::write(' - ID: '.$result['id']);
            \Cli::write(' - RFC: '.$result['rfc']);
            \Cli::write(' - Periodo: '.$result['period']);
            \Cli::write(' - Estado: '.$result['status']);
            $this->log_event($result['rfc'], $result['period'], 'success', 'Periodo fiscal cerrado.', $result);
        } catch (\Exception $e) {
            \Log::error('Closefiscalperiod: '.$e->getMessage());
            $this->log_event($rfc, $period, 'error', 'Error cerrando periodo fiscal.', ['error' => $e->getMessage()]);
            \Cli::write('Error cerrando periodo fiscal: '.$e->getMessage());
        }
    }

    protected function log_event($rfc, $period, $status, $summary, array $details)
    {
        \Service_Core_Fiscal_EventLogger::log([
            'taxpayer_rfc' => $rfc,
            'fiscal_period' => $period,
            'event_type' => 'period_close',
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
