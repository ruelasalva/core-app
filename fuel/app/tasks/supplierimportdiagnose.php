<?php
namespace Fuel\Tasks;

/**
 * TASK SUPPLIERIMPORTDIAGNOSE
 *
 * Diagnostica infraestructura base de importacion de proveedores.
 *
 * Uso:
 * php oil refine supplierimportdiagnose
 */
class Supplierimportdiagnose
{
    public function run()
    {
        try {
            $manager = new \Service_Core_SupplierImport_Manager();
            $diagnostics = $manager->diagnostics();

            \Cli::write('');
            \Cli::write('Diagnostico de importacion de proveedores');
            \Cli::write('-----------------------------------------');
            \Cli::write('Tablas requeridas:');

            foreach ((array) \Arr::get($diagnostics, 'schema', []) as $table => $exists) {
                \Cli::write(' - '.$table.': '.($exists ? 'OK' : 'FALTA'));
            }

            if (!$manager->schema_ready()) {
                \Cli::write('');
                \Cli::write('Falta ejecutar la migracion de infraestructura de importacion de proveedores.');
                return;
            }

            \Cli::write('');
            \Cli::write('Conteos:');
            \Cli::write(' - Importaciones: '.(int) \Arr::get($diagnostics, 'runs_count', 0));
            \Cli::write(' - Filas de importacion: '.(int) \Arr::get($diagnostics, 'rows_count', 0));

            \Cli::write('');
            \Cli::write('Proveedores de integracion activos:');
            $providers = (array) \Arr::get($diagnostics, 'providers', []);
            if (empty($providers)) {
                \Cli::write(' - Sin proveedores de integracion registrados.');
            } else {
                foreach ($providers as $provider) {
                    \Cli::write(
                        ' - '.(string) $provider['code'].
                        ' | '.(string) $provider['name'].
                        ' | categoria: '.(string) $provider['category']
                    );
                }
            }

            \Cli::write('');
            \Cli::write('No se modificaron datos.');
        } catch (\Exception $e) {
            \Cli::write('');
            \Cli::write('ERROR: '.$e->getMessage());
            \Log::error('Fallo supplierimportdiagnose: '.$e->getMessage());
        }
    }
}
