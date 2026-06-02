<?php
namespace Fuel\Tasks;

/**
 * TASK SUPPLIERIMPORTCSV
 *
 * Lee CSV generico de proveedor y lo guarda en staging cuando dry-run=0.
 * No crea productos, no actualiza precios y no toca inventario.
 *
 * Uso:
 * php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=1
 * php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=0
 */
class Supplierimportcsv
{
    public function run()
    {
        $options = $this->options();

        try {
            $result = (new \Service_Core_SupplierImport_Manager())->import_csv($options);
            $this->print_result($result);
        } catch (\Exception $e) {
            \Cli::write('[ERROR] '.$e->getMessage());
            \Log::error('Fallo supplierimportcsv: '.$e->getMessage());
        }
    }

    protected function print_result(array $result)
    {
        \Cli::write('');
        \Cli::write('Importacion CSV de proveedor');
        \Cli::write('----------------------------');
        \Cli::write('Proveedor: '.(string) \Arr::get($result, 'provider', ''));
        \Cli::write('Archivo: '.(string) \Arr::get($result, 'file', ''));
        \Cli::write('Modo: '.((int) \Arr::get($result, 'dry_run', 1) === 1 ? 'dry-run' : 'staging'));
        \Cli::write('Run ID: '.(int) \Arr::get($result, 'import_run_id', 0));
        \Cli::write('');
        \Cli::write('Totales:');
        \Cli::write(' - Filas leidas: '.(int) \Arr::get($result, 'total_rows', 0));
        \Cli::write(' - Filas normalizadas: '.(int) \Arr::get($result, 'normalized', 0));
        \Cli::write(' - Filas insertadas: '.(int) \Arr::get($result, 'inserted', 0));
        \Cli::write(' - Filas omitidas: '.(int) \Arr::get($result, 'skipped', 0));
        \Cli::write(' - Advertencias: '.(int) \Arr::get($result, 'warnings', 0));
        \Cli::write(' - Errores: '.(int) \Arr::get($result, 'errors', 0));

        $messages = array_slice((array) \Arr::get($result, 'messages', []), 0, 20);
        if (!empty($messages)) {
            \Cli::write('');
            \Cli::write('Mensajes:');
            foreach ($messages as $message) {
                \Cli::write(' - '.$message);
            }
        }

        \Cli::write('');
        \Cli::write('No se tocaron productos reales, precios, inventario ni imagenes.');
    }

    protected function options()
    {
        $options = ['dry-run' => 1];
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
