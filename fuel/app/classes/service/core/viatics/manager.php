<?php

class Service_Core_Viatics_Manager
{
    public function schema_ready()
    {
        foreach (['core_viatic_trips', 'core_viatic_advances', 'core_viatic_expenses', 'core_viatic_settlements'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                return false;
            }
        }
        return true;
    }

    public function data_for_admin($user_id, $user_group, $is_super_admin, $department_id)
    {
        $trips = $this->trips_for_admin($user_id, $user_group, $is_super_admin, $department_id);
        $trip_ids = array_map(function ($trip) {
            return (int) $trip['id'];
        }, $trips);

        return [
            'trips' => $trips,
            'advances' => $this->advances_by_trip_ids($trip_ids),
            'expenses' => $this->expenses_by_trip_ids($trip_ids),
            'options' => $this->options(),
            'stats' => $this->stats($trips),
        ];
    }

    public function data_for_portal_reseller($party_id, $user_id)
    {
        $trips = \DB::select(
                ['t.id', 'id'], ['t.folio', 'folio'], ['t.seller_id', 'seller_id'], ['t.employee_id', 'employee_id'],
                ['t.user_id', 'user_id'], ['t.department_id', 'department_id'], ['t.title', 'title'],
                ['t.destination', 'destination'], ['t.travel_reason', 'travel_reason'], ['t.start_date', 'start_date'],
                ['t.end_date', 'end_date'], ['t.status', 'status'], ['t.currency_code', 'currency_code'],
                ['t.requested_amount', 'requested_amount'], ['t.advance_total', 'advance_total'],
                ['t.expense_total', 'expense_total'], ['t.balance_total', 'balance_total'],
                ['t.settlement_account_id', 'settlement_account_id'], ['t.accounting_entry_id', 'accounting_entry_id'],
                ['t.notes', 'notes'], ['t.created_at', 'created_at'], ['s.name', 'seller_name']
            )
            ->from(['core_viatic_trips', 't'])
            ->join(['core_sales_sellers', 's'], 'left')->on('t.seller_id', '=', 's.id')
            ->where('t.active', '=', 1)
            ->where('s.active', '=', 1)
            ->where('s.seller_type', '=', 'reseller')
            ->where('s.party_id', '=', (int) $party_id)
            ->where_open()
                ->where('t.user_id', '=', (int) $user_id)
                ->or_where('s.user_id', '=', (int) $user_id)
            ->where_close()
            ->order_by('t.id', 'desc')
            ->limit(300)
            ->execute()
            ->as_array();

        $trip_ids = array_map(function ($trip) {
            return (int) $trip['id'];
        }, $trips);

        return [
            'trips' => $trips,
            'advances' => $this->advances_by_trip_ids($trip_ids),
            'expenses' => $this->expenses_by_trip_ids($trip_ids),
            'options' => [
                'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
                'accounts' => $this->account_options(),
                'bank_accounts' => $this->select_options('core_catalog_bank_accounts', 'id', 'name'),
            ],
            'stats' => $this->stats($trips),
        ];
    }

    public function save_trip(array $values, $user_id)
    {
        $id = (int) \Arr::get($values, 'id', 0);
        $title = trim((string) \Arr::get($values, 'title', ''));
        $start_date = trim((string) \Arr::get($values, 'start_date', ''));
        $end_date = trim((string) \Arr::get($values, 'end_date', ''));
        if ($title === '' || $start_date === '' || $end_date === '') {
            throw new \InvalidArgumentException('Titulo y fechas son obligatorios.');
        }

        $trip_user_id = (int) \Arr::get($values, 'user_id', 0);
        if ($trip_user_id <= 0) {
            $trip_user_id = (int) $user_id;
        }

        $data = [
            'seller_id' => (int) \Arr::get($values, 'seller_id', 0),
            'employee_id' => (int) \Arr::get($values, 'employee_id', 0),
            'user_id' => $trip_user_id,
            'department_id' => (int) \Arr::get($values, 'department_id', 0),
            'title' => $title,
            'destination' => trim((string) \Arr::get($values, 'destination', '')),
            'travel_reason' => trim((string) \Arr::get($values, 'travel_reason', '')),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $this->trip_status(\Arr::get($values, 'status', 'draft')),
            'currency_code' => strtoupper(substr(trim((string) \Arr::get($values, 'currency_code', 'MXN')), 0, 3)),
            'requested_amount' => round(max(0, (float) \Arr::get($values, 'requested_amount', 0)), 2),
            'settlement_account_id' => (int) \Arr::get($values, 'settlement_account_id', 0),
            'notes' => trim((string) \Arr::get($values, 'notes', '')),
            'active' => (int) (bool) \Arr::get($values, 'active', true),
        ];

        if ($id > 0) {
            $trip = Model_Core_Viatic_Trip::find($id);
            if (!$trip) {
                throw new \RuntimeException('Viaje no encontrado.');
            }
            $trip->set($data);
        } else {
            $data['folio'] = $this->next_folio();
            $data['advance_total'] = 0;
            $data['expense_total'] = 0;
            $data['balance_total'] = 0;
            $data['accounting_entry_id'] = 0;
            $data['created_by'] = (int) $user_id;
            $trip = Model_Core_Viatic_Trip::forge($data);
        }

        $trip->save();
        $this->recalculate_trip((int) $trip->id);
        return (int) $trip->id;
    }

    public function save_advance(array $values, $user_id)
    {
        $trip_id = (int) \Arr::get($values, 'trip_id', 0);
        if ($trip_id < 1) {
            throw new \InvalidArgumentException('Selecciona el viaje.');
        }

        $amount = round(max(0, (float) \Arr::get($values, 'amount', 0)), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El anticipo debe ser mayor a cero.');
        }

        $id = (int) \Arr::get($values, 'id', 0);
        $data = [
            'trip_id' => $trip_id,
            'bank_account_id' => (int) \Arr::get($values, 'bank_account_id', 0),
            'currency_code' => strtoupper(substr(trim((string) \Arr::get($values, 'currency_code', 'MXN')), 0, 3)),
            'amount' => $amount,
            'requested_at' => $this->parse_date_to_ts(\Arr::get($values, 'requested_at', date('Y-m-d'))),
            'paid_at' => $this->parse_date_to_ts(\Arr::get($values, 'paid_at', '')),
            'status' => $this->advance_status(\Arr::get($values, 'status', 'draft')),
            'reference' => trim((string) \Arr::get($values, 'reference', '')),
            'notes' => trim((string) \Arr::get($values, 'notes', '')),
            'active' => (int) (bool) \Arr::get($values, 'active', true),
        ];

        if ($id > 0) {
            $advance = Model_Core_Viatic_Advance::find($id);
            if (!$advance) {
                throw new \RuntimeException('Anticipo no encontrado.');
            }
            $advance->set($data);
        } else {
            $data['created_by'] = (int) $user_id;
            $advance = Model_Core_Viatic_Advance::forge($data);
        }

        $advance->save();
        $this->recalculate_trip($trip_id);
        return (int) $advance->id;
    }

    public function save_expense(array $values, $user_id)
    {
        $trip_id = (int) \Arr::get($values, 'trip_id', 0);
        if ($trip_id < 1) {
            throw new \InvalidArgumentException('Selecciona el viaje.');
        }

        $total = round(max(0, (float) \Arr::get($values, 'total', 0)), 2);
        $subtotal = round(max(0, (float) \Arr::get($values, 'subtotal', 0)), 2);
        $tax_total = round(max(0, (float) \Arr::get($values, 'tax_total', 0)), 2);
        if ($total <= 0) {
            $total = round($subtotal + $tax_total, 2);
        }
        if ($total <= 0) {
            throw new \InvalidArgumentException('El gasto debe ser mayor a cero.');
        }

        $id = (int) \Arr::get($values, 'id', 0);
        $data = [
            'trip_id' => $trip_id,
            'expense_date' => trim((string) \Arr::get($values, 'expense_date', date('Y-m-d'))),
            'concept' => trim((string) \Arr::get($values, 'concept', '')),
            'vendor_name' => trim((string) \Arr::get($values, 'vendor_name', '')),
            'document_folio' => trim((string) \Arr::get($values, 'document_folio', '')),
            'payment_method' => $this->payment_method(\Arr::get($values, 'payment_method', 'cash')),
            'account_id' => (int) \Arr::get($values, 'account_id', 0),
            'currency_code' => strtoupper(substr(trim((string) \Arr::get($values, 'currency_code', 'MXN')), 0, 3)),
            'exchange_rate' => max(0.000001, (float) \Arr::get($values, 'exchange_rate', 1)),
            'subtotal' => $subtotal,
            'tax_total' => $tax_total,
            'total' => $total,
            'notes' => trim((string) \Arr::get($values, 'notes', '')),
            'active' => (int) (bool) \Arr::get($values, 'active', true),
        ];

        if ($data['concept'] === '') {
            throw new \InvalidArgumentException('Captura el concepto del gasto.');
        }

        if ($id > 0) {
            $expense = Model_Core_Viatic_Expense::find($id);
            if (!$expense) {
                throw new \RuntimeException('Gasto no encontrado.');
            }
            $expense->set($data);
        } else {
            $data['created_by'] = (int) $user_id;
            $expense = Model_Core_Viatic_Expense::forge($data);
        }

        $expense->save();
        $this->recalculate_trip($trip_id);
        return (int) $expense->id;
    }

    public function settle_trip($trip_id, array $values, $user_id)
    {
        $trip = Model_Core_Viatic_Trip::find((int) $trip_id);
        if (!$trip || (int) $trip->active !== 1) {
            throw new \RuntimeException('Viaje no encontrado.');
        }

        $this->recalculate_trip((int) $trip_id);
        $trip = Model_Core_Viatic_Trip::find((int) $trip_id);

        $balance = round((float) $trip->balance_total, 2);
        if ($balance > 0) {
            $balance_type = 'to_return';
        } elseif ($balance < 0) {
            $balance_type = 'to_pay';
        } else {
            $balance_type = 'equalized';
        }

        $data = [
            'trip_id' => (int) $trip->id,
            'advance_total' => (float) $trip->advance_total,
            'expense_total' => (float) $trip->expense_total,
            'balance_total' => $balance,
            'balance_type' => $balance_type,
            'status' => 'closed',
            'settled_at' => time(),
            'notes' => trim((string) \Arr::get($values, 'notes', '')),
            'active' => 1,
            'created_by' => (int) $user_id,
        ];

        $settlement = Model_Core_Viatic_Settlement::forge($data);
        $settlement->save();

        $trip->status = 'settled';
        $trip->save();

        return (int) $settlement->id;
    }

    public function generate_accounting_draft($trip_id, $user_id)
    {
        $trip = Model_Core_Viatic_Trip::find((int) $trip_id);
        if (!$trip || (int) $trip->active !== 1) {
            throw new \RuntimeException('Viaje no encontrado.');
        }
        if ((int) $trip->settlement_account_id < 1) {
            throw new \InvalidArgumentException('Selecciona la cuenta de liquidacion del viaje.');
        }
        if (!\DBUtil::table_exists('core_accounting_journal_entries') || !\DBUtil::table_exists('core_accounting_journal_lines')) {
            throw new \RuntimeException('Faltan tablas contables para generar borrador.');
        }

        $expenses = \DB::select('id', 'account_id', 'concept', 'total', 'currency_code', 'exchange_rate')
            ->from('core_viatic_expenses')
            ->where('trip_id', '=', (int) $trip->id)
            ->where('active', '=', 1)
            ->where('total', '>', 0)
            ->execute()
            ->as_array();

        if (empty($expenses)) {
            throw new \InvalidArgumentException('No hay gastos para contabilizar.');
        }

        foreach ($expenses as $expense) {
            if ((int) $expense['account_id'] < 1) {
                throw new \InvalidArgumentException('Cada gasto debe tener una cuenta contable.');
            }
        }

        $entry = Model_Core_Accounting_Journal_Entry::forge([
            'folio' => $this->next_entry_folio(),
            'entry_type' => 'diario',
            'entry_date' => date('Y-m-d'),
            'period' => date('Y-m'),
            'status' => 'draft',
            'source_module' => 'viatics',
            'source_entity_type' => 'viatic_trip',
            'source_entity_id' => (int) $trip->id,
            'currency_code' => (string) $trip->currency_code,
            'exchange_rate' => 1,
            'total_debit' => 0,
            'total_credit' => 0,
            'description' => 'Viaticos viaje '.$trip->folio.' '.$trip->title,
            'created_by' => (int) $user_id,
            'active' => 1,
        ]);
        $entry->save();

        $total = 0;
        $sort = 10;
        foreach ($expenses as $expense) {
            $amount = round((float) $expense['total'], 2);
            $total += $amount;
            Model_Core_Accounting_Journal_Line::forge([
                'entry_id' => (int) $entry->id,
                'account_id' => (int) $expense['account_id'],
                'party_id' => 0,
                'department_id' => (int) $trip->department_id,
                'cost_center_id' => 0,
                'description' => substr((string) $expense['concept'], 0, 255),
                'debit' => $amount,
                'credit' => 0,
                'currency_code' => (string) $trip->currency_code,
                'exchange_rate' => 1,
                'sort_order' => $sort,
                'active' => 1,
            ])->save();
            $sort += 10;
        }

        Model_Core_Accounting_Journal_Line::forge([
            'entry_id' => (int) $entry->id,
            'account_id' => (int) $trip->settlement_account_id,
            'party_id' => 0,
            'department_id' => (int) $trip->department_id,
            'cost_center_id' => 0,
            'description' => 'Contra cuenta viaticos '.$trip->folio,
            'debit' => 0,
            'credit' => round($total, 2),
            'currency_code' => (string) $trip->currency_code,
            'exchange_rate' => 1,
            'sort_order' => $sort,
            'active' => 1,
        ])->save();

        $entry->total_debit = round($total, 2);
        $entry->total_credit = round($total, 2);
        $entry->save();

        $trip->accounting_entry_id = (int) $entry->id;
        $trip->save();

        return (int) $entry->id;
    }

    protected function trips_for_admin($user_id, $user_group, $is_super_admin, $department_id)
    {
        $query = \DB::select(
                ['t.id', 'id'], ['t.folio', 'folio'], ['t.seller_id', 'seller_id'], ['t.employee_id', 'employee_id'],
                ['t.user_id', 'user_id'], ['t.department_id', 'department_id'], ['t.title', 'title'],
                ['t.destination', 'destination'], ['t.travel_reason', 'travel_reason'], ['t.start_date', 'start_date'],
                ['t.end_date', 'end_date'], ['t.status', 'status'], ['t.currency_code', 'currency_code'],
                ['t.requested_amount', 'requested_amount'], ['t.advance_total', 'advance_total'],
                ['t.expense_total', 'expense_total'], ['t.balance_total', 'balance_total'],
                ['t.settlement_account_id', 'settlement_account_id'], ['t.accounting_entry_id', 'accounting_entry_id'],
                ['t.notes', 'notes'], ['t.created_at', 'created_at'], ['s.name', 'seller_name'],
                ['e.full_name', 'employee_name'], ['d.name', 'department_name'], ['u.username', 'username']
            )
            ->from(['core_viatic_trips', 't'])
            ->join(['core_sales_sellers', 's'], 'left')->on('t.seller_id', '=', 's.id')
            ->join(['core_employees', 'e'], 'left')->on('t.employee_id', '=', 'e.id')
            ->join(['core_departments', 'd'], 'left')->on('t.department_id', '=', 'd.id')
            ->join(['users', 'u'], 'left')->on('t.user_id', '=', 'u.id')
            ->where('t.active', '=', 1);

        $can_view_all = (bool) $is_super_admin || in_array((int) $user_group, [80, 90, 100], true);
        if (!$can_view_all) {
            $query->where_open();
            $query->where('t.user_id', '=', (int) $user_id)
                ->or_where('s.user_id', '=', (int) $user_id)
                ->or_where('e.user_id', '=', (int) $user_id);
            if ((int) $department_id > 0) {
                $query->or_where('t.department_id', '=', (int) $department_id);
            }
            $query->where_close();
        }

        return $query
            ->order_by('t.id', 'desc')
            ->limit(500)
            ->execute()
            ->as_array();
    }

    protected function advances_by_trip_ids(array $trip_ids)
    {
        if (empty($trip_ids)) {
            return [];
        }

        return \DB::select('id', 'trip_id', 'bank_account_id', 'currency_code', 'amount', 'requested_at', 'paid_at', 'status', 'reference', 'notes')
            ->from('core_viatic_advances')
            ->where('active', '=', 1)
            ->where('trip_id', 'in', $trip_ids)
            ->order_by('id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function expenses_by_trip_ids(array $trip_ids)
    {
        if (empty($trip_ids)) {
            return [];
        }

        return \DB::select('id', 'trip_id', 'expense_date', 'concept', 'vendor_name', 'document_folio', 'payment_method', 'account_id', 'currency_code', 'exchange_rate', 'subtotal', 'tax_total', 'total', 'notes')
            ->from('core_viatic_expenses')
            ->where('active', '=', 1)
            ->where('trip_id', 'in', $trip_ids)
            ->order_by('expense_date', 'desc')
            ->order_by('id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function options()
    {
        return [
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'accounts' => $this->account_options(),
            'bank_accounts' => $this->select_options('core_catalog_bank_accounts', 'id', 'name'),
            'departments' => $this->select_options('core_departments', 'id', 'name'),
            'users' => $this->select_options('users', 'id', 'username', false),
            'employees' => $this->select_options('core_employees', 'id', 'full_name'),
            'sellers' => $this->select_options('core_sales_sellers', 'id', 'name'),
        ];
    }

    protected function stats(array $trips)
    {
        $open = 0;
        $settled = 0;
        $advance = 0;
        $expense = 0;
        foreach ($trips as $trip) {
            if ((string) $trip['status'] === 'settled') {
                $settled++;
            } else {
                $open++;
            }
            $advance += (float) $trip['advance_total'];
            $expense += (float) $trip['expense_total'];
        }

        return [
            'trips' => count($trips),
            'open' => $open,
            'settled' => $settled,
            'advance_total' => round($advance, 2),
            'expense_total' => round($expense, 2),
            'balance_total' => round($advance - $expense, 2),
        ];
    }

    protected function recalculate_trip($trip_id)
    {
        $advance_row = \DB::select([\DB::expr('COALESCE(SUM(amount),0)'), 'total'])
            ->from('core_viatic_advances')
            ->where('trip_id', '=', (int) $trip_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        $expense_row = \DB::select([\DB::expr('COALESCE(SUM(total),0)'), 'total'])
            ->from('core_viatic_expenses')
            ->where('trip_id', '=', (int) $trip_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        $advance_total = round((float) \Arr::get($advance_row, 'total', 0), 2);
        $expense_total = round((float) \Arr::get($expense_row, 'total', 0), 2);
        $balance_total = round($advance_total - $expense_total, 2);

        \DB::update('core_viatic_trips')
            ->set([
                'advance_total' => $advance_total,
                'expense_total' => $expense_total,
                'balance_total' => $balance_total,
                'updated_at' => time(),
            ])
            ->where('id', '=', (int) $trip_id)
            ->execute();
    }

    protected function next_folio()
    {
        $base = 'VIA-'.date('Ymd').'-';
        $i = 1;
        do {
            $folio = $base.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $exists = \DB::select('id')->from('core_viatic_trips')->where('folio', '=', $folio)->execute()->current();
            $i++;
        } while ($exists);

        return $folio;
    }

    protected function next_entry_folio()
    {
        $base = 'POL-VIA-'.date('Ym').'-';
        $i = 1;
        do {
            $folio = $base.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $exists = \DB::select('id')->from('core_accounting_journal_entries')->where('folio', '=', $folio)->execute()->current();
            $i++;
        } while ($exists);
        return $folio;
    }

    protected function select_options($table, $value_field, $label_field, $active_only = true)
    {
        if (!\DBUtil::table_exists($table)) {
            return [];
        }

        $query = \DB::select($value_field, $label_field)->from($table);
        if ($active_only && \DBUtil::field_exists($table, ['active'])) {
            $query->where('active', '=', 1);
        }
        $rows = $query->order_by($label_field, 'asc')->execute();

        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $options;
    }

    protected function account_options()
    {
        if (!\DBUtil::table_exists('core_accounting_accounts')) {
            return [];
        }
        $items = [];
        foreach (\DB::select('id', 'code', 'name')->from('core_accounting_accounts')->where('active', '=', 1)->where('is_postable', '=', 1)->order_by('code', 'asc')->execute() as $row) {
            $items[] = ['value' => (string) $row['id'], 'label' => $row['code'].' - '.$row['name']];
        }
        return $items;
    }

    protected function trip_status($value)
    {
        $value = trim((string) $value);
        return in_array($value, ['draft', 'submitted', 'settled', 'cancelled'], true) ? $value : 'draft';
    }

    protected function advance_status($value)
    {
        $value = trim((string) $value);
        return in_array($value, ['draft', 'paid', 'applied', 'cancelled'], true) ? $value : 'draft';
    }

    protected function payment_method($value)
    {
        $value = trim((string) $value);
        return in_array($value, ['cash', 'card', 'transfer', 'other'], true) ? $value : 'cash';
    }

    protected function parse_date_to_ts($value)
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return 0;
        }
        return strtotime($value.' 00:00:00');
    }
}
