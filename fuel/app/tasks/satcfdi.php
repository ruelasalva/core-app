<?php
namespace Fuel\Tasks;

/**
 * TASK SATCFDI
 *
 * Importa XML CFDI al indice fiscal de Core-App.
 *
 * Uso:
 * php oil r satcfdi:import_file ruta\factura.xml
 * php oil r satcfdi:import_dir ruta\xml 100
 * php oil r satcfdi:status
 */
class Satcfdi
{
    public function run()
    {
        return $this->status();
    }

    public function status()
    {
        echo "\nAuditoria SAT CFDI\n";
        echo " - CFDI: ".\DB::count_records('core_sat_cfdi')."\n";
        echo " - Detalles: ".$this->count_if_exists('core_sat_cfdi_details')."\n";
        echo " - REP documentos: ".$this->count_if_exists('core_sat_payment_details')."\n";
        echo " - Recibidos: ".$this->count_cfdi('direction', 'received')."\n";
        echo " - Emitidos: ".$this->count_cfdi('direction', 'issued')."\n";
        echo " - Cancelados: ".$this->count_cfdi('sat_status', 'cancelado')."\n";
    }

    public function import_file($path = '')
    {
        if (trim((string) $path) === '') {
            echo "\n[ERROR] Indica la ruta del XML.\n";
            return;
        }

        $cfdi = (new \Service_Core_Sat_Cfdi_Importer())->import_file($path, [
            'origin' => 'oil',
        ]);

        echo "\n[OK] CFDI importado: ".$cfdi->uuid."\n";
        echo " - Tipo: ".$cfdi->voucher_type."\n";
        echo " - Direccion: ".$cfdi->direction."\n";
        echo " - Total: ".$cfdi->currency.' '.number_format((float) $cfdi->total, 2)."\n";
    }

    public function import_dir($path = '', $limit = 500)
    {
        if (trim((string) $path) === '' || !is_dir($path)) {
            echo "\n[ERROR] Indica una carpeta valida con XML.\n";
            return;
        }

        $limit = max(1, (int) $limit);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $service = new \Service_Core_Sat_Cfdi_Importer();
        $ok = 0;
        $errors = 0;

        foreach ($files as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'xml') {
                continue;
            }

            try {
                $service->import_file($file->getPathname(), ['origin' => 'oil_dir']);
                $ok++;
                echo ".";
            } catch (\Exception $e) {
                $errors++;
                echo "E";
                \Log::error('[SATCFDI] No se pudo importar '.$file->getPathname().': '.$e->getMessage());
            }

            if (($ok + $errors) >= $limit) {
                break;
            }
        }

        echo "\n[OK] Importacion terminada\n";
        echo " - Importados: ".$ok."\n";
        echo " - Errores: ".$errors."\n";
    }

    protected function count_cfdi($field, $value)
    {
        return (int) \DB::select()->from('core_sat_cfdi')->where($field, '=', $value)->execute()->count();
    }

    protected function count_if_exists($table)
    {
        return \DBUtil::table_exists($table) ? (int) \DB::count_records($table) : 0;
    }
}
