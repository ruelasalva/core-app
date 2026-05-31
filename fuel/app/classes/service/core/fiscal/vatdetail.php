<?php

/**
 * SERVICE CORE_FISCAL_VATDETAIL
 *
 * Calcula el IVA mensual detallado desde core_fiscal_ledger_lines.
 *
 * @package  app
 */
class Service_Core_Fiscal_VatDetail
{
    /**
     * CALCULATE
     *
     * CONSOLIDA IVA MENSUAL DE SOLO LECTURA DESDE EL LIBRO FISCAL.
     *
     * @access  public
     * @return  Array
     */
    public function calculate($rfc, $period)
    {
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $this->validate_schema();

        \Log::info('Fiscal VAT Detail: inicio RFC='.$rfc.' periodo='.$period);

        $row = \DB::query("
            SELECT COUNT(*) AS ledger_rows,
                   COALESCE(SUM(CASE WHEN direction = 'issued' AND tax_code = '002' AND tax_type = 'transferred' AND tax_rate > 0 THEN base_amount_mxn ELSE 0 END),0) AS sales_taxed_base,
                   COALESCE(SUM(CASE WHEN direction = 'issued' AND tax_code = '002' AND tax_type = 'transferred' AND tax_rate > 0 THEN tax_amount_mxn ELSE 0 END),0) AS sales_vat_transferred,
                   COALESCE(SUM(CASE WHEN direction = 'issued' AND tax_code = '002' AND tax_type = 'transferred' AND tax_rate = 0 AND UPPER(COALESCE(tax_factor_type, '')) <> 'EXENTO' THEN base_amount_mxn ELSE 0 END),0) AS sales_zero_base,
                   COALESCE(SUM(CASE WHEN direction = 'issued' AND (UPPER(COALESCE(tax_factor_type, '')) = 'EXENTO' OR COALESCE(tax_object, '') = '01') THEN base_amount_mxn ELSE 0 END),0) AS sales_exempt_base,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '002' AND tax_type = 'transferred' AND tax_rate > 0 THEN base_amount_mxn ELSE 0 END),0) AS purchases_taxed_base,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '002' AND tax_type = 'transferred' AND tax_rate > 0 THEN tax_amount_mxn ELSE 0 END),0) AS purchases_vat_creditable,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '002' AND tax_type = 'transferred' AND tax_rate = 0 AND UPPER(COALESCE(tax_factor_type, '')) <> 'EXENTO' THEN base_amount_mxn ELSE 0 END),0) AS purchases_zero_base,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND (UPPER(COALESCE(tax_factor_type, '')) = 'EXENTO' OR COALESCE(tax_object, '') = '01') THEN base_amount_mxn ELSE 0 END),0) AS purchases_exempt_base,
                   COALESCE(SUM(CASE WHEN direction = 'issued' AND tax_code = '002' AND tax_type = 'retained' THEN tax_amount_mxn ELSE 0 END),0) AS vat_retained_by_customers,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '002' AND tax_type = 'retained' THEN tax_amount_mxn ELSE 0 END),0) AS vat_retained_from_suppliers,
                   COALESCE(SUM(CASE WHEN direction = 'received' AND tax_code = '001' AND tax_type = 'retained' THEN tax_amount_mxn ELSE 0 END),0) AS isr_retained_from_suppliers
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
        ")->execute()->current();

        $sales_vat = $this->money($row['sales_vat_transferred']);
        $purchase_vat = $this->money($row['purchases_vat_creditable']);
        $vat_retained_by_customers = $this->money($row['vat_retained_by_customers']);
        $vat_retained_from_suppliers = $this->money($row['vat_retained_from_suppliers']);

        $detail = [
            'rfc' => $rfc,
            'period' => $period,
            'ledger_rows' => (int) $row['ledger_rows'],
            'sales' => [
                'taxed_base' => $this->money($row['sales_taxed_base']),
                'vat_transferred' => $sales_vat,
                'zero_base' => $this->money($row['sales_zero_base']),
                'exempt_base' => $this->money($row['sales_exempt_base']),
            ],
            'purchases' => [
                'taxed_base' => $this->money($row['purchases_taxed_base']),
                'vat_creditable' => $purchase_vat,
                'zero_base' => $this->money($row['purchases_zero_base']),
                'exempt_base' => $this->money($row['purchases_exempt_base']),
            ],
            'withholdings' => [
                'vat_retained' => $this->money($vat_retained_by_customers + $vat_retained_from_suppliers),
                'vat_retained_by_customers' => $vat_retained_by_customers,
                'vat_retained_from_suppliers' => $vat_retained_from_suppliers,
                'isr_retained' => $this->money($row['isr_retained_from_suppliers']),
            ],
            'result' => [
                'vat_caused' => $sales_vat,
                'vat_creditable' => $purchase_vat,
                'preliminary_vat_payable' => $this->money($sales_vat - $purchase_vat - $vat_retained_by_customers),
            ],
            'warnings' => [],
        ];

        if ($detail['ledger_rows'] === 0) {
            $detail['warnings'][] = 'No hay lineas fiscales para el RFC y periodo indicado.';
        }

        \Log::info('Fiscal VAT Detail: fin RFC='.$rfc.' periodo='.$period.' lineas='.$detail['ledger_rows'].' iva_preliminar='.$detail['result']['preliminary_vat_payable']);

        return $detail;
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
        $rfc = strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
        if ($rfc === '') {
            throw new \InvalidArgumentException('RFC requerido para detalle IVA.');
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
