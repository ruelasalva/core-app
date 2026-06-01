<?php

/**
 * SERVICE CORE_FISCAL_EVENTLOGGER
 *
 * Registra eventos fiscales de forma best-effort sin interrumpir procesos.
 *
 * @package  app
 */
class Service_Core_Fiscal_EventLogger
{
    /**
     * LOG
     *
     * GUARDA UN EVENTO FISCAL SI LA TABLA EXISTE. NUNCA LANZA EXCEPCIONES.
     *
     * @access  public
     * @return  Bool
     */
    public static function log(array $data)
    {
        try {
            if (!\DBUtil::table_exists('core_fiscal_events')) {
                \Log::warning('Bitacora fiscal no disponible: falta tabla core_fiscal_events.');
                return false;
            }

            $now = time();
            $rfc = self::normalize_rfc(\Arr::get($data, 'taxpayer_rfc', \Arr::get($data, 'rfc', '')));
            $period = self::normalize_period(\Arr::get($data, 'fiscal_period', \Arr::get($data, 'period', '')));
            $status = self::normalize_status(\Arr::get($data, 'event_status', \Arr::get($data, 'status', 'success')));
            $details = \Arr::get($data, 'details', \Arr::get($data, 'details_json', []));

            \DB::insert('core_fiscal_events')->set([
                'company_id' => (int) \Arr::get($data, 'company_id', self::company_id($rfc)),
                'taxpayer_rfc' => $rfc,
                'fiscal_period' => $period,
                'event_type' => self::trim_value(\Arr::get($data, 'event_type', ''), 60),
                'event_status' => $status,
                'source_module' => self::trim_value(\Arr::get($data, 'source_module', 'fiscal'), 60),
                'source_entity_type' => self::trim_value(\Arr::get($data, 'source_entity_type', ''), 60),
                'source_entity_id' => (int) \Arr::get($data, 'source_entity_id', 0),
                'summary' => self::trim_value(\Arr::get($data, 'summary', ''), 255),
                'details_json' => is_string($details) ? $details : json_encode($details),
                'executed_by' => (int) \Arr::get($data, 'executed_by', 0),
                'executed_at' => (int) \Arr::get($data, 'executed_at', $now),
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            return true;
        } catch (\Exception $e) {
            \Log::warning('No se pudo registrar evento fiscal: '.$e->getMessage());
            return false;
        }
    }

    protected static function normalize_rfc($rfc)
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
    }

    protected static function normalize_period($period)
    {
        $period = trim((string) $period);
        return preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period) ? $period : '';
    }

    protected static function normalize_status($status)
    {
        $status = trim((string) $status);
        return in_array($status, ['success', 'warning', 'error', 'skipped'], true) ? $status : 'success';
    }

    protected static function trim_value($value, $limit)
    {
        return substr(trim((string) $value), 0, (int) $limit);
    }

    protected static function company_id($rfc)
    {
        if ($rfc === '' || !\DBUtil::table_exists('core_companies')) {
            return 0;
        }

        $row = \DB::select('id')
            ->from('core_companies')
            ->where('rfc', '=', $rfc)
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }
}
