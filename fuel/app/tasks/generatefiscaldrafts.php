<?php
namespace Fuel\Tasks;

/**
 * TAREA GENERATEFISCALDRAFTS
 *
 * Genera una poliza contable fiscal en borrador desde el libro fiscal.
 *
 * Uso:
 * php oil refine generatefiscaldrafts --rfc=SET180322811 --period=2026-05
 *
 * @package  app
 */
class Generatefiscaldrafts
{
    /**
     * RUN
     *
     * EJECUTA EL GENERADOR DE POLIZAS FISCALES PRELIMINARES.
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
            \Cli::write('Uso: php oil refine generatefiscaldrafts --rfc=RFCEMPRESA --period=2026-05');
            return;
        }

        try {
            $result = (new \Service_Core_Fiscal_AccountingDraftGenerator())->generate($rfc, $period, 1);

            \Cli::write($result['created'] ? 'Poliza fiscal borrador generada' : 'No se genero nueva poliza fiscal');
            \Cli::write(' - Entry ID: '.$result['entry_id']);
            \Cli::write(' - Folio: '.$result['folio']);
            \Cli::write(' - Lineas creadas: '.$result['lines_created']);
            \Cli::write(' - Total debe: '.$result['total_debit']);
            \Cli::write(' - Total haber: '.$result['total_credit']);
            \Cli::write(' - Diferencia: '.$result['difference']);

            if (!empty($result['warnings'])) {
                \Cli::write('Advertencias:');
                foreach ($result['warnings'] as $warning) {
                    \Cli::write(' - '.$warning);
                }
            }

            \Service_Core_Fiscal_EventLogger::log([
                'taxpayer_rfc' => $rfc,
                'fiscal_period' => $period,
                'event_type' => 'draft_generation',
                'event_status' => !$result['created'] ? 'skipped' : (!empty($result['warnings']) ? 'warning' : 'success'),
                'source_module' => 'fiscal',
                'source_entity_type' => 'accounting_journal_entry',
                'source_entity_id' => (int) $result['entry_id'],
                'summary' => $result['created'] ? 'Borrador de poliza fiscal generado.' : 'No se genero nueva poliza fiscal.',
                'details' => $result,
                'executed_by' => 1,
            ]);
        } catch (\Exception $e) {
            \Log::error('Generatefiscaldrafts: '.$e->getMessage());
            \Service_Core_Fiscal_EventLogger::log([
                'taxpayer_rfc' => $rfc,
                'fiscal_period' => $period,
                'event_type' => 'draft_generation',
                'event_status' => 'error',
                'source_module' => 'fiscal',
                'source_entity_type' => 'accounting_journal_entry',
                'summary' => 'Error generando borrador de poliza fiscal.',
                'details' => ['error' => $e->getMessage()],
                'executed_by' => 1,
            ]);
            \Cli::write('Error generando poliza fiscal borrador: '.$e->getMessage());
        }
    }

    /**
     * OPTIONS
     *
     * LEE PARAMETROS --clave=valor EN OIL.
     *
     * @access  protected
     * @return  Array
     */
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
