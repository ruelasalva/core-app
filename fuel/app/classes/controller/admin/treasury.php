<?php

/**
 * CONTROLADOR ADMIN_TREASURY
 *
 * Administra tesoreria, posicion bancaria y flujo de efectivo proyectado.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Treasury extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE TESORERIA
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('treasury.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE TESORERIA
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Tesoreria';
        $this->template->content = View::forge('admin/treasury/index');
    }

    /**
     * DATA
     *
     * ENTREGA POSICION, PROYECCION Y AJUSTES MANUALES
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA ESTRUCTURA
            $this->assert_schema_ready();
            $filters = $this->period_filters();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'bank_accounts' => $this->bank_accounts(),
                'forecast' => $this->forecast($filters),
                'manual_items' => $this->manual_items($filters),
                'options' => $this->options(),
                'stats' => $this->stats($filters),
                'period_filters' => $filters,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando tesoreria: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar tesoreria.'], 500);
        }
    }

    /**
     * SAVE ITEM
     *
     * CREA O ACTUALIZA UN MOVIMIENTO PROYECTADO MANUAL
     *
     * @access  public
     * @return  Response
     */
    public function action_save_item()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('treasury.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            if ((float) \Arr::get($val, 'amount', 0) <= 0) {
                return $this->json_response(['error' => 'El importe debe ser mayor a cero.'], 422);
            }
            if (trim((string) \Arr::get($val, 'planned_date', '')) === '') {
                return $this->json_response(['error' => 'La fecha proyectada es obligatoria.'], 422);
            }

            # SE PREPARAN DATOS
            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'flow_type' => $this->flow_type(\Arr::get($val, 'flow_type', 'inflow')),
                'source_module' => 'manual',
                'source_entity_type' => '',
                'source_entity_id' => 0,
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'bank_account_id' => (int) \Arr::get($val, 'bank_account_id', 0),
                'planned_date' => trim((string) \Arr::get($val, 'planned_date', date('Y-m-d'))),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'amount' => round((float) \Arr::get($val, 'amount', 0), 2),
                'probability' => min(100, max(0, (float) \Arr::get($val, 'probability', 100))),
                'status' => $this->status(\Arr::get($val, 'status', 'planned')),
                'reference' => trim((string) \Arr::get($val, 'reference', '')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];

            # SE CREA O ACTUALIZA
            if ($id > 0) {
                $item = Model_Core_Treasury_Cashflow_Item::find($id);
                if (!$item) {
                    return $this->json_response(['error' => 'Proyeccion no encontrada.'], 404);
                }
                $old = $item->to_array();
                $item->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_folio();
                $data['created_by'] = $this->user_id;
                $item = Model_Core_Treasury_Cashflow_Item::forge($data);
            }
            $item->save();

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'treasury',
                'action' => $id > 0 ? 'update_cashflow_item' : 'create_cashflow_item',
                'entity_type' => 'treasury_cashflow_item',
                'entity_id' => (int) $item->id,
                'summary' => 'Proyeccion de flujo '.$item->folio,
                'old_values' => $old,
                'new_values' => $item->to_array(),
            ]);

            return $this->json_response([
                'status' => 'ok',
                'bank_accounts' => $this->bank_accounts(),
                'forecast' => $this->forecast($this->period_filters()),
                'manual_items' => $this->manual_items($this->period_filters()),
                'stats' => $this->stats($this->period_filters()),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando proyeccion de tesoreria: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la proyeccion.'], 400);
        }
    }

    protected function bank_accounts()
    {
        $items = [];
        $rows = \DB::select(['a.id', 'id'], ['a.name', 'name'], ['a.account_number', 'account_number'], ['a.clabe', 'clabe'], ['a.currency_code', 'currency_code'], ['b.name', 'bank_name'])
            ->from(['core_catalog_bank_accounts', 'a'])
            ->join(['core_catalog_banks', 'b'], 'left')->on('a.bank_id', '=', 'b.id')
            ->where('a.active', '=', 1)
            ->order_by('a.name', 'asc')
            ->execute();

        foreach ($rows as $row) {
            $row['balance'] = $this->bank_account_balance((int) $row['id']);
            $items[] = $row;
        }
        return $items;
    }

    protected function forecast(array $filters = [])
    {
        $items = [];
        $filters = $filters ?: $this->period_filters();

        if (\DBUtil::table_exists('core_billing_invoices')) {
            foreach (\DB::select(['i.id', 'id'], ['i.folio', 'folio'], ['i.party_id', 'party_id'], ['p.name', 'party_name'], ['i.due_date', 'planned_date'], ['i.currency_code', 'currency_code'], ['i.balance_due', 'amount'])
                ->from(['core_billing_invoices', 'i'])
                ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
                ->where('i.invoice_type', '=', 'sale')
                ->where('i.active', '=', 1)
                ->where('i.balance_due', '>', 0)
                ->where('i.due_date', '!=', '')
                ->where('i.due_date', '>=', $filters['start_date'])
                ->where('i.due_date', '<=', $filters['end_date'])
                ->execute() as $row) {
                $items[] = $this->forecast_row('inflow', 'CxC', 'billing_invoice', $row);
            }
        }

        if (\DBUtil::table_exists('core_purchase_invoices')) {
            foreach (\DB::select(['i.id', 'id'], ['i.folio', 'folio'], ['i.party_id', 'party_id'], ['p.name', 'party_name'], ['i.due_date', 'planned_date'], ['i.currency_code', 'currency_code'], ['i.balance_due', 'amount'])
                ->from(['core_purchase_invoices', 'i'])
                ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
                ->where('i.active', '=', 1)
                ->where('i.balance_due', '>', 0)
                ->where('i.due_date', '!=', '')
                ->where('i.due_date', '>=', $filters['start_date'])
                ->where('i.due_date', '<=', $filters['end_date'])
                ->execute() as $row) {
                $items[] = $this->forecast_row('outflow', 'CxP', 'purchase_invoice', $row);
            }
        }

        foreach ($this->manual_items($filters) as $row) {
            if ($row['status'] !== 'cancelled') {
                $items[] = $this->forecast_row($row['flow_type'], 'Manual', 'treasury_cashflow_item', [
                    'id' => $row['id'],
                    'folio' => $row['folio'],
                    'party_id' => $row['party_id'],
                    'party_name' => $row['party_name'],
                    'planned_date' => $row['planned_date'],
                    'currency_code' => $row['currency_code'],
                    'amount' => $row['amount'],
                    'probability' => $row['probability'],
                    'status' => $row['status'],
                ]);
            }
        }

        usort($items, function ($a, $b) {
            return strcmp($a['planned_date'], $b['planned_date']);
        });

        $balance = $this->cash_position();
        foreach ($items as &$item) {
            $impact = (float) $item['weighted_amount'] * ($item['flow_type'] === 'outflow' ? -1 : 1);
            $balance += $impact;
            $item['running_balance'] = round($balance, 2);
        }

        return array_slice($items, 0, 300);
    }

    protected function manual_items(array $filters = [])
    {
        $items = [];
        $filters = $filters ?: $this->period_filters();
        $rows = \DB::select(['t.id', 'id'], ['t.folio', 'folio'], ['t.flow_type', 'flow_type'], ['t.party_id', 'party_id'], ['p.name', 'party_name'], ['t.bank_account_id', 'bank_account_id'], ['t.planned_date', 'planned_date'], ['t.currency_code', 'currency_code'], ['t.amount', 'amount'], ['t.probability', 'probability'], ['t.status', 'status'], ['t.reference', 'reference'], ['t.notes', 'notes'], ['t.active', 'active'])
            ->from(['core_treasury_cashflow_items', 't'])
            ->join(['core_parties', 'p'], 'left')->on('t.party_id', '=', 'p.id')
            ->where('t.active', '=', 1)
            ->where('t.planned_date', '>=', $filters['start_date'])
            ->where('t.planned_date', '<=', $filters['end_date'])
            ->order_by('t.planned_date', 'asc')
            ->order_by('t.id', 'desc')
            ->limit(200)
            ->execute();
        foreach ($rows as $row) {
            $items[] = $row;
        }
        return $items;
    }

    protected function options()
    {
        return [
            'bank_accounts' => $this->select_options('core_catalog_bank_accounts', 'id', 'name'),
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
        ];
    }

    protected function stats(array $filters = [])
    {
        $filters = $filters ?: $this->period_filters();
        $forecast = $this->forecast($filters);
        $inflow = 0;
        $outflow = 0;
        foreach ($forecast as $row) {
            if ($row['flow_type'] === 'outflow') {
                $outflow += (float) $row['weighted_amount'];
            } else {
                $inflow += (float) $row['weighted_amount'];
            }
        }
        return [
            'cash_position' => $this->cash_position(),
            'inflow_30' => round($inflow, 2),
            'outflow_30' => round($outflow, 2),
            'net_30' => round($inflow - $outflow, 2),
        ];
    }

    protected function bank_account_balance($bank_account_id)
    {
        if (!\DBUtil::table_exists('core_bank_movements')) {
            return 0;
        }

        $row = \DB::select(
                [\DB::expr("COALESCE(SUM(CASE WHEN movement_type = 'deposit' THEN amount WHEN movement_type IN ('withdrawal','fee') THEN -amount ELSE amount END),0)"), 'balance']
            )
            ->from('core_bank_movements')
            ->where('bank_account_id', '=', (int) $bank_account_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();
        return $row ? round((float) $row['balance'], 2) : 0;
    }

    protected function cash_position()
    {
        $total = 0;
        foreach ($this->bank_accounts() as $account) {
            $total += (float) $account['balance'];
        }
        return round($total, 2);
    }

    protected function forecast_row($flow_type, $source, $entity_type, array $row)
    {
        $amount = (float) $row['amount'];
        $probability = isset($row['probability']) ? (float) $row['probability'] : 100;
        return [
            'flow_type' => $flow_type,
            'source' => $source,
            'entity_type' => $entity_type,
            'entity_id' => (int) $row['id'],
            'folio' => (string) $row['folio'],
            'party_id' => (int) $row['party_id'],
            'party_name' => (string) \Arr::get($row, 'party_name', ''),
            'planned_date' => (string) ($row['planned_date'] ?: date('Y-m-d')),
            'currency_code' => (string) $row['currency_code'],
            'amount' => round($amount, 2),
            'probability' => $probability,
            'weighted_amount' => round($amount * ($probability / 100), 2),
            'status' => (string) \Arr::get($row, 'status', 'planned'),
            'running_balance' => 0,
        ];
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

    protected function next_folio()
    {
        return 'TES-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_treasury_cashflow_items') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_treasury_cashflow_items', 'core_catalog_bank_accounts', 'core_bank_movements'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de tesoreria.');
            }
        }
    }

    protected function flow_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['inflow', 'outflow'], true) ? $value : 'inflow';
    }

    protected function status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['planned', 'confirmed', 'completed', 'cancelled'], true) ? $value : 'planned';
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
