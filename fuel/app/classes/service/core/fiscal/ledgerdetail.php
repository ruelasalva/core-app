<?php

/**
 * SERVICE CORE_FISCAL_LEDGERDETAIL
 *
 * Consulta el libro fiscal de solo lectura desde core_fiscal_ledger_lines.
 *
 * @package  app
 */
class Service_Core_Fiscal_LedgerDetail
{
    const DEFAULT_LIMIT = 500;

    /**
     * SEARCH
     *
     * DEVUELVE MOVIMIENTOS FISCALES DEL PERIODO CON FILTROS SEGUROS.
     *
     * @access  public
     * @return  Array
     */
    public function search($rfc, $period, array $filters = [], $limit = self::DEFAULT_LIMIT)
    {
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $limit = $this->normalize_limit($limit);
        $this->validate_schema();

        \Log::info('Fiscal Ledger Detail: inicio RFC='.$rfc.' periodo='.$period.' limite='.$limit);

        $where = $this->where_sql($rfc, $period, $filters);
        $count_row = \DB::query("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines l
            WHERE ".$where."
        ")->execute()->current();
        $total = $count_row ? (int) $count_row['total'] : 0;

        $rows = \DB::query("
            SELECT l.id,
                   l.source_hash,
                   l.fiscal_period_id,
                   l.build_id,
                   l.cfdi_id,
                   l.cfdi_detail_id,
                   l.payment_detail_id,
                   l.taxpayer_rfc,
                   l.counterparty_rfc,
                   l.emitter_rfc,
                   l.receiver_rfc,
                   l.uuid,
                   l.related_uuid,
                   l.direction,
                   l.cfdi_type,
                   l.payment_method,
                   l.payment_form,
                   l.payment_policy,
                   l.line_number,
                   l.line_type,
                   l.product_service_code,
                   l.identification_number,
                   l.description,
                   l.tax_object,
                   l.base_amount,
                   l.discount_amount,
                   l.tax_code,
                   l.tax_type,
                   l.tax_factor_type,
                   l.tax_rate,
                   l.tax_amount,
                   l.currency,
                   l.exchange_rate,
                   l.base_amount_mxn,
                   l.tax_amount_mxn,
                   l.issue_date,
                   l.stamped_at,
                   l.fiscal_period,
                   l.sat_status,
                   l.source_origin,
                   l.xml_available,
                   l.created_at,
                   l.updated_at
            FROM core_fiscal_ledger_lines l
            WHERE ".$where."
            ORDER BY l.issue_date DESC, l.id DESC
            LIMIT ".$limit."
        ")->execute()->as_array();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->format_row($row);
        }

        $warnings = [];
        if ($total > $limit) {
            $warnings[] = 'Existen '.$total.' movimientos fiscales para los filtros seleccionados. Se muestran solo los primeros '.$limit.'. Refina los filtros para consultar menos registros.';
        }
        if ($total === 0) {
            $warnings[] = 'No hay movimientos fiscales para el RFC, periodo y filtros seleccionados.';
        }

        \Log::info('Fiscal Ledger Detail: fin RFC='.$rfc.' periodo='.$period.' total='.$total.' mostrados='.count($items));

        return [
            'rfc' => $rfc,
            'period' => $period,
            'filters' => $this->public_filters($filters),
            'items' => $items,
            'total' => $total,
            'shown' => count($items),
            'limit' => $limit,
            'has_more' => $total > $limit,
            'warnings' => $warnings,
        ];
    }

    /**
     * WHERE SQL
     *
     * CONSTRUYE CONDICIONES SQL SOLO LECTURA CON VALORES ESCAPADOS.
     *
     * @access  protected
     * @return  String
     */
    protected function where_sql($rfc, $period, array $filters)
    {
        $where = [
            "l.taxpayer_rfc = ".$this->sql($rfc),
            "l.fiscal_period = ".$this->sql($period),
            "l.active = 1",
        ];

        $uuid = strtoupper(trim((string) \Arr::get($filters, 'uuid', '')));
        if ($uuid !== '') {
            $where[] = "l.uuid LIKE ".$this->like_sql($uuid);
        }

        $filter_rfc = $this->soft_rfc((string) \Arr::get($filters, 'rfc', ''));
        if ($filter_rfc !== '') {
            $like = $this->like_sql($filter_rfc);
            $where[] = "(l.emitter_rfc LIKE ".$like." OR l.receiver_rfc LIKE ".$like." OR l.counterparty_rfc LIKE ".$like.")";
        }

        $direction = trim((string) \Arr::get($filters, 'direction', ''));
        if (in_array($direction, ['issued', 'received'], true)) {
            $where[] = "l.direction = ".$this->sql($direction);
        }

        $tax_code = trim((string) \Arr::get($filters, 'tax_code', ''));
        if ($tax_code !== '') {
            $where[] = "l.tax_code = ".$this->sql($tax_code);
        }

        $cfdi_type = strtoupper(trim((string) \Arr::get($filters, 'cfdi_type', '')));
        if ($cfdi_type !== '') {
            $where[] = "l.cfdi_type = ".$this->sql($cfdi_type);
        }

        $sat_status = trim((string) \Arr::get($filters, 'sat_status', ''));
        if ($sat_status !== '') {
            $where[] = "l.sat_status = ".$this->sql($sat_status);
        }

        return implode("\n              AND ", $where);
    }

    /**
     * FORMAT ROW
     *
     * NORMALIZA TIPOS Y ETIQUETAS PARA LA VISTA.
     *
     * @access  protected
     * @return  Array
     */
    protected function format_row(array $row)
    {
        return [
            'id' => (int) $row['id'],
            'source_hash' => (string) $row['source_hash'],
            'fiscal_period_id' => (int) $row['fiscal_period_id'],
            'build_id' => (int) $row['build_id'],
            'cfdi_id' => (int) $row['cfdi_id'],
            'cfdi_detail_id' => (int) $row['cfdi_detail_id'],
            'payment_detail_id' => (int) $row['payment_detail_id'],
            'taxpayer_rfc' => (string) $row['taxpayer_rfc'],
            'counterparty_rfc' => (string) $row['counterparty_rfc'],
            'emitter_rfc' => (string) $row['emitter_rfc'],
            'receiver_rfc' => (string) $row['receiver_rfc'],
            'uuid' => (string) $row['uuid'],
            'related_uuid' => (string) $row['related_uuid'],
            'direction' => (string) $row['direction'],
            'direction_label' => $this->direction_label((string) $row['direction']),
            'cfdi_type' => (string) $row['cfdi_type'],
            'cfdi_type_label' => $this->cfdi_type_label((string) $row['cfdi_type']),
            'payment_method' => (string) $row['payment_method'],
            'payment_form' => (string) $row['payment_form'],
            'payment_policy' => (string) $row['payment_policy'],
            'line_number' => (int) $row['line_number'],
            'line_type' => (string) $row['line_type'],
            'product_service_code' => (string) $row['product_service_code'],
            'identification_number' => (string) $row['identification_number'],
            'description' => (string) $row['description'],
            'tax_object' => (string) $row['tax_object'],
            'base_amount' => $this->money($row['base_amount']),
            'discount_amount' => $this->money($row['discount_amount']),
            'tax_code' => (string) $row['tax_code'],
            'tax_code_label' => $this->tax_code_label((string) $row['tax_code']),
            'tax_type' => (string) $row['tax_type'],
            'tax_type_label' => $this->tax_type_label((string) $row['tax_type']),
            'tax_factor_type' => (string) $row['tax_factor_type'],
            'tax_rate' => round((float) $row['tax_rate'], 6),
            'tax_amount' => $this->money($row['tax_amount']),
            'currency' => (string) $row['currency'],
            'exchange_rate' => round((float) $row['exchange_rate'], 6),
            'base_amount_mxn' => $this->money($row['base_amount_mxn']),
            'tax_amount_mxn' => $this->money($row['tax_amount_mxn']),
            'issue_date' => (string) $row['issue_date'],
            'stamped_at' => (string) $row['stamped_at'],
            'fiscal_period' => (string) $row['fiscal_period'],
            'sat_status' => (string) $row['sat_status'],
            'source_origin' => (string) $row['source_origin'],
            'xml_available' => (int) $row['xml_available'],
            'created_at' => (int) $row['created_at'],
            'updated_at' => (int) $row['updated_at'],
        ];
    }

    protected function validate_schema()
    {
        if (!\DBUtil::table_exists('core_fiscal_ledger_lines')) {
            throw new \RuntimeException('Tabla requerida no existe: core_fiscal_ledger_lines.');
        }
    }

    protected function normalize_rfc($rfc)
    {
        $rfc = strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
        if ($rfc === '') {
            throw new \InvalidArgumentException('RFC requerido para consultar el libro fiscal.');
        }
        return $rfc;
    }

    protected function soft_rfc($rfc)
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
    }

    protected function normalize_period($period)
    {
        $period = trim((string) $period);
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period)) {
            throw new \InvalidArgumentException('Periodo invalido. Usa formato YYYY-MM.');
        }
        return $period;
    }

    protected function normalize_limit($limit)
    {
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > self::DEFAULT_LIMIT) {
            return self::DEFAULT_LIMIT;
        }
        return $limit;
    }

    protected function public_filters(array $filters)
    {
        return [
            'uuid' => strtoupper(trim((string) \Arr::get($filters, 'uuid', ''))),
            'rfc' => $this->soft_rfc((string) \Arr::get($filters, 'rfc', '')),
            'direction' => trim((string) \Arr::get($filters, 'direction', '')),
            'tax_code' => trim((string) \Arr::get($filters, 'tax_code', '')),
            'cfdi_type' => strtoupper(trim((string) \Arr::get($filters, 'cfdi_type', ''))),
            'sat_status' => trim((string) \Arr::get($filters, 'sat_status', '')),
        ];
    }

    protected function direction_label($direction)
    {
        return $direction === 'issued' ? 'Emitido' : ($direction === 'received' ? 'Recibido' : $direction);
    }

    protected function tax_type_label($tax_type)
    {
        return $tax_type === 'transferred' ? 'Trasladado' : ($tax_type === 'retained' ? 'Retenido' : $tax_type);
    }

    protected function tax_code_label($tax_code)
    {
        $labels = [
            '001' => 'ISR',
            '002' => 'IVA',
            '003' => 'IEPS',
        ];

        return isset($labels[$tax_code]) ? $labels[$tax_code].' ('.$tax_code.')' : $tax_code;
    }

    protected function cfdi_type_label($type)
    {
        $labels = [
            'I' => 'Ingreso',
            'E' => 'Egreso',
            'P' => 'Pago',
            'N' => 'Nomina',
            'T' => 'Traslado',
        ];

        return isset($labels[$type]) ? $labels[$type].' ('.$type.')' : $type;
    }

    protected function money($value)
    {
        return round((float) $value, 6);
    }

    protected function sql($value)
    {
        return \DB::quote((string) $value);
    }

    protected function like_sql($value)
    {
        $value = addcslashes((string) $value, "\\%_");
        return \DB::quote('%'.$value.'%');
    }
}
