<?php

/**
 * CONTROLADOR ADMIN_ACCOUNTING
 *
 * Base contable: catalogo de cuentas, polizas y reglas de contabilizacion.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Accounting extends Controller_Adminbase
{
    public function before()
    {
        parent::before();
        $this->require_access('accounting.access[view]');
    }

    public function action_index()
    {
        $this->template->title = 'Contabilidad';
        $this->template->content = View::forge('admin/accounting/index');
    }

    public function action_data()
    {
        try {
            $this->assert_schema_ready();
            return $this->json_response([
                'accounts' => $this->accounts(),
                'entries' => $this->entries(),
                'lines' => $this->lines((int) \Input::get('entry_id', 0)),
                'rules' => $this->rules(),
                'options' => $this->options(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando contabilidad: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar contabilidad.'], 500);
        }
    }

    public function action_save_account()
    {
        $this->require_access('accounting.access[edit]');
        $val = (array) \Input::json();

        try {
            if (trim((string) \Arr::get($val, 'code', '')) === '' || trim((string) \Arr::get($val, 'name', '')) === '') {
                return $this->json_response(['error' => 'Codigo y nombre son obligatorios.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'code' => trim((string) \Arr::get($val, 'code', '')),
                'name' => trim((string) \Arr::get($val, 'name', '')),
                'account_type' => $this->account_type(\Arr::get($val, 'account_type', 'asset')),
                'parent_id' => (int) \Arr::get($val, 'parent_id', 0),
                'level' => max(1, (int) \Arr::get($val, 'level', 1)),
                'nature' => $this->nature(\Arr::get($val, 'nature', 'debit')),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'sat_group_code' => trim((string) \Arr::get($val, 'sat_group_code', '')),
                'requires_party' => (int) (bool) \Arr::get($val, 'requires_party', false),
                'requires_cost_center' => (int) (bool) \Arr::get($val, 'requires_cost_center', false),
                'is_postable' => (int) (bool) \Arr::get($val, 'is_postable', true),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $account = Model_Core_Accounting_Account::find($id);
                if (!$account) {
                    return $this->json_response(['error' => 'Cuenta no encontrada.'], 404);
                }
                $old = $account->to_array();
                $account->set($data);
            } else {
                $old = [];
                $account = Model_Core_Accounting_Account::forge($data);
            }
            $account->save();

            Helper_Core_Audit::log([
                'module' => 'accounting',
                'action' => $id > 0 ? 'update_account' : 'create_account',
                'entity_type' => 'accounting_account',
                'entity_id' => (int) $account->id,
                'summary' => 'Cuenta contable '.$account->code.' '.$account->name,
                'old_values' => $old,
                'new_values' => $account->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'accounts' => $this->accounts(), 'options' => $this->options(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando cuenta contable: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la cuenta.'], 400);
        }
    }

    public function action_save_entry()
    {
        $this->require_access('accounting.access[edit]');
        $val = (array) \Input::json();

        try {
            $id = (int) \Arr::get($val, 'id', 0);
            $entry_date = trim((string) \Arr::get($val, 'entry_date', date('Y-m-d')));
            $data = [
                'entry_type' => $this->codeify(\Arr::get($val, 'entry_type', 'diario')),
                'entry_date' => $entry_date,
                'period' => substr($entry_date, 0, 7),
                'status' => $this->entry_status(\Arr::get($val, 'status', 'draft')),
                'source_module' => $this->codeify(\Arr::get($val, 'source_module', 'manual')),
                'source_entity_type' => trim((string) \Arr::get($val, 'source_entity_type', '')),
                'source_entity_id' => (int) \Arr::get($val, 'source_entity_id', 0),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $entry = Model_Core_Accounting_Journal_Entry::find($id);
                if (!$entry) {
                    return $this->json_response(['error' => 'Poliza no encontrada.'], 404);
                }
                $old = $entry->to_array();
                $entry->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_entry_folio();
                $data['created_by'] = $this->user_id;
                $entry = Model_Core_Accounting_Journal_Entry::forge($data);
            }
            $entry->save();
            $this->recalculate_entry((int) $entry->id);

            Helper_Core_Audit::log([
                'module' => 'accounting',
                'action' => $id > 0 ? 'update_entry' : 'create_entry',
                'entity_type' => 'accounting_entry',
                'entity_id' => (int) $entry->id,
                'summary' => 'Poliza '.$entry->folio,
                'old_values' => $old,
                'new_values' => $entry->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'entry_id' => (int) $entry->id, 'entries' => $this->entries(), 'lines' => $this->lines((int) $entry->id), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando poliza: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la poliza.'], 400);
        }
    }

    public function action_save_line()
    {
        $this->require_access('accounting.access[edit]');
        $val = (array) \Input::json();

        try {
            $entry_id = (int) \Arr::get($val, 'entry_id', 0);
            $account_id = (int) \Arr::get($val, 'account_id', 0);
            if ($entry_id < 1 || $account_id < 1) {
                return $this->json_response(['error' => 'Poliza y cuenta son obligatorias.'], 422);
            }
            $debit = max(0, (float) \Arr::get($val, 'debit', 0));
            $credit = max(0, (float) \Arr::get($val, 'credit', 0));
            if (($debit <= 0 && $credit <= 0) || ($debit > 0 && $credit > 0)) {
                return $this->json_response(['error' => 'Captura debe o haber, solo uno por linea.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'entry_id' => $entry_id,
                'account_id' => $account_id,
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'cost_center' => trim((string) \Arr::get($val, 'cost_center', '')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $line = Model_Core_Accounting_Journal_Line::find($id);
                if (!$line) {
                    return $this->json_response(['error' => 'Linea no encontrada.'], 404);
                }
                $line->set($data);
            } else {
                $line = Model_Core_Accounting_Journal_Line::forge($data);
            }
            $line->save();
            $this->recalculate_entry($entry_id);

            return $this->json_response(['status' => 'ok', 'entries' => $this->entries(), 'lines' => $this->lines($entry_id), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando linea contable: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la linea.'], 400);
        }
    }

    public function action_post_entry()
    {
        $this->require_access('accounting.access[edit]');
        $id = (int) \Arr::get((array) \Input::json(), 'id', 0);

        try {
            $entry = Model_Core_Accounting_Journal_Entry::find($id);
            if (!$entry) {
                return $this->json_response(['error' => 'Poliza no encontrada.'], 404);
            }
            $this->recalculate_entry($id);
            $entry = Model_Core_Accounting_Journal_Entry::find($id);
            if (round((float) $entry->total_debit, 2) !== round((float) $entry->total_credit, 2) || (float) $entry->total_debit <= 0) {
                return $this->json_response(['error' => 'La poliza no esta cuadrada. Debe y haber deben ser iguales.'], 422);
            }
            $entry->status = 'posted';
            $entry->posted_by = $this->user_id;
            $entry->posted_at = time();
            $entry->save();

            return $this->json_response(['status' => 'ok', 'entries' => $this->entries(), 'lines' => $this->lines($id), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error contabilizando poliza: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo contabilizar la poliza.'], 400);
        }
    }

    public function action_save_rule()
    {
        $this->require_access('accounting.access[edit]');
        $val = (array) \Input::json();

        try {
            if (trim((string) \Arr::get($val, 'rule_code', '')) === '' || trim((string) \Arr::get($val, 'name', '')) === '') {
                return $this->json_response(['error' => 'Codigo y nombre son obligatorios.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'rule_code' => $this->codeify(\Arr::get($val, 'rule_code', '')),
                'name' => trim((string) \Arr::get($val, 'name', '')),
                'source_module' => $this->codeify(\Arr::get($val, 'source_module', '')),
                'source_event' => $this->codeify(\Arr::get($val, 'source_event', '')),
                'debit_account_id' => (int) \Arr::get($val, 'debit_account_id', 0),
                'credit_account_id' => (int) \Arr::get($val, 'credit_account_id', 0),
                'amount_source' => $this->codeify(\Arr::get($val, 'amount_source', 'total')),
                'requires_party' => (int) (bool) \Arr::get($val, 'requires_party', false),
                'auto_post' => (int) (bool) \Arr::get($val, 'auto_post', false),
                'priority' => (int) \Arr::get($val, 'priority', 100),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $rule = Model_Core_Accounting_Posting_Rule::find($id);
                if (!$rule) {
                    return $this->json_response(['error' => 'Regla no encontrada.'], 404);
                }
                $rule->set($data);
            } else {
                $rule = Model_Core_Accounting_Posting_Rule::forge($data);
            }
            $rule->save();

            return $this->json_response(['status' => 'ok', 'rules' => $this->rules(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando regla contable: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la regla.'], 400);
        }
    }

    protected function accounts()
    {
        return \DB::select('*')->from('core_accounting_accounts')->order_by('code', 'asc')->execute()->as_array();
    }

    protected function entries()
    {
        return \DB::select('*')->from('core_accounting_journal_entries')->where('active', '=', 1)->order_by('entry_date', 'desc')->order_by('id', 'desc')->limit(200)->execute()->as_array();
    }

    protected function lines($entry_id)
    {
        if ($entry_id < 1) {
            return [];
        }
        return \DB::select(['l.id', 'id'], ['l.entry_id', 'entry_id'], ['l.account_id', 'account_id'], ['a.code', 'account_code'], ['a.name', 'account_name'], ['l.party_id', 'party_id'], ['p.name', 'party_name'], ['l.description', 'description'], ['l.debit', 'debit'], ['l.credit', 'credit'], ['l.currency_code', 'currency_code'], ['l.sort_order', 'sort_order'])
            ->from(['core_accounting_journal_lines', 'l'])
            ->join(['core_accounting_accounts', 'a'], 'left')->on('l.account_id', '=', 'a.id')
            ->join(['core_parties', 'p'], 'left')->on('l.party_id', '=', 'p.id')
            ->where('l.entry_id', '=', $entry_id)
            ->where('l.active', '=', 1)
            ->order_by('l.sort_order', 'asc')
            ->order_by('l.id', 'asc')
            ->execute()
            ->as_array();
    }

    protected function rules()
    {
        return \DB::select(['r.id', 'id'], ['r.rule_code', 'rule_code'], ['r.name', 'name'], ['r.source_module', 'source_module'], ['r.source_event', 'source_event'], ['r.debit_account_id', 'debit_account_id'], ['d.code', 'debit_code'], ['d.name', 'debit_name'], ['r.credit_account_id', 'credit_account_id'], ['c.code', 'credit_code'], ['c.name', 'credit_name'], ['r.amount_source', 'amount_source'], ['r.requires_party', 'requires_party'], ['r.auto_post', 'auto_post'], ['r.priority', 'priority'], ['r.notes', 'notes'], ['r.active', 'active'])
            ->from(['core_accounting_posting_rules', 'r'])
            ->join(['core_accounting_accounts', 'd'], 'left')->on('r.debit_account_id', '=', 'd.id')
            ->join(['core_accounting_accounts', 'c'], 'left')->on('r.credit_account_id', '=', 'c.id')
            ->order_by('r.priority', 'asc')
            ->execute()
            ->as_array();
    }

    protected function options()
    {
        return [
            'accounts' => $this->account_options(),
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
        ];
    }

    protected function stats()
    {
        $entries = \DB::select()->from('core_accounting_journal_entries')->where('active', '=', 1)->execute();
        return [
            'accounts' => (int) \DB::select()->from('core_accounting_accounts')->where('active', '=', 1)->execute()->count(),
            'entries' => (int) $entries->count(),
            'draft' => (int) \DB::select()->from('core_accounting_journal_entries')->where('status', '=', 'draft')->where('active', '=', 1)->execute()->count(),
            'posted' => (int) \DB::select()->from('core_accounting_journal_entries')->where('status', '=', 'posted')->where('active', '=', 1)->execute()->count(),
            'rules' => (int) \DB::select()->from('core_accounting_posting_rules')->where('active', '=', 1)->execute()->count(),
        ];
    }

    protected function account_options()
    {
        $options = [];
        foreach (\DB::select('id', 'code', 'name')->from('core_accounting_accounts')->where('active', '=', 1)->where('is_postable', '=', 1)->order_by('code', 'asc')->execute() as $row) {
            $options[] = ['value' => (string) $row['id'], 'label' => $row['code'].' - '.$row['name']];
        }
        return $options;
    }

    protected function select_options($table, $value_field, $label_field)
    {
        if (!\DBUtil::table_exists($table)) {
            return [];
        }
        $options = [];
        foreach (\DB::select($value_field, $label_field)->from($table)->where('active', '=', 1)->order_by($label_field, 'asc')->execute() as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $options;
    }

    protected function recalculate_entry($entry_id)
    {
        $row = \DB::select(\DB::expr('COALESCE(SUM(debit),0) as total_debit'), \DB::expr('COALESCE(SUM(credit),0) as total_credit'))
            ->from('core_accounting_journal_lines')
            ->where('entry_id', '=', (int) $entry_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        \DB::update('core_accounting_journal_entries')->set([
            'total_debit' => round((float) $row['total_debit'], 2),
            'total_credit' => round((float) $row['total_credit'], 2),
            'updated_at' => time(),
        ])->where('id', '=', (int) $entry_id)->execute();
    }

    protected function next_entry_folio()
    {
        return 'POL-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_accounting_journal_entries') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_accounting_accounts', 'core_accounting_journal_entries', 'core_accounting_journal_lines', 'core_accounting_posting_rules'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de contabilidad.');
            }
        }
    }

    protected function account_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['asset', 'liability', 'equity', 'income', 'expense', 'cost'], true) ? $value : 'asset';
    }

    protected function nature($value)
    {
        $value = $this->codeify($value);
        return $value === 'credit' ? 'credit' : 'debit';
    }

    protected function entry_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['draft', 'posted', 'cancelled'], true) ? $value : 'draft';
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
