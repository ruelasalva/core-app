<?php

/**
 * CONTROLADOR ADMIN_BUDGETS
 *
 * Administra presupuestos, partidas y control presupuestal.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Budgets extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE PRESUPUESTOS
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('budgets.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE PRESUPUESTOS
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Presupuestos';
        $this->template->content = View::forge('admin/budgets/index');
    }

    /**
     * DATA
     *
     * ENTREGA PLANES, PARTIDAS Y RESUMEN PRESUPUESTAL
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA ESTRUCTURA
            $this->assert_schema_ready();

            # SE REGRESA INFORMACION PARA VUE
            $plan_id = (int) \Input::get('plan_id', 0);
            $filters = $this->period_filters();
            return $this->json_response([
                'plans' => $this->plans(),
                'lines' => $this->lines($plan_id, $filters),
                'summary' => $this->summary($plan_id, $filters),
                'options' => $this->options(),
                'stats' => $this->stats(),
                'period_filters' => $filters,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando presupuestos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar presupuestos.'], 500);
        }
    }

    /**
     * SAVE PLAN
     *
     * CREA O ACTUALIZA PLAN PRESUPUESTAL
     *
     * @access  public
     * @return  Response
     */
    public function action_save_plan()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('budgets.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            if (trim((string) \Arr::get($val, 'code', '')) === '' || trim((string) \Arr::get($val, 'name', '')) === '') {
                return $this->json_response(['error' => 'Codigo y nombre son obligatorios.'], 422);
            }

            # SE PREPARAN DATOS
            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'code' => strtoupper(trim((string) \Arr::get($val, 'code', ''))),
                'name' => trim((string) \Arr::get($val, 'name', '')),
                'fiscal_year_id' => (int) \Arr::get($val, 'fiscal_year_id', 0),
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'cost_center_id' => (int) \Arr::get($val, 'cost_center_id', 0),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'status' => $this->plan_status(\Arr::get($val, 'status', 'draft')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];

            # SE CREA O ACTUALIZA
            if ($id > 0) {
                $plan = Model_Core_Budget_Plan::find($id);
                if (!$plan) {
                    return $this->json_response(['error' => 'Presupuesto no encontrado.'], 404);
                }
                $old = $plan->to_array();
                $plan->set($data);
            } else {
                $old = [];
                $data['created_by'] = $this->user_id;
                $plan = Model_Core_Budget_Plan::forge($data);
            }
            if ($data['status'] === 'approved' && (int) $plan->approved_by === 0) {
                $plan->approved_by = $this->user_id;
                $plan->approved_at = time();
            }
            $plan->save();
            $this->recalculate_plan((int) $plan->id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'budgets',
                'action' => $id > 0 ? 'update_plan' : 'create_plan',
                'entity_type' => 'budget_plan',
                'entity_id' => (int) $plan->id,
                'summary' => 'Presupuesto '.$plan->code.' '.$plan->name,
                'old_values' => $old,
                'new_values' => $plan->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'plan_id' => (int) $plan->id, 'plans' => $this->plans(), 'summary' => $this->summary((int) $plan->id), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando presupuesto: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el presupuesto.'], 400);
        }
    }

    /**
     * SAVE LINE
     *
     * CREA O ACTUALIZA PARTIDA PRESUPUESTAL
     *
     * @access  public
     * @return  Response
     */
    public function action_save_line()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('budgets.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            $plan_id = (int) \Arr::get($val, 'plan_id', 0);
            if ($plan_id < 1 || (float) \Arr::get($val, 'amount', 0) <= 0) {
                return $this->json_response(['error' => 'Selecciona presupuesto e importe mayor a cero.'], 422);
            }

            # SE PREPARAN DATOS
            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'plan_id' => $plan_id,
                'account_id' => (int) \Arr::get($val, 'account_id', 0),
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'cost_center_id' => (int) \Arr::get($val, 'cost_center_id', 0),
                'period_start' => trim((string) \Arr::get($val, 'period_start', date('Y-01-01'))),
                'period_end' => trim((string) \Arr::get($val, 'period_end', date('Y-12-31'))),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'amount' => round((float) \Arr::get($val, 'amount', 0), 2),
                'warning_threshold' => min(100, max(0, (float) \Arr::get($val, 'warning_threshold', 80))),
                'block_threshold' => min(999, max(0, (float) \Arr::get($val, 'block_threshold', 100))),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];

            # SE CREA O ACTUALIZA
            if ($id > 0) {
                $line = Model_Core_Budget_Line::find($id);
                if (!$line) {
                    return $this->json_response(['error' => 'Partida no encontrada.'], 404);
                }
                $old = $line->to_array();
                $line->set($data);
            } else {
                $old = [];
                $line = Model_Core_Budget_Line::forge($data);
            }
            $line->save();
            $this->recalculate_plan($plan_id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'budgets',
                'action' => $id > 0 ? 'update_line' : 'create_line',
                'entity_type' => 'budget_line',
                'entity_id' => (int) $line->id,
                'summary' => 'Partida presupuestal #'.$line->id,
                'old_values' => $old,
                'new_values' => $line->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'plans' => $this->plans(), 'lines' => $this->lines($plan_id), 'summary' => $this->summary($plan_id), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando partida presupuestal: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la partida.'], 400);
        }
    }

    protected function plans()
    {
        return \DB::select(['p.id', 'id'], ['p.code', 'code'], ['p.name', 'name'], ['p.fiscal_year_id', 'fiscal_year_id'], ['fy.name', 'fiscal_year_name'], ['p.department_id', 'department_id'], ['d.name', 'department_name'], ['p.cost_center_id', 'cost_center_id'], ['cc.code', 'cost_center_code'], ['cc.name', 'cost_center_name'], ['p.currency_code', 'currency_code'], ['p.status', 'status'], ['p.total_amount', 'total_amount'], ['p.notes', 'notes'], ['p.active', 'active'])
            ->from(['core_budget_plans', 'p'])
            ->join(['core_accounting_fiscal_years', 'fy'], 'left')->on('p.fiscal_year_id', '=', 'fy.id')
            ->join(['core_departments', 'd'], 'left')->on('p.department_id', '=', 'd.id')
            ->join(['core_accounting_cost_centers', 'cc'], 'left')->on('p.cost_center_id', '=', 'cc.id')
            ->where('p.active', '=', 1)
            ->order_by('p.id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function lines($plan_id, array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        if ($plan_id < 1) {
            $plan = \DB::select('id')->from('core_budget_plans')->where('active', '=', 1)->order_by('id', 'desc')->execute()->current();
            $plan_id = $plan ? (int) $plan['id'] : 0;
        }
        if ($plan_id < 1) {
            return [];
        }

        $items = [];
        $rows = \DB::select(['l.id', 'id'], ['l.plan_id', 'plan_id'], ['l.account_id', 'account_id'], ['a.code', 'account_code'], ['a.name', 'account_name'], ['l.department_id', 'department_id'], ['d.name', 'department_name'], ['l.cost_center_id', 'cost_center_id'], ['cc.code', 'cost_center_code'], ['cc.name', 'cost_center_name'], ['l.period_start', 'period_start'], ['l.period_end', 'period_end'], ['l.currency_code', 'currency_code'], ['l.amount', 'amount'], ['l.warning_threshold', 'warning_threshold'], ['l.block_threshold', 'block_threshold'], ['l.notes', 'notes'], ['l.active', 'active'])
            ->from(['core_budget_lines', 'l'])
            ->join(['core_accounting_accounts', 'a'], 'left')->on('l.account_id', '=', 'a.id')
            ->join(['core_departments', 'd'], 'left')->on('l.department_id', '=', 'd.id')
            ->join(['core_accounting_cost_centers', 'cc'], 'left')->on('l.cost_center_id', '=', 'cc.id')
            ->where('l.plan_id', '=', $plan_id)
            ->where('l.active', '=', 1)
            ->where('l.period_start', '<=', $filters['end_date'])
            ->where('l.period_end', '>=', $filters['start_date'])
            ->order_by('l.period_start', 'asc')
            ->order_by('l.id', 'asc')
            ->execute();

        foreach ($rows as $row) {
            $actual = $this->actual_amount($row, $filters);
            $row['actual_amount'] = $actual;
            $row['available_amount'] = round((float) $row['amount'] - $actual, 2);
            $row['used_percent'] = (float) $row['amount'] > 0 ? round(($actual / (float) $row['amount']) * 100, 2) : 0;
            $items[] = $row;
        }
        return $items;
    }

    protected function summary($plan_id, array $filters = [])
    {
        $lines = $this->lines($plan_id, $filters);
        $budget = 0;
        $actual = 0;
        foreach ($lines as $line) {
            $budget += (float) $line['amount'];
            $actual += (float) $line['actual_amount'];
        }
        return [
            'budget_amount' => round($budget, 2),
            'actual_amount' => round($actual, 2),
            'available_amount' => round($budget - $actual, 2),
            'used_percent' => $budget > 0 ? round(($actual / $budget) * 100, 2) : 0,
        ];
    }

    protected function options()
    {
        return [
            'fiscal_years' => $this->select_options('core_accounting_fiscal_years', 'id', 'name'),
            'departments' => $this->select_options('core_departments', 'id', 'name'),
            'cost_centers' => $this->cost_center_options(),
            'accounts' => $this->account_options(),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
        ];
    }

    protected function stats()
    {
        $active = (int) \DB::select()->from('core_budget_plans')->where('active', '=', 1)->execute()->count();
        $approved = (int) \DB::select()->from('core_budget_plans')->where('status', '=', 'approved')->where('active', '=', 1)->execute()->count();
        $row = \DB::select([\DB::expr('COALESCE(SUM(total_amount),0)'), 'total'])->from('core_budget_plans')->where('active', '=', 1)->execute()->current();
        $total = $row ? (float) $row['total'] : 0;
        return ['plans' => $active, 'approved' => $approved, 'total_amount' => round($total, 2), 'lines' => (int) \DB::count_records('core_budget_lines')];
    }

    protected function actual_amount(array $line, array $filters = [])
    {
        if (!\DBUtil::table_exists('core_accounting_journal_lines') || !\DBUtil::table_exists('core_accounting_journal_entries')) {
            return 0;
        }

        $filters = $filters ?: $this->period_filters();
        $period_start = max((string) $line['period_start'], $filters['start_date']);
        $period_end = min((string) $line['period_end'], $filters['end_date']);

        $query = \DB::select([\DB::expr('COALESCE(SUM(l.debit - l.credit),0)'), 'actual'])
            ->from(['core_accounting_journal_lines', 'l'])
            ->join(['core_accounting_journal_entries', 'e'], 'inner')->on('l.entry_id', '=', 'e.id')
            ->where('l.active', '=', 1)
            ->where('e.active', '=', 1)
            ->where('e.status', '!=', 'cancelled')
            ->where('e.entry_date', '>=', $period_start)
            ->where('e.entry_date', '<=', $period_end);

        foreach (['account_id', 'department_id', 'cost_center_id'] as $field) {
            if ((int) $line[$field] > 0) {
                $query->where('l.'.$field, '=', (int) $line[$field]);
            }
        }

        $row = $query->execute()->current();
        return $row ? round(max(0, (float) $row['actual']), 2) : 0;
    }

    protected function recalculate_plan($plan_id)
    {
        $row = \DB::select([\DB::expr('COALESCE(SUM(amount),0)'), 'total'])
            ->from('core_budget_lines')
            ->where('plan_id', '=', (int) $plan_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();
        $plan = Model_Core_Budget_Plan::find((int) $plan_id);
        if ($plan) {
            $plan->total_amount = $row ? round((float) $row['total'], 2) : 0;
            $plan->save();
        }
    }

    protected function select_options($table, $value_field, $label_field)
    {
        if (!\DBUtil::table_exists($table)) {
            return [];
        }
        $rows = \DB::select($value_field, $label_field)->from($table)->where('active', '=', 1)->order_by($label_field, 'asc')->execute();
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $options;
    }

    protected function cost_center_options()
    {
        $items = [];
        foreach (\DB::select('id', 'code', 'name')->from('core_accounting_cost_centers')->where('active', '=', 1)->order_by('code', 'asc')->execute() as $row) {
            $items[] = ['value' => (string) $row['id'], 'label' => $row['code'].' - '.$row['name']];
        }
        return $items;
    }

    protected function account_options()
    {
        $items = [];
        foreach (\DB::select('id', 'code', 'name')->from('core_accounting_accounts')->where('active', '=', 1)->where('is_postable', '=', 1)->order_by('code', 'asc')->execute() as $row) {
            $items[] = ['value' => (string) $row['id'], 'label' => $row['code'].' - '.$row['name']];
        }
        return $items;
    }

    protected function assert_schema_ready()
    {
        foreach (['core_budget_plans', 'core_budget_lines', 'core_accounting_accounts', 'core_accounting_cost_centers'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de presupuestos.');
            }
        }
    }

    protected function plan_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['draft', 'approved', 'closed', 'cancelled'], true) ? $value : 'draft';
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
