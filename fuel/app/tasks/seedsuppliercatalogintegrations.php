<?php
namespace Fuel\Tasks;

/**
 * TASK SEEDSUPPLIERCATALOGINTEGRATIONS
 *
 * Crea proveedores de integracion para catalogos de proveedor solo si no existen.
 * No configura credenciales, no importa productos y no toca inventario.
 */
class Seedsuppliercatalogintegrations
{
    protected $providers = [
        ['csv_manual', 'CSV / Excel manual'],
        ['cva_api', 'CVA API'],
        ['ct_api', 'CT API'],
        ['syscom_api', 'Syscom API'],
        ['tvc_api', 'TVC API'],
        ['pch_api', 'PCH API'],
        ['exel_api', 'Exel API'],
        ['tonersparaimpresoras_scraper', 'Toners Para Impresoras Scraper'],
    ];

    public function run()
    {
        if (!\DBUtil::table_exists('core_integration_providers')) {
            \Cli::write('[ERROR] Falta la tabla core_integration_providers. Ejecuta migraciones de integraciones.');
            \Log::warning('No se pudo sembrar supplier_catalog: falta core_integration_providers.');
            return;
        }

        $created = 0;
        $existing = 0;

        foreach ($this->providers as $index => $item) {
            $code = $item[0];
            $name = $item[1];

            $row = \DB::select('id')
                ->from('core_integration_providers')
                ->where('code', '=', $code)
                ->execute()
                ->current();

            if ($row) {
                $existing++;
                \Cli::write('Existe: '.$code.' - '.$name);
                continue;
            }

            \DB::insert('core_integration_providers')->set([
                'code' => $code,
                'name' => $name,
                'category' => 'supplier_catalog',
                'description' => 'Fuente de importacion de catalogo de proveedor.',
                'website_url' => '',
                'adapter_class' => '',
                'requires_install' => 0,
                'install_notes' => 'Pendiente de implementar. Sin credenciales requeridas en esta fase.',
                'config_schema_json' => null,
                'sort_order' => ($index + 1) * 10,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();

            $created++;
            \Cli::write('Creado: '.$code.' - '.$name);
        }

        \Log::info('Seed supplier_catalog ejecutado. creados='.$created.' existentes='.$existing);
        \Cli::write('');
        \Cli::write('Resumen: creados='.$created.' existentes='.$existing);
        \Cli::write('No se tocaron productos, precios, inventario ni imagenes.');
    }
}
