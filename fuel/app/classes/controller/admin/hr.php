<?php

/**
 * CONTROLADOR ADMIN_HR
 *
 * Administra recursos humanos, empleados no necesariamente usuarios y base de nomina.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Hr extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMINISTRATIVA Y PERMISO DE RH
     *
     * @return  Void
     */
    public function before()
    {
        parent::before();
        $this->require_access('hr.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE RH Y NOMINA
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Recursos Humanos';
        $this->template->content = \View::forge('admin/hr/index');
    }

    /**
     * DATA
     *
     * ENTREGA EMPLEADOS, PERIODOS, CORRIDAS Y OPCIONES
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            $this->assert_schema_ready();
            return $this->json_response([
                'employees' => $this->employees(),
                'periods' => $this->periods(),
                'runs' => $this->runs(),
                'items' => $this->items((int) \Input::get('run_id', 0)),
                'options' => $this->options(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando RH: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar Recursos Humanos.'], 500);
        }
    }

    /**
     * SAVE EMPLOYEE
     *
     * CREA O ACTUALIZA UN EMPLEADO DE RH
     *
     * @access  public
     * @return  Response
     */
    public function action_save_employee()
    {
        $this->require_access('hr.access[edit]');
        $val = (array) \Input::json();

        try {
            $name = trim((string) \Arr::get($val, 'full_name', ''));
            if ($name === '') {
                return $this->json_response(['error' => 'El nombre del empleado es obligatorio.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'user_id' => (int) \Arr::get($val, 'user_id', 0),
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'branch_id' => (int) \Arr::get($val, 'branch_id', 0),
                'employee_number' => trim((string) \Arr::get($val, 'employee_number', '')),
                'full_name' => $name,
                'email' => trim((string) \Arr::get($val, 'email', '')),
                'rfc' => strtoupper(trim((string) \Arr::get($val, 'rfc', ''))),
                'curp' => strtoupper(trim((string) \Arr::get($val, 'curp', ''))),
                'nss' => trim((string) \Arr::get($val, 'nss', '')),
                'position' => trim((string) \Arr::get($val, 'position', '')),
                'hire_date' => trim((string) \Arr::get($val, 'hire_date', '')),
                'termination_date' => trim((string) \Arr::get($val, 'termination_date', '')),
                'payroll_status' => $this->codeify(\Arr::get($val, 'payroll_status', 'active')),
                'salary_daily' => max(0, (float) \Arr::get($val, 'salary_daily', 0)),
                'salary_integrated' => max(0, (float) \Arr::get($val, 'salary_integrated', 0)),
                'payment_frequency' => $this->codeify(\Arr::get($val, 'payment_frequency', 'quincenal')),
                'bank_account_id' => (int) \Arr::get($val, 'bank_account_id', 0),
                'sat_regime_code' => trim((string) \Arr::get($val, 'sat_regime_code', '02')),
                'contract_type' => $this->codeify(\Arr::get($val, 'contract_type', 'indefinido')),
                'work_shift' => $this->codeify(\Arr::get($val, 'work_shift', 'diurna')),
                'risk_class' => trim((string) \Arr::get($val, 'risk_class', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json_response(['error' => 'El correo del empleado no es valido.'], 422);
            }

            if ($id > 0) {
                $employee = \Model_Core_Employee::find($id);
                if (!$employee) {
                    return $this->json_response(['error' => 'Empleado no encontrado.'], 404);
                }
                $old = $employee->to_array();
                $employee->set($data);
            } else {
                $old = [];
                if ($data['employee_number'] === '') {
                    $data['employee_number'] = $this->next_employee_number();
                }
                $employee = \Model_Core_Employee::forge($data);
            }
            $employee->save();
            $this->audit($id > 0 ? 'update_employee' : 'create_employee', 'employee', $employee, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando empleado RH: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el empleado.'], 400);
        }
    }

    /**
     * SAVE PERIOD
     *
     * CREA O ACTUALIZA UN PERIODO DE NOMINA
     *
     * @access  public
     * @return  Response
     */
    public function action_save_period()
    {
        $this->require_access('hr.access[edit]');
        $val = (array) \Input::json();

        try {
            $name = trim((string) \Arr::get($val, 'name', ''));
            $date_from = trim((string) \Arr::get($val, 'date_from', ''));
            $date_to = trim((string) \Arr::get($val, 'date_to', ''));
            if ($name === '' || $date_from === '' || $date_to === '') {
                return $this->json_response(['error' => 'Nombre y fechas del periodo son obligatorios.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'name' => $name,
                'period_type' => $this->codeify(\Arr::get($val, 'period_type', 'quincenal')),
                'date_from' => $date_from,
                'date_to' => $date_to,
                'payment_date' => trim((string) \Arr::get($val, 'payment_date', '')),
                'status' => $this->period_status(\Arr::get($val, 'status', 'open')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $period = \Model_Core_Hr_Payroll_Period::find($id);
                if (!$period) {
                    return $this->json_response(['error' => 'Periodo no encontrado.'], 404);
                }
                $old = $period->to_array();
                $period->set($data);
            } else {
                $old = [];
                $data['code'] = $this->next_code('NOM', 'core_hr_payroll_periods', 'code');
                $period = \Model_Core_Hr_Payroll_Period::forge($data);
            }
            $period->save();
            $this->audit($id > 0 ? 'update_period' : 'create_period', 'payroll_period', $period, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando periodo RH: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el periodo.'], 400);
        }
    }

    /**
     * SAVE RUN
     *
     * CREA O ACTUALIZA UNA CORRIDA DE NOMINA
     *
     * @access  public
     * @return  Response
     */
    public function action_save_run()
    {
        $this->require_access('hr.access[edit]');
        $val = (array) \Input::json();

        try {
            $period_id = (int) \Arr::get($val, 'period_id', 0);
            if ($period_id < 1) {
                return $this->json_response(['error' => 'Selecciona un periodo de nomina.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'period_id' => $period_id,
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'run_type' => $this->codeify(\Arr::get($val, 'run_type', 'ordinary')),
                'status' => $this->run_status(\Arr::get($val, 'status', 'draft')),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'payment_batch_id' => (int) \Arr::get($val, 'payment_batch_id', 0),
                'accounting_entry_id' => (int) \Arr::get($val, 'accounting_entry_id', 0),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $run = \Model_Core_Hr_Payroll_Run::find($id);
                if (!$run) {
                    return $this->json_response(['error' => 'Corrida no encontrada.'], 404);
                }
                $old = $run->to_array();
                $run->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_code('NOM-RUN', 'core_hr_payroll_runs', 'folio');
                $data['created_by'] = $this->user_id;
                $run = \Model_Core_Hr_Payroll_Run::forge($data);
            }
            $run->save();
            $this->recalculate_run((int) $run->id);
            $run = \Model_Core_Hr_Payroll_Run::find((int) $run->id);
            $this->audit($id > 0 ? 'update_run' : 'create_run', 'payroll_run', $run, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando corrida RH: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la corrida.'], 400);
        }
    }

    /**
     * SAVE ITEM
     *
     * AGREGA O ACTUALIZA UN EMPLEADO EN LA CORRIDA
     *
     * @access  public
     * @return  Response
     */
    public function action_save_item()
    {
        $this->require_access('hr.access[edit]');
        $val = (array) \Input::json();

        try {
            $run_id = (int) \Arr::get($val, 'run_id', 0);
            $employee_id = (int) \Arr::get($val, 'employee_id', 0);
            if ($run_id < 1 || $employee_id < 1) {
                return $this->json_response(['error' => 'Selecciona corrida y empleado.'], 422);
            }

            $perceptions = max(0, (float) \Arr::get($val, 'perception_total', 0));
            $deductions = max(0, (float) \Arr::get($val, 'deduction_total', 0));
            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'run_id' => $run_id,
                'employee_id' => $employee_id,
                'cfdi_id' => (int) \Arr::get($val, 'cfdi_id', 0),
                'fiscal_document_id' => (int) \Arr::get($val, 'fiscal_document_id', 0),
                'payment_id' => (int) \Arr::get($val, 'payment_id', 0),
                'days_paid' => max(0, (float) \Arr::get($val, 'days_paid', 15)),
                'perception_total' => $perceptions,
                'deduction_total' => $deductions,
                'net_total' => max(0, round($perceptions - $deductions, 2)),
                'sat_status' => $this->codeify(\Arr::get($val, 'sat_status', 'pending')),
                'payment_status' => $this->codeify(\Arr::get($val, 'payment_status', 'pending')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $item = \Model_Core_Hr_Payroll_Item::find($id);
                if (!$item) {
                    return $this->json_response(['error' => 'Partida de nomina no encontrada.'], 404);
                }
                $item->set($data);
            } else {
                $item = \Model_Core_Hr_Payroll_Item::forge($data);
            }
            $item->save();
            $this->recalculate_run($run_id);

            return $this->json_response(['status' => 'ok', 'runs' => $this->runs(), 'items' => $this->items($run_id), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando partida RH: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la partida de nomina.'], 400);
        }
    }

    protected function employees()
    {
        $rows = \DB::select(['e.id', 'id'], ['e.user_id', 'user_id'], ['u.username', 'username'], ['e.party_id', 'party_id'], ['e.department_id', 'department_id'], ['d.name', 'department_name'], ['e.branch_id', 'branch_id'], ['b.name', 'branch_name'], ['e.employee_number', 'employee_number'], ['e.full_name', 'full_name'], ['e.email', 'email'], ['e.rfc', 'rfc'], ['e.curp', 'curp'], ['e.nss', 'nss'], ['e.position', 'position'], ['e.hire_date', 'hire_date'], ['e.termination_date', 'termination_date'], ['e.payroll_status', 'payroll_status'], ['e.salary_daily', 'salary_daily'], ['e.salary_integrated', 'salary_integrated'], ['e.payment_frequency', 'payment_frequency'], ['e.bank_account_id', 'bank_account_id'], ['e.sat_regime_code', 'sat_regime_code'], ['e.contract_type', 'contract_type'], ['e.work_shift', 'work_shift'], ['e.risk_class', 'risk_class'], ['e.active', 'active'])
            ->from(['core_employees', 'e'])
            ->join(['users', 'u'], 'left')->on('e.user_id', '=', 'u.id')
            ->join(['core_departments', 'd'], 'left')->on('e.department_id', '=', 'd.id')
            ->join(['core_branches', 'b'], 'left')->on('e.branch_id', '=', 'b.id')
            ->order_by('e.full_name', 'asc')
            ->execute()
            ->as_array();

        return $rows;
    }

    protected function periods()
    {
        return \DB::select()
            ->from('core_hr_payroll_periods')
            ->where('active', '=', 1)
            ->order_by('date_from', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function runs()
    {
        return \DB::select(['r.id', 'id'], ['r.folio', 'folio'], ['r.period_id', 'period_id'], ['p.name', 'period_name'], ['r.department_id', 'department_id'], ['d.name', 'department_name'], ['r.run_type', 'run_type'], ['r.status', 'status'], ['r.currency_code', 'currency_code'], ['r.perception_total', 'perception_total'], ['r.deduction_total', 'deduction_total'], ['r.net_total', 'net_total'], ['r.payment_batch_id', 'payment_batch_id'], ['r.accounting_entry_id', 'accounting_entry_id'], ['r.created_at', 'created_at'])
            ->from(['core_hr_payroll_runs', 'r'])
            ->join(['core_hr_payroll_periods', 'p'], 'left')->on('r.period_id', '=', 'p.id')
            ->join(['core_departments', 'd'], 'left')->on('r.department_id', '=', 'd.id')
            ->where('r.active', '=', 1)
            ->order_by('r.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function items($run_id)
    {
        if ($run_id < 1) {
            return [];
        }
        return \DB::select(['i.id', 'id'], ['i.run_id', 'run_id'], ['i.employee_id', 'employee_id'], ['e.full_name', 'employee_name'], ['e.rfc', 'employee_rfc'], ['i.cfdi_id', 'cfdi_id'], ['c.uuid', 'cfdi_uuid'], ['i.fiscal_document_id', 'fiscal_document_id'], ['i.payment_id', 'payment_id'], ['pay.folio', 'payment_folio'], ['i.days_paid', 'days_paid'], ['i.perception_total', 'perception_total'], ['i.deduction_total', 'deduction_total'], ['i.net_total', 'net_total'], ['i.sat_status', 'sat_status'], ['i.payment_status', 'payment_status'], ['i.notes', 'notes'])
            ->from(['core_hr_payroll_items', 'i'])
            ->join(['core_employees', 'e'])->on('i.employee_id', '=', 'e.id')
            ->join(['core_sat_cfdi', 'c'], 'left')->on('i.cfdi_id', '=', 'c.id')
            ->join(['core_payments', 'pay'], 'left')->on('i.payment_id', '=', 'pay.id')
            ->where('i.run_id', '=', $run_id)
            ->where('i.active', '=', 1)
            ->order_by('e.full_name', 'asc')
            ->execute()
            ->as_array();
    }

    protected function options()
    {
        return [
            'users' => $this->select_options('users', 'id', 'username', false),
            'departments' => $this->select_options('core_departments', 'id', 'name'),
            'branches' => $this->select_options('core_branches', 'id', 'name'),
            'bank_accounts' => \DBUtil::table_exists('core_catalog_bank_accounts') ? $this->select_options('core_catalog_bank_accounts', 'id', 'name') : [],
            'periods' => $this->select_options('core_hr_payroll_periods', 'id', 'name'),
            'employees' => $this->select_options('core_employees', 'id', 'full_name'),
            'payments' => \DBUtil::table_exists('core_payments') ? $this->payment_options() : [],
            'cfdi_payroll' => \DBUtil::table_exists('core_sat_cfdi') ? $this->cfdi_payroll_options() : [],
            'sat_payroll_regimes' => \DBUtil::table_exists('core_sat_payroll_regimes') ? Helper_Core_Sat_Catalog::options('core_sat_payroll_regimes') : [],
        ];
    }

    protected function stats()
    {
        return [
            'employees' => (int) \DB::select()->from('core_employees')->where('active', '=', 1)->execute()->count(),
            'active_payroll' => (int) \DB::select()->from('core_employees')->where('active', '=', 1)->where('payroll_status', '=', 'active')->execute()->count(),
            'periods_open' => (int) \DB::select()->from('core_hr_payroll_periods')->where('active', '=', 1)->where('status', '=', 'open')->execute()->count(),
            'net_pending' => (float) \DB::select([\DB::expr('COALESCE(SUM(net_total),0)'), 'total'])->from('core_hr_payroll_items')->where('active', '=', 1)->where('payment_status', '!=', 'paid')->execute()->current()['total'],
        ];
    }

    protected function recalculate_run($run_id)
    {
        $totals = \DB::select([\DB::expr('COALESCE(SUM(perception_total),0)'), 'perceptions'], [\DB::expr('COALESCE(SUM(deduction_total),0)'), 'deductions'], [\DB::expr('COALESCE(SUM(net_total),0)'), 'net'])
            ->from('core_hr_payroll_items')
            ->where('run_id', '=', (int) $run_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        \DB::update('core_hr_payroll_runs')
            ->set([
                'perception_total' => round((float) $totals['perceptions'], 2),
                'deduction_total' => round((float) $totals['deductions'], 2),
                'net_total' => round((float) $totals['net'], 2),
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $run_id)
            ->execute();
    }

    protected function select_options($table, $value_field, $label_field, $active = true)
    {
        if (!\DBUtil::table_exists($table)) {
            return [];
        }
        $query = \DB::select($value_field, $label_field)->from($table);
        if ($active && \DBUtil::field_exists($table, ['active'])) {
            $query->where('active', '=', 1);
        }
        $rows = [];
        foreach ($query->order_by($label_field, 'asc')->execute() as $row) {
            $rows[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $rows;
    }

    protected function payment_options()
    {
        $rows = [];
        foreach (\DB::select('id', 'folio', 'amount', 'currency_code', 'status')->from('core_payments')->where('payment_type', '=', 'outgoing')->where('active', '=', 1)->order_by('id', 'desc')->limit(100)->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => $row['folio'].' - '.$row['currency_code'].' '.number_format((float) $row['amount'], 2).' - '.$row['status']];
        }
        return $rows;
    }

    protected function cfdi_payroll_options()
    {
        $rows = [];
        foreach (\DB::select('id', 'uuid', 'emitter_name', 'receiver_name', 'total')->from('core_sat_cfdi')->where('voucher_type', '=', 'N')->order_by('id', 'desc')->limit(100)->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => $row['uuid'].' - '.($row['receiver_name'] ?: $row['emitter_name']).' - '.number_format((float) $row['total'], 2)];
        }
        return $rows;
    }

    protected function period_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['open', 'closed', 'cancelled'], true) ? $value : 'open';
    }

    protected function run_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['draft', 'calculated', 'stamped', 'paid', 'cancelled'], true) ? $value : 'draft';
    }

    protected function next_employee_number()
    {
        return 'EMP-'.str_pad((string) ((int) \DB::count_records('core_employees') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function next_code($prefix, $table, $field)
    {
        $base = $prefix.'-'.date('Ymd').'-';
        $row = \DB::select(\DB::expr('COUNT(*) as total'))->from($table)->where($field, 'like', $base.'%')->execute()->current();
        return $base.str_pad(((int) $row['total']) + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function audit($action, $entity_type, $model, array $old)
    {
        \Helper_Core_Audit::log([
            'module' => 'hr',
            'action' => $action,
            'business_event' => 'hr.'.$action,
            'entity_type' => $entity_type,
            'entity_id' => (int) $model->id,
            'summary' => ucfirst(str_replace('_', ' ', $action)).' '.$entity_type,
            'old_values' => $old,
            'new_values' => $model->to_array(),
        ]);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_employees', 'core_hr_payroll_periods', 'core_hr_payroll_runs', 'core_hr_payroll_items'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de RH.');
            }
        }
        if (!\DBUtil::field_exists('core_employees', ['rfc', 'salary_daily', 'payroll_status'])) {
            throw new \RuntimeException('Falta actualizar estructura de empleados para RH.');
        }
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
