<?php

/**
 * SERVICE CORE_SAT_REPTAXIMPORTER
 *
 * Importa impuestos de complementos de pago REP desde XML SAT ya guardados.
 *
 * Esta clase no modifica pagos, saldos, conciliaciones, libro fiscal, DIOT,
 * IVA mensual ni contabilidad. Solo inserta registros faltantes en
 * core_sat_payment_taxes usando source_hash como llave idempotente.
 */
class Service_Core_Sat_RepTaxImporter
{
    /**
     * IMPORT
     *
     * @param   array  $filters  uuid, rfc, period
     * @return  array
     */
    public function import(array $filters = [])
    {
        $result = $this->empty_result();

        if (!$this->required_tables_exist($result)) {
            return $result;
        }

        $cfdis = $this->payment_cfdis($filters);
        $result['rep_found'] = count($cfdis);

        foreach ($cfdis as $cfdi) {
            $this->import_cfdi((array) $cfdi, $result);
        }

        \Log::info('[REP_TAX_IMPORTER] REP revisados='.$result['rep_found'].' insertados='.$result['inserted'].' omitidos='.$result['skipped'].' errores='.$result['error_count']);

        return $result;
    }

    protected function import_cfdi(array $cfdi, array &$result)
    {
        $result['processed_rep']++;

        $rep_uuid = strtoupper(trim((string) \Arr::get($cfdi, 'uuid', '')));
        $xml_path = $this->resolve_xml_path((string) \Arr::get($cfdi, 'xml_path', ''));

        if ($xml_path === '') {
            $this->warning($result, 'REP '.$rep_uuid.' omitido: no se encontro XML fisico.');
            return;
        }

        try {
            $parsed = \Helper_Core_Sat_Xml::parse_file($xml_path);
            $payments = (array) \Arr::get($parsed, 'payments', []);
            $result['documents_found'] += count($payments);

            foreach ($payments as $payment) {
                $this->import_payment_document((array) $cfdi, (array) $payment, $result);
            }
        } catch (\Exception $e) {
            $result['error_count']++;
            $result['errors'][] = 'REP '.$rep_uuid.': '.$e->getMessage();
            \Log::error('[REP_TAX_IMPORTER] REP '.$rep_uuid.': '.$e->getMessage());
        }
    }

    protected function import_payment_document(array $cfdi, array $payment, array &$result)
    {
        $rep_uuid = strtoupper(trim((string) \Arr::get($payment, 'rep_uuid', \Arr::get($cfdi, 'uuid', ''))));
        $invoice_uuid = strtoupper(trim((string) \Arr::get($payment, 'invoice_uuid', \Arr::get($payment, 'payment_uuid', ''))));
        $taxes = (array) \Arr::get($payment, 'taxes', []);

        if ($invoice_uuid === '') {
            $this->warning($result, 'REP '.$rep_uuid.' contiene un documento relacionado sin UUID.');
            return;
        }

        if (empty($taxes)) {
            return;
        }

        $payment_detail = $this->payment_detail(
            (int) \Arr::get($cfdi, 'id', 0),
            $invoice_uuid,
            (int) \Arr::get($payment, 'partiality_number', 0),
            (float) \Arr::get($payment, 'paid_amount', 0)
        );

        if (!$payment_detail) {
            $this->warning($result, 'REP '.$rep_uuid.' / factura '.$invoice_uuid.' omitida: no existe core_sat_payment_details relacionado.');
            return;
        }

        foreach ($taxes as $tax) {
            $tax = (array) $tax;
            $result['taxes_found']++;

            $source_hash = $this->source_hash($rep_uuid, $invoice_uuid, $tax);
            if ($this->source_hash_exists($source_hash)) {
                $result['skipped']++;
                continue;
            }

            $row = [
                'source_hash' => $source_hash,
                'payment_cfdi_id' => (int) \Arr::get($cfdi, 'id', 0),
                'payment_detail_id' => (int) \Arr::get($payment_detail, 'id', 0),
                'invoice_cfdi_id' => (int) \Arr::get($payment_detail, 'invoice_cfdi_id', 0),
                'payment_uuid' => $rep_uuid,
                'invoice_uuid' => $invoice_uuid,
                'tax_scope' => (string) \Arr::get($tax, 'tax_scope', ''),
                'tax_code' => (string) \Arr::get($tax, 'tax_code', ''),
                'tax_type' => (string) \Arr::get($tax, 'tax_type', ''),
                'tax_factor_type' => (string) \Arr::get($tax, 'tax_factor_type', ''),
                'tax_rate' => (float) \Arr::get($tax, 'tax_rate', 0),
                'base_amount' => (float) \Arr::get($tax, 'base_amount', 0),
                'tax_amount' => (float) \Arr::get($tax, 'tax_amount', 0),
                'currency' => (string) \Arr::get($tax, 'currency', \Arr::get($payment, 'currency', 'MXN')),
                'exchange_rate' => (float) \Arr::get($tax, 'exchange_rate', \Arr::get($payment, 'exchange_rate', 1)),
                'payment_date' => $this->datetime((string) \Arr::get($tax, 'payment_date', \Arr::get($payment, 'payment_date', ''))),
                'active' => 1,
            ];

            try {
                \Model_Core_Sat_Payment_Tax::forge($row)->save();
                $result['inserted']++;
                if ($result['sample_row'] === null) {
                    $result['sample_row'] = $row;
                }
            } catch (\Exception $e) {
                $result['error_count']++;
                $result['errors'][] = 'REP '.$rep_uuid.' / factura '.$invoice_uuid.': '.$e->getMessage();
                \Log::error('[REP_TAX_IMPORTER] Error insertando impuesto REP '.$rep_uuid.': '.$e->getMessage());
            }
        }
    }

    protected function payment_cfdis(array $filters)
    {
        $query = \DB::select('id', 'uuid', 'emitter_rfc', 'receiver_rfc', 'issued_at', 'voucher_type', 'sat_status', 'xml_path')
            ->from('core_sat_cfdi')
            ->where('voucher_type', '=', 'P');

        $uuid = strtoupper(trim((string) \Arr::get($filters, 'uuid', '')));
        if ($uuid !== '') {
            $query->where('uuid', '=', $uuid);
        }

        $rfc = strtoupper(trim((string) \Arr::get($filters, 'rfc', '')));
        if ($rfc !== '') {
            $query->and_where_open()
                ->where('emitter_rfc', '=', $rfc)
                ->or_where('receiver_rfc', '=', $rfc)
                ->and_where_close();
        }

        $period = trim((string) \Arr::get($filters, 'period', ''));
        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            $from = $period.'-01 00:00:00';
            $to = date('Y-m-t 23:59:59', strtotime($period.'-01'));
            $query->where('issued_at', '>=', $from)->where('issued_at', '<=', $to);
        }

        return $query->order_by('issued_at', 'asc')->order_by('id', 'asc')->execute()->as_array();
    }

    protected function payment_detail($payment_cfdi_id, $invoice_uuid, $partiality_number, $paid_amount)
    {
        $query = \DB::select()
            ->from('core_sat_payment_details')
            ->where('payment_cfdi_id', '=', (int) $payment_cfdi_id)
            ->where('invoice_uuid', '=', $invoice_uuid);

        if ($partiality_number > 0) {
            $query->where('partiality_number', '=', $partiality_number);
        }

        $row = $query->order_by('id', 'asc')->execute()->current();
        if ($row) {
            return (array) $row;
        }

        return \DB::select()
            ->from('core_sat_payment_details')
            ->where('payment_cfdi_id', '=', (int) $payment_cfdi_id)
            ->where('invoice_uuid', '=', $invoice_uuid)
            ->order_by('id', 'asc')
            ->execute()
            ->current();
    }

    protected function source_hash($rep_uuid, $invoice_uuid, array $tax)
    {
        return hash('sha256', implode('|', [
            strtoupper((string) $rep_uuid),
            strtoupper((string) $invoice_uuid),
            (string) \Arr::get($tax, 'tax_scope', ''),
            (string) \Arr::get($tax, 'tax_code', ''),
            (string) \Arr::get($tax, 'tax_type', ''),
            $this->decimal_key(\Arr::get($tax, 'tax_rate', 0)),
            $this->decimal_key(\Arr::get($tax, 'base_amount', 0)),
            $this->decimal_key(\Arr::get($tax, 'tax_amount', 0)),
        ]));
    }

    protected function source_hash_exists($source_hash)
    {
        return (bool) \DB::select('id')
            ->from('core_sat_payment_taxes')
            ->where('source_hash', '=', $source_hash)
            ->limit(1)
            ->execute()
            ->current();
    }

    protected function required_tables_exist(array &$result)
    {
        $required = ['core_sat_cfdi', 'core_sat_payment_details', 'core_sat_payment_taxes'];
        foreach ($required as $table) {
            if (!\DBUtil::table_exists($table)) {
                $result['error_count']++;
                $result['errors'][] = 'Falta la tabla '.$table.'. Ejecuta la migracion correspondiente antes de importar impuestos REP.';
                \Log::warning('[REP_TAX_IMPORTER] Falta tabla '.$table.'.');
                return false;
            }
        }

        return true;
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
            $base = realpath(APPPATH.'../..');
            if ($base) {
                $candidates[] = $base.DIRECTORY_SEPARATOR.ltrim($xml_path, '\\/');
            }
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    protected function datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    protected function decimal_key($value)
    {
        return number_format((float) $value, 6, '.', '');
    }

    protected function warning(array &$result, $message)
    {
        $result['warning_count']++;
        $result['warnings'][] = $message;
        \Log::warning('[REP_TAX_IMPORTER] '.$message);
    }

    protected function empty_result()
    {
        return [
            'rep_found' => 0,
            'processed_rep' => 0,
            'documents_found' => 0,
            'taxes_found' => 0,
            'inserted' => 0,
            'skipped' => 0,
            'warning_count' => 0,
            'error_count' => 0,
            'warnings' => [],
            'errors' => [],
            'sample_row' => null,
        ];
    }
}
