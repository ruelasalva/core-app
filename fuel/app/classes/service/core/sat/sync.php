<?php

/**
 * SERVICE CORE_SAT_SYNC
 *
 * Orquesta solicitudes SAT para XML, metadata y comparacion fiscal.
 *
 * @package  app
 */
class Service_Core_Sat_Sync
{
    /**
     * CREATE REQUEST
     *
     * CREA UNA SOLICITUD LOCAL PARA DESCARGA XML O METADATA
     *
     * @access  public
     * @return  Model_Core_Sat_Sync_Request
     */
    public function create_request(array $data)
    {
        # SE NORMALIZAN DATOS
        $download_type = \Arr::get($data, 'download_type', 'xml') === 'metadata' ? 'metadata' : 'xml';
        $direction = \Arr::get($data, 'direction', 'received') === 'issued' ? 'issued' : 'received';
        $date_from = trim((string) \Arr::get($data, 'date_from', date('Y-m-d')));
        $date_to = trim((string) \Arr::get($data, 'date_to', $date_from));

        # SE CREA REGISTRO LOCAL
        $request = Model_Core_Sat_Sync_Request::forge([
            'request_type' => $direction.'_'.$download_type,
            'download_type' => $download_type,
            'direction' => $direction,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'status' => 'pending',
            'sat_request_id' => '',
            'attempts' => 0,
            'package_count' => 0,
            'downloaded_count' => 0,
            'processed_count' => 0,
            'missing_count' => 0,
            'cancelled_count' => 0,
            'error_message' => '',
        ]);
        $request->save();

        # SE AUDITA SOLICITUD
        Helper_Core_Audit::log([
            'module' => 'sat',
            'action' => 'create_sync_request',
            'entity_type' => 'sat_sync_request',
            'entity_id' => (int) $request->id,
            'summary' => 'Solicitud SAT '.$request->request_type.' '.$date_from.' a '.$date_to,
            'new_values' => $request->to_array(),
        ]);

        return $request;
    }

    /**
     * SUBMIT PENDING
     *
     * PREPARA SOLICITUDES PENDIENTES; EN PRODUCCION REQUIERE ADAPTADOR SAT REAL
     *
     * @access  public
     * @return  Array
     */
    public function submit_pending($limit = 5)
    {
        # SE CONSULTAN SOLICITUDES PENDIENTES
        $requests = Model_Core_Sat_Sync_Request::query()
            ->where('status', '=', 'pending')
            ->order_by('id', 'asc')
            ->limit((int) $limit)
            ->get();

        # SE PROCESAN SOLICITUDES
        $result = ['processed' => 0, 'blocked' => 0, 'errors' => []];
        foreach ($requests as $request) {
            try {
                $config = Model_Core_Sat_Config::get_current();
                if ($config->mode === 'production') {
                    $this->mark_blocked($request, 'Pendiente implementar adaptador phpcfdi/sat-ws-descarga-masiva para produccion.');
                    $result['blocked']++;
                    continue;
                }

                # EN MODO TEST SE SIMULA ID DE SOLICITUD SIN CONTACTAR AL SAT
                $request->sat_request_id = 'LOCAL-'.strtoupper($request->download_type).'-'.$request->id.'-'.date('YmdHis');
                $request->status = 'requested';
                $request->attempts = (int) $request->attempts + 1;
                $request->error_message = '';
                $request->save();

                $this->event($request, 'request_submitted', 'Solicitud SAT preparada en modo test.');
                $result['processed']++;
            } catch (\Exception $e) {
                $result['errors'][] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * VERIFY REQUESTS
     *
     * VERIFICA SOLICITUDES ENVIADAS; EN TEST LAS MARCA COMO SIN RESULTADOS
     *
     * @access  public
     * @return  Array
     */
    public function verify_requests($limit = 10)
    {
        # SE CONSULTAN SOLICITUDES ENVIADAS
        $requests = Model_Core_Sat_Sync_Request::query()
            ->where('status', 'in', ['requested', 'accepted', 'processing'])
            ->order_by('id', 'asc')
            ->limit((int) $limit)
            ->get();

        # SE PROCESAN SOLICITUDES
        $result = ['processed' => 0, 'completed' => 0];
        foreach ($requests as $request) {
            $config = Model_Core_Sat_Config::get_current();
            if ($config->mode === 'production') {
                $this->mark_blocked($request, 'Pendiente consultar estado real con adaptador SAT.');
                continue;
            }

            # EN MODO TEST QUEDA TERMINADA SIN RESULTADOS REALES
            $request->status = 'completed_no_results';
            $request->package_count = 0;
            $request->downloaded_count = 0;
            $request->processed_count = 0;
            $request->save();

            $this->event($request, 'request_verified', 'Solicitud SAT verificada en modo test sin paquetes.');
            $result['processed']++;
            $result['completed']++;
        }

        return $result;
    }

    /**
     * COMPARE METADATA
     *
     * MARCA CFDI DE METADATA SIN XML Y CUENTA CANCELADOS DETECTADOS
     *
     * @access  public
     * @return  Array
     */
    public function compare_metadata($limit = 500)
    {
        # SE DETECTAN CFDI CREADOS POR METADATA SIN XML
        $missing = Model_Core_Sat_Cfdi::query()
            ->where('origin', '=', 'metadata')
            ->where('xml_path', '=', '')
            ->limit((int) $limit)
            ->get();

        $missing_count = 0;
        foreach ($missing as $cfdi) {
            if ((int) $cfdi->missing_xml !== 1) {
                $cfdi->missing_xml = 1;
                $cfdi->metadata_seen_at = $cfdi->metadata_seen_at ?: time();
                $cfdi->save();
                $missing_count++;
            }
        }

        # SE CUENTAN CANCELADOS ACTUALES
        $cancelled_count = (int) \DB::select()->from('core_sat_cfdi')->where('sat_status', '=', 'cancelado')->execute()->count();

        # SE AUDITA RESUMEN
        Helper_Core_Audit::log([
            'module' => 'sat',
            'action' => 'compare_metadata',
            'entity_type' => 'sat_cfdi',
            'entity_id' => 0,
            'summary' => 'Comparacion metadata SAT: '.$missing_count.' CFDI marcados sin XML.',
            'metadata' => [
                'missing_count' => $missing_count,
                'cancelled_count' => $cancelled_count,
            ],
        ]);

        return [
            'missing_marked' => $missing_count,
            'cancelled_count' => $cancelled_count,
        ];
    }

    protected function mark_blocked(Model_Core_Sat_Sync_Request $request, $message)
    {
        $request->status = 'blocked';
        $request->attempts = (int) $request->attempts + 1;
        $request->error_message = $message;
        $request->save();

        $this->event($request, 'request_blocked', $message);
    }

    protected function event(Model_Core_Sat_Sync_Request $request, $event_type, $summary)
    {
        Model_Core_Sat_Cfdi_Event::forge([
            'cfdi_id' => 0,
            'event_type' => $event_type,
            'payload_json' => json_encode([
                'request_id' => (int) $request->id,
                'sat_request_id' => (string) $request->sat_request_id,
                'status' => (string) $request->status,
                'summary' => $summary,
            ]),
        ])->save();
    }
}
