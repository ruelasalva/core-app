<?php
namespace Fuel\Tasks;

/**
 * TAREA FISCALVATSUMMARY
 *
 * Muestra resumen mensual preliminar de IVA desde el libro fiscal.
 *
 * Uso:
 * php oil refine fiscalvatsummary --rfc=SET180322811 --period=2026-05
 *
 * @package  app
 */
class Fiscalvatsummary
{
    /**
     * RUN
     *
     * EJECUTA RESUMEN PRELIMINAR DE IVA.
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
            \Cli::write('Uso: php oil refine fiscalvatsummary --rfc=SET180322811 --period=2026-05');
            return;
        }

        try {
            $summary = (new \Service_Core_Fiscal_VatSummary())->calculate($rfc, $period);

            \Cli::write('Resumen mensual IVA preliminar');
            \Cli::write(' - RFC: '.$summary['rfc']);
            \Cli::write(' - Periodo: '.$summary['period']);
            \Cli::write(' - Lineas fiscales: '.$summary['ledger_rows']);
            \Cli::write(' - IVA trasladado emitido: '.$this->money($summary['issued_vat_transferred']));
            \Cli::write(' - IVA acreditable recibido: '.$this->money($summary['received_vat_transferred']));
            \Cli::write(' - IVA retenido por clientes: '.$this->money($summary['vat_retained_by_customers']));
            \Cli::write(' - IVA retenido a proveedores: '.$this->money($summary['vat_retained_from_suppliers']));
            \Cli::write(' - ISR retenido a proveedores: '.$this->money($summary['isr_retained_from_suppliers']));
            \Cli::write(' - IVA preliminar por pagar: '.$this->money($summary['preliminary_vat_payable']));

            if (!empty($summary['warnings'])) {
                \Cli::write('');
                \Cli::write('Warnings');
                foreach ($summary['warnings'] as $warning) {
                    \Cli::write(' - '.$warning);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Fiscalvatsummary: '.$e->getMessage());
            \Cli::write('Error calculando resumen IVA: '.$e->getMessage());
        }
    }

    /**
     * OPTIONS
     *
     * LEE PARAMETROS --CLAVE=VALOR EN OIL.
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

    protected function money($value)
    {
        return number_format((float) $value, 2, '.', ',');
    }
}
