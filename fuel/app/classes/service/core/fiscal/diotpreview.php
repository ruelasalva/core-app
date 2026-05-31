<?php

/**
 * SERVICE CORE_FISCAL_DIOTPREVIEW
 *
 * Prepara una vista DIOT de solo lectura desde core_fiscal_ledger_lines.
 *
 * @package  app
 */
class Service_Core_Fiscal_DiotPreview
{
    /**
     * CALCULATE
     *
     * AGRUPA CFDI RECIBIDOS POR RFC DE PROVEEDOR.
     *
     * @access  public
     * @return  Array
     */
    public function calculate($rfc, $period)
    {
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $this->validate_schema();

        \Log::info('Fiscal DIOT Preview: inicio RFC='.$rfc.' periodo='.$period);

        $provider_name_sql = $this->provider_name_sql();
        $provider_joins = $this->provider_joins_sql();

        $rows = \DB::query("
            SELECT l.counterparty_rfc,
                   ".$provider_name_sql." AS counterparty_name,
                   COUNT(*) AS movement_count,
                   COUNT(DISTINCT CASE WHEN l.uuid <> '' THEN l.uuid ELSE CONCAT('cfdi:', l.cfdi_id) END) AS cfdi_count,
                   COALESCE(SUM(CASE WHEN l.tax_code = '002' AND l.tax_type = 'transferred' AND l.tax_rate > 0 THEN l.base_amount_mxn ELSE 0 END),0) AS taxed_base,
                   COALESCE(SUM(CASE WHEN l.tax_code = '002' AND l.tax_type = 'transferred' AND l.tax_rate > 0 THEN l.tax_amount_mxn ELSE 0 END),0) AS creditable_vat,
                   COALESCE(SUM(CASE WHEN l.tax_code = '002' AND l.tax_type = 'retained' THEN l.tax_amount_mxn ELSE 0 END),0) AS vat_retained,
                   COALESCE(SUM(CASE WHEN l.tax_code = '001' AND l.tax_type = 'retained' THEN l.tax_amount_mxn ELSE 0 END),0) AS isr_retained,
                   COALESCE(SUM(CASE WHEN l.xml_available = 0 THEN 1 ELSE 0 END),0) AS xml_missing_count,
                   COALESCE(SUM(CASE WHEN LOWER(COALESCE(l.sat_status, '')) = 'cancelado' THEN 1 ELSE 0 END),0) AS cancelled_count,
                   COALESCE(SUM(CASE WHEN l.tax_code = '002' AND l.tax_type = 'transferred' AND l.tax_rate > 0 AND l.base_amount_mxn > 0 AND l.tax_amount_mxn <= 0 THEN 1 ELSE 0 END),0) AS base_without_vat_count,
                   COALESCE(SUM(CASE WHEN l.tax_code = '002' AND l.tax_type = 'transferred' AND l.tax_amount_mxn > 0 AND l.base_amount_mxn <= 0 THEN 1 ELSE 0 END),0) AS vat_without_base_count
            FROM core_fiscal_ledger_lines l
            ".$provider_joins."
            WHERE l.taxpayer_rfc = ".$this->sql($rfc)."
              AND l.fiscal_period = ".$this->sql($period)."
              AND l.direction = 'received'
              AND l.active = 1
            GROUP BY l.counterparty_rfc
            ORDER BY counterparty_name ASC, l.counterparty_rfc ASC
        ")->execute()->as_array();

        $items = [];
        $totals = [
            'taxed_base' => 0,
            'creditable_vat' => 0,
            'vat_retained' => 0,
            'isr_retained' => 0,
            'movement_count' => 0,
            'cfdi_count' => 0,
            'warning_count' => 0,
        ];

        foreach ($rows as $row) {
            $warnings = $this->warnings_for_row($row);

            $item = [
                'counterparty_rfc' => strtoupper((string) $row['counterparty_rfc']),
                'counterparty_name' => (string) $row['counterparty_name'],
                'taxed_base' => $this->money($row['taxed_base']),
                'creditable_vat' => $this->money($row['creditable_vat']),
                'vat_retained' => $this->money($row['vat_retained']),
                'isr_retained' => $this->money($row['isr_retained']),
                'movement_count' => (int) $row['movement_count'],
                'cfdi_count' => (int) $row['cfdi_count'],
                'warnings' => $warnings,
            ];

            $items[] = $item;
            $totals['taxed_base'] += $item['taxed_base'];
            $totals['creditable_vat'] += $item['creditable_vat'];
            $totals['vat_retained'] += $item['vat_retained'];
            $totals['isr_retained'] += $item['isr_retained'];
            $totals['movement_count'] += $item['movement_count'];
            $totals['cfdi_count'] += $item['cfdi_count'];
            $totals['warning_count'] += count($warnings);
        }

        $totals['taxed_base'] = $this->money($totals['taxed_base']);
        $totals['creditable_vat'] = $this->money($totals['creditable_vat']);
        $totals['vat_retained'] = $this->money($totals['vat_retained']);
        $totals['isr_retained'] = $this->money($totals['isr_retained']);

        $preview = [
            'rfc' => $rfc,
            'period' => $period,
            'items' => $items,
            'totals' => $totals,
            'warnings' => [],
        ];

        if (count($items) === 0) {
            $preview['warnings'][] = 'No hay CFDI recibidos en el libro fiscal para el RFC y periodo indicado.';
        }

        \Log::info('Fiscal DIOT Preview: fin RFC='.$rfc.' periodo='.$period.' proveedores='.count($items).' cfdi='.$totals['cfdi_count']);

        return $preview;
    }

    /**
     * WARNINGS FOR ROW
     *
     * GENERA ADVERTENCIAS DIOT POR PROVEEDOR.
     *
     * @access  protected
     * @return  Array
     */
    protected function warnings_for_row(array $row)
    {
        $warnings = [];
        $rfc = strtoupper((string) $row['counterparty_rfc']);
        $name = trim((string) $row['counterparty_name']);

        if ($rfc === '' || $rfc === 'XAXX010101000' || $rfc === 'XEXX010101000') {
            $warnings[] = 'RFC generico';
        }

        if ($name === '') {
            $warnings[] = 'Proveedor sin nombre';
        }

        if ((int) $row['xml_missing_count'] > 0) {
            $warnings[] = 'CFDI sin XML: '.(int) $row['xml_missing_count'];
        }

        if ((int) $row['cancelled_count'] > 0) {
            $warnings[] = 'CFDI cancelado: '.(int) $row['cancelled_count'];
        }

        if ((int) $row['base_without_vat_count'] > 0) {
            $warnings[] = 'Base sin IVA: '.(int) $row['base_without_vat_count'];
        }

        if ((int) $row['vat_without_base_count'] > 0) {
            $warnings[] = 'IVA sin base: '.(int) $row['vat_without_base_count'];
        }

        return $warnings;
    }

    /**
     * PROVIDER NAME SQL
     *
     * RESPETA PRIORIDAD: SAT CFDI EMITTER_NAME, TERCEROS, VACIO.
     *
     * @access  protected
     * @return  String
     */
    protected function provider_name_sql()
    {
        $candidates = [];

        if ($this->sat_cfdi_name_available()) {
            $candidates[] = "MAX(CASE WHEN c.emitter_name IS NOT NULL AND c.emitter_name <> '' THEN c.emitter_name ELSE NULL END)";
        }

        if ($this->parties_name_available()) {
            if (\DBUtil::field_exists('core_parties', ['legal_name'])) {
                $candidates[] = "MAX(CASE WHEN p.legal_name IS NOT NULL AND p.legal_name <> '' THEN p.legal_name ELSE NULL END)";
            }
            if (\DBUtil::field_exists('core_parties', ['name'])) {
                $candidates[] = "MAX(CASE WHEN p.name IS NOT NULL AND p.name <> '' THEN p.name ELSE NULL END)";
            }
        }

        $candidates[] = "''";

        return 'COALESCE('.implode(', ', $candidates).')';
    }

    /**
     * PROVIDER JOINS SQL
     *
     * AGREGA JOINS OPCIONALES SIN ROMPER INSTALACIONES INCOMPLETAS.
     *
     * @access  protected
     * @return  String
     */
    protected function provider_joins_sql()
    {
        $joins = [];

        if ($this->sat_cfdi_name_available()) {
            $joins[] = 'LEFT JOIN core_sat_cfdi c ON c.id = l.cfdi_id';
        }

        if ($this->parties_name_available()) {
            $party_name_fields = [];
            if (\DBUtil::field_exists('core_parties', ['legal_name'])) {
                $party_name_fields[] = "MAX(NULLIF(legal_name, '')) AS legal_name";
            } else {
                $party_name_fields[] = "'' AS legal_name";
            }
            if (\DBUtil::field_exists('core_parties', ['name'])) {
                $party_name_fields[] = "MAX(NULLIF(name, '')) AS name";
            } else {
                $party_name_fields[] = "'' AS name";
            }

            $joins[] = "LEFT JOIN (
                SELECT UPPER(rfc) AS party_rfc, ".implode(', ', $party_name_fields)."
                FROM core_parties
                WHERE active = 1
                GROUP BY UPPER(rfc)
            ) p ON p.party_rfc = UPPER(l.counterparty_rfc)";
        }

        return implode("\n            ", $joins);
    }

    protected function sat_cfdi_name_available()
    {
        return \DBUtil::table_exists('core_sat_cfdi')
            && \DBUtil::field_exists('core_sat_cfdi', ['emitter_name']);
    }

    protected function parties_name_available()
    {
        return \DBUtil::table_exists('core_parties')
            && \DBUtil::field_exists('core_parties', ['rfc'])
            && (\DBUtil::field_exists('core_parties', ['legal_name']) || \DBUtil::field_exists('core_parties', ['name']));
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
            throw new \InvalidArgumentException('RFC requerido para preparacion DIOT.');
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

    protected function money($value)
    {
        return round((float) $value, 6);
    }

    protected function sql($value)
    {
        return \DB::quote((string) $value);
    }
}
