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
        } catch (\Exception $e) {
            \Log::error('Openfiscalperiod: '.$e->getMessage());
            \Cli::write('Error abriendo periodo fiscal: '.$e->getMessage());
        }
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
