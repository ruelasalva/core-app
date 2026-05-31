<?php
namespace Fuel\Tasks;

/**
 * TAREA LOCKFISCALPERIOD
 *
 * Bloquea un periodo fiscal sin cerrarlo.
 *
 * Uso:
 * php oil refine lockfiscalperiod --rfc=SET180322811 --period=2026-05
 *
 * @package  app
 */
class Lockfiscalperiod
{
    /**
     * RUN
     *
     * BLOQUEA PERIODO FISCAL.
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
            \Cli::write('Uso: php oil refine lockfiscalperiod --rfc=SET180322811 --period=2026-05');
            return;
        }

        try {
            $service = new \Service_Core_Fiscal_PeriodService();
            $current = $service->find($rfc, $period);

            if ($current && (string) $current['status'] === 'closed') {
                \Cli::write('Error: el periodo fiscal ya esta cerrado y no puede bloquearse.');
                return;
            }

            if ($current && (string) $current['status'] === 'locked') {
                \Cli::write('Periodo fiscal ya estaba bloqueado');
                \Cli::write(' - ID: '.$current['id']);
                \Cli::write(' - RFC: '.strtoupper(trim((string) $rfc)));
                \Cli::write(' - Periodo: '.$period);
                \Cli::write(' - Estado: locked');
                return;
            }

            $result = $service->lock($rfc, $period, 0);
            \Cli::write('Periodo fiscal bloqueado');
            \Cli::write(' - ID: '.$result['id']);
            \Cli::write(' - RFC: '.$result['rfc']);
            \Cli::write(' - Periodo: '.$result['period']);
            \Cli::write(' - Estado: '.$result['status']);
        } catch (\Exception $e) {
            \Log::error('Lockfiscalperiod: '.$e->getMessage());
            \Cli::write('Error bloqueando periodo fiscal: '.$e->getMessage());
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
