<?php

/**
 * CONTROLADOR ADMIN_RECEIVABLES
 *
 * Administra cuentas por cobrar, cartera vencida y gestiones de cobranza.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Receivables extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE CUENTAS POR COBRAR
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('receivables.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE CUENTAS POR COBRAR
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Cuentas por cobrar';
        $this->template->content = View::forge('admin/receivables/index');
    }

    /**
     * DATA
     *
     * ENTREGA CARTERA, DOCUMENTOS Y GESTIONES DE COBRANZA
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
            return $this->json_response([
                'customers' => $this->customers(),
                'documents' => $this->documents(),
                'actions' => $this->actions(),
                'options' => $this->options(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando cuentas por cobrar: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar cuentas por cobrar.'], 500);
        }
    }

    /**
     * SAVE ACTION
     *
     * CREA O ACTUALIZA GESTION DE COBRANZA
     *
     * @access  public
     * @return  Response
     */
    public function action_save_action()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('receivables.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            $party_id = (int) \Arr::get($val, 'party_id', 0);
            if ($party_id < 1) {
                return $this->json_response(['error' => 'Selecciona un cliente.'], 422);
            }

            # SE PREPARAN DATOS
            $id = (int) \Arr::get($val, 'id', 0);
            $status = $this->action_status(\Arr::get($val, 'status', 'pending'));
            $data = [
                'party_id' => $party_id,
                'invoice_id' => (int) \Arr::get($val, 'invoice_id', 0),
                'action_type' => $this->action_type(\Arr::get($val, 'action_type', 'call')),
                'status' => $status,
                'priority' => $this->priority(\Arr::get($val, 'priority', 'normal')),
                'assigned_user_id' => (int) \Arr::get($val, 'assigned_user_id', 0),
                'action_date' => trim((string) \Arr::get($val, 'action_date', date('Y-m-d'))),
                'next_action_date' => trim((string) \Arr::get($val, 'next_action_date', '')),
                'promise_date' => trim((string) \Arr::get($val, 'promise_date', '')),
                'promise_amount' => round((float) \Arr::get($val, 'promise_amount', 0), 2),
                'result' => trim((string) \Arr::get($val, 'result', '')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'completed_by' => 0,
                'completed_at' => 0,
                'active' => $this->bool_value(\Arr::get($val, 'active', true)),
            ];

            # SE CREA O ACTUALIZA
            if ($id > 0) {
                $action = Model_Core_Ar_Collection_Action::find($id);
                if (!$action) {
                    return $this->json_response(['error' => 'Gestion no encontrada.'], 404);
                }
                $old = $action->to_array();
                $action->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_action_folio();
                $data['created_by'] = $this->user_id;
                $action = Model_Core_Ar_Collection_Action::forge($data);
            }
            if ($status === 'done') {
                $action->completed_by = $this->user_id;
                $action->completed_at = time();
            }
            $action->save();

            # SE SINCRONIZA ESTADO CREDITICIO DEL CLIENTE
            $this->sync_customer_status($party_id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'receivables',
                'action' => $id > 0 ? 'update_collection_action' : 'create_collection_action',
                'entity_type' => 'ar_collection_action',
                'entity_id' => (int) $action->id,
                'summary' => 'Gestion de cobranza '.$action->folio,
                'old_values' => $old,
                'new_values' => $action->to_array(),
            ]);

            return $this->json_response([
                'status' => 'ok',
                'customers' => $this->customers(),
                'documents' => $this->documents(),
                'actions' => $this->actions(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando gestion de cobranza: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la gestion.'], 400);
        }
    }

    /**
     * SAVE CUSTOMER STATUS
     *
     * ACTUALIZA ESTADO DE CREDITO DEL CLIENTE
     *
     * @access  public
     * @return  Response
     */
    public function action_save_customer_status()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('receivables.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            $party_id = (int) \Arr::get($val, 'party_id', 0);
            if ($party_id < 1) {
                return $this->json_response(['error' => 'Selecciona un cliente.'], 422);
            }

            # SE OBTIENE O CREA ESTADO
            $status = Model_Core_Ar_Customer_Status::query()->where('party_id', $party_id)->get_one();
            if (!$status) {
                $status = Model_Core_Ar_Customer_Status::forge(['party_id' => $party_id]);
                $old = [];
            } else {
                $old = $status->to_array();
            }
            $status->credit_status = $this->credit_status(\Arr::get($val, 'credit_status', 'normal'));
            $status->credit_limit = round((float) \Arr::get($val, 'credit_limit', 0), 2);
            $status->credit_days = (int) \Arr::get($val, 'credit_days', 0);
            $status->notes = trim((string) \Arr::get($val, 'notes', ''));
            $status->reviewed_by = $this->user_id;
            $status->last_review_at = time();
            $status->active = 1;
            $status->save();
            $this->sync_customer_status($party_id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'receivables',
                'action' => 'update_customer_credit_status',
                'entity_type' => 'ar_customer_status',
                'entity_id' => (int) $status->id,
                'summary' => 'Estado de credito cliente #'.$party_id,
                'old_values' => $old,
                'new_values' => $status->to_array(),
            ]);

            return $this->json_response(['status' => 'ok', 'customers' => $this->customers(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error guardando estado de credito: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar estado de credito.'], 400);
        }
    }

    protected function customers()
    {
        $rows = \DB::select(
                ['p.id', 'party_id'], ['p.name', 'party_name'], ['p.rfc', 'rfc'],
                ['p.credit_limit', 'party_credit_limit'], ['p.credit_days', 'party_credit_days'],
                [\DB::expr("COUNT(CASE WHEN i.invoice_type = 'sale' AND i.active = 1 AND i.balance_due > 0 THEN i.id END)"), 'documents_count'],
                [\DB::expr("COALESCE(SUM(CASE WHEN i.invoice_type = 'sale' AND i.active = 1 AND i.balance_due > 0 THEN i.balance_due ELSE 0 END),0)"), 'balance_due'],
                [\DB::expr("COALESCE(SUM(CASE WHEN i.invoice_type = 'sale' AND i.active = 1 AND i.balance_due > 0 AND i.due_date <> '' AND i.due_date < CURDATE() THEN i.balance_due ELSE 0 END),0)"), 'overdue_balance'],
                ['s.credit_status', 'credit_status'], ['s.notes', 'credit_notes'], ['s.credit_limit', 'managed_credit_limit'], ['s.credit_days', 'managed_credit_days']
            )
            ->from(['core_parties', 'p'])
            ->join(['core_billing_invoices', 'i'], 'left')->on('i.party_id', '=', 'p.id')
            ->join(['core_ar_customer_statuses', 's'], 'left')->on('s.party_id', '=', 'p.id')
            ->where('p.active', '=', 1)
            ->where('p.party_type', 'in', ['customer', 'both'])
            ->group_by('p.id')
            ->order_by(\DB::expr('balance_due'), 'desc')
            ->execute()
            ->as_array();

        foreach ($rows as &$row) {
            $row['credit_status'] = $row['credit_status'] ?: 'normal';
            $row['credit_limit'] = (float) $row['managed_credit_limit'] > 0 ? (float) $row['managed_credit_limit'] : (float) $row['party_credit_limit'];
            $row['credit_days'] = (int) $row['managed_credit_days'] > 0 ? (int) $row['managed_credit_days'] : (int) $row['party_credit_days'];
            $row['available_credit'] = round((float) $row['credit_limit'] - (float) $row['balance_due'], 2);
        }
        return $rows;
    }

    protected function documents()
    {
        return \DB::select(['i.id', 'id'], ['i.folio', 'folio'], ['i.party_id', 'party_id'], ['p.name', 'party_name'], ['i.issue_date', 'issue_date'], ['i.due_date', 'due_date'], ['i.currency_code', 'currency_code'], ['i.total', 'total'], ['i.balance_due', 'balance_due'], ['i.status', 'status'], ['i.uuid', 'uuid'])
            ->from(['core_billing_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('i.balance_due', '>', 0)
            ->order_by('i.due_date', 'asc')
            ->order_by('i.id', 'desc')
            ->limit(300)
            ->execute()
            ->as_array();
    }

    protected function actions()
    {
        return \DB::select(['a.id', 'id'], ['a.folio', 'folio'], ['a.party_id', 'party_id'], ['p.name', 'party_name'], ['a.invoice_id', 'invoice_id'], ['i.folio', 'invoice_folio'], ['a.action_type', 'action_type'], ['a.status', 'status'], ['a.priority', 'priority'], ['a.action_date', 'action_date'], ['a.next_action_date', 'next_action_date'], ['a.promise_date', 'promise_date'], ['a.promise_amount', 'promise_amount'], ['a.result', 'result'], ['a.notes', 'notes'], ['u.username', 'assigned_user_name'])
            ->from(['core_ar_collection_actions', 'a'])
            ->join(['core_parties', 'p'], 'left')->on('a.party_id', '=', 'p.id')
            ->join(['core_billing_invoices', 'i'], 'left')->on('a.invoice_id', '=', 'i.id')
            ->join(['users', 'u'], 'left')->on('a.assigned_user_id', '=', 'u.id')
            ->where('a.active', '=', 1)
            ->order_by('a.action_date', 'desc')
            ->order_by('a.id', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();
    }

    protected function options()
    {
        return [
            'customers' => $this->customer_options(),
            'documents' => $this->document_options(),
            'users' => $this->user_options(),
        ];
    }

    protected function stats()
    {
        $documents = $this->documents();
        $total = 0;
        $overdue = 0;
        foreach ($documents as $document) {
            $total += (float) $document['balance_due'];
            if (!empty($document['due_date']) && $document['due_date'] < date('Y-m-d')) {
                $overdue += (float) $document['balance_due'];
            }
        }
        return [
            'documents' => count($documents),
            'balance_due' => round($total, 2),
            'overdue_balance' => round($overdue, 2),
            'actions_pending' => (int) \DB::select()->from('core_ar_collection_actions')->where('status', '!=', 'done')->where('active', '=', 1)->execute()->count(),
        ];
    }

    protected function customer_options()
    {
        $items = [];
        foreach (\DB::select('id', 'name')->from('core_parties')->where('active', '=', 1)->where('party_type', 'in', ['customer', 'both'])->order_by('name', 'asc')->execute() as $row) {
            $items[] = ['value' => (string) $row['id'], 'label' => (string) $row['name']];
        }
        return $items;
    }

    protected function document_options()
    {
        $items = [];
        foreach ($this->documents() as $row) {
            $items[] = ['value' => (string) $row['id'], 'label' => $row['folio'].' - '.$row['party_name'].' - $'.number_format((float) $row['balance_due'], 2)];
        }
        return $items;
    }

    protected function user_options()
    {
        $items = [];
        foreach (\DB::select('id', 'username')->from('users')->order_by('username', 'asc')->execute() as $row) {
            $items[] = ['value' => (string) $row['id'], 'label' => (string) $row['username']];
        }
        return $items;
    }

    protected function sync_customer_status($party_id)
    {
        $row = \DB::select([\DB::expr('COALESCE(SUM(balance_due),0)'), 'balance_due'], [\DB::expr("COALESCE(SUM(CASE WHEN due_date <> '' AND due_date < CURDATE() THEN balance_due ELSE 0 END),0)"), 'overdue_balance'])
            ->from('core_billing_invoices')
            ->where('party_id', '=', (int) $party_id)
            ->where('invoice_type', '=', 'sale')
            ->where('active', '=', 1)
            ->where('balance_due', '>', 0)
            ->execute()
            ->current();

        $status = Model_Core_Ar_Customer_Status::query()->where('party_id', (int) $party_id)->get_one();
        if (!$status) {
            $party = \DB::select('credit_limit', 'credit_days')->from('core_parties')->where('id', '=', (int) $party_id)->execute()->current();
            $status = Model_Core_Ar_Customer_Status::forge([
                'party_id' => (int) $party_id,
                'credit_status' => 'normal',
                'credit_limit' => $party ? (float) $party['credit_limit'] : 0,
                'credit_days' => $party ? (int) $party['credit_days'] : 0,
                'active' => 1,
            ]);
        }
        $status->current_balance = round((float) $row['balance_due'], 2);
        $status->overdue_balance = round((float) $row['overdue_balance'], 2);
        $status->save();
    }

    protected function next_action_folio()
    {
        return 'COB-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_ar_collection_actions') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_ar_customer_statuses', 'core_ar_collection_actions', 'core_billing_invoices', 'core_parties'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de cuentas por cobrar.');
            }
        }
    }

    protected function action_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['call', 'email', 'whatsapp', 'visit', 'promise', 'note'], true) ? $value : 'call';
    }

    protected function action_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['pending', 'scheduled', 'done', 'cancelled'], true) ? $value : 'pending';
    }

    protected function priority($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['low', 'normal', 'high', 'urgent'], true) ? $value : 'normal';
    }

    protected function credit_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['normal', 'watch', 'hold', 'blocked'], true) ? $value : 'normal';
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
