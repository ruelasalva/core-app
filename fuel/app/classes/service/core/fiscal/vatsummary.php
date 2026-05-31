<?php

/**
 * SERVICE CORE_FISCAL_VATSUMMARY
 *
 * Calcula resumen mensual preliminar de IVA desde el libro fiscal.
 *
 * @package  app
 */
class Service_Core_Fiscal_VatSummary
{
    /**
     * CALCULATE
     *
     * CALCULA IVA MENSUAL DESDE CORE_FISCAL_LEDGER_LINES.
     *
     * @access  public
     * @return  Array
     */
    public function calculate($rfc, $period)
    {
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $this->validate_schema();

        \Log::info('Fiscal VAT Summary: inicio RFC='.$rfc.' periodo='.$period);

        $row = \DB::query("
            SELECT COUNT(*) AS ledger_rows,
                   COALESCE(SUM(CASE WHEN direction = 'issued' AND tax_code = '002' AND tax_type = 'transferred' THEN tax_amount_mxn ELSE 0 END),0) AS issued_vat_transferred,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '002' AND tax_type = 'transferred' THEN tax_amount_mxn ELSE 0 END),0) AS received_vat_transferred,
                   COALESCE(SUM(CASE WHEN direction = 'issued' AND tax_code = '002' AND tax_type = 'retained' THEN tax_amount_mxn ELSE 0 END),0) AS vat_retained_by_customers,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '002' AND tax_type = 'retained' THEN tax_amount_mxn ELSE 0 END),0) AS vat_retained_from_suppliers,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '001' AND tax_type = 'retained' THEN tax_amount_mxn ELSE 0 END),0) AS isr_retained_from_suppliers
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
        ")->execute()->current();

        $summary = [
            'rfc' => $rfc,
            'period' => $period,
            'ledger_rows' => (int) $row['ledger_rows'],
            'issued_vat_transferred' => round((float) $row['issued_vat_transferred'], 6),
            'received_vat_transferred' => round((float) $row['received_vat_transferred'], 6),
            'vat_retained_by_customers' => round((float) $row['vat_retained_by_customers'], 6),
            'vat_retained_from_suppliers' => round((float) $row['vat_retained_from_suppliers'], 6),
            'isr_retained_from_suppliers' => round((float) $row['isr_retained_from_suppliers'], 6),
            'warnings' => [],
        ];

        $summary['preliminary_vat_payable'] = round(
            $summary['issued_vat_transferred']
            - $summary['received_vat_transferred']
            - $summary['vat_retained_by_customers'],
            6
        );

        if ($summary['ledger_rows'] === 0) {
            $summary['warnings'][] = 'No hay lineas en core_fiscal_ledger_lines para el RFC y periodo indicado.';
        }

        \Log::info('Fiscal VAT Summary: fin RFC='.$rfc.' periodo='.$period.' lineas='.$summary['ledger_rows'].' iva_preliminar='.$summary['preliminary_vat_payable']);

        return $summary;
    }

    /**
     * VALIDATE SCHEMA
     *
     * CONFIRMA TABLA REQUERIDA.
     *
     * @access  protected
     * @return  Void
     */
    protected function validate_schema()
    {
        if (!\DBUtil::table_exists('core_fiscal_ledger_lines')) {
            throw new \RuntimeException('Tabla requerida no existe: core_fiscal_ledger_lines.');
        }
    }

    protected function normalize_rfc($rfc)
    {
        $rfc = strtoupper(trim((string) $rfc));
        if ($rfc === '' || !preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
            throw new \InvalidArgumentException('RFC invalido para resumen IVA.');
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

    protected function sql($value)
    {
        return \DB::quote((string) $value);
    }
}
