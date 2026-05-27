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
        $date_from = $this->normalize_sat_date(\Arr::get($data, 'date_from', $this->sat_today()));
        $date_to = trim((string) \Arr::get($data, 'date_to', $date_from));
        $date_to = $this->normalize_sat_date($date_to ?: $date_from);
        $today = $this->sat_today();

        if ($date_from > $today) {
            throw new \RuntimeException('La fecha inicial no puede ser futura para el SAT. Hoy SAT: '.$today.'.');
        }
        if ($date_to > $today) {
            $date_to = $today;
        }
        if ($date_to < $date_from) {
            throw new \RuntimeException('La fecha final no puede ser menor que la inicial.');
        }

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
     * ENVIA SOLICITUDES PENDIENTES AL SAT O LAS SIMULA EN MODO TEST
     *
     * @access  public
     * @return  Array
     */
    public function submit_pending($limit = 5, array $request_ids = [])
    {
        # SE CONSULTAN SOLICITUDES PENDIENTES
        $query = Model_Core_Sat_Sync_Request::query()->order_by('id', 'asc');
        if (!empty($request_ids)) {
            $query->where('id', 'in', $this->clean_ids($request_ids));
            $query->where('status', 'in', ['pending', 'blocked']);
        } else {
            $query->where('status', '=', 'pending')->limit((int) $limit);
        }
        $requests = $query->get();

        # SE PROCESAN SOLICITUDES
        $result = ['processed' => 0, 'blocked' => 0, 'errors' => []];
        foreach ($requests as $request) {
            try {
                $config = Model_Core_Sat_Config::get_current();
                if ($config->mode === 'production') {
                    $service = $this->sat_service();
                    $query = $this->with_sat_timezone(function () use ($service, $request) {
                        return $service->query($this->query_parameters($request));
                    });
                    $message = $query->getStatus()->getCode().' '.$query->getStatus()->getMessage();

                    if ($query->getStatus()->isAccepted()) {
                        $request->attempts = (int) $request->attempts + 1;
                        $request->sat_request_id = $query->getRequestId();
                        $request->status = 'requested';
                        $request->error_message = '';
                        $request->save();
                        $this->event($request, 'request_submitted', 'Solicitud SAT enviada: '.$message);
                        $result['processed']++;
                    } else {
                        $this->mark_blocked($request, 'SAT no acepto la solicitud: '.$message);
                        $result['blocked']++;
                    }
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
                $this->mark_blocked($request, $e->getMessage());
                $result['errors'][] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * VERIFY REQUESTS
     *
     * VERIFICA SOLICITUDES ENVIADAS Y REGISTRA PAQUETES DISPONIBLES
     *
     * @access  public
     * @return  Array
     */
    public function verify_requests($limit = 10, array $request_ids = [])
    {
        # SE CONSULTAN SOLICITUDES ENVIADAS
        $query = Model_Core_Sat_Sync_Request::query()
            ->where('status', 'in', ['requested', 'accepted', 'processing', 'ready_to_download'])
            ->order_by('id', 'asc');
        if (!empty($request_ids)) {
            $query->where('id', 'in', $this->clean_ids($request_ids));
        } else {
            $query->limit((int) $limit);
        }
        $requests = $query->get();

        # SE PROCESAN SOLICITUDES
        $result = ['processed' => 0, 'completed' => 0, 'packages' => 0, 'errors' => []];
        foreach ($requests as $request) {
            try {
                $config = Model_Core_Sat_Config::get_current();
                if ($config->mode === 'production') {
                    $verify = $this->sat_service()->verify((string) $request->sat_request_id);
                    $status = $verify->getStatusRequest();
                    $code = $verify->getCodeRequest();

                    $request->attempts = (int) $request->attempts + 1;
                    $request->package_count = (int) $verify->countPackages();
                    $request->error_message = trim($verify->getStatus()->getCode().' '.$verify->getStatus()->getMessage().' | '.$code->getValue().' '.$code->getMessage().' | '.$status->getMessage());

                    if ($status->isFinished()) {
                        foreach ($verify->getPackagesIds() as $package_id) {
                            if (!$this->package_exists($package_id)) {
                                Model_Core_Sat_Package::forge([
                                    'sync_request_id' => (int) $request->id,
                                    'package_id' => (string) $package_id,
                                    'package_type' => (string) $request->download_type,
                                    'xml_count' => 0,
                                    'status' => 'ready',
                                    'path' => '',
                                    'sha256_hash' => '',
                                ])->save();
                                $result['packages']++;
                            }
                        }
                        $request->status = $request->package_count > 0 ? 'ready_to_download' : 'completed_no_results';
                        $result['completed']++;
                    } elseif ($status->isAccepted() || $status->isInProgress()) {
                        $request->status = 'processing';
                    } else {
                        $request->status = 'blocked';
                    }

                    $request->save();
                    $this->event($request, 'request_verified', 'Solicitud SAT verificada: '.$request->error_message);
                    $result['processed']++;
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
            } catch (\Exception $e) {
                $this->mark_blocked($request, $e->getMessage());
                $result['errors'][] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * DOWNLOAD PACKAGES
     *
     * DESCARGA PAQUETES DISPONIBLES Y PROCESA XML CUANDO APLICA
     *
     * @access  public
     * @return  Array
     */
    public function download_packages($limit = 5, array $package_ids = [], array $request_ids = [])
    {
        $query = Model_Core_Sat_Package::query()
            ->where('status', 'in', ['ready', 'download_error', 'download_limited'])
            ->order_by('id', 'asc');
        if (!empty($package_ids)) {
            $query->where('id', 'in', $this->clean_ids($package_ids));
        } elseif (!empty($request_ids)) {
            $query->where('sync_request_id', 'in', $this->clean_ids($request_ids));
        } else {
            $query->limit((int) $limit);
        }
        $packages = $query->get();

        $result = ['downloaded' => 0, 'processed' => 0, 'errors' => []];
        foreach ($packages as $package) {
            try {
                $request = Model_Core_Sat_Sync_Request::find((int) $package->sync_request_id);
                if (!$request) {
                    throw new \RuntimeException('Solicitud SAT no encontrada para paquete '.$package->package_id.'.');
                }

                $local_path = $this->local_package_path($request, $package);
                if ($local_path !== '') {
                    $package->path = $package->path ?: $this->package_relative_path($request, $package);
                    $package->status = 'downloaded';
                    $package->xml_count = $this->process_package($package, $local_path, $request);
                    $package->status = 'processed';
                    $package->save();

                    $this->refresh_request_counts($request);
                    $result['processed'] += (int) $package->xml_count;
                    continue;
                }
                if ($package->status === 'download_limited') {
                    throw new \RuntimeException('El SAT ya marco limite de descargas para el paquete '.$package->package_id.' y no hay ZIP local para reprocesar.');
                }

                $config = Model_Core_Sat_Config::get_current();
                if ($config->mode !== 'production') {
                    $package->status = 'downloaded';
                    $package->save();
                    $this->refresh_request_counts($request);
                    $result['downloaded']++;
                    continue;
                }

                $download = $this->sat_service()->download((string) $package->package_id);
                if (!$download->getStatus()->isAccepted()) {
                    throw new \RuntimeException('SAT no entrego paquete '.$package->package_id.': '.$download->getStatus()->getCode().' '.$download->getStatus()->getMessage());
                }

                $zip_content = $download->getPackageContent();
                $relative_path = $this->package_relative_path($request, $package);
                $absolute_path = $this->absolute_storage_path($relative_path);
                $this->ensure_dir(dirname($absolute_path));
                file_put_contents($absolute_path, $zip_content);

                $package->path = $relative_path;
                $package->sha256_hash = hash('sha256', $zip_content);
                $package->status = 'downloaded';
                $package->save();
                $package->xml_count = $this->process_package($package, $absolute_path, $request);
                $package->status = 'processed';
                $package->save();

                $this->refresh_request_counts($request);
                $result['downloaded']++;
                $result['processed'] += (int) $package->xml_count;
            } catch (\Exception $e) {
                $package->status = $package->status === 'download_limited' || strpos($e->getMessage(), '5008') !== false ? 'download_limited' : 'download_error';
                $package->save();
                $result['errors'][] = $e->getMessage();
            }
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

    protected function sat_service()
    {
        if (!class_exists('\PhpCfdi\SatWsDescargaMasiva\Service')) {
            throw new \RuntimeException('Falta instalar phpcfdi/sat-ws-descarga-masiva con composer.');
        }

        $credential = Model_Core_Sat_Credential::query()
            ->where('credential_type', '=', 'fiel')
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->get_one();

        if (!$credential) {
            throw new \RuntimeException('No hay FIEL activa para descargar del SAT.');
        }

        $cer_path = $this->absolute_storage_path((string) $credential->cer_path);
        $key_path = $this->absolute_storage_path((string) $credential->key_path);
        if (!is_file($cer_path) || !is_file($key_path)) {
            throw new \RuntimeException('La FIEL activa necesita archivos .cer y .key cargados.');
        }

        $password = (string) $credential->password_encrypted;
        $password = $password !== '' ? \Crypt::decode($password) : '';
        if ($password === '') {
            throw new \RuntimeException('La FIEL activa necesita password de la llave privada.');
        }

        $fiel = \PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel::create(
            file_get_contents($cer_path),
            file_get_contents($key_path),
            $password
        );
        if (!$fiel->isValid()) {
            throw new \RuntimeException('La FIEL no es valida o esta vencida.');
        }

        return new \PhpCfdi\SatWsDescargaMasiva\Service(
            new \PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder($fiel),
            new \PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient()
        );
    }

    protected function query_parameters(Model_Core_Sat_Sync_Request $request)
    {
        $date_from = $this->normalize_sat_date($request->date_from);
        $date_to = $this->normalize_sat_date($request->date_to ?: $date_from);
        if ($date_from > $this->sat_today()) {
            throw new \RuntimeException('La fecha inicial '.$date_from.' es futura para el SAT.');
        }
        if ($date_to > $this->sat_today()) {
            $date_to = $this->sat_today();
        }

        $period = \PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod::createFromValues(
            new \DateTimeImmutable($date_from.' 00:00:00', new \DateTimeZone('America/Mexico_City')),
            new \DateTimeImmutable($date_to.' 23:59:59', new \DateTimeZone('America/Mexico_City'))
        );
        $download_type = $request->direction === 'issued'
            ? \PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType::issued()
            : \PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType::received();
        $request_type = $request->download_type === 'xml'
            ? \PhpCfdi\SatWsDescargaMasiva\Shared\RequestType::xml()
            : \PhpCfdi\SatWsDescargaMasiva\Shared\RequestType::metadata();

        $parameters = \PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters::create($period, $download_type, $request_type);
        if ($request->download_type === 'xml') {
            $parameters = $parameters->withDocumentStatus(\PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus::active());
        }

        return $parameters;
    }

    protected function with_sat_timezone($callback)
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('America/Mexico_City');
        try {
            return $callback();
        } finally {
            date_default_timezone_set($previous);
        }
    }

    protected function sat_today()
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d');
    }

    protected function normalize_sat_date($value)
    {
        $value = trim((string) $value);
        $timezone = new \DateTimeZone('America/Mexico_City');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            $date = new \DateTimeImmutable($value, $timezone);
        } catch (\Exception $e) {
            throw new \RuntimeException('Fecha SAT invalida: '.$value);
        }

        return $date->setTimezone($timezone)->format('Y-m-d');
    }

    protected function package_exists($package_id)
    {
        return (bool) Model_Core_Sat_Package::query()
            ->where('package_id', '=', (string) $package_id)
            ->get_one();
    }

    protected function process_package(Model_Core_Sat_Package $package, $absolute_path, Model_Core_Sat_Sync_Request $request = null)
    {
        if ($package->package_type === 'metadata') {
            return $this->process_metadata_package($absolute_path, $request);
        }

        $reader = \PhpCfdi\SatWsDescargaMasiva\PackageReader\CfdiPackageReader::createFromFile($absolute_path);
        $importer = new Service_Core_Sat_Cfdi_Importer();
        $count = 0;
        foreach ($reader->cfdis() as $uuid => $xml) {
            $xml_relative = 'fuel/app/storage/sat/xml/'.date('Y/m').'/'.strtoupper($uuid).'.xml';
            $xml_absolute = $this->absolute_storage_path($xml_relative);
            $this->ensure_dir(dirname($xml_absolute));
            file_put_contents($xml_absolute, $xml);
            $importer->import_file($xml_absolute, ['origin' => 'sat', 'xml_path' => $xml_relative]);
            $count++;
        }

        return $count;
    }

    protected function process_metadata_package($absolute_path, Model_Core_Sat_Sync_Request $request = null)
    {
        $reader = \PhpCfdi\SatWsDescargaMasiva\PackageReader\MetadataPackageReader::createFromFile($absolute_path);
        $count = 0;
        foreach ($reader->metadata() as $uuid => $item) {
            $uuid = strtoupper((string) $uuid);
            if ($uuid === '') {
                continue;
            }

            $cfdi = Model_Core_Sat_Cfdi::query()->where('uuid', '=', $uuid)->get_one();
            if (!$cfdi) {
                $cfdi = Model_Core_Sat_Cfdi::forge(['uuid' => $uuid]);
            }

            $emitter_party = $this->party_by_rfc((string) $item->rfcEmisor);
            $receiver_party = $this->party_by_rfc((string) $item->rfcReceptor);
            $direction = $request && in_array((string) $request->direction, ['issued', 'received'], true)
                ? (string) $request->direction
                : $this->direction_from_rfc((string) $item->rfcEmisor, (string) $item->rfcReceptor);
            $sat_status = strtolower((string) $item->estatus) === 'cancelado' ? 'cancelado' : 'vigente';

            $cfdi->set([
                'uuid' => $uuid,
                'direction' => $direction,
                'version' => (string) $cfdi->version,
                'serie' => (string) $cfdi->serie,
                'folio' => (string) $cfdi->folio,
                'emitter_rfc' => strtoupper((string) $item->rfcEmisor),
                'emitter_party_id' => $emitter_party ? (int) $emitter_party['id'] : 0,
                'emitter_name' => (string) $item->nombreEmisor,
                'emitter_regime' => (string) $cfdi->emitter_regime,
                'receiver_rfc' => strtoupper((string) $item->rfcReceptor),
                'receiver_party_id' => $receiver_party ? (int) $receiver_party['id'] : 0,
                'customer_party_id' => $direction === 'issued' && $receiver_party ? (int) $receiver_party['id'] : 0,
                'supplier_party_id' => $direction === 'received' && $emitter_party ? (int) $emitter_party['id'] : 0,
                'receiver_name' => (string) $item->nombreReceptor,
                'receiver_regime' => (string) $cfdi->receiver_regime,
                'receiver_zip' => (string) $cfdi->receiver_zip,
                'issued_at' => $this->datetime((string) $item->fechaEmision),
                'stamped_at' => $this->nullable_datetime((string) $item->fechaCertificacionSat),
                'total' => (float) $item->monto,
                'subtotal' => (float) $cfdi->subtotal,
                'discount' => (float) $cfdi->discount,
                'tax_transferred_total' => (float) $cfdi->tax_transferred_total,
                'tax_withheld_total' => (float) $cfdi->tax_withheld_total,
                'currency' => (string) ($cfdi->currency ?: 'MXN'),
                'voucher_type' => (string) $item->efectoComprobante,
                'export_code' => (string) $cfdi->export_code,
                'place_of_issue' => (string) $cfdi->place_of_issue,
                'payment_method' => (string) $cfdi->payment_method,
                'payment_form' => (string) $cfdi->payment_form,
                'conditions_payment' => (string) $cfdi->conditions_payment,
                'certificate_number' => (string) $cfdi->certificate_number,
                'certificate_sat_number' => (string) $cfdi->certificate_sat_number,
                'pac_rfc' => (string) $item->rfcPac,
                'seal_cfdi' => (string) $cfdi->seal_cfdi,
                'seal_sat' => (string) $cfdi->seal_sat,
                'cfdi_use' => (string) $cfdi->cfdi_use,
                'sat_status' => $sat_status,
                'sat_status_code' => '',
                'sat_status_message' => (string) $item->estatus,
                'cancelled_at' => strtotime((string) $item->fechaCancelacion) ?: 0,
                'last_validated_at' => time(),
                'metadata_seen_at' => time(),
                'missing_xml' => (string) $cfdi->xml_path === '' ? 1 : 0,
                'complements_json' => $cfdi->complements_json ?: null,
                'has_payment_complement' => (int) $cfdi->has_payment_complement,
                'has_waybill' => (int) $cfdi->has_waybill,
                'origin' => (string) $cfdi->xml_path === '' ? 'metadata' : (string) $cfdi->origin,
                'processed' => (int) $cfdi->processed,
                'accounted' => (int) $cfdi->accounted,
                'xml_path' => (string) $cfdi->xml_path,
                'sales_status' => $direction === 'issued' ? ((int) ($receiver_party['id'] ?? 0) > 0 ? 'candidate' : 'unmatched') : (string) $cfdi->sales_status,
                'purchase_status' => $direction === 'received' ? ((int) ($emitter_party['id'] ?? 0) > 0 ? 'candidate' : 'unmatched') : (string) $cfdi->purchase_status,
                'portal_visible_customer' => $direction === 'issued' && $receiver_party ? 1 : (int) $cfdi->portal_visible_customer,
                'portal_visible_supplier' => $direction === 'received' && $emitter_party ? 1 : (int) $cfdi->portal_visible_supplier,
                'reviewed_by' => (int) $cfdi->reviewed_by,
                'reviewed_at' => (int) $cfdi->reviewed_at,
            ]);
            $cfdi->save();
            $count++;
        }

        return $count;
    }

    protected function clean_ids(array $ids)
    {
        $clean = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[] = $id;
            }
        }
        return array_values(array_unique($clean));
    }

    protected function package_relative_path(Model_Core_Sat_Sync_Request $request, Model_Core_Sat_Package $package)
    {
        $safe_package = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $package->package_id);
        return 'fuel/app/storage/sat/packages/'.date('Y/m').'/'.$request->download_type.'_'.$request->direction.'_'.$safe_package.'.zip';
    }

    protected function local_package_path(Model_Core_Sat_Sync_Request $request, Model_Core_Sat_Package $package)
    {
        $candidates = [];
        if ((string) $package->path !== '') {
            $candidates[] = (string) $package->path;
        }
        $candidates[] = $this->package_relative_path($request, $package);

        foreach ($candidates as $candidate) {
            $absolute = $this->absolute_storage_path($candidate);
            if ($absolute !== '' && is_file($absolute)) {
                return $absolute;
            }
        }

        return '';
    }

    protected function absolute_storage_path($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        $normalized = str_replace('\\', '/', $path);
        if (is_file($path) || is_dir($path)) {
            return $path;
        }
        if (strpos($normalized, 'fuel/app/') === 0) {
            return APPPATH.substr($normalized, strlen('fuel/app/'));
        }
        if (strpos($normalized, 'storage/') === 0) {
            return APPPATH.$normalized;
        }
        return $path;
    }

    protected function ensure_dir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }

    protected function refresh_request_counts(Model_Core_Sat_Sync_Request $request)
    {
        $packages = Model_Core_Sat_Package::query()
            ->where('sync_request_id', '=', (int) $request->id)
            ->get();

        $downloaded = 0;
        $processed = 0;
        foreach ($packages as $package) {
            if (in_array($package->status, ['downloaded', 'processed'], true)) {
                $downloaded++;
            }
            if ($package->status === 'processed') {
                $processed += (int) $package->xml_count;
            }
        }

        $request->downloaded_count = $downloaded;
        $request->processed_count = $processed;
        if ($request->package_count > 0 && $downloaded >= (int) $request->package_count) {
            $request->status = 'completed';
        }
        $request->save();
    }

    protected function party_by_rfc($rfc)
    {
        $rfc = strtoupper(trim((string) $rfc));
        if ($rfc === '') {
            return null;
        }

        $row = \DB::select('id', 'party_type')
            ->from('core_parties')
            ->where('rfc', '=', $rfc)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return $row ?: null;
    }

    protected function direction_from_rfc($emitter_rfc, $receiver_rfc = '')
    {
        $rfcs = $this->company_rfcs();
        $emitter_rfc = strtoupper(trim((string) $emitter_rfc));
        $receiver_rfc = strtoupper(trim((string) $receiver_rfc));
        if ($emitter_rfc !== '' && in_array($emitter_rfc, $rfcs, true)) {
            return 'issued';
        }
        if ($receiver_rfc !== '' && in_array($receiver_rfc, $rfcs, true)) {
            return 'received';
        }
        return 'received';
    }

    protected function company_rfcs()
    {
        $rfcs = [];
        if (\DBUtil::table_exists('core_companies')) {
            foreach (\DB::select('rfc')->from('core_companies')->execute() as $row) {
                $rfc = strtoupper(trim((string) $row['rfc']));
                if ($rfc !== '') {
                    $rfcs[$rfc] = $rfc;
                }
            }
        }
        if (\DBUtil::table_exists('core_sat_credentials')) {
            foreach (\DB::select('rfc')->from('core_sat_credentials')->where('active', '=', 1)->execute() as $row) {
                $rfc = strtoupper(trim((string) $row['rfc']));
                if ($rfc !== '') {
                    $rfcs[$rfc] = $rfc;
                }
            }
        }
        return array_values($rfcs);
    }

    protected function datetime($value)
    {
        $time = strtotime($value);
        return $time ? date('Y-m-d H:i:s', $time) : date('Y-m-d H:i:s');
    }

    protected function nullable_datetime($value)
    {
        $time = strtotime($value);
        return $time ? date('Y-m-d H:i:s', $time) : null;
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
