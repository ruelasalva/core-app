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
        } catch (\Exception $e) {
            \Log::error('Closefiscalperiod: '.$e->getMessage());
            \Cli::write('Error cerrando periodo fiscal: '.$e->getMessage());
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
