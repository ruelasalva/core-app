<?php

/**
 * SERVICE CORE_FISCAL_TAXLEDGERBUILDER
 *
 * Construye el libro fiscal base desde CFDI SAT importados.
 *
 * @package  app
 */
class Service_Core_Fiscal_TaxLedgerBuilder
{
    /**
     * BUILD
     *
     * GENERA MOVIMIENTOS FISCALES POR RFC Y PERIODO.
     *
     * @access  public
     * @return  Array
     */
    public function build($rfc, $period)
    {
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $dates = $this->period_dates($period);
        $now = time();

        $this->validate_schema();
        (new Service_Core_Fiscal_PeriodService())->assert_rebuild_allowed($rfc, $period);

        \Log::info('Fiscal Ledger: inicio build RFC='.$rfc.' periodo='.$period);

        \DB::start_transaction();
        try {
            $period_id = $this->find_or_create_period($rfc, $period, $dates, $now);
            $build_id = $this->create_build($period_id, $rfc, $dates, $now);
            $result = [
                'rfc' => $rfc,
                'period' => $period,
                'fiscal_period_id' => $period_id,
                'build_id' => $build_id,
                'cfdi_count' => 0,
                'detail_count' => 0,
                'line_count' => 0,
                'skipped_cancelled' => 0,
                'skipped_duplicates' => 0,
                'error_count' => 0,
                'errors' => [],
            ];

            $cfdis = $this->cfdis($rfc, $dates);
            foreach ($cfdis as $cfdi) {
                try {
                    if ($this->is_cancelled($cfdi)) {
                        $result['skipped_cancelled']++;
                        continue;
                    }

                    $result['cfdi_count']++;
                    $details = $this->details((int) $cfdi['id']);
                    foreach ($details as $detail) {
                        $result['detail_count']++;
                        $movements = $this->tax_movements($detail);
                        foreach ($movements as $movement) {
                            $row = $this->ledger_row($cfdi, $detail, $movement, $period_id, $build_id, $rfc, $period, $now);
                            if ($this->source_exists($row['source_hash'])) {
                                $result['skipped_duplicates']++;
                                continue;
                            }
                            \DB::insert('core_fiscal_ledger_lines')->set($row)->execute();
                            $result['line_count']++;
                        }
                    }
                } catch (\Exception $e) {
                    $result['error_count']++;
                    $result['errors'][] = 'CFDI '.$cfdi['uuid'].': '.$e->getMessage();
                    \Log::error('Fiscal Ledger: error CFDI '.$cfdi['uuid'].' - '.$e->getMessage());
                }
            }

            $this->finish_build($build_id, $result, 'completed', '', $now);
            \DB::commit_transaction();

            \Log::info('Fiscal Ledger: build completado RFC='.$rfc.' periodo='.$period.' cfdi='.$result['cfdi_count'].' detalles='.$result['detail_count'].' lineas='.$result['line_count'].' errores='.$result['error_count']);

            return $result;
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Log::error('Fiscal Ledger: error general RFC='.$rfc.' periodo='.$period.' - '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * VALIDATE SCHEMA
     *
     * CONFIRMA TABLAS REQUERIDAS ANTES DE PROCESAR.
     *
     * @access  protected
     * @return  Void
     */
    protected function validate_schema()
    {
        foreach (['core_sat_cfdi', 'core_sat_cfdi_details', 'core_fiscal_periods', 'core_fiscal_ledger_builds', 'core_fiscal_ledger_lines'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Tabla requerida no existe: '.$table.'. Ejecuta migraciones antes de construir el libro fiscal.');
            }
        }
    }

    /**
     * FIND OR CREATE PERIOD
     *
     * CREA EL PERIODO FISCAL SI NO EXISTE.
     *
     * @access  protected
     * @return  Int
     */
    protected function find_or_create_period($rfc, $period, array $dates, $now)
    {
        $row = \DB::select('id')
            ->from('core_fiscal_periods')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('period_key', '=', $period)
            ->execute()
            ->current();

        if ($row) {
            return (int) $row['id'];
        }

        list($year, $month) = explode('-', $period);
        $insert = \DB::insert('core_fiscal_periods')->set([
            'company_id' => $this->company_id($rfc),
            'taxpayer_rfc' => $rfc,
            'fiscal_year' => (int) $year,
            'fiscal_month' => (int) $month,
            'period_key' => $period,
            'date_from' => $dates['from'],
            'date_to' => $dates['to'],
            'status' => 'open',
            'locked_by' => 0,
            'locked_at' => 0,
            'closed_by' => 0,
            'closed_at' => 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        return (int) $insert[0];
    }

    /**
     * CREATE BUILD
     *
     * REGISTRA LA EJECUCION DEL PROCESO.
     *
     * @access  protected
     * @return  Int
     */
    protected function create_build($period_id, $rfc, array $dates, $now)
    {
        $insert = \DB::insert('core_fiscal_ledger_builds')->set([
            'fiscal_period_id' => (int) $period_id,
            'taxpayer_rfc' => $rfc,
            'build_type' => 'initial',
            'source_module' => 'sat_cfdi',
            'date_from' => $dates['from'],
            'date_to' => $dates['to'],
            'status' => 'running',
            'cfdi_count' => 0,
            'detail_count' => 0,
            'line_count' => 0,
            'error_count' => 0,
            'error_message' => '',
            'started_at' => $now,
            'finished_at' => 0,
            'created_by' => 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        return (int) $insert[0];
    }

    /**
     * FINISH BUILD
     *
     * ACTUALIZA TOTALES DEL BUILD.
     *
     * @access  protected
     * @return  Void
     */
    protected function finish_build($build_id, array $result, $status, $message, $now)
    {
        \DB::update('core_fiscal_ledger_builds')
            ->set([
                'status' => $status,
                'cfdi_count' => (int) $result['cfdi_count'],
                'detail_count' => (int) $result['detail_count'],
                'line_count' => (int) $result['line_count'],
                'error_count' => (int) $result['error_count'],
                'error_message' => $message ?: implode(' | ', array_slice($result['errors'], 0, 10)),
                'finished_at' => $now,
                'updated_at' => $now,
            ])
            ->where('id', '=', (int) $build_id)
            ->execute();
    }

    /**
     * CFDIS
     *
     * OBTIENE CFDI DEL RFC Y PERIODO.
     *
     * @access  protected
     * @return  Database_Result
     */
    protected function cfdis($rfc, array $dates)
    {
        return \DB::select()
            ->from('core_sat_cfdi')
            ->where('issued_at', '>=', $dates['from'].' 00:00:00')
            ->where('issued_at', '<=', $dates['to'].' 23:59:59')
            ->and_where_open()
                ->where('emitter_rfc', '=', $rfc)
                ->or_where('receiver_rfc', '=', $rfc)
            ->and_where_close()
            ->order_by('issued_at', 'asc')
            ->order_by('id', 'asc')
            ->execute();
    }

    /**
     * DETAILS
     *
     * OBTIENE CONCEPTOS CFDI.
     *
     * @access  protected
     * @return  Database_Result
     */
    protected function details($cfdi_id)
    {
        return \DB::select()
            ->from('core_sat_cfdi_details')
            ->where('cfdi_id', '=', (int) $cfdi_id)
            ->where('line_type', '=', 'concept')
            ->order_by('line_number', 'asc')
            ->order_by('id', 'asc')
            ->execute();
    }

    /**
     * TAX MOVEMENTS
     *
     * CONVIERTE IMPUESTOS GUARDADOS EN MOVIMIENTOS ATOMICOS.
     *
     * @access  protected
     * @return  Array
     */
    protected function tax_movements(array $detail)
    {
        $movements = [];

        $this->add_movement($movements, $detail, '002', 'transferred', 'vat_base', 'vat_rate', 'vat_amount');
        $this->add_movement($movements, $detail, '003', 'transferred', 'ieps_base', 'ieps_rate', 'ieps_amount');
        $this->add_movement($movements, $detail, '002', 'retained', 'ret_vat_base', 'ret_vat_rate', 'ret_vat_amount');
        $this->add_movement($movements, $detail, '001', 'retained', 'ret_isr_base', 'ret_isr_rate', 'ret_isr_amount');

        return $movements;
    }

    /**
     * ADD MOVEMENT
     *
     * AGREGA MOVIMIENTO SI TIENE BASE, TASA O IMPORTE FISCAL.
     *
     * @access  protected
     * @return  Void
     */
    protected function add_movement(array &$movements, array $detail, $tax_code, $tax_type, $base_field, $rate_field, $amount_field)
    {
        $base = (float) $detail[$base_field];
        $rate_raw = trim((string) $detail[$rate_field]);
        $amount = (float) $detail[$amount_field];

        if ($base == 0.0 && $amount == 0.0 && $rate_raw === '') {
            return;
        }

        $movements[] = [
            'tax_code' => $tax_code,
            'tax_type' => $tax_type,
            'tax_factor_type' => stripos($rate_raw, 'exento') !== false ? 'Exento' : 'Tasa',
            'tax_rate' => $this->decimal_rate($rate_raw),
            'base_amount' => $base > 0 ? $base : (float) $detail['amount'],
            'tax_amount' => $amount,
        ];
    }

    /**
     * LEDGER ROW
     *
     * PREPARA FILA PARA CORE_FISCAL_LEDGER_LINES.
     *
     * @access  protected
     * @return  Array
     */
    protected function ledger_row(array $cfdi, array $detail, array $movement, $period_id, $build_id, $rfc, $period, $now)
    {
        $direction = (string) $cfdi['direction'];
        $emitter_rfc = $this->normalize_rfc($cfdi['emitter_rfc']);
        $receiver_rfc = $this->normalize_rfc($cfdi['receiver_rfc']);
        $counterparty_rfc = $direction === 'issued' ? $receiver_rfc : $emitter_rfc;
        $exchange_rate = (float) $cfdi['exchange_rate'] > 0 ? (float) $cfdi['exchange_rate'] : 1;
        $source_hash = $this->source_hash($cfdi, $detail, $movement);

        return [
            'source_hash' => $source_hash,
            'fiscal_period_id' => (int) $period_id,
            'build_id' => (int) $build_id,
            'cfdi_id' => (int) $cfdi['id'],
            'cfdi_detail_id' => (int) $detail['id'],
            'payment_detail_id' => 0,
            'taxpayer_rfc' => $rfc,
            'counterparty_rfc' => $counterparty_rfc,
            'emitter_rfc' => $emitter_rfc,
            'receiver_rfc' => $receiver_rfc,
            'uuid' => strtoupper((string) $cfdi['uuid']),
            'related_uuid' => '',
            'direction' => $direction,
            'cfdi_type' => (string) $cfdi['voucher_type'],
            'payment_method' => (string) $cfdi['payment_method'],
            'payment_form' => (string) $cfdi['payment_form'],
            'payment_policy' => (string) $cfdi['payment_method'],
            'line_number' => (int) $detail['line_number'],
            'line_type' => (string) $detail['line_type'],
            'product_service_code' => (string) $detail['product_service_code'],
            'identification_number' => (string) $detail['identification_number'],
            'description' => (string) $detail['description'],
            'tax_object' => (string) $detail['tax_object'],
            'base_amount' => round((float) $movement['base_amount'], 6),
            'discount_amount' => round((float) $detail['discount'], 6),
            'tax_code' => (string) $movement['tax_code'],
            'tax_type' => (string) $movement['tax_type'],
            'tax_factor_type' => (string) $movement['tax_factor_type'],
            'tax_rate' => round((float) $movement['tax_rate'], 6),
            'tax_amount' => round((float) $movement['tax_amount'], 6),
            'currency' => (string) $cfdi['currency'] ?: 'MXN',
            'exchange_rate' => round($exchange_rate, 6),
            'base_amount_mxn' => round((float) $movement['base_amount'] * $exchange_rate, 6),
            'tax_amount_mxn' => round((float) $movement['tax_amount'] * $exchange_rate, 6),
            'issue_date' => (string) $cfdi['issued_at'],
            'stamped_at' => $cfdi['stamped_at'] ?: null,
            'fiscal_period' => $period,
            'sat_status' => (string) $cfdi['sat_status'],
            'source_origin' => (string) $cfdi['origin'],
            'xml_available' => (string) $cfdi['xml_path'] !== '' ? 1 : 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * SOURCE EXISTS
     *
     * EVITA DUPLICADOS POR HASH.
     *
     * @access  protected
     * @return  Bool
     */
    protected function source_exists($source_hash)
    {
        return (bool) \DB::select('id')
            ->from('core_fiscal_ledger_lines')
            ->where('source_hash', '=', (string) $source_hash)
            ->execute()
            ->current();
    }

    /**
     * SOURCE HASH
     *
     * GENERA HASH UNICO DEL MOVIMIENTO FISCAL.
     *
     * @access  protected
     * @return  String
     */
    protected function source_hash(array $cfdi, array $detail, array $movement)
    {
        return hash('sha256', implode('|', [
            (int) $cfdi['id'],
            strtoupper((string) $cfdi['uuid']),
            (int) $detail['id'],
            (int) $detail['line_number'],
            (string) $movement['tax_code'],
            (string) $movement['tax_type'],
            (string) $movement['tax_factor_type'],
            number_format((float) $movement['tax_rate'], 6, '.', ''),
            number_format((float) $movement['base_amount'], 6, '.', ''),
            number_format((float) $movement['tax_amount'], 6, '.', ''),
        ]));
    }

    protected function is_cancelled(array $cfdi)
    {
        return strtolower(trim((string) $cfdi['sat_status'])) === 'cancelado';
    }

    protected function normalize_rfc($rfc)
    {
        $rfc = strtoupper(trim((string) $rfc));
        if ($rfc === '' || !preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
            throw new \InvalidArgumentException('RFC invalido para construir libro fiscal.');
        }
        return $rfc;
    }

    protected function normalize_period($period)
    {
        $period = trim((string) $period);
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period)) {
            throw new \InvalidArgumentException('Periodo invalido. Usa formato YYYY-MM.');
        }
        return $period;
    }

    protected function period_dates($period)
    {
        $from = $period.'-01';
        return [
            'from' => $from,
            'to' => date('Y-m-t', strtotime($from)),
        ];
    }

    protected function decimal_rate($rate)
    {
        $rate = trim((string) $rate);
        if ($rate === '' || stripos($rate, 'exento') !== false) {
            return 0;
        }
        $rate = str_replace('%', '', $rate);
        $value = (float) $rate;
        return $value > 1 ? $value / 100 : $value;
    }

    protected function company_id($rfc)
    {
        if (!\DBUtil::table_exists('core_companies')) {
            return 0;
        }

        $row = \DB::select('id')
            ->from('core_companies')
            ->where('rfc', '=', $rfc)
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }
}
