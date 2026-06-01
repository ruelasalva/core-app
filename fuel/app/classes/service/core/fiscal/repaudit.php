<?php

/**
 * SERVICE CORE_FISCAL_REPAUDIT
 *
 * Auditoria de facturas PPD y complementos de pago REP.
 * Solo consulta informacion existente; no modifica pagos, saldos ni calculos fiscales.
 *
 * @package  app
 */
class Service_Core_Fiscal_RepAudit
{
    protected $warnings = [];

    /**
     * AUDIT
     *
     * Construye el tablero de auditoria REP/PPD de solo lectura.
     *
     * @access  public
     * @return  Array
     */
    public function audit($rfc, $period, array $filters = [], $rfc_source_label = '')
    {
        $this->warnings = [];
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $type = $this->normalize_type(\Arr::get($filters, 'type', 'all'));

        $data = $this->empty_result($rfc, $period, $type, $rfc_source_label);

        $missing = $this->missing_tables([
            'core_sat_cfdi',
            'core_sat_payment_details',
            'core_payments',
            'core_payment_allocations',
        ]);

        if (!empty($missing)) {
            $data['warnings'][] = 'Faltan tablas requeridas para auditoria REP/PPD: '.implode(', ', $missing).'.';
            \Log::warning('Auditoria REP/PPD incompleta por tablas faltantes: '.implode(', ', $missing));
            return $data;
        }

        if ($rfc === '') {
            $data['warnings'][] = 'No hay RFC fiscal configurado. Captura el RFC de la empresa o una credencial SAT activa.';
            \Log::warning('Auditoria REP/PPD sin RFC configurado.');
            return $data;
        }

        $dates = $this->period_dates($period);
        $rep_tax_available = \DBUtil::table_exists('core_sat_payment_taxes');
        if (!$rep_tax_available) {
            $data['warnings'][] = 'La tabla core_sat_payment_taxes no existe. La auditoria REP/PPD sigue disponible, pero no se muestran impuestos REP persistidos.';
            \Log::warning('Auditoria REP/PPD sin tabla core_sat_payment_taxes.');
        }

        $payments = $this->payment_totals_by_invoice();
        $rep_by_invoice = $this->rep_rows_by_invoice($dates, $type, $rfc);
        $items = $this->ppd_invoice_items($rfc, $dates, $type, $payments, $rep_by_invoice);
        $rep_items = $this->rep_items($rfc, $dates, $type);
        if ($rep_tax_available) {
            $rep_items = $this->enrich_rep_items_with_taxes($rep_items, $dates, $type, $rfc);
            $rep_by_invoice = $this->enrich_rep_map_with_taxes($rep_by_invoice, $rep_items);
            $items = $this->ppd_invoice_items($rfc, $dates, $type, $payments, $rep_by_invoice);
        }
        $internal_payment_items = $this->internal_payments_without_rep($dates);
        $duplicate_count = $this->duplicate_rep_count($dates, $type, $rfc);

        $summary = $this->summary($items, $rep_items, $internal_payment_items, $duplicate_count);

        $data['summary'] = $summary;
        $data['items'] = $items;
        $data['issued_items'] = $this->items_by_direction($items, 'issued');
        $data['received_items'] = $this->items_by_direction($items, 'received');
        $data['rep_items'] = $rep_items;
        $data['internal_payment_items'] = $internal_payment_items;
        $data['warnings'] = array_values(array_unique(array_merge($data['warnings'], $this->warnings)));

        \Log::info('Auditoria REP/PPD consultada RFC='.$rfc.' periodo='.$period.' tipo='.$type.' ppd='.count($items).' rep='.count($rep_items));

        return $data;
    }

    protected function empty_result($rfc, $period, $type, $rfc_source_label)
    {
        return [
            'rfc' => $rfc,
            'rfc_source_label' => (string) $rfc_source_label,
            'period' => $period,
            'filters' => ['type' => $type],
            'summary' => [
                'ppd_issued' => 0,
                'ppd_received' => 0,
                'ppd_without_rep' => 0,
                'related_rep' => 0,
                'cancelled_rep' => 0,
                'rep_without_xml' => 0,
                'duplicate_rep' => 0,
                'internal_payments_without_rep' => 0,
                'rep_without_internal_payment' => 0,
                'pending_balance' => 0,
                'rep_with_saved_taxes' => 0,
                'rep_without_saved_taxes' => 0,
                'rep_tax_movements' => 0,
                'rep_dr_base' => 0,
                'rep_dr_vat' => 0,
                'rep_p_base' => 0,
                'rep_p_vat' => 0,
                'rep_retentions' => 0,
            ],
            'items' => [],
            'issued_items' => [],
            'received_items' => [],
            'rep_items' => [],
            'internal_payment_items' => [],
            'warnings' => [],
        ];
    }

    protected function ppd_invoice_items($rfc, array $dates, $type, array $payments, array $rep_by_invoice)
    {
        $query = \DB::select(
                'id', 'uuid', 'direction', 'serie', 'folio', 'emitter_rfc', 'emitter_name',
                'receiver_rfc', 'receiver_name', 'issued_at', 'currency', 'total',
                'payment_method', 'payment_form', 'sat_status', 'missing_xml', 'xml_path'
            )
            ->from('core_sat_cfdi')
            ->where('issued_at', '>=', $dates['from_datetime'])
            ->where('issued_at', '<=', $dates['to_datetime'])
            ->where('voucher_type', '=', 'I')
            ->where_open()
                ->where('payment_method', '=', 'PPD')
                ->or_where_open()
                    ->where('payment_method', '=', '')
                    ->where('missing_xml', '=', 1)
                ->or_where_close()
            ->where_close();

        $this->apply_direction_filter($query, $type);
        $this->apply_company_rfc_filter($query, $rfc, $type);

        $rows = $query->order_by('issued_at', 'asc')->order_by('id', 'asc')->execute();
        $items = [];

        foreach ($rows as $row) {
            $uuid = strtoupper(trim((string) $row['uuid']));
            $direction = (string) $row['direction'];
            $total = round((float) $row['total'], 2);
            $paid = round((float) \Arr::get($payments, $uuid, 0), 2);
            $balance = round(max(0, $total - $paid), 2);
            $reps = (array) \Arr::get($rep_by_invoice, $uuid, []);
            $status = $this->ppd_status($row, $paid, $balance, $reps);

            $items[] = [
                'id' => (int) $row['id'],
                'uuid' => $uuid,
                'direction' => $direction,
                'direction_label' => $this->direction_label($direction),
                'serie' => (string) $row['serie'],
                'folio' => (string) $row['folio'],
                'issued_at' => (string) $row['issued_at'],
                'issued_label' => $this->date_label((string) $row['issued_at']),
                'counterparty_rfc' => $direction === 'issued' ? (string) $row['receiver_rfc'] : (string) $row['emitter_rfc'],
                'counterparty_name' => $direction === 'issued' ? (string) $row['receiver_name'] : (string) $row['emitter_name'],
                'currency' => (string) $row['currency'],
                'total' => $total,
                'paid_amount' => $paid,
                'pending_balance' => $balance,
                'payment_method' => (string) $row['payment_method'],
                'payment_form' => (string) $row['payment_form'],
                'sat_status' => (string) $row['sat_status'],
                'xml_available' => $this->xml_available($row),
                'rep_count' => count($reps),
                'rep_uuids' => array_values(array_unique(array_map(function ($rep) {
                    return (string) \Arr::get((array) $rep, 'payment_uuid', '');
                }, $reps))),
                'status' => $status,
                'status_label' => $this->ppd_status_label($status),
            ];
        }

        return $items;
    }

    protected function payment_totals_by_invoice()
    {
        $totals = [];
        $rows = \DB::select(
                'pd.invoice_uuid',
                [\DB::expr("COALESCE(SUM(CASE WHEN LOWER(COALESCE(p.sat_status,'')) LIKE '%cancel%' THEN 0 ELSE pd.paid_amount END),0)"), 'paid']
            )
            ->from(['core_sat_payment_details', 'pd'])
            ->join(['core_sat_cfdi', 'p'], 'left')->on('pd.payment_cfdi_id', '=', 'p.id')
            ->group_by('pd.invoice_uuid')
            ->execute();

        foreach ($rows as $row) {
            $uuid = strtoupper(trim((string) $row['invoice_uuid']));
            if ($uuid !== '') {
                $totals[$uuid] = (float) $row['paid'];
            }
        }

        return $totals;
    }

    protected function rep_rows_by_invoice(array $dates, $type, $rfc)
    {
        $rows = $this->base_rep_query($rfc, $dates, $type)
            ->execute();

        $map = [];
        foreach ($rows as $row) {
            $uuid = strtoupper(trim((string) $row['invoice_uuid']));
            if ($uuid === '') {
                continue;
            }
            if (!isset($map[$uuid])) {
                $map[$uuid] = [];
            }
            $map[$uuid][] = $this->format_rep_row($row);
        }

        return $map;
    }

    protected function rep_items($rfc, array $dates, $type)
    {
        $rows = $this->base_rep_query($rfc, $dates, $type)
            ->order_by('p.issued_at', 'desc')
            ->order_by('pd.id', 'desc')
            ->limit(500)
            ->execute();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->format_rep_row($row);
        }

        return $items;
    }

    protected function base_rep_query($rfc, array $dates, $type)
    {
        $query = \DB::select(
                ['pd.id', 'payment_detail_id'],
                ['pd.payment_cfdi_id', 'payment_cfdi_id'],
                ['pd.invoice_cfdi_id', 'invoice_cfdi_id'],
                ['pd.invoice_uuid', 'invoice_uuid'],
                ['pd.series', 'series'],
                ['pd.folio', 'folio'],
                ['pd.currency', 'currency'],
                ['pd.partiality_number', 'partiality_number'],
                ['pd.previous_balance', 'previous_balance'],
                ['pd.paid_amount', 'paid_amount'],
                ['pd.remaining_balance', 'remaining_balance'],
                ['pd.tax_object', 'tax_object'],
                ['p.uuid', 'payment_uuid'],
                ['p.direction', 'payment_direction'],
                ['p.emitter_rfc', 'payment_emitter_rfc'],
                ['p.receiver_rfc', 'payment_receiver_rfc'],
                ['p.issued_at', 'payment_issued_at'],
                ['p.sat_status', 'payment_sat_status'],
                ['p.xml_path', 'payment_xml_path'],
                ['p.missing_xml', 'payment_missing_xml'],
                ['pay.id', 'internal_payment_id']
            )
            ->from(['core_sat_payment_details', 'pd'])
            ->join(['core_sat_cfdi', 'p'], 'left')->on('pd.payment_cfdi_id', '=', 'p.id')
            ->join(['core_payments', 'pay'], 'left')->on('pay.external_id', '=', \DB::expr("CONCAT('sat_rep:', pd.id)"))
            ->where('p.issued_at', '>=', $dates['from_datetime'])
            ->where('p.issued_at', '<=', $dates['to_datetime'])
            ->where('p.voucher_type', '=', 'P');

        $this->apply_direction_filter($query, $type, 'p');
        $this->apply_company_rfc_filter($query, $rfc, $type, 'p');

        return $query;
    }

    protected function format_rep_row(array $row)
    {
        $cancelled = $this->is_cancelled_status((string) $row['payment_sat_status']);
        $xml_available = $this->xml_available([
            'xml_path' => (string) $row['payment_xml_path'],
            'missing_xml' => (int) $row['payment_missing_xml'],
        ]);

        return [
            'payment_detail_id' => (int) $row['payment_detail_id'],
            'payment_cfdi_id' => (int) $row['payment_cfdi_id'],
            'invoice_cfdi_id' => (int) $row['invoice_cfdi_id'],
            'payment_uuid' => strtoupper((string) $row['payment_uuid']),
            'invoice_uuid' => strtoupper((string) $row['invoice_uuid']),
            'direction' => (string) $row['payment_direction'],
            'direction_label' => $this->direction_label((string) $row['payment_direction']),
            'payment_issued_at' => (string) $row['payment_issued_at'],
            'payment_issued_label' => $this->date_label((string) $row['payment_issued_at']),
            'partiality_number' => (int) $row['partiality_number'],
            'previous_balance' => (float) $row['previous_balance'],
            'paid_amount' => (float) $row['paid_amount'],
            'remaining_balance' => (float) $row['remaining_balance'],
            'currency' => (string) $row['currency'],
            'sat_status' => (string) $row['payment_sat_status'],
            'cancelled' => $cancelled,
            'xml_available' => $xml_available,
            'internal_payment_id' => (int) $row['internal_payment_id'],
            'has_internal_payment' => (int) $row['internal_payment_id'] > 0,
            'status_label' => $cancelled ? 'REP cancelado' : (!$xml_available ? 'REP sin XML' : ((int) $row['internal_payment_id'] > 0 ? 'Con pago interno' : 'Sin pago interno')),
            'has_rep_taxes' => false,
            'dr_tax_count' => 0,
            'p_tax_count' => 0,
            'rep_tax_count' => 0,
            'rep_tax_amount' => 0,
            'rep_dr_base' => 0,
            'rep_dr_vat' => 0,
            'rep_p_base' => 0,
            'rep_p_vat' => 0,
            'rep_retentions' => 0,
            'rep_tax_warning_status' => 'Sin impuestos guardados',
            'rep_tax_warnings' => [],
        ];
    }

    protected function enrich_rep_items_with_taxes(array $rep_items, array $dates, $type, $rfc)
    {
        if (empty($rep_items)) {
            return $rep_items;
        }

        $ids = [];
        foreach ($rep_items as $rep) {
            $id = (int) \Arr::get($rep, 'payment_detail_id', 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return $rep_items;
        }

        $stats = $this->rep_tax_stats_by_detail($ids);
        $duplicates = $this->rep_tax_duplicates_by_detail($ids);
        $this->append_rep_tax_global_warnings($dates, $type, $rfc);

        foreach ($rep_items as &$rep) {
            $detail_id = (int) \Arr::get($rep, 'payment_detail_id', 0);
            $stat = isset($stats[$detail_id]) ? $stats[$detail_id] : [];
            $warnings = [];

            $rep['rep_tax_count'] = (int) \Arr::get($stat, 'tax_count', 0);
            $rep['dr_tax_count'] = (int) \Arr::get($stat, 'dr_tax_count', 0);
            $rep['p_tax_count'] = (int) \Arr::get($stat, 'p_tax_count', 0);
            $rep['rep_tax_amount'] = round((float) \Arr::get($stat, 'tax_amount', 0), 2);
            $rep['rep_dr_base'] = round((float) \Arr::get($stat, 'dr_base', 0), 2);
            $rep['rep_dr_vat'] = round((float) \Arr::get($stat, 'dr_vat', 0), 2);
            $rep['rep_p_base'] = round((float) \Arr::get($stat, 'p_base', 0), 2);
            $rep['rep_p_vat'] = round((float) \Arr::get($stat, 'p_vat', 0), 2);
            $rep['rep_retentions'] = round((float) \Arr::get($stat, 'retentions', 0), 2);
            $rep['has_rep_taxes'] = $rep['rep_tax_count'] > 0;

            if ($rep['xml_available'] && !$rep['has_rep_taxes']) {
                $warnings[] = 'REP con XML pero sin impuestos guardados';
            }
            if ($rep['dr_tax_count'] > 0 && $rep['p_tax_count'] < 1) {
                $warnings[] = 'REP con DR pero sin P';
            }
            if ($rep['p_tax_count'] > 0 && $rep['dr_tax_count'] < 1) {
                $warnings[] = 'REP con P pero sin DR';
            }
            if ((int) \Arr::get($duplicates, $detail_id, 0) > 0) {
                $warnings[] = 'REP con impuestos duplicados';
            }

            $rep['rep_tax_warnings'] = $warnings;
            $rep['rep_tax_warning_status'] = empty($warnings) ? ($rep['has_rep_taxes'] ? 'Correcto' : 'Sin impuestos guardados') : implode('; ', $warnings);
        }
        unset($rep);

        return $rep_items;
    }

    protected function enrich_rep_map_with_taxes(array $rep_by_invoice, array $rep_items)
    {
        $by_detail = [];
        foreach ($rep_items as $rep) {
            $by_detail[(int) \Arr::get($rep, 'payment_detail_id', 0)] = $rep;
        }

        foreach ($rep_by_invoice as $uuid => &$reps) {
            foreach ($reps as &$rep) {
                $detail_id = (int) \Arr::get((array) $rep, 'payment_detail_id', 0);
                if (isset($by_detail[$detail_id])) {
                    $rep = $by_detail[$detail_id];
                }
            }
            unset($rep);
        }
        unset($reps);

        return $rep_by_invoice;
    }

    protected function rep_tax_stats_by_detail(array $detail_ids)
    {
        $stats = [];

        $rows = \DB::select(
                'payment_detail_id',
                [\DB::expr('COUNT(*)'), 'tax_count'],
                [\DB::expr("SUM(CASE WHEN tax_scope = 'DR' THEN 1 ELSE 0 END)"), 'dr_tax_count'],
                [\DB::expr("SUM(CASE WHEN tax_scope = 'P' THEN 1 ELSE 0 END)"), 'p_tax_count'],
                [\DB::expr('COALESCE(SUM(tax_amount),0)'), 'tax_amount'],
                [\DB::expr("COALESCE(SUM(CASE WHEN tax_scope = 'DR' AND tax_code = '002' AND tax_type = 'transferred' THEN base_amount ELSE 0 END),0)"), 'dr_base'],
                [\DB::expr("COALESCE(SUM(CASE WHEN tax_scope = 'DR' AND tax_code = '002' AND tax_type = 'transferred' THEN tax_amount ELSE 0 END),0)"), 'dr_vat'],
                [\DB::expr("COALESCE(SUM(CASE WHEN tax_scope = 'P' AND tax_code = '002' AND tax_type = 'transferred' THEN base_amount ELSE 0 END),0)"), 'p_base'],
                [\DB::expr("COALESCE(SUM(CASE WHEN tax_scope = 'P' AND tax_code = '002' AND tax_type = 'transferred' THEN tax_amount ELSE 0 END),0)"), 'p_vat'],
                [\DB::expr("COALESCE(SUM(CASE WHEN tax_type = 'retained' THEN tax_amount ELSE 0 END),0)"), 'retentions']
            )
            ->from('core_sat_payment_taxes')
            ->where('active', '=', 1)
            ->where('payment_detail_id', 'in', $detail_ids)
            ->group_by('payment_detail_id')
            ->execute();

        foreach ($rows as $row) {
            $stats[(int) $row['payment_detail_id']] = (array) $row;
        }

        return $stats;
    }

    protected function rep_tax_duplicates_by_detail(array $detail_ids)
    {
        $duplicates = [];

        $rows = \DB::select('payment_detail_id', [\DB::expr('COUNT(*)'), 'duplicate_rows'])
            ->from('core_sat_payment_taxes')
            ->where('active', '=', 1)
            ->where('payment_detail_id', 'in', $detail_ids)
            ->where('source_hash', 'in', \DB::expr("(SELECT source_hash FROM core_sat_payment_taxes WHERE active = 1 GROUP BY source_hash HAVING COUNT(*) > 1)"))
            ->group_by('payment_detail_id')
            ->execute();

        foreach ($rows as $row) {
            $duplicates[(int) $row['payment_detail_id']] = (int) $row['duplicate_rows'];
        }

        return $duplicates;
    }

    protected function append_rep_tax_global_warnings(array $dates, $type, $rfc)
    {
        $where = $this->rep_tax_where($dates, $type, $rfc);

        $missing_detail = (int) \DB::query("
            SELECT COUNT(*) AS total
            FROM core_sat_payment_taxes t
            LEFT JOIN core_sat_cfdi p ON t.payment_cfdi_id = p.id
            WHERE ".implode(' AND ', $where)."
              AND t.active = 1
              AND COALESCE(t.payment_detail_id,0) = 0
        ")->execute()->get('total', 0);

        $missing_invoice_uuid = (int) \DB::query("
            SELECT COUNT(*) AS total
            FROM core_sat_payment_taxes t
            LEFT JOIN core_sat_cfdi p ON t.payment_cfdi_id = p.id
            WHERE ".implode(' AND ', $where)."
              AND t.active = 1
              AND COALESCE(t.invoice_uuid,'') = ''
        ")->execute()->get('total', 0);

        $duplicates = (int) \DB::query("
            SELECT COUNT(*) AS total
            FROM (
                SELECT t.source_hash, COUNT(*) AS rows_count
                FROM core_sat_payment_taxes t
                LEFT JOIN core_sat_cfdi p ON t.payment_cfdi_id = p.id
                WHERE ".implode(' AND ', $where)."
                  AND t.active = 1
                GROUP BY t.source_hash
                HAVING rows_count > 1
            ) d
        ")->execute()->get('total', 0);

        if ($missing_detail > 0) {
            $this->warnings[] = 'Hay '.$missing_detail.' impuestos REP guardados pero sin payment_detail_id.';
        }
        if ($missing_invoice_uuid > 0) {
            $this->warnings[] = 'Hay '.$missing_invoice_uuid.' impuestos REP guardados pero sin invoice_uuid.';
        }
        if ($duplicates > 0) {
            $this->warnings[] = 'Hay '.$duplicates.' source_hash de impuestos REP duplicados.';
        }
    }

    protected function rep_tax_where(array $dates, $type, $rfc)
    {
        $where = [
            "p.issued_at >= ".$this->quote($dates['from_datetime']),
            "p.issued_at <= ".$this->quote($dates['to_datetime']),
            "p.voucher_type = 'P'",
        ];

        if ($type === 'issued') {
            $where[] = "p.direction = 'issued'";
            $where[] = "p.emitter_rfc = ".$this->quote($rfc);
        } elseif ($type === 'received') {
            $where[] = "p.direction = 'received'";
            $where[] = "p.receiver_rfc = ".$this->quote($rfc);
        } else {
            $where[] = "(p.emitter_rfc = ".$this->quote($rfc)." OR p.receiver_rfc = ".$this->quote($rfc).")";
        }

        return $where;
    }

    protected function internal_payments_without_rep(array $dates)
    {
        $sql = "
            SELECT
                p.id,
                p.folio,
                p.payment_type,
                p.payment_date,
                p.amount,
                p.currency_code,
                p.reference,
                p.external_id,
                p.rep_status,
                COUNT(a.id) AS allocations
            FROM core_payments p
            LEFT JOIN core_payment_allocations a ON a.payment_id = p.id
            WHERE p.payment_date >= ".$this->quote($dates['from'])."
              AND p.payment_date <= ".$this->quote($dates['to'])."
              AND p.active = 1
              AND (p.external_id IS NULL OR p.external_id = '' OR p.external_id NOT LIKE 'sat_rep:%')
            GROUP BY p.id
            HAVING allocations > 0
            ORDER BY p.payment_date DESC, p.id DESC
            LIMIT 100
        ";

        $rows = \DB::query($sql)->execute();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'folio' => (string) $row['folio'],
                'payment_type' => (string) $row['payment_type'],
                'payment_type_label' => $this->payment_type_label((string) $row['payment_type']),
                'payment_date' => (string) $row['payment_date'],
                'amount' => (float) $row['amount'],
                'currency_code' => (string) $row['currency_code'],
                'reference' => (string) $row['reference'],
                'external_id' => (string) $row['external_id'],
                'rep_status' => (string) $row['rep_status'],
                'allocations' => (int) $row['allocations'],
            ];
        }

        return $items;
    }

    protected function duplicate_rep_count(array $dates, $type, $rfc)
    {
        $where = [
            "p.issued_at >= ".$this->quote($dates['from_datetime']),
            "p.issued_at <= ".$this->quote($dates['to_datetime']),
            "p.voucher_type = 'P'",
        ];

        if ($type === 'issued') {
            $where[] = "p.direction = 'issued'";
            $where[] = "p.emitter_rfc = ".$this->quote($rfc);
        } elseif ($type === 'received') {
            $where[] = "p.direction = 'received'";
            $where[] = "p.receiver_rfc = ".$this->quote($rfc);
        } else {
            $where[] = "(p.emitter_rfc = ".$this->quote($rfc)." OR p.receiver_rfc = ".$this->quote($rfc).")";
        }

        $sql = "
            SELECT COUNT(*) AS duplicates
            FROM (
                SELECT pd.invoice_uuid, pd.partiality_number, pd.paid_amount, COUNT(*) AS rows_count
                FROM core_sat_payment_details pd
                LEFT JOIN core_sat_cfdi p ON pd.payment_cfdi_id = p.id
                WHERE ".implode(' AND ', $where)."
                GROUP BY pd.invoice_uuid, pd.partiality_number, pd.paid_amount
                HAVING rows_count > 1
            ) d
        ";

        return (int) \DB::query($sql)->execute()->get('duplicates', 0);
    }

    protected function summary(array $items, array $rep_items, array $internal_payment_items, $duplicate_count)
    {
        $summary = [
            'ppd_issued' => 0,
            'ppd_received' => 0,
            'ppd_without_rep' => 0,
            'related_rep' => count($rep_items),
            'cancelled_rep' => 0,
            'rep_without_xml' => 0,
            'duplicate_rep' => (int) $duplicate_count,
            'internal_payments_without_rep' => count($internal_payment_items),
            'rep_without_internal_payment' => 0,
            'pending_balance' => 0,
            'issued_without_rep' => 0,
            'received_without_rep' => 0,
            'issued_paid' => 0,
            'received_paid' => 0,
            'rep_with_saved_taxes' => 0,
            'rep_without_saved_taxes' => 0,
            'rep_tax_movements' => 0,
            'rep_dr_base' => 0,
            'rep_dr_vat' => 0,
            'rep_p_base' => 0,
            'rep_p_vat' => 0,
            'rep_retentions' => 0,
        ];

        foreach ($items as $item) {
            if ($item['direction'] === 'issued') {
                $summary['ppd_issued']++;
            } elseif ($item['direction'] === 'received') {
                $summary['ppd_received']++;
            }
            if ($item['rep_count'] < 1) {
                $summary['ppd_without_rep']++;
                if ($item['direction'] === 'issued') {
                    $summary['issued_without_rep']++;
                } elseif ($item['direction'] === 'received') {
                    $summary['received_without_rep']++;
                }
            }
            if ($item['status'] === 'pagado') {
                if ($item['direction'] === 'issued') {
                    $summary['issued_paid']++;
                } elseif ($item['direction'] === 'received') {
                    $summary['received_paid']++;
                }
            }
            $summary['pending_balance'] += (float) $item['pending_balance'];
        }

        foreach ($rep_items as $rep) {
            if ($rep['cancelled']) {
                $summary['cancelled_rep']++;
            }
            if (!$rep['xml_available']) {
                $summary['rep_without_xml']++;
            }
            if (!$rep['has_internal_payment']) {
                $summary['rep_without_internal_payment']++;
            }
            if ((bool) \Arr::get($rep, 'has_rep_taxes', false)) {
                $summary['rep_with_saved_taxes']++;
            } else {
                $summary['rep_without_saved_taxes']++;
            }
            $summary['rep_tax_movements'] += (int) \Arr::get($rep, 'rep_tax_count', 0);
            $summary['rep_dr_base'] += (float) \Arr::get($rep, 'rep_dr_base', 0);
            $summary['rep_dr_vat'] += (float) \Arr::get($rep, 'rep_dr_vat', 0);
            $summary['rep_p_base'] += (float) \Arr::get($rep, 'rep_p_base', 0);
            $summary['rep_p_vat'] += (float) \Arr::get($rep, 'rep_p_vat', 0);
            $summary['rep_retentions'] += (float) \Arr::get($rep, 'rep_retentions', 0);
        }

        $summary['pending_balance'] = round($summary['pending_balance'], 2);
        $summary['rep_dr_base'] = round($summary['rep_dr_base'], 2);
        $summary['rep_dr_vat'] = round($summary['rep_dr_vat'], 2);
        $summary['rep_p_base'] = round($summary['rep_p_base'], 2);
        $summary['rep_p_vat'] = round($summary['rep_p_vat'], 2);
        $summary['rep_retentions'] = round($summary['rep_retentions'], 2);

        return $summary;
    }

    protected function items_by_direction(array $items, $direction)
    {
        return array_values(array_filter($items, function ($item) use ($direction) {
            return (string) \Arr::get((array) $item, 'direction', '') === $direction;
        }));
    }

    protected function apply_direction_filter($query, $type, $alias = null)
    {
        $field = $alias ? $alias.'.direction' : 'direction';
        if ($type === 'issued') {
            $query->where($field, '=', 'issued');
        } elseif ($type === 'received') {
            $query->where($field, '=', 'received');
        }
    }

    protected function apply_company_rfc_filter($query, $rfc, $type, $alias = null)
    {
        $prefix = $alias ? $alias.'.' : '';
        if ($type === 'issued') {
            $query->where($prefix.'emitter_rfc', '=', $rfc);
            return;
        }
        if ($type === 'received') {
            $query->where($prefix.'receiver_rfc', '=', $rfc);
            return;
        }

        $query->where_open()
            ->where($prefix.'emitter_rfc', '=', $rfc)
            ->or_where($prefix.'receiver_rfc', '=', $rfc)
        ->where_close();
    }

    protected function ppd_status(array $row, $paid, $balance, array $reps)
    {
        if (!$this->xml_available($row)) {
            return 'sin_xml';
        }
        foreach ($reps as $rep) {
            if ((bool) \Arr::get((array) $rep, 'cancelled', false)) {
                return 'rep_cancelado';
            }
        }
        if ((float) $paid <= 0) {
            return 'sin_rep';
        }
        if ((float) $balance > 1) {
            return 'parcial';
        }
        return 'pagado';
    }

    protected function ppd_status_label($status)
    {
        $labels = [
            'sin_xml' => 'Sin XML',
            'rep_cancelado' => 'REP cancelado',
            'sin_rep' => 'Sin REP',
            'parcial' => 'Parcial',
            'pagado' => 'Pagado',
        ];

        return isset($labels[$status]) ? $labels[$status] : 'No disponible';
    }

    protected function direction_label($direction)
    {
        return $direction === 'issued' ? 'Emitido' : ($direction === 'received' ? 'Recibido' : 'No disponible');
    }

    protected function payment_type_label($type)
    {
        return $type === 'received' ? 'Cobro' : ($type === 'sent' ? 'Pago' : $type);
    }

    protected function is_cancelled_status($status)
    {
        return strpos(strtolower((string) $status), 'cancel') !== false;
    }

    protected function xml_available(array $row)
    {
        return trim((string) \Arr::get($row, 'xml_path', \Arr::get($row, 'payment_xml_path', ''))) !== ''
            && (int) \Arr::get($row, 'missing_xml', \Arr::get($row, 'payment_missing_xml', 0)) !== 1;
    }

    protected function missing_tables(array $tables)
    {
        $missing = [];
        foreach ($tables as $table) {
            if (!\DBUtil::table_exists($table)) {
                $missing[] = $table;
            }
        }
        return $missing;
    }

    protected function period_dates($period)
    {
        $from = $period.'-01';
        $to = date('Y-m-t', strtotime($from));

        return [
            'from' => $from,
            'to' => $to,
            'from_datetime' => $from.' 00:00:00',
            'to_datetime' => $to.' 23:59:59',
        ];
    }

    protected function date_label($value)
    {
        if (trim((string) $value) === '') {
            return 'Sin fecha';
        }
        $time = strtotime((string) $value);
        return $time ? date('Y-m-d', $time) : (string) $value;
    }

    protected function normalize_rfc($rfc)
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
    }

    protected function normalize_period($period)
    {
        $period = trim((string) $period);
        return preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period) ? $period : date('Y-m');
    }

    protected function normalize_type($type)
    {
        $type = trim((string) $type);
        return in_array($type, ['issued', 'received'], true) ? $type : 'all';
    }

    protected function quote($value)
    {
        return \DB::quote((string) $value);
    }
}
