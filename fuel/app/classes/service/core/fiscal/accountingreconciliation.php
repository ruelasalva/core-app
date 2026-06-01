<?php

/**
 * SERVICE CORE_FISCAL_ACCOUNTINGRECONCILIATION
 *
 * Compara importes fiscales del libro fiscal contra movimientos contables
 * contabilizados en las cuentas configuradas.
 *
 * @package  app
 */
class Service_Core_Fiscal_AccountingReconciliation
{
    protected $definitions = [
        [
            'key' => 'iva_trasladado',
            'concept' => 'IVA trasladado',
            'tax_code' => '002',
            'tax_type' => 'transferred',
            'direction' => 'issued',
        ],
        [
            'key' => 'iva_acreditable',
            'concept' => 'IVA acreditable',
            'tax_code' => '002',
            'tax_type' => 'transferred',
            'direction' => 'received',
        ],
        [
            'key' => 'iva_retenido',
            'concept' => 'IVA retenido',
            'tax_code' => '002',
            'tax_type' => 'retained',
            'direction' => '',
        ],
        [
            'key' => 'isr_retenido',
            'concept' => 'ISR retenido',
            'tax_code' => '001',
            'tax_type' => 'retained',
            'direction' => '',
        ],
    ];

    /**
     * CALCULATE
     *
     * GENERA LA CONCILIACION FISCAL-CONTABLE DEL PERIODO.
     *
     * @access  public
     * @return  Array
     */
    public function calculate($rfc, $period)
    {
        $rfc = $this->normalize_rfc($rfc);
        $period = trim((string) $period);
        $this->assert_required_tables();
        $dates = $this->period_dates($period);

        $items = [];
        $totals = [
            'fiscal_amount' => 0,
            'accounting_amount' => 0,
            'difference' => 0,
        ];
        $warnings = [];

        foreach ($this->definitions as $definition) {
            $mapping = $this->mapping_for($definition);
            $fiscal_amount = $this->fiscal_amount($rfc, $period, $definition);
            $accounting_amount = 0;
            $account_label = '';
            $status = 'Sin cuenta configurada';

            if ($mapping) {
                $account_label = trim($mapping['account_code'].' - '.$mapping['account_name'], ' -');
                $accounting_amount = $this->accounting_amount((int) $mapping['account_id'], $dates);
                $difference = round($fiscal_amount - $accounting_amount, 6);

                if (abs($difference) <= 0.01) {
                    $status = 'OK';
                } elseif (abs($accounting_amount) <= 0.000001 && abs($fiscal_amount) > 0.000001) {
                    $status = 'Sin movimientos contables';
                } else {
                    $status = 'Diferencia';
                }
            } else {
                $difference = round($fiscal_amount, 6);
                $warnings[] = 'Sin cuenta configurada para '.$definition['concept'].'.';
            }

            $items[] = [
                'key' => $definition['key'],
                'concept' => $definition['concept'],
                'tax_code' => $definition['tax_code'],
                'tax_type' => $definition['tax_type'],
                'direction' => $definition['direction'],
                'account_id' => $mapping ? (int) $mapping['account_id'] : 0,
                'account_code' => $mapping ? (string) $mapping['account_code'] : '',
                'account_name' => $mapping ? (string) $mapping['account_name'] : '',
                'account_label' => $account_label,
                'fiscal_amount' => round($fiscal_amount, 6),
                'accounting_amount' => round($accounting_amount, 6),
                'difference' => $difference,
                'status' => $status,
            ];

            $totals['fiscal_amount'] += $fiscal_amount;
            $totals['accounting_amount'] += $accounting_amount;
            $totals['difference'] += $difference;
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round((float) $value, 6);
        }

        \Log::info('Conciliacion fiscal-contable consultada RFC='.$rfc.' periodo='.$period.' partidas='.count($items));

        return [
            'rfc' => $rfc,
            'period' => $period,
            'items' => $items,
            'totals' => $totals,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    protected function mapping_for(array $definition)
    {
        $query = \DB::select(
                ['m.account_id', 'account_id'],
                ['a.code', 'account_code'],
                ['a.name', 'account_name'],
                ['a.nature', 'account_nature']
            )
            ->from(['core_fiscal_account_mappings', 'm'])
            ->join(['core_accounting_accounts', 'a'], 'INNER')
            ->on('a.id', '=', 'm.account_id')
            ->where('m.tax_code', '=', $definition['tax_code'])
            ->where('m.tax_type', '=', $definition['tax_type'])
            ->where('m.direction', '=', $definition['direction'])
            ->where('m.active', '=', 1)
            ->where('a.active', '=', 1)
            ->limit(1);

        $row = $query->execute()->current();
        if ($row) {
            return $row;
        }

        if ($definition['direction'] !== '') {
            return null;
        }

        return \DB::select(
                ['m.account_id', 'account_id'],
                ['a.code', 'account_code'],
                ['a.name', 'account_name'],
                ['a.nature', 'account_nature']
            )
            ->from(['core_fiscal_account_mappings', 'm'])
            ->join(['core_accounting_accounts', 'a'], 'INNER')
            ->on('a.id', '=', 'm.account_id')
            ->where('m.tax_code', '=', $definition['tax_code'])
            ->where('m.tax_type', '=', $definition['tax_type'])
            ->where('m.direction', '=', '')
            ->where('m.active', '=', 1)
            ->where('a.active', '=', 1)
            ->limit(1)
            ->execute()
            ->current();
    }

    protected function fiscal_amount($rfc, $period, array $definition)
    {
        $query = \DB::select([\DB::expr('COALESCE(SUM(tax_amount_mxn), 0)'), 'amount'])
            ->from('core_fiscal_ledger_lines')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('fiscal_period', '=', $period)
            ->where('tax_code', '=', $definition['tax_code'])
            ->where('tax_type', '=', $definition['tax_type'])
            ->where('active', '=', 1);

        if ($definition['direction'] !== '') {
            $query->where('direction', '=', $definition['direction']);
        }

        $row = $query->execute()->current();
        return $row ? (float) $row['amount'] : 0.0;
    }

    protected function accounting_amount($account_id, array $dates)
    {
        $row = \DB::select(
                ['a.nature', 'nature'],
                [\DB::expr('COALESCE(SUM(l.debit), 0)'), 'debit'],
                [\DB::expr('COALESCE(SUM(l.credit), 0)'), 'credit']
            )
            ->from(['core_accounting_journal_lines', 'l'])
            ->join(['core_accounting_journal_entries', 'e'], 'INNER')
            ->on('e.id', '=', 'l.entry_id')
            ->join(['core_accounting_accounts', 'a'], 'INNER')
            ->on('a.id', '=', 'l.account_id')
            ->where('l.account_id', '=', $account_id)
            ->where('l.active', '=', 1)
            ->where('e.active', '=', 1)
            ->where('e.status', '=', 'posted')
            ->where('e.entry_date', '>=', $dates['from'])
            ->where('e.entry_date', '<=', $dates['to'])
            ->execute()
            ->current();

        if (!$row) {
            return 0.0;
        }

        $debit = (float) $row['debit'];
        $credit = (float) $row['credit'];
        $nature = (string) $row['nature'];

        return $nature === 'credit' ? ($credit - $debit) : ($debit - $credit);
    }

    protected function assert_required_tables()
    {
        $tables = [
            'core_fiscal_ledger_lines',
            'core_fiscal_account_mappings',
            'core_accounting_accounts',
            'core_accounting_journal_entries',
            'core_accounting_journal_lines',
        ];

        foreach ($tables as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Tabla requerida no encontrada: '.$table);
            }
        }
    }

    protected function normalize_rfc($rfc)
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
    }

    protected function period_dates($period)
    {
        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $period)) {
            $period = date('Y-m');
        }

        $from = $period.'-01';
        return [
            'from' => $from,
            'to' => date('Y-m-t', strtotime($from)),
        ];
    }
}
