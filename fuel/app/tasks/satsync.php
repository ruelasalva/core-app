<?php
namespace Fuel\Tasks;

/**
 * TASK SATSYNC
 *
 * Ejecuta procesos SAT/CFDI desde Oil.
 *
 * Uso:
 * php oil r satsync:status
 * php oil r satsync:request metadata received 2026-05-01 2026-05-12
 * php oil r satsync:submit
 * php oil r satsync:verify
 * php oil r satsync:download
 * php oil r satsync:compare
 */
class Satsync
{
    public function run()
    {
        return $this->status();
    }

    public function status()
    {
        echo "\nSAT Sync\n";
        echo " - Solicitudes: ".\DB::count_records('core_sat_sync_requests')."\n";
        echo " - CFDI: ".\DB::count_records('core_sat_cfdi')."\n";
        echo " - Sin XML: ".$this->count('core_sat_cfdi', 'missing_xml', 1)."\n";
        echo " - Cancelados: ".$this->count('core_sat_cfdi', 'sat_status', 'cancelado')."\n";
    }

    public function request($download_type = 'metadata', $direction = 'received', $date_from = null, $date_to = null)
    {
        $service = new \Service_Core_Sat_Sync();
        $request = $service->create_request([
            'download_type' => $download_type,
            'direction' => $direction,
            'date_from' => $date_from ?: date('Y-m-d'),
            'date_to' => $date_to ?: ($date_from ?: date('Y-m-d')),
        ]);

        echo "\nSolicitud SAT creada: #".$request->id." ".$request->request_type."\n";
    }

    public function submit($limit = 5)
    {
        $result = (new \Service_Core_Sat_Sync())->submit_pending((int) $limit);
        echo "\nSAT submit\n";
        echo " - Procesadas: ".$result['processed']."\n";
        echo " - Bloqueadas: ".$result['blocked']."\n";
        if (!empty($result['errors'])) {
            echo " - Errores: ".implode(' | ', $result['errors'])."\n";
        }
    }

    public function verify($limit = 10)
    {
        $result = (new \Service_Core_Sat_Sync())->verify_requests((int) $limit);
        echo "\nSAT verify\n";
        echo " - Procesadas: ".$result['processed']."\n";
        echo " - Completadas: ".$result['completed']."\n";
    }

    public function compare($limit = 500)
    {
        $result = (new \Service_Core_Sat_Sync())->compare_metadata((int) $limit);
        echo "\nSAT compare metadata\n";
        echo " - Marcados sin XML: ".$result['missing_marked']."\n";
        echo " - Cancelados actuales: ".$result['cancelled_count']."\n";
    }

    public function download($limit = 5)
    {
        $result = (new \Service_Core_Sat_Sync())->download_packages((int) $limit);
        echo "\nSAT download\n";
        echo " - Paquetes descargados: ".$result['downloaded']."\n";
        echo " - CFDI/metadata procesados: ".$result['processed']."\n";
        if (!empty($result['errors'])) {
            echo " - Errores: ".implode(' | ', $result['errors'])."\n";
        }
    }

    protected function count($table, $field, $value)
    {
        return (int) \DB::select()->from($table)->where($field, '=', $value)->execute()->count();
    }
}
