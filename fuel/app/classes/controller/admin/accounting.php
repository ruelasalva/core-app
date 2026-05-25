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
                'fiscal_years' => $this->fiscal_years(),
                'periods' => $this->periods(),
                'cost_centers' => $this->cost_centers(),
                'entries' => $this->entries(),
                'lines' => $this->lines((int) \Input::get('entry_id', 0)),
                'rules' => $this->rules(),
                'trial_balance' => $this->trial_balance(),
                'general_ledger' => $this->general_ledger((int) \Input::get('account_id', 0)),
                'income_statement' => $this->income_statement(),
                'balance_sheet' => $this->balance_sheet(),
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
                'requires_party' => $this->bool_value(\Arr::get($val, 'requires_party', false)),
                'requires_cost_center' => $this->bool_value(\Arr::get($val, 'requires_cost_center', false)),
                'is_postable' => $this->bool_value(\Arr::get($val, 'is_postable', true)),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
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
            $period = $this->period_for_date($entry_date);
            if (!$period || (int) $period['locked'] === 1 || (string) $period['status'] === 'closed' || (int) $period['allow_manual_entries'] !== 1) {
                return $this->json_response(['error' => 'El periodo contable no esta abierto para polizas manuales.'], 422);
            }
            $data = [
                'entry_type' => $this->codeify(\Arr::get($val, 'entry_type', 'diario')),
                'entry_date' => $entry_date,
                'period' => (string) $period['period_key'],
                'period_id' => (int) $period['id'],
                'status' => $this->entry_status(\Arr::get($val, 'status', 'draft')),
                'source_module' => $this->codeify(\Arr::get($val, 'source_module', 'manual')),
                'source_entity_type' => trim((string) \Arr::get($val, 'source_entity_type', '')),
                'source_entity_id' => (int) \Arr::get($val, 'source_entity_id', 0),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
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
                'cost_center_id' => (int) \Arr::get($val, 'cost_center_id', 0),
                'cost_center' => trim((string) \Arr::get($val, 'cost_center', '')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
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
            $period = $this->period_for_date((string) $entry->entry_date);
            if (!$period || (int) $period['locked'] === 1 || (string) $period['status'] === 'closed') {
                return $this->json_response(['error' => 'El periodo contable esta cerrado o bloqueado.'], 422);
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
                'requires_party' => $this->bool_value(\Arr::get($val, 'requires_party', false)),
                'auto_post' => $this->bool_value(\Arr::get($val, 'auto_post', false)),
                'priority' => (int) \Arr::get($val, 'priority', 100),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
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

    public function action_save_period()
    {
        $this->require_access('accounting.access[edit]');
        $val = (array) \Input::json();

        try {
            $id = (int) \Arr::get($val, 'id', 0);
            $period_key = trim((string) \Arr::get($val, 'period_key', ''));
            if ($period_key === '' || trim((string) \Arr::get($val, 'name', '')) === '') {
                return $this->json_response(['error' => 'Periodo y nombre son obligatorios.'], 422);
            }

            $data = [
                'fiscal_year_id' => (int) \Arr::get($val, 'fiscal_year_id', 0),
                'period_key' => $period_key,
                'name' => trim((string) \Arr::get($val, 'name', '')),
                'start_date' => trim((string) \Arr::get($val, 'start_date', '')),
                'end_date' => trim((string) \Arr::get($val, 'end_date', '')),
                'status' => $this->period_status(\Arr::get($val, 'status', 'open')),
                'allow_manual_entries' => $this->bool_value(\Arr::get($val, 'allow_manual_entries', true)),
                'allow_operational_posting' => $this->bool_value(\Arr::get($val, 'allow_operational_posting', true)),
                'locked' => $this->bool_value(\Arr::get($val, 'locked', false)),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];

            if ($id > 0) {
                $period = Model_Core_Accounting_Period::find($id);
                if (!$period) {
                    return $this->json_response(['error' => 'Periodo no encontrado.'], 404);
                }
                $old = $period->to_array();
                $period->set($data);
            } else {
                $old = [];
                $period = Model_Core_Accounting_Period::forge($data);
            }
            if ($data['status'] === 'closed' || (int) $data['locked'] === 1) {
                $period->closed_by = $this->user_id;
                $period->closed_at = time();
            }
            $period->save();

            Helper_Core_Audit::log([
                'module' => 'accounting',
                'action' => $id > 0 ? 'update_period' : 'create_period',
                'entity_type' => 'accounting_period',
                'entity_id' => (int) $period->id,
                'summary' => 'Periodo contable '.$period->period_key,
                'old_values' => $old,
                'new_values' => $period->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'periods' => $this->periods(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando periodo contable: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el periodo.'], 400);
        }
    }

    public function action_save_cost_center()
    {
        $this->require_access('accounting.access[edit]');
        $val = (array) \Input::json();

        try {
            if (trim((string) \Arr::get($val, 'code', '')) === '' || trim((string) \Arr::get($val, 'name', '')) === '') {
                return $this->json_response(['error' => 'Codigo y nombre son obligatorios.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'code' => $this->codeify(\Arr::get($val, 'code', '')),
                'name' => trim((string) \Arr::get($val, 'name', '')),
                'center_type' => $this->cost_center_type(\Arr::get($val, 'center_type', 'department')),
                'parent_id' => (int) \Arr::get($val, 'parent_id', 0),
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'branch_id' => (int) \Arr::get($val, 'branch_id', 0),
                'manager_user_id' => (int) \Arr::get($val, 'manager_user_id', 0),
                'budget_amount' => round((float) \Arr::get($val, 'budget_amount', 0), 2),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];

            if ($id > 0) {
                $center = Model_Core_Accounting_Cost_Center::find($id);
                if (!$center) {
                    return $this->json_response(['error' => 'Centro de costo no encontrado.'], 404);
                }
                $old = $center->to_array();
                $center->set($data);
            } else {
                $old = [];
                $center = Model_Core_Accounting_Cost_Center::forge($data);
            }
            $center->save();

            Helper_Core_Audit::log([
                'module' => 'accounting',
                'action' => $id > 0 ? 'update_cost_center' : 'create_cost_center',
                'entity_type' => 'accounting_cost_center',
                'entity_id' => (int) $center->id,
                'summary' => 'Centro de costo '.$center->code.' '.$center->name,
                'old_values' => $old,
                'new_values' => $center->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'cost_centers' => $this->cost_centers(), 'options' => $this->options(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando centro de costo: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el centro de costo.'], 400);
        }
    }

    protected function accounts()
    {
        return \DB::select('*')->from('core_accounting_accounts')->order_by('code', 'asc')->execute()->as_array();
    }

    protected function fiscal_years()
    {
        return \DB::select('*')->from('core_accounting_fiscal_years')->where('active', '=', 1)->order_by('code', 'desc')->execute()->as_array();
    }

    protected function periods()
    {
        return \DB::select(['p.id', 'id'], ['p.fiscal_year_id', 'fiscal_year_id'], ['y.code', 'fiscal_year_code'], ['p.period_key', 'period_key'], ['p.name', 'name'], ['p.start_date', 'start_date'], ['p.end_date', 'end_date'], ['p.status', 'status'], ['p.allow_manual_entries', 'allow_manual_entries'], ['p.allow_operational_posting', 'allow_operational_posting'], ['p.locked', 'locked'], ['p.active', 'active'])
            ->from(['core_accounting_periods', 'p'])
            ->join(['core_accounting_fiscal_years', 'y'], 'left')->on('p.fiscal_year_id', '=', 'y.id')
            ->where('p.active', '=', 1)
            ->order_by('p.period_key', 'desc')
            ->execute()
            ->as_array();
    }

    protected function cost_centers()
    {
        return \DB::select(['cc.id', 'id'], ['cc.code', 'code'], ['cc.name', 'name'], ['cc.center_type', 'center_type'], ['cc.parent_id', 'parent_id'], ['cc.department_id', 'department_id'], ['d.name', 'department_name'], ['cc.branch_id', 'branch_id'], ['b.name', 'branch_name'], ['cc.manager_user_id', 'manager_user_id'], ['cc.budget_amount', 'budget_amount'], ['cc.currency_code', 'currency_code'], ['cc.notes', 'notes'], ['cc.active', 'active'])
            ->from(['core_accounting_cost_centers', 'cc'])
            ->join(['core_departments', 'd'], 'left')->on('cc.department_id', '=', 'd.id')
            ->join(['core_branches', 'b'], 'left')->on('cc.branch_id', '=', 'b.id')
            ->where('cc.active', '=', 1)
            ->order_by('cc.code', 'asc')
            ->execute()
            ->as_array();
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
        return \DB::select(['l.id', 'id'], ['l.entry_id', 'entry_id'], ['l.account_id', 'account_id'], ['a.code', 'account_code'], ['a.name', 'account_name'], ['l.party_id', 'party_id'], ['p.name', 'party_name'], ['l.department_id', 'department_id'], ['l.cost_center_id', 'cost_center_id'], ['cc.code', 'cost_center_code'], ['cc.name', 'cost_center_name'], ['l.description', 'description'], ['l.debit', 'debit'], ['l.credit', 'credit'], ['l.currency_code', 'currency_code'], ['l.sort_order', 'sort_order'])
            ->from(['core_accounting_journal_lines', 'l'])
            ->join(['core_accounting_accounts', 'a'], 'left')->on('l.account_id', '=', 'a.id')
            ->join(['core_parties', 'p'], 'left')->on('l.party_id', '=', 'p.id')
            ->join(['core_accounting_cost_centers', 'cc'], 'left')->on('l.cost_center_id', '=', 'cc.id')
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

    protected function trial_balance()
    {
        $items = [];
        $rows = \DB::select(
                ['a.id', 'account_id'], ['a.code', 'account_code'], ['a.name', 'account_name'], ['a.account_type', 'account_type'], ['a.nature', 'nature'],
                [\DB::expr('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.debit ELSE 0 END),0)'), 'debit'],
                [\DB::expr('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.credit ELSE 0 END),0)'), 'credit']
            )
            ->from(['core_accounting_accounts', 'a'])
            ->join(['core_accounting_journal_lines', 'l'], 'left')->on('l.account_id', '=', 'a.id')->on('l.active', '=', \DB::expr('1'))
            ->join(['core_accounting_journal_entries', 'e'], 'left')->on('l.entry_id', '=', 'e.id')->on('e.active', '=', \DB::expr('1'))->on('e.status', '=', \DB::expr("'posted'"))
            ->where('a.active', '=', 1)
            ->group_by('a.id')
            ->order_by('a.code', 'asc')
            ->execute();

        foreach ($rows as $row) {
            $debit = (float) $row['debit'];
            $credit = (float) $row['credit'];
            $balance = $debit - $credit;
            $row['debit'] = round($debit, 2);
            $row['credit'] = round($credit, 2);
            $row['debit_balance'] = $balance > 0 ? round($balance, 2) : 0;
            $row['credit_balance'] = $balance < 0 ? round(abs($balance), 2) : 0;
            $items[] = $row;
        }
        return $items;
    }

    protected function general_ledger($account_id)
    {
        if ($account_id < 1) {
            $row = \DB::select('id')->from('core_accounting_accounts')->where('active', '=', 1)->where('is_postable', '=', 1)->order_by('code', 'asc')->execute()->current();
            $account_id = $row ? (int) $row['id'] : 0;
        }
        if ($account_id < 1) {
            return [];
        }

        $balance = 0;
        $items = [];
        $rows = \DB::select(['e.id', 'entry_id'], ['e.folio', 'folio'], ['e.entry_date', 'entry_date'], ['e.entry_type', 'entry_type'], ['e.source_module', 'source_module'], ['l.description', 'description'], ['l.party_id', 'party_id'], ['p.name', 'party_name'], ['l.debit', 'debit'], ['l.credit', 'credit'])
            ->from(['core_accounting_journal_lines', 'l'])
            ->join(['core_accounting_journal_entries', 'e'], 'inner')->on('l.entry_id', '=', 'e.id')
            ->join(['core_parties', 'p'], 'left')->on('l.party_id', '=', 'p.id')
            ->where('l.account_id', '=', $account_id)
            ->where('l.active', '=', 1)
            ->where('e.active', '=', 1)
            ->where('e.status', '=', 'posted')
            ->order_by('e.entry_date', 'asc')
            ->order_by('e.id', 'asc')
            ->order_by('l.id', 'asc')
            ->limit(500)
            ->execute();

        foreach ($rows as $row) {
            $balance += (float) $row['debit'] - (float) $row['credit'];
            $row['running_balance'] = round($balance, 2);
            $items[] = $row;
        }
        return $items;
    }

    protected function income_statement()
    {
        $rows = $this->account_type_totals(['income', 'expense', 'cost']);
        $income = (float) \Arr::get($rows, 'income', 0);
        $expense = (float) \Arr::get($rows, 'expense', 0);
        $cost = (float) \Arr::get($rows, 'cost', 0);
        return [
            'income' => round($income, 2),
            'cost' => round($cost, 2),
            'gross_profit' => round($income - $cost, 2),
            'expense' => round($expense, 2),
            'net_income' => round($income - $cost - $expense, 2),
        ];
    }

    protected function balance_sheet()
    {
        $rows = $this->account_type_totals(['asset', 'liability', 'equity']);
        $asset = (float) \Arr::get($rows, 'asset', 0);
        $liability = (float) \Arr::get($rows, 'liability', 0);
        $equity = (float) \Arr::get($rows, 'equity', 0);
        return [
            'asset' => round($asset, 2),
            'liability' => round($liability, 2),
            'equity' => round($equity, 2),
            'liability_equity' => round($liability + $equity, 2),
            'difference' => round($asset - ($liability + $equity), 2),
        ];
    }

    protected function account_type_totals(array $types)
    {
        $totals = [];
        $rows = \DB::select(['a.account_type', 'account_type'], ['a.nature', 'nature'], [\DB::expr('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.debit ELSE 0 END),0)'), 'debit'], [\DB::expr('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.credit ELSE 0 END),0)'), 'credit'])
            ->from(['core_accounting_accounts', 'a'])
            ->join(['core_accounting_journal_lines', 'l'], 'left')->on('l.account_id', '=', 'a.id')->on('l.active', '=', \DB::expr('1'))
            ->join(['core_accounting_journal_entries', 'e'], 'left')->on('l.entry_id', '=', 'e.id')->on('e.active', '=', \DB::expr('1'))->on('e.status', '=', \DB::expr("'posted'"))
            ->where('a.active', '=', 1)
            ->where('a.account_type', 'in', $types)
            ->group_by('a.account_type')
            ->group_by('a.nature')
            ->execute();

        foreach ($rows as $row) {
            $debit = (float) $row['debit'];
            $credit = (float) $row['credit'];
            $amount = (string) $row['nature'] === 'credit' ? $credit - $debit : $debit - $credit;
            $totals[$row['account_type']] = (float) \Arr::get($totals, $row['account_type'], 0) + $amount;
        }
        return $totals;
    }

    protected function options()
    {
        return [
            'accounts' => $this->account_options(),
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'departments' => $this->select_options('core_departments', 'id', 'name'),
            'branches' => $this->select_options('core_branches', 'id', 'name'),
            'cost_centers' => $this->cost_center_options(),
            'fiscal_years' => $this->fiscal_year_options(),
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
            'periods_open' => (int) \DB::select()->from('core_accounting_periods')->where('status', '=', 'open')->where('active', '=', 1)->execute()->count(),
            'cost_centers' => (int) \DB::select()->from('core_accounting_cost_centers')->where('active', '=', 1)->execute()->count(),
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

    protected function cost_center_options()
    {
        $options = [];
        foreach (\DB::select('id', 'code', 'name')->from('core_accounting_cost_centers')->where('active', '=', 1)->order_by('code', 'asc')->execute() as $row) {
            $options[] = ['value' => (string) $row['id'], 'label' => $row['code'].' - '.$row['name']];
        }
        return $options;
    }

    protected function fiscal_year_options()
    {
        $options = [];
        foreach (\DB::select('id', 'code', 'name')->from('core_accounting_fiscal_years')->where('active', '=', 1)->order_by('code', 'desc')->execute() as $row) {
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
        foreach (['core_accounting_accounts', 'core_accounting_journal_entries', 'core_accounting_journal_lines', 'core_accounting_posting_rules', 'core_accounting_fiscal_years', 'core_accounting_periods', 'core_accounting_cost_centers'] as $table) {
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

    protected function period_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['open', 'soft_closed', 'closed'], true) ? $value : 'open';
    }

    protected function cost_center_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['department', 'branch', 'project', 'sales_channel', 'other'], true) ? $value : 'department';
    }

    protected function period_for_date($date)
    {
        $period_key = substr((string) $date, 0, 7);
        $period = \DB::select('*')->from('core_accounting_periods')->where('period_key', '=', $period_key)->where('active', '=', 1)->execute()->current();
        if ($period) {
            return $period;
        }
        return $this->ensure_period($period_key);
    }

    protected function ensure_period($period_key)
    {
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $period_key)) {
            return null;
        }
        $year = substr($period_key, 0, 4);
        $year_row = \DB::select('id')->from('core_accounting_fiscal_years')->where('code', '=', $year)->execute()->current();
        if (!$year_row) {
            list($year_id,) = \DB::insert('core_accounting_fiscal_years')->set([
                'code' => $year,
                'name' => 'Ejercicio '.$year,
                'start_date' => $year.'-01-01',
                'end_date' => $year.'-12-31',
                'status' => 'open',
                'locked' => 0,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
        } else {
            $year_id = (int) $year_row['id'];
        }

        $start = $period_key.'-01';
        $end = date('Y-m-t', strtotime($start));
        list($period_id,) = \DB::insert('core_accounting_periods')->set([
            'fiscal_year_id' => (int) $year_id,
            'period_key' => $period_key,
            'name' => 'Periodo '.$period_key,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'open',
            'allow_manual_entries' => 1,
            'allow_operational_posting' => 1,
            'locked' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        return \DB::select('*')->from('core_accounting_periods')->where('id', '=', (int) $period_id)->execute()->current();
    }

    protected function bool_value($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'on', 'yes', 'si'], true) ? 1 : 0;
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
