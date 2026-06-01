<?php
namespace Fuel\Tasks;

/**
 * TAREA IMPORTREPTAXES
 *
 * Importa impuestos de REP desde XML SAT hacia core_sat_payment_taxes.
 *
 * Uso:
 * php oil refine importreptaxes
 * php oil refine importreptaxes --uuid=UUID_REP
 * php oil refine importreptaxes --rfc=RFC --period=YYYY-MM
 */
class Importreptaxes
{
    public function run()
    {
        $filters = $this->options();

        try {
            $result = (new \Service_Core_Sat_RepTaxImporter())->import($filters);
            $this->print_result($result, 'Importacion de impuestos REP');
        } catch (\Exception $e) {
            \Log::error('[IMPORTREPTAXES] '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function print_result(array $result, $title)
    {
        \Cli::write($title);
        \Cli::write(' - REP encontrados: '.(int) \Arr::get($result, 'rep_found', 0));
        \Cli::write(' - REP procesados: '.(int) \Arr::get($result, 'processed_rep', 0));
        \Cli::write(' - Documentos relacionados: '.(int) \Arr::get($result, 'documents_found', 0));
        \Cli::write(' - Impuestos detectados: '.(int) \Arr::get($result, 'taxes_found', 0));
        \Cli::write(' - Filas importadas: '.(int) \Arr::get($result, 'inserted', 0));
        \Cli::write(' - Filas omitidas: '.(int) \Arr::get($result, 'skipped', 0));
        \Cli::write(' - Advertencias: '.(int) \Arr::get($result, 'warning_count', 0));
        \Cli::write(' - Errores: '.(int) \Arr::get($result, 'error_count', 0));

        foreach ((array) \Arr::get($result, 'warnings', []) as $warning) {
            \Cli::write('[ADVERTENCIA] '.$warning);
        }
        foreach ((array) \Arr::get($result, 'errors', []) as $error) {
            \Cli::write('[ERROR] '.$error);
        }

        $sample = \Arr::get($result, 'sample_row');
        if ($sample) {
            \Cli::write('Muestra insertada:');
            \Cli::write(' - source_hash: '.(string) \Arr::get($sample, 'source_hash', ''));
            \Cli::write(' - payment_uuid: '.(string) \Arr::get($sample, 'payment_uuid', ''));
            \Cli::write(' - invoice_uuid: '.(string) \Arr::get($sample, 'invoice_uuid', ''));
            \Cli::write(' - impuesto: '.(string) \Arr::get($sample, 'tax_code', '').' '.(string) \Arr::get($sample, 'tax_type', ''));
            \Cli::write(' - base: '.number_format((float) \Arr::get($sample, 'base_amount', 0), 6));
            \Cli::write(' - importe: '.number_format((float) \Arr::get($sample, 'tax_amount', 0), 6));
        } else {
            \Cli::write('Muestra insertada: sin filas nuevas.');
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
