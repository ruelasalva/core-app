<?php

/**
 * SERVICE CORE_FISCAL_ACCOUNTINGDRAFTGENERATOR
 *
 * Genera polizas contables preliminares en borrador desde el libro fiscal.
 *
 * @package  app
 */
class Service_Core_Fiscal_AccountingDraftGenerator
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
     * GENERATE
     *
     * CREA UNA POLIZA EN BORRADOR DESDE TOTALES FISCALES.
     *
     * @access  public
     * @return  Array
     */
    public function generate($rfc, $period, $user_id = 1)
    {
        $rfc = $this->normalize_rfc($rfc);
        $period = $this->normalize_period($period);
        $now = time();

        $this->validate_schema();
        $fiscal_period = $this->open_fiscal_period($rfc, $period);
        $mappings = $this->validated_mappings();
        $amounts = $this->fiscal_amounts($rfc, $period);
        $warnings = [];

        if (!$this->has_postable_amount($amounts)) {
            throw new \RuntimeException('No hay importes fiscales para generar poliza preliminar en '.$period.'.');
        }

        $existing = $this->existing_entry((int) $fiscal_period['id']);
        if ($existing) {
            $warnings[] = 'Ya existe una poliza fiscal activa para este periodo; no se creo duplicado.';
            return $this->existing_result($existing, $warnings);
        }

        \DB::start_transaction();
        try {
            $entry_id = $this->create_entry($rfc, $period, (int) $fiscal_period['id'], $now, (int) $user_id);
            $lines_created = 0;
            $sort_order = 10;

            foreach ($this->definitions as $definition) {
                $amount = round((float) \Arr::get($amounts, $definition['key'], 0), 2);
                if (abs($amount) <= 0.000001) {
                    $warnings[] = 'Sin importe fiscal para '.$definition['concept'].'.';
                    continue;
                }

                $mapping = $mappings[$definition['key']];
                $line = $this->line_data($entry_id, $definition, $mapping, $amount, $sort_order, $now);
                \DB::insert('core_accounting_journal_lines')->set($line)->execute();
                $lines_created++;
                $sort_order += 10;
            }

            $totals = $this->recalculate_entry($entry_id, $now);
            $difference = round((float) $totals['total_debit'] - (float) $totals['total_credit'], 2);
            if (abs($difference) > 0.01) {
                $balance_account = $this->preliminary_balance_account();
                $line = $this->balance_line_data($entry_id, $balance_account, $difference, $sort_order, $now);
                \DB::insert('core_accounting_journal_lines')->set($line)->execute();
                $lines_created++;
                $warnings[] = 'Se agrego una linea de cuadre fiscal preliminar. No representa pago definitivo de impuestos.';

                $totals = $this->recalculate_entry($entry_id, $now);
                $difference = round((float) $totals['total_debit'] - (float) $totals['total_credit'], 2);
                if (abs($difference) > 0.01) {
                    $warnings[] = 'La poliza quedo descuadrada; debe revisarse antes de contabilizar.';
                }
            }

            $entry = $this->entry_by_id($entry_id);
            \DB::commit_transaction();

            \Log::info('Poliza fiscal borrador generada RFC='.$rfc.' periodo='.$period.' entry_id='.$entry_id.' lineas='.$lines_created.' diferencia='.$difference);

            return [
                'entry_id' => $entry_id,
                'folio' => (string) $entry['folio'],
                'lines_created' => $lines_created,
                'total_debit' => (float) $totals['total_debit'],
                'total_credit' => (float) $totals['total_credit'],
                'difference' => $difference,
                'warnings' => $warnings,
                'created' => true,
            ];
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Log::error('Error generando poliza fiscal borrador RFC='.$rfc.' periodo='.$period.' - '.$e->getMessage());
            throw $e;
        }
    }

    protected function line_data($entry_id, array $definition, array $mapping, $amount, $sort_order, $now)
    {
        $amount = abs((float) $amount);
        $nature = (string) \Arr::get($mapping, 'account_nature', 'debit');

        return [
            'entry_id' => (int) $entry_id,
            'account_id' => (int) $mapping['account_id'],
            'party_id' => 0,
            'department_id' => 0,
            'cost_center_id' => 0,
            'cost_center' => '',
            'description' => 'Movimiento fiscal preliminar: '.$definition['concept'],
            'debit' => $nature === 'credit' ? 0 : round($amount, 2),
            'credit' => $nature === 'credit' ? round($amount, 2) : 0,
            'currency_code' => 'MXN',
            'exchange_rate' => 1,
            'sort_order' => (int) $sort_order,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function balance_line_data($entry_id, array $account, $difference, $sort_order, $now)
    {
        $amount = abs((float) $difference);

        return [
            'entry_id' => (int) $entry_id,
            'account_id' => (int) $account['id'],
            'party_id' => 0,
            'department_id' => 0,
            'cost_center_id' => 0,
            'cost_center' => '',
            'description' => 'Ajuste fiscal preliminar para cuadrar poliza',
            'debit' => $difference < 0 ? round($amount, 2) : 0,
            'credit' => $difference > 0 ? round($amount, 2) : 0,
            'currency_code' => 'MXN',
            'exchange_rate' => 1,
            'sort_order' => (int) $sort_order,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function create_entry($rfc, $period, $fiscal_period_id, $now, $user_id)
    {
        $accounting_period_id = $this->accounting_period_id($period);
        $folio = $this->next_folio($period);

        $insert = \DB::insert('core_accounting_journal_entries')->set([
            'folio' => $folio,
            'entry_type' => 'fiscal',
            'entry_date' => $period.'-'.date('t', strtotime($period.'-01')),
            'period' => $period,
            'period_id' => $accounting_period_id,
            'status' => 'draft',
            'source_module' => 'fiscal',
            'source_entity_type' => 'fiscal_period',
            'source_entity_id' => (int) $fiscal_period_id,
            'currency_code' => 'MXN',
            'exchange_rate' => 1,
            'total_debit' => 0,
            'total_credit' => 0,
            'description' => 'Poliza fiscal preliminar '.$period.' '.$rfc,
            'created_by' => $user_id,
            'posted_by' => 0,
            'posted_at' => 0,
            'locked' => 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        return (int) $insert[0];
    }

    protected function fiscal_amounts($rfc, $period)
    {
        $amounts = [];
        foreach ($this->definitions as $definition) {
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
            $amounts[$definition['key']] = $row ? (float) $row['amount'] : 0.0;
        }

        return $amounts;
    }

    protected function validated_mappings()
    {
        $mappings = [];
        $missing = [];

        foreach ($this->definitions as $definition) {
            $mapping = $this->mapping_for($definition);
            if (!$mapping) {
                $missing[] = $definition['concept'];
                continue;
            }
            $mappings[$definition['key']] = $mapping;
        }

        if (!empty($missing)) {
            throw new \RuntimeException('Faltan mapeos fiscal-contables: '.implode(', ', $missing).'.');
        }

        return $mappings;
    }

    protected function has_postable_amount(array $amounts)
    {
        foreach ($amounts as $amount) {
            if (abs((float) $amount) > 0.000001) {
                return true;
            }
        }

        return false;
    }

    protected function mapping_for(array $definition)
    {
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
            ->where('m.direction', '=', $definition['direction'])
            ->where('m.active', '=', 1)
            ->where('a.active', '=', 1)
            ->where('a.is_postable', '=', 1)
            ->limit(1)
            ->execute()
            ->current();
    }

    protected function preliminary_balance_account()
    {
        if (!\DBUtil::table_exists('core_settings')) {
            throw new \RuntimeException('Falta configurar la cuenta de cuadre fiscal preliminar. Ejecuta php oil refine repairfiscalaccounts.');
        }

        $row = \DB::select('value')
            ->from('core_settings')
            ->where('setting_group', '=', 'fiscal')
            ->where('setting_key', '=', 'preliminary_balance_account_id')
            ->execute()
            ->current();

        $account_id = $row ? (int) $row['value'] : 0;
        if ($account_id < 1) {
            throw new \RuntimeException('No hay cuenta de cuadre fiscal preliminar configurada. Ejecuta php oil refine repairfiscalaccounts.');
        }

        $account = \DB::select('id', 'code', 'name', 'nature', 'is_postable', 'active')
            ->from('core_accounting_accounts')
            ->where('id', '=', $account_id)
            ->execute()
            ->current();

        if (!$account || (int) $account['active'] !== 1 || (int) $account['is_postable'] !== 1) {
            throw new \RuntimeException('La cuenta de cuadre fiscal preliminar no existe o no es afectable.');
        }

        return $account;
    }

    protected function open_fiscal_period($rfc, $period)
    {
        $row = \DB::select()
            ->from('core_fiscal_periods')
            ->where('taxpayer_rfc', '=', $rfc)
            ->where('period_key', '=', $period)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$row) {
            throw new \RuntimeException('No existe periodo fiscal para '.$rfc.' '.$period.'. Construye el libro fiscal primero.');
        }

        if ((string) $row['status'] !== 'open') {
            throw new \RuntimeException('El periodo fiscal '.$period.' no esta abierto. Estado actual: '.$row['status'].'.');
        }

        return $row;
    }

    protected function existing_entry($fiscal_period_id)
    {
        return \DB::select()
            ->from('core_accounting_journal_entries')
            ->where('source_module', '=', 'fiscal')
            ->where('source_entity_type', '=', 'fiscal_period')
            ->where('source_entity_id', '=', (int) $fiscal_period_id)
            ->where('status', 'in', ['draft', 'posted'])
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->execute()
            ->current();
    }

    protected function existing_result(array $entry, array $warnings)
    {
        $totals = $this->line_totals((int) $entry['id']);

        return [
            'entry_id' => (int) $entry['id'],
            'folio' => (string) $entry['folio'],
            'lines_created' => 0,
            'total_debit' => (float) $totals['total_debit'],
            'total_credit' => (float) $totals['total_credit'],
            'difference' => round((float) $totals['total_debit'] - (float) $totals['total_credit'], 2),
            'warnings' => $warnings,
            'created' => false,
        ];
    }

    protected function recalculate_entry($entry_id, $now)
    {
        $totals = $this->line_totals($entry_id);

        \DB::update('core_accounting_journal_entries')->set([
            'total_debit' => $totals['total_debit'],
            'total_credit' => $totals['total_credit'],
            'updated_at' => $now,
        ])->where('id', '=', (int) $entry_id)->execute();

        return $totals;
    }

    protected function line_totals($entry_id)
    {
        $row = \DB::select(
                [\DB::expr('COALESCE(SUM(debit), 0)'), 'total_debit'],
                [\DB::expr('COALESCE(SUM(credit), 0)'), 'total_credit'],
                [\DB::expr('COUNT(*)'), 'line_count']
            )
            ->from('core_accounting_journal_lines')
            ->where('entry_id', '=', (int) $entry_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return [
            'total_debit' => round((float) $row['total_debit'], 2),
            'total_credit' => round((float) $row['total_credit'], 2),
            'line_count' => (int) $row['line_count'],
        ];
    }

    protected function entry_by_id($entry_id)
    {
        return \DB::select()
            ->from('core_accounting_journal_entries')
            ->where('id', '=', (int) $entry_id)
            ->execute()
            ->current();
    }

    protected function accounting_period_id($period)
    {
        if (!\DBUtil::table_exists('core_accounting_periods')) {
            return 0;
        }

        $row = \DB::select('id')
            ->from('core_accounting_periods')
            ->where('period_key', '=', $period)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }

    protected function next_folio($period)
    {
        $base = 'FIS-'.str_replace('-', '', $period).'-';
        $count = (int) \DB::select([\DB::expr('COUNT(*)'), 'total'])
            ->from('core_accounting_journal_entries')
            ->where('folio', 'like', $base.'%')
            ->execute()
            ->get('total', 0);

        return $base.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function validate_schema()
    {
        $tables = [
            'core_fiscal_ledger_lines',
            'core_fiscal_account_mappings',
            'core_accounting_accounts',
            'core_accounting_journal_entries',
            'core_accounting_journal_lines',
            'core_fiscal_periods',
            'core_settings',
        ];

        foreach ($tables as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Tabla requerida no existe: '.$table.'. Ejecuta migraciones antes de generar polizas fiscales.');
            }
        }
    }

    protected function normalize_rfc($rfc)
    {
        $rfc = strtoupper(preg_replace('/\s+/', '', trim((string) $rfc)));
        if ($rfc === '' || !preg_match('/^[A-Z&\x{00D1}]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
            throw new \InvalidArgumentException('RFC invalido.');
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
}
