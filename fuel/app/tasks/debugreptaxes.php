<?php
namespace Fuel\Tasks;

/**
 * TAREA DEBUGREPTAXES
 *
 * Lee un REP por UUID, parsea impuestos del complemento de pago y muestra totales.
 * No guarda informacion en base de datos.
 *
 * Uso:
 * php oil refine debugreptaxes --uuid=UUID_REP
 *
 * @package  app
 */
class Debugreptaxes
{
    /**
     * RUN
     *
     * EJECUTA LA DEPURACION DE IMPUESTOS REP.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        $options = $this->options();
        $uuid = strtoupper(trim((string) \Arr::get($options, 'uuid', '')));

        if ($uuid === '') {
            \Cli::write('Uso: php oil refine debugreptaxes --uuid=UUID_REP');
            return;
        }

        if (!\DBUtil::table_exists('core_sat_cfdi')) {
            \Cli::write('[ERROR] No existe la tabla core_sat_cfdi.');
            return;
        }

        $row = \DB::select('id', 'uuid', 'voucher_type', 'xml_path')
            ->from('core_sat_cfdi')
            ->where('uuid', '=', $uuid)
            ->execute()
            ->current();

        if (!$row) {
            \Cli::write('[ERROR] No se encontro CFDI con UUID '.$uuid.'.');
            return;
        }

        if ((string) $row['voucher_type'] !== 'P') {
            \Cli::write('[ADVERTENCIA] El CFDI '.$uuid.' no es tipo P. Tipo detectado: '.(string) $row['voucher_type']);
        }

        $path = $this->resolve_xml_path((string) $row['xml_path']);
        if ($path === '') {
            \Cli::write('[ERROR] No se encontro XML fisico para el REP '.$uuid.'.');
            return;
        }

        try {
            $data = \Helper_Core_Sat_Xml::parse_file($path);
            $payments = (array) \Arr::get($data, 'payments', []);
            $tax_count = 0;
            $base_total = 0;
            $tax_total = 0;
            $by_type = [
                'DR transferred' => 0,
                'DR retained' => 0,
                'P transferred' => 0,
                'P retained' => 0,
            ];

            foreach ($payments as $payment) {
                foreach ((array) \Arr::get((array) $payment, 'taxes', []) as $tax) {
                    $tax = (array) $tax;
                    $tax_count++;
                    $base_total += (float) \Arr::get($tax, 'base_amount', 0);
                    $tax_total += (float) \Arr::get($tax, 'tax_amount', 0);
                    $key = (string) \Arr::get($tax, 'tax_scope', '').' '.(string) \Arr::get($tax, 'tax_type', '');
                    if (!array_key_exists($key, $by_type)) {
                        $by_type[$key] = 0;
                    }
                    $by_type[$key]++;
                }
            }

            \Cli::write('Depuracion REP');
            \Cli::write(' - UUID REP: '.$uuid);
            \Cli::write(' - XML: '.$path);
            \Cli::write(' - Documentos relacionados: '.count($payments));
            \Cli::write(' - Impuestos detectados: '.$tax_count);
            \Cli::write(' - Base total parseada: '.number_format($base_total, 6));
            \Cli::write(' - Impuesto total parseado: '.number_format($tax_total, 6));
            foreach ($by_type as $type => $count) {
                \Cli::write(' - '.$type.': '.$count);
            }

            if (!empty($payments)) {
                $sample = (array) $payments[0];
                \Cli::write('Muestra primer documento relacionado:');
                \Cli::write(' - invoice_uuid: '.(string) \Arr::get($sample, 'invoice_uuid', ''));
                \Cli::write(' - rep_uuid: '.(string) \Arr::get($sample, 'rep_uuid', ''));
                \Cli::write(' - payment_date: '.(string) \Arr::get($sample, 'payment_date', ''));
                \Cli::write(' - currency: '.(string) \Arr::get($sample, 'currency', ''));
                \Cli::write(' - exchange_rate: '.(string) \Arr::get($sample, 'exchange_rate', ''));
                \Cli::write(' - partiality_number: '.(string) \Arr::get($sample, 'partiality_number', ''));
                \Cli::write(' - paid_amount: '.(string) \Arr::get($sample, 'paid_amount', ''));
                \Cli::write(' - taxes: '.count((array) \Arr::get($sample, 'taxes', [])));
            }
        } catch (\Exception $e) {
            \Log::error('Debugreptaxes: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function resolve_xml_path($xml_path)
    {
        $xml_path = trim((string) $xml_path);
        if ($xml_path === '') {
            return '';
        }

        $candidates = [$xml_path];
        if (defined('DOCROOT')) {
            $candidates[] = rtrim(DOCROOT, '\\/').DIRECTORY_SEPARATOR.ltrim($xml_path, '\\/');
        }
        if (defined('APPPATH')) {
            $candidates[] = realpath(APPPATH.'../..').DIRECTORY_SEPARATOR.ltrim($xml_path, '\\/');
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
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
