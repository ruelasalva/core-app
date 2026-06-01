<?php
namespace Fuel\Tasks;

/**
 * TAREA BUILDFISCALLEDGER
 *
 * Construye el libro fiscal base desde CFDI SAT importados.
 *
 * Uso:
 * php oil refine buildfiscalledger --rfc=RFCEMPRESA --period=2026-05
 *
 * @package  app
 */
class Buildfiscalledger
{
    /**
     * RUN
     *
     * EJECUTA EL BUILDER DEL LIBRO FISCAL.
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
            \Cli::write('Uso: php oil refine buildfiscalledger --rfc=RFCEMPRESA --period=2026-05');
            return;
        }

        try {
            $result = (new \Service_Core_Fiscal_TaxLedgerBuilder())->build($rfc, $period);

            \Cli::write('Libro fiscal generado');
            \Cli::write(' - RFC: '.$result['rfc']);
            \Cli::write(' - Periodo: '.$result['period']);
            \Cli::write(' - Periodo fiscal ID: '.$result['fiscal_period_id']);
            \Cli::write(' - Build ID: '.$result['build_id']);
            \Cli::write(' - CFDI procesados: '.$result['cfdi_count']);
            \Cli::write(' - Detalles procesados: '.$result['detail_count']);
            \Cli::write(' - Lineas fiscales creadas: '.$result['line_count']);
            \Cli::write(' - Lineas concepto PUE creadas: '.$result['pue_concept_lines_created']);
            \Cli::write(' - Lineas impuesto concepto PPD omitidas: '.$result['ppd_concept_tax_lines_skipped']);
            \Cli::write(' - CFDI cancelados omitidos: '.$result['skipped_cancelled']);
            \Cli::write(' - Duplicados omitidos: '.$result['skipped_duplicates']);
            \Cli::write(' - Impuestos REP DR encontrados: '.$result['rep_tax_rows_found']);
            \Cli::write(' - Lineas REP DR creadas: '.$result['rep_tax_lines_inserted']);
            \Cli::write(' - REP DR duplicados omitidos: '.$result['rep_tax_duplicates_skipped']);
            \Cli::write(' - REP DR sin factura relacionada: '.$result['rep_tax_missing_related_invoice']);
            \Cli::write(' - REP cancelados omitidos: '.$result['rep_tax_cancelled_skipped']);
            \Cli::write(' - Errores REP DR: '.$result['rep_tax_errors']);
            \Cli::write(' - Errores: '.$result['error_count']);

            if (!empty($result['warnings'])) {
                \Cli::write('Advertencias:');
                foreach ($result['warnings'] as $warning) {
                    \Cli::write(' - '.$warning);
                }
            }

            if (!empty($result['errors'])) {
                \Cli::write('Errores detectados:');
                foreach ($result['errors'] as $error) {
                    \Cli::write(' - '.$error);
                }
            }

            \Service_Core_Fiscal_EventLogger::log([
                'taxpayer_rfc' => $result['rfc'],
                'fiscal_period' => $result['period'],
                'event_type' => 'ledger_build',
                'event_status' => (int) $result['error_count'] > 0 ? 'warning' : 'success',
                'source_module' => 'fiscal',
                'source_entity_type' => 'fiscal_ledger_build',
                'source_entity_id' => (int) $result['build_id'],
                'summary' => 'Construccion de libro fiscal finalizada.',
                'details' => $result,
                'executed_by' => 0,
            ]);
        } catch (\Exception $e) {
            \Log::error('Buildfiscalledger: '.$e->getMessage());
            \Service_Core_Fiscal_EventLogger::log([
                'taxpayer_rfc' => $rfc,
                'fiscal_period' => $period,
                'event_type' => 'ledger_build',
                'event_status' => 'error',
                'source_module' => 'fiscal',
                'source_entity_type' => 'fiscal_ledger_build',
                'summary' => 'Error construyendo libro fiscal.',
                'details' => ['error' => $e->getMessage()],
                'executed_by' => 0,
            ]);
            \Cli::write('Error construyendo libro fiscal: '.$e->getMessage());
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
