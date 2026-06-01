<?php
namespace Fuel\Tasks;

/**
 * TAREA VALIDATEFISCALLEDGER
 *
 * Valida el libro fiscal contra CFDI SAT importados sin modificar datos.
 *
 * Uso:
 * php oil refine validatefiscalledger --rfc=SET180322811 --period=2026-05
 *
 * @package  app
 */
class Validatefiscalledger
{
    protected $warnings = [];
    protected $errors = [];
    protected $suggestions = [];

    /**
     * RUN
     *
     * EJECUTA VALIDACION DE SOLO LECTURA.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        $this->warnings = [];
        $this->errors = [];
        $this->suggestions = [];

        $options = $this->options();
        $rfc = $this->normalize_rfc(isset($options['rfc']) ? $options['rfc'] : '');
        $period = $this->normalize_period(isset($options['period']) ? $options['period'] : '');

        if ($rfc === '' || $period === '') {
            \Cli::write('Uso: php oil refine validatefiscalledger --rfc=SET180322811 --period=2026-05');
            return;
        }

        try {
            $this->validate_schema();

            $dates = $this->period_dates($period);
            \Log::info('Validate Fiscal Ledger: inicio RFC='.$rfc.' periodo='.$period);

            $source = $this->source_summary($rfc, $dates);
            $ledger = $this->ledger_summary($rfc, $period);
            $diffs = $this->differences($source, $ledger);
            $this->run_integrity_checks($rfc, $period, $dates);

            $this->output($rfc, $period, $source, $ledger, $diffs);
            $this->store_validation($rfc, $period, $source, $ledger, $diffs);
            \Log::info('Validate Fiscal Ledger: fin RFC='.$rfc.' periodo='.$period.' errores='.count($this->errors).' advertencias='.count($this->warnings));
        } catch (\Exception $e) {
            \Log::error('Validate Fiscal Ledger: '.$e->getMessage());
            \Service_Core_Fiscal_EventLogger::log([
                'taxpayer_rfc' => $rfc,
                'fiscal_period' => $period,
                'event_type' => 'ledger_validation',
                'event_status' => 'error',
                'source_module' => 'fiscal',
                'source_entity_type' => 'fiscal_validation',
                'summary' => 'Error validando libro fiscal.',
                'details' => ['error' => $e->getMessage()],
                'executed_by' => 0,
            ]);
            \Cli::write('Error validando libro fiscal: '.$e->getMessage());
        }
    }

    /**
     * SOURCE SUMMARY
     *
     * RESUME CFDI SAT Y DETALLES ORIGEN.
     *
     * @access  protected
     * @return  Array
     */
    protected function source_summary($rfc, array $dates)
    {
        $where = $this->source_where($rfc, $dates);

        $cfdi = $this->row("
            SELECT COUNT(*) AS cfdi_count
            FROM core_sat_cfdi c
            WHERE ".$where."
        ");

        $detail = $this->row("
            SELECT COUNT(d.id) AS detail_count,
                   COALESCE(SUM(d.vat_amount),0) AS iva_transferred,
                   COALESCE(SUM(d.ret_vat_amount),0) AS iva_retained,
                   COALESCE(SUM(d.ret_isr_amount),0) AS isr_retained,
                   COALESCE(SUM(d.ieps_amount),0) AS ieps_transferred,
                   COALESCE(SUM(CASE WHEN d.vat_base <> 0 OR d.vat_amount <> 0 OR TRIM(COALESCE(d.vat_rate,'')) <> '' THEN 1 ELSE 0 END),0)
                 + COALESCE(SUM(CASE WHEN d.ieps_base <> 0 OR d.ieps_amount <> 0 OR TRIM(COALESCE(d.ieps_rate,'')) <> '' THEN 1 ELSE 0 END),0)
                 + COALESCE(SUM(CASE WHEN d.ret_vat_base <> 0 OR d.ret_vat_amount <> 0 OR TRIM(COALESCE(d.ret_vat_rate,'')) <> '' THEN 1 ELSE 0 END),0)
                 + COALESCE(SUM(CASE WHEN d.ret_isr_base <> 0 OR d.ret_isr_amount <> 0 OR TRIM(COALESCE(d.ret_isr_rate,'')) <> '' THEN 1 ELSE 0 END),0)
                   AS expected_ledger_rows
            FROM core_sat_cfdi c
            INNER JOIN core_sat_cfdi_details d ON d.cfdi_id = c.id AND d.line_type = 'concept'
            WHERE ".$where."
        ");

        return [
            'cfdi_count' => (int) $cfdi['cfdi_count'],
            'detail_count' => (int) $detail['detail_count'],
            'ledger_rows' => (int) $detail['expected_ledger_rows'],
            'iva_transferred' => (float) $detail['iva_transferred'],
            'iva_retained' => (float) $detail['iva_retained'],
            'isr_retained' => (float) $detail['isr_retained'],
            'ieps_transferred' => (float) $detail['ieps_transferred'],
        ];
    }

    /**
     * LEDGER SUMMARY
     *
     * RESUME LINEAS DEL LIBRO FISCAL.
     *
     * @access  protected
     * @return  Array
     */
    protected function ledger_summary($rfc, $period)
    {
        $rfc_sql = $this->sql($rfc);
        $period_sql = $this->sql($period);

        $row = $this->row("
            SELECT COUNT(*) AS ledger_rows,
                   COUNT(DISTINCT cfdi_id) AS cfdi_count,
                   COUNT(DISTINCT CASE WHEN line_type = 'concept' THEN cfdi_detail_id ELSE NULL END) AS detail_count,
                   COALESCE(SUM(CASE WHEN tax_code = '002' AND tax_type = 'transferred' THEN tax_amount ELSE 0 END),0) AS iva_transferred,
                   COALESCE(SUM(CASE WHEN tax_code = '002' AND tax_type = 'retained' THEN tax_amount ELSE 0 END),0) AS iva_retained,
                   COALESCE(SUM(CASE WHEN tax_code = '001' AND tax_type = 'retained' THEN tax_amount ELSE 0 END),0) AS isr_retained,
                   COALESCE(SUM(CASE WHEN tax_code = '003' AND tax_type = 'transferred' THEN tax_amount ELSE 0 END),0) AS ieps_transferred
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$rfc_sql."
              AND fiscal_period = ".$period_sql."
              AND active = 1
        ");

        return [
            'cfdi_count' => (int) $row['cfdi_count'],
            'detail_count' => (int) $row['detail_count'],
            'ledger_rows' => (int) $row['ledger_rows'],
            'iva_transferred' => (float) $row['iva_transferred'],
            'iva_retained' => (float) $row['iva_retained'],
            'isr_retained' => (float) $row['isr_retained'],
            'ieps_transferred' => (float) $row['ieps_transferred'],
        ];
    }

    /**
     * DIFFERENCES
     *
     * COMPARA ORIGEN CONTRA LIBRO.
     *
     * @access  protected
     * @return  Array
     */
    protected function differences(array $source, array $ledger)
    {
        $fields = [
            'cfdi_count' => 'CFDI procesados',
            'detail_count' => 'Detalles procesados',
            'ledger_rows' => 'Lineas fiscales generadas',
            'iva_transferred' => 'IVA trasladado',
            'iva_retained' => 'IVA retenido',
            'isr_retained' => 'ISR retenido',
            'ieps_transferred' => 'IEPS trasladado',
        ];
        $diffs = [];

        foreach ($fields as $field => $label) {
            $source_value = (float) $source[$field];
            $ledger_value = (float) $ledger[$field];
            $diff = round($source_value - $ledger_value, 6);
            $diffs[$field] = [
                'label' => $label,
                'source' => $source_value,
                'ledger' => $ledger_value,
                'difference' => $diff,
            ];

            if (abs($diff) > 0.01) {
                $this->warnings[] = $label.' no coincide. Origen='.$source_value.' Libro='.$ledger_value.' Diferencia='.$diff;
            }
        }

        return $diffs;
    }

    /**
     * RUN INTEGRITY CHECKS
     *
     * EJECUTA VALIDACIONES DE CONSISTENCIA.
     *
     * @access  protected
     * @return  Void
     */
    protected function run_integrity_checks($rfc, $period, array $dates)
    {
        $this->check_cfdi_with_details_without_ledger($rfc, $period, $dates);
        $this->check_ledger_without_valid_cfdi($rfc, $period);
        $this->check_ledger_without_valid_detail($rfc, $period);
        $this->check_duplicate_source_hash($rfc, $period);
        $this->check_empty_source_hash($rfc, $period);
        $this->check_missing_required_fields($rfc, $period);
        $this->check_missing_tax_code($rfc, $period);
        $this->check_invalid_tax_type($rfc, $period);
    }

    protected function check_cfdi_with_details_without_ledger($rfc, $period, array $dates)
    {
        $where = $this->source_where($rfc, $dates);
        $period_sql = $this->sql($period);
        $rows = $this->rows("
            SELECT c.id, c.uuid
            FROM core_sat_cfdi c
            INNER JOIN core_sat_cfdi_details d ON d.cfdi_id = c.id AND d.line_type = 'concept'
            LEFT JOIN core_fiscal_ledger_lines l ON l.cfdi_id = c.id
                AND l.taxpayer_rfc = ".$this->sql($rfc)."
                AND l.fiscal_period = ".$period_sql."
                AND l.active = 1
            WHERE ".$where."
            GROUP BY c.id, c.uuid
            HAVING COUNT(d.id) > 0 AND COUNT(l.id) = 0
            ORDER BY c.issued_at ASC
            LIMIT 20
        ");

        if ($rows) {
            $this->warnings[] = 'Hay CFDI con detalles pero sin lineas fiscales: '.$this->list_uuids($rows);
            $this->suggestions[] = 'Revisar si esos CFDI no tienen impuestos o ejecutar buildfiscalledger para el RFC/periodo.';
        }
    }

    protected function check_ledger_without_valid_cfdi($rfc, $period)
    {
        $count = $this->count("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines l
            LEFT JOIN core_sat_cfdi c ON c.id = l.cfdi_id
            WHERE l.taxpayer_rfc = ".$this->sql($rfc)."
              AND l.fiscal_period = ".$this->sql($period)."
              AND l.active = 1
              AND c.id IS NULL
        ");

        if ($count > 0) {
            $this->errors[] = 'Lineas fiscales sin cfdi_id valido: '.$count;
            $this->suggestions[] = 'Reconstruir el periodo fiscal despues de verificar que no existan CFDI SAT eliminados.';
        }
    }

    protected function check_ledger_without_valid_detail($rfc, $period)
    {
        $count = $this->count("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines l
            LEFT JOIN core_sat_cfdi_details d ON d.id = l.cfdi_detail_id
            WHERE l.taxpayer_rfc = ".$this->sql($rfc)."
              AND l.fiscal_period = ".$this->sql($period)."
              AND l.active = 1
              AND l.line_type = 'concept'
              AND d.id IS NULL
        ");

        if ($count > 0) {
            $this->errors[] = 'Lineas fiscales de concepto sin cfdi_detail_id valido: '.$count;
            $this->suggestions[] = 'Revisar importaciones XML reemplazadas y reconstruir el libro fiscal del periodo.';
        }
    }

    protected function check_duplicate_source_hash($rfc, $period)
    {
        $rows = $this->rows("
            SELECT source_hash, COUNT(*) AS total
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
            GROUP BY source_hash
            HAVING COUNT(*) > 1
            LIMIT 20
        ");

        if ($rows) {
            $this->errors[] = 'Existen source_hash duplicados: '.count($rows).' hashes detectados.';
            $this->suggestions[] = 'Validar indice unico uidx_core_fiscal_ledger_source_hash y reconstruir duplicados.';
        }
    }

    protected function check_empty_source_hash($rfc, $period)
    {
        $count = $this->count("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
              AND TRIM(COALESCE(source_hash,'')) = ''
        ");

        if ($count > 0) {
            $this->errors[] = 'Lineas fiscales con source_hash vacio: '.$count;
            $this->suggestions[] = 'Reconstruir esas lineas; el builder debe generar source_hash SHA-256 siempre.';
        }
    }

    protected function check_missing_required_fields($rfc, $period)
    {
        $missing_rfc = $this->count("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
              AND TRIM(COALESCE(taxpayer_rfc,'')) = ''
        ");

        $missing_period = $this->count("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
              AND TRIM(COALESCE(fiscal_period,'')) = ''
        ");

        if ($missing_rfc > 0) {
            $this->errors[] = 'Lineas fiscales sin taxpayer_rfc: '.$missing_rfc;
        }
        if ($missing_period > 0) {
            $this->errors[] = 'Lineas fiscales sin fiscal_period: '.$missing_period;
        }
    }

    protected function check_missing_tax_code($rfc, $period)
    {
        $count = $this->count("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
              AND tax_amount > 0
              AND TRIM(COALESCE(tax_code,'')) = ''
        ");

        if ($count > 0) {
            $this->errors[] = 'Lineas fiscales con tax_amount > 0 sin tax_code: '.$count;
            $this->suggestions[] = 'Corregir mapeo de impuestos antes de calcular IVA o DIOT.';
        }
    }

    protected function check_invalid_tax_type($rfc, $period)
    {
        $count = $this->count("
            SELECT COUNT(*) AS total
            FROM core_fiscal_ledger_lines
            WHERE taxpayer_rfc = ".$this->sql($rfc)."
              AND fiscal_period = ".$this->sql($period)."
              AND active = 1
              AND tax_type NOT IN ('transferred', 'retained')
        ");

        if ($count > 0) {
            $this->errors[] = 'Lineas fiscales con tax_type invalido: '.$count;
            $this->suggestions[] = 'Los valores permitidos son transferred y retained.';
        }
    }

    /**
     * OUTPUT
     *
     * IMPRIME RESUMEN, DIFERENCIAS, ADVERTENCIAS Y ERRORES.
     *
     * @access  protected
     * @return  Void
     */
    protected function output($rfc, $period, array $source, array $ledger, array $diffs)
    {
        \Cli::write('Validacion libro fiscal');
        \Cli::write(' - RFC: '.$rfc);
        \Cli::write(' - Periodo: '.$period);

        \Cli::write('');
        \Cli::write('Resumen origen SAT');
        \Cli::write(' - CFDI: '.$source['cfdi_count']);
        \Cli::write(' - Detalles: '.$source['detail_count']);
        \Cli::write(' - Movimientos esperados: '.$source['ledger_rows']);

        \Cli::write('');
        \Cli::write('Resumen libro fiscal');
        \Cli::write(' - CFDI: '.$ledger['cfdi_count']);
        \Cli::write(' - Detalles: '.$ledger['detail_count']);
        \Cli::write(' - Lineas fiscales: '.$ledger['ledger_rows']);

        \Cli::write('');
        \Cli::write('Diferencias');
        foreach ($diffs as $diff) {
            \Cli::write(' - '.$diff['label'].': origen='.$this->money($diff['source']).' libro='.$this->money($diff['ledger']).' diferencia='.$this->money($diff['difference']));
        }

        $this->print_list('Warnings', $this->warnings);
        $this->print_list('Errors', $this->errors);
        $this->print_list('Suggested fixes', array_values(array_unique($this->suggestions)));
    }

    protected function validate_schema()
    {
        foreach (['core_sat_cfdi', 'core_sat_cfdi_details', 'core_fiscal_ledger_lines'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Tabla requerida no existe: '.$table.'.');
            }
        }

        if (!\DBUtil::table_exists('core_fiscal_validations')) {
            throw new \RuntimeException('Falta ejecutar migración 066.');
        }
    }

    /**
     * STORE VALIDATION
     *
     * PERSISTE EL RESULTADO DE LA VALIDACION PARA CIERRE FISCAL Y AUDITORIA.
     *
     * @access  protected
     * @return  Void
     */
    protected function store_validation($rfc, $period, array $source, array $ledger, array $diffs)
    {
        $now = time();
        $status = $this->validation_status();
        $summary = [
            'source' => $source,
            'ledger' => $ledger,
            'differences' => $diffs,
            'warnings' => array_values(array_unique($this->warnings)),
            'errors' => array_values(array_unique($this->errors)),
            'suggestions' => array_values(array_unique($this->suggestions)),
        ];

        \DB::insert('core_fiscal_validations')->set([
            'company_id' => $this->company_id($rfc),
            'taxpayer_rfc' => $rfc,
            'fiscal_period' => $period,
            'validation_type' => 'ledger_integrity',
            'status' => $status,
            'warnings_count' => count($summary['warnings']),
            'errors_count' => count($summary['errors']),
            'summary_json' => json_encode($summary),
            'executed_by' => 0,
            'executed_at' => $now,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        \Cli::write('');
        \Cli::write('Resultado guardado en core_fiscal_validations con estado: '.$status);
        \Log::info('Validate Fiscal Ledger: resultado persistido RFC='.$rfc.' periodo='.$period.' estado='.$status);

        \Service_Core_Fiscal_EventLogger::log([
            'company_id' => $this->company_id($rfc),
            'taxpayer_rfc' => $rfc,
            'fiscal_period' => $period,
            'event_type' => 'ledger_validation',
            'event_status' => $status === 'ok' ? 'success' : $status,
            'source_module' => 'fiscal',
            'source_entity_type' => 'fiscal_validation',
            'summary' => 'Validacion de libro fiscal finalizada.',
            'details' => $summary,
            'executed_by' => 0,
        ]);
    }

    protected function validation_status()
    {
        if (count($this->errors) > 0) {
            return 'error';
        }
        if (count($this->warnings) > 0) {
            return 'warning';
        }
        return 'ok';
    }

    protected function company_id($rfc)
    {
        if (!\DBUtil::table_exists('core_companies')) {
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

    protected function source_where($rfc, array $dates)
    {
        return "c.issued_at >= ".$this->sql($dates['from'].' 00:00:00')."
            AND c.issued_at <= ".$this->sql($dates['to'].' 23:59:59')."
            AND LOWER(COALESCE(c.sat_status,'')) <> 'cancelado'
            AND (c.emitter_rfc = ".$this->sql($rfc)." OR c.receiver_rfc = ".$this->sql($rfc).")";
    }

    protected function rows($sql)
    {
        return \DB::query($sql)->execute()->as_array();
    }

    protected function row($sql)
    {
        $row = \DB::query($sql)->execute()->current();
        return $row ?: [];
    }

    protected function count($sql)
    {
        $row = $this->row($sql);
        return (int) (isset($row['total']) ? $row['total'] : 0);
    }

    protected function sql($value)
    {
        return \DB::quote((string) $value);
    }

    protected function list_uuids(array $rows)
    {
        $uuids = [];
        foreach ($rows as $row) {
            $uuids[] = (string) $row['uuid'];
        }
        return implode(', ', $uuids);
    }

    protected function print_list($title, array $items)
    {
        \Cli::write('');
        \Cli::write($title);
        if (empty($items)) {
            \Cli::write(' - Ninguno');
            return;
        }
        foreach ($items as $item) {
            \Cli::write(' - '.$item);
        }
    }

    protected function money($value)
    {
        return number_format((float) $value, 6, '.', '');
    }

    protected function normalize_rfc($rfc)
    {
        $rfc = strtoupper(trim((string) $rfc));
        if ($rfc === '' || !preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
            return '';
        }
        return $rfc;
    }

    protected function normalize_period($period)
    {
        $period = trim((string) $period);
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period)) {
            return '';
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

    protected function options()
    {
        $options = [];
        $argv = isset($_SERVER['argv']) ? (array) $_SERVER['argv'] : [];

        foreach ($argv as $arg) {
            if (strpos($arg, '--') !== 0) {
                continue;
            }

            $arg = substr($arg, 2);
            $parts = explode('=', $arg, 2);
            $key = trim((string) $parts[0]);
            $value = isset($parts[1]) ? trim((string) $parts[1]) : '';
            if ($key !== '') {
                $options[$key] = $value;
            }
        }

        return $options;
    }
}
