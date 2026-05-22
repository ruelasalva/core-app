<?php

/**
 * CONTROLADOR ADMIN_COMMISSIONS
 *
 * Administra vendedores, planes, reglas, cuotas y liquidaciones de comisiones.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Commissions extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMINISTRATIVA Y PERMISO DE COMISIONES.
     *
     * @return  Void
     */
    public function before()
    {
        parent::before();
        $this->require_access('commissions.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE COMISIONES.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->template->title = 'Comisiones';
        $this->template->content = \View::forge('admin/commissions/index');
    }

    /**
     * DATA
     *
     * ENTREGA VENDEDORES, REGLAS, CUOTAS Y MOVIMIENTOS.
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            $this->assert_schema_ready();

            return $this->json_response([
                'sellers' => $this->sellers(),
                'plans' => $this->plans(),
                'rules' => $this->rules(),
                'quotas' => $this->quotas(),
                'entries' => $this->entries(),
                'settlements' => $this->settlements(),
                'options' => $this->options(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando comisiones: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar comisiones.'], 500);
        }
    }

    /**
     * SAVE SELLER
     *
     * CREA O ACTUALIZA UN VENDEDOR INTERNO O REVENDEDOR.
     *
     * @access  public
     * @return  Response
     */
    public function action_save_seller()
    {
        $this->require_access('commissions.access[edit]');
        $val = (array) \Input::json();

        try {
            $name = trim((string) \Arr::get($val, 'name', ''));
            if ($name === '') {
                return $this->json_response(['error' => 'El nombre del vendedor es obligatorio.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'code' => $this->unique_code('VEN', 'core_sales_sellers', 'code', trim((string) \Arr::get($val, 'code', '')), $id),
                'name' => $name,
                'seller_type' => $this->seller_type(\Arr::get($val, 'seller_type', 'employee')),
                'employee_id' => (int) \Arr::get($val, 'employee_id', 0),
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'user_id' => (int) \Arr::get($val, 'user_id', 0),
                'default_commission_plan_id' => (int) \Arr::get($val, 'default_commission_plan_id', 0),
                'base_commission_percent' => max(0, (float) \Arr::get($val, 'base_commission_percent', 0)),
                'quota_commission_percent' => max(0, (float) \Arr::get($val, 'quota_commission_percent', 0)),
                'payment_commission_percent' => max(0, (float) \Arr::get($val, 'payment_commission_percent', 0)),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($data['seller_type'] === 'employee' && $data['employee_id'] < 1) {
                return $this->json_response(['error' => 'Selecciona el empleado que funcionara como vendedor.'], 422);
            }
            if ($data['seller_type'] === 'reseller' && $data['party_id'] < 1) {
                return $this->json_response(['error' => 'Selecciona el tercero revendedor.'], 422);
            }

            if ($id > 0) {
                $seller = \Model_Core_Sales_Seller::find($id);
                if (!$seller) {
                    return $this->json_response(['error' => 'Vendedor no encontrado.'], 404);
                }
                $old = $seller->to_array();
                $seller->set($data);
            } else {
                $old = [];
                $seller = \Model_Core_Sales_Seller::forge($data);
            }

            $seller->save();
            $this->audit('save_seller', 'sales_seller', $seller, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando vendedor: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el vendedor.'], 400);
        }
    }

    /**
     * SAVE PLAN
     *
     * CREA O ACTUALIZA UN PLAN DE COMISION.
     *
     * @access  public
     * @return  Response
     */
    public function action_save_plan()
    {
        $this->require_access('commissions.access[edit]');
        $val = (array) \Input::json();

        try {
            $name = trim((string) \Arr::get($val, 'name', ''));
            if ($name === '') {
                return $this->json_response(['error' => 'El nombre del plan es obligatorio.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'code' => $this->unique_code('COM-PLAN', 'core_commission_plans', 'code', trim((string) \Arr::get($val, 'code', '')), $id),
                'name' => $name,
                'applies_to' => $this->applies_to(\Arr::get($val, 'applies_to', 'all')),
                'valid_from' => trim((string) \Arr::get($val, 'valid_from', '')),
                'valid_until' => trim((string) \Arr::get($val, 'valid_until', '')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            $plan = $id > 0 ? \Model_Core_Commission_Plan::find($id) : null;
            $old = $plan ? $plan->to_array() : [];
            if ($plan) {
                $plan->set($data);
            } else {
                $plan = \Model_Core_Commission_Plan::forge($data);
            }
            $plan->save();
            $this->audit('save_plan', 'commission_plan', $plan, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando plan de comision: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el plan.'], 400);
        }
    }

    /**
     * SAVE RULE
     *
     * CREA O ACTUALIZA UNA REGLA DE COMISION.
     *
     * @access  public
     * @return  Response
     */
    public function action_save_rule()
    {
        $this->require_access('commissions.access[edit]');
        $val = (array) \Input::json();

        try {
            $name = trim((string) \Arr::get($val, 'name', ''));
            if ($name === '') {
                return $this->json_response(['error' => 'El nombre de la regla es obligatorio.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'plan_id' => (int) \Arr::get($val, 'plan_id', 0),
                'code' => $this->unique_code('COM-REG', 'core_commission_rules', 'code', trim((string) \Arr::get($val, 'code', '')), $id),
                'name' => $name,
                'rule_scope' => $this->rule_scope(\Arr::get($val, 'rule_scope', 'general')),
                'seller_id' => (int) \Arr::get($val, 'seller_id', 0),
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'product_id' => (int) \Arr::get($val, 'product_id', 0),
                'brand_id' => (int) \Arr::get($val, 'brand_id', 0),
                'category_id' => (int) \Arr::get($val, 'category_id', 0),
                'subcategory_id' => (int) \Arr::get($val, 'subcategory_id', 0),
                'trigger_event' => $this->trigger_event(\Arr::get($val, 'trigger_event', 'sale')),
                'calculation_base' => $this->calculation_base(\Arr::get($val, 'calculation_base', 'line_total')),
                'value_type' => $this->value_type(\Arr::get($val, 'value_type', 'percent')),
                'value' => max(0, (float) \Arr::get($val, 'value', 0)),
                'min_quantity' => max(0, (float) \Arr::get($val, 'min_quantity', 0)),
                'min_amount' => max(0, (float) \Arr::get($val, 'min_amount', 0)),
                'priority' => (int) \Arr::get($val, 'priority', 100),
                'stackable' => (int) (bool) \Arr::get($val, 'stackable', true),
                'valid_from' => trim((string) \Arr::get($val, 'valid_from', '')),
                'valid_until' => trim((string) \Arr::get($val, 'valid_until', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            $rule = $id > 0 ? \Model_Core_Commission_Rule::find($id) : null;
            $old = $rule ? $rule->to_array() : [];
            if ($rule) {
                $rule->set($data);
            } else {
                $rule = \Model_Core_Commission_Rule::forge($data);
            }
            $rule->save();
            $this->audit('save_rule', 'commission_rule', $rule, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando regla de comision: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la regla.'], 400);
        }
    }

    /**
     * SAVE QUOTA
     *
     * CREA O ACTUALIZA UNA CUOTA DE VENDEDOR.
     *
     * @access  public
     * @return  Response
     */
    public function action_save_quota()
    {
        $this->require_access('commissions.access[edit]');
        $val = (array) \Input::json();

        try {
            $seller_id = (int) \Arr::get($val, 'seller_id', 0);
            if ($seller_id < 1) {
                return $this->json_response(['error' => 'Selecciona vendedor para la cuota.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'seller_id' => $seller_id,
                'plan_id' => (int) \Arr::get($val, 'plan_id', 0),
                'period_code' => trim((string) \Arr::get($val, 'period_code', date('Ym'))),
                'date_from' => trim((string) \Arr::get($val, 'date_from', date('Y-m-01'))),
                'date_to' => trim((string) \Arr::get($val, 'date_to', date('Y-m-t'))),
                'target_amount' => max(0, (float) \Arr::get($val, 'target_amount', 0)),
                'target_quantity' => max(0, (float) \Arr::get($val, 'target_quantity', 0)),
                'bonus_percent' => max(0, (float) \Arr::get($val, 'bonus_percent', 0)),
                'bonus_amount' => max(0, (float) \Arr::get($val, 'bonus_amount', 0)),
                'status' => $this->quota_status(\Arr::get($val, 'status', 'open')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            $quota = $id > 0 ? \Model_Core_Commission_Quota::find($id) : null;
            $old = $quota ? $quota->to_array() : [];
            if ($quota) {
                $quota->set($data);
            } else {
                $quota = \Model_Core_Commission_Quota::forge($data);
            }
            $quota->save();
            $this->audit('save_quota', 'commission_quota', $quota, $old);

            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando cuota de comision: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la cuota.'], 400);
        }
    }

    /**
     * SAVE ADJUSTMENT
     *
     * AGREGA UN AJUSTE MANUAL COMO MOVIMIENTO DE COMISION.
     *
     * @access  public
     * @return  Response
     */
    public function action_save_adjustment()
    {
        $this->require_access('commissions.access[authorize]');
        $val = (array) \Input::json();

        try {
            $seller_id = (int) \Arr::get($val, 'seller_id', 0);
            $amount = (float) \Arr::get($val, 'amount', 0);
            $reason = trim((string) \Arr::get($val, 'reason', ''));
            if ($seller_id < 1 || abs($amount) <= 0 || $reason === '') {
                return $this->json_response(['error' => 'Selecciona vendedor, importe y motivo del ajuste.'], 422);
            }

            $adjustment = \Model_Core_Commission_Adjustment::forge([
                'entry_id' => (int) \Arr::get($val, 'entry_id', 0),
                'settlement_id' => (int) \Arr::get($val, 'settlement_id', 0),
                'seller_id' => $seller_id,
                'adjustment_type' => $this->codeify(\Arr::get($val, 'adjustment_type', 'manual')),
                'amount' => round($amount, 2),
                'reason' => $reason,
                'created_by' => $this->user_id,
                'active' => 1,
            ]);
            $adjustment->save();

            \Model_Core_Commission_Entry::forge([
                'seller_id' => $seller_id,
                'plan_id' => 0,
                'rule_id' => 0,
                'quota_id' => 0,
                'trigger_event' => 'adjustment',
                'source_module' => 'commissions',
                'source_entity_type' => 'commission_adjustment',
                'source_entity_id' => (int) $adjustment->id,
                'source_item_id' => 0,
                'party_id' => 0,
                'product_id' => 0,
                'currency_code' => 'MXN',
                'base_amount' => round($amount, 2),
                'commission_percent' => 0,
                'commission_amount' => round($amount, 2),
                'status' => 'earned',
                'earned_at' => time(),
                'settlement_id' => 0,
                'notes' => $reason,
                'created_by' => $this->user_id,
                'active' => 1,
            ])->save();

            $this->audit('save_adjustment', 'commission_adjustment', $adjustment, []);
            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error guardando ajuste de comision: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el ajuste.'], 400);
        }
    }

    /**
     * GENERATE FROM ORDER
     *
     * CALCULA COMISIONES DE VENTA PARA UN PEDIDO.
     *
     * @access  public
     * @return  Response
     */
    public function action_generate_from_order()
    {
        $this->require_access('commissions.access[create]');
        $val = (array) \Input::json();

        try {
            $order_id = (int) \Arr::get($val, 'order_id', 0);
            if ($order_id < 1) {
                return $this->json_response(['error' => 'Selecciona el pedido.'], 422);
            }

            $created = $this->generate_commissions_for_order($order_id, 'sale');
            return $this->json_response(['status' => 'ok', 'created' => $created, 'entries' => $this->entries(), 'stats' => $this->stats()]);
        } catch (\Exception $e) {
            \Log::error('Error generando comisiones de pedido: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron generar comisiones: '.$e->getMessage()], 400);
        }
    }

    /**
     * CREATE SETTLEMENT
     *
     * CREA UNA LIQUIDACION CON COMISIONES GANADAS Y SIN LIQUIDAR.
     *
     * @access  public
     * @return  Response
     */
    public function action_create_settlement()
    {
        $this->require_access('commissions.access[authorize]');
        $val = (array) \Input::json();

        try {
            $seller_id = (int) \Arr::get($val, 'seller_id', 0);
            $date_from = trim((string) \Arr::get($val, 'date_from', date('Y-m-01')));
            $date_to = trim((string) \Arr::get($val, 'date_to', date('Y-m-t')));
            if ($seller_id < 1) {
                return $this->json_response(['error' => 'Selecciona vendedor para liquidar.'], 422);
            }

            $entries = \DB::select('id', 'commission_amount')
                ->from('core_commission_entries')
                ->where('seller_id', '=', $seller_id)
                ->where('status', '=', 'earned')
                ->where('settlement_id', '=', 0)
                ->where('active', '=', 1)
                ->where('created_at', '>=', strtotime($date_from.' 00:00:00'))
                ->where('created_at', '<=', strtotime($date_to.' 23:59:59'))
                ->execute()
                ->as_array();

            if (empty($entries)) {
                return $this->json_response(['error' => 'No hay comisiones ganadas pendientes para ese periodo.'], 422);
            }

            $subtotal = 0;
            foreach ($entries as $entry) {
                $subtotal += (float) $entry['commission_amount'];
            }

            $settlement = \Model_Core_Commission_Settlement::forge([
                'folio' => $this->next_code('COM-LIQ', 'core_commission_settlements', 'folio'),
                'seller_id' => $seller_id,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'currency_code' => 'MXN',
                'subtotal' => round($subtotal, 2),
                'adjustment_total' => 0,
                'total' => round($subtotal, 2),
                'status' => 'draft',
                'payment_id' => 0,
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'created_by' => $this->user_id,
                'active' => 1,
            ]);
            $settlement->save();

            foreach ($entries as $entry) {
                \DB::update('core_commission_entries')
                    ->set(['settlement_id' => (int) $settlement->id, 'status' => 'settled', 'updated_at' => time()])
                    ->where('id', '=', (int) $entry['id'])
                    ->execute();
            }

            $this->audit('create_settlement', 'commission_settlement', $settlement, []);
            return $this->action_data();
        } catch (\Exception $e) {
            \Log::error('Error creando liquidacion de comisiones: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear la liquidacion.'], 400);
        }
    }

    protected function sellers()
    {
        return \DB::select(['s.id', 'id'], ['s.code', 'code'], ['s.name', 'name'], ['s.seller_type', 'seller_type'], ['s.employee_id', 'employee_id'], ['e.full_name', 'employee_name'], ['s.party_id', 'party_id'], ['p.name', 'party_name'], ['s.user_id', 'user_id'], ['u.username', 'username'], ['s.default_commission_plan_id', 'default_commission_plan_id'], ['cp.name', 'plan_name'], ['s.base_commission_percent', 'base_commission_percent'], ['s.quota_commission_percent', 'quota_commission_percent'], ['s.payment_commission_percent', 'payment_commission_percent'], ['s.active', 'active'])
            ->from(['core_sales_sellers', 's'])
            ->join(['core_employees', 'e'], 'left')->on('s.employee_id', '=', 'e.id')
            ->join(['core_parties', 'p'], 'left')->on('s.party_id', '=', 'p.id')
            ->join(['users', 'u'], 'left')->on('s.user_id', '=', 'u.id')
            ->join(['core_commission_plans', 'cp'], 'left')->on('s.default_commission_plan_id', '=', 'cp.id')
            ->order_by('s.name', 'asc')
            ->execute()
            ->as_array();
    }

    protected function plans()
    {
        return \DB::select()->from('core_commission_plans')->where('active', '=', 1)->order_by('name', 'asc')->execute()->as_array();
    }

    protected function rules()
    {
        return \DB::select(['r.id', 'id'], ['r.plan_id', 'plan_id'], ['p.name', 'plan_name'], ['r.code', 'code'], ['r.name', 'name'], ['r.rule_scope', 'rule_scope'], ['r.seller_id', 'seller_id'], ['s.name', 'seller_name'], ['r.party_id', 'party_id'], ['pa.name', 'party_name'], ['r.product_id', 'product_id'], ['pr.name', 'product_name'], ['r.brand_id', 'brand_id'], ['b.name', 'brand_name'], ['r.category_id', 'category_id'], ['c.name', 'category_name'], ['r.subcategory_id', 'subcategory_id'], ['sc.name', 'subcategory_name'], ['r.trigger_event', 'trigger_event'], ['r.calculation_base', 'calculation_base'], ['r.value_type', 'value_type'], ['r.value', 'value'], ['r.min_quantity', 'min_quantity'], ['r.min_amount', 'min_amount'], ['r.priority', 'priority'], ['r.stackable', 'stackable'], ['r.valid_from', 'valid_from'], ['r.valid_until', 'valid_until'], ['r.active', 'active'])
            ->from(['core_commission_rules', 'r'])
            ->join(['core_commission_plans', 'p'], 'left')->on('r.plan_id', '=', 'p.id')
            ->join(['core_sales_sellers', 's'], 'left')->on('r.seller_id', '=', 's.id')
            ->join(['core_parties', 'pa'], 'left')->on('r.party_id', '=', 'pa.id')
            ->join(['core_commerce_products', 'pr'], 'left')->on('r.product_id', '=', 'pr.id')
            ->join(['core_commerce_brands', 'b'], 'left')->on('r.brand_id', '=', 'b.id')
            ->join(['core_commerce_categories', 'c'], 'left')->on('r.category_id', '=', 'c.id')
            ->join(['core_commerce_subcategories', 'sc'], 'left')->on('r.subcategory_id', '=', 'sc.id')
            ->where('r.active', '=', 1)
            ->order_by('r.priority', 'asc')
            ->order_by('r.id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function quotas()
    {
        return \DB::select(['q.id', 'id'], ['q.seller_id', 'seller_id'], ['s.name', 'seller_name'], ['q.plan_id', 'plan_id'], ['p.name', 'plan_name'], ['q.period_code', 'period_code'], ['q.date_from', 'date_from'], ['q.date_to', 'date_to'], ['q.target_amount', 'target_amount'], ['q.target_quantity', 'target_quantity'], ['q.bonus_percent', 'bonus_percent'], ['q.bonus_amount', 'bonus_amount'], ['q.status', 'status'], ['q.active', 'active'])
            ->from(['core_commission_quotas', 'q'])
            ->join(['core_sales_sellers', 's'], 'left')->on('q.seller_id', '=', 's.id')
            ->join(['core_commission_plans', 'p'], 'left')->on('q.plan_id', '=', 'p.id')
            ->where('q.active', '=', 1)
            ->order_by('q.date_from', 'desc')
            ->execute()
            ->as_array();
    }

    protected function entries()
    {
        return \DB::select(['e.id', 'id'], ['e.seller_id', 'seller_id'], ['s.name', 'seller_name'], ['e.rule_id', 'rule_id'], ['r.name', 'rule_name'], ['e.trigger_event', 'trigger_event'], ['e.source_entity_type', 'source_entity_type'], ['e.source_entity_id', 'source_entity_id'], ['e.source_item_id', 'source_item_id'], ['e.party_id', 'party_id'], ['p.name', 'party_name'], ['e.product_id', 'product_id'], ['pr.name', 'product_name'], ['e.currency_code', 'currency_code'], ['e.base_amount', 'base_amount'], ['e.commission_percent', 'commission_percent'], ['e.commission_amount', 'commission_amount'], ['e.status', 'status'], ['e.settlement_id', 'settlement_id'], ['e.notes', 'notes'], ['e.created_at', 'created_at'])
            ->from(['core_commission_entries', 'e'])
            ->join(['core_sales_sellers', 's'], 'left')->on('e.seller_id', '=', 's.id')
            ->join(['core_commission_rules', 'r'], 'left')->on('e.rule_id', '=', 'r.id')
            ->join(['core_parties', 'p'], 'left')->on('e.party_id', '=', 'p.id')
            ->join(['core_commerce_products', 'pr'], 'left')->on('e.product_id', '=', 'pr.id')
            ->where('e.active', '=', 1)
            ->order_by('e.id', 'desc')
            ->limit(300)
            ->execute()
            ->as_array();
    }

    protected function settlements()
    {
        return \DB::select(['l.id', 'id'], ['l.folio', 'folio'], ['l.seller_id', 'seller_id'], ['s.name', 'seller_name'], ['l.date_from', 'date_from'], ['l.date_to', 'date_to'], ['l.currency_code', 'currency_code'], ['l.subtotal', 'subtotal'], ['l.adjustment_total', 'adjustment_total'], ['l.total', 'total'], ['l.status', 'status'], ['l.payment_id', 'payment_id'], ['l.notes', 'notes'], ['l.created_at', 'created_at'])
            ->from(['core_commission_settlements', 'l'])
            ->join(['core_sales_sellers', 's'], 'left')->on('l.seller_id', '=', 's.id')
            ->where('l.active', '=', 1)
            ->order_by('l.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function options()
    {
        return [
            'sellers' => $this->select_options('core_sales_sellers', 'id', 'name'),
            'plans' => $this->select_options('core_commission_plans', 'id', 'name'),
            'employees' => $this->select_options('core_employees', 'id', 'full_name'),
            'users' => $this->select_options('users', 'id', 'username', false),
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'customers' => $this->party_options(['customer', 'both']),
            'resellers' => $this->party_options(['reseller', 'partner', 'customer', 'both']),
            'products' => $this->select_options('core_commerce_products', 'id', 'name'),
            'brands' => $this->select_options('core_commerce_brands', 'id', 'name'),
            'categories' => $this->select_options('core_commerce_categories', 'id', 'name'),
            'subcategories' => $this->select_options('core_commerce_subcategories', 'id', 'name'),
            'orders' => $this->order_options(),
        ];
    }

    protected function stats()
    {
        $pending = \DB::select([\DB::expr('COALESCE(SUM(commission_amount),0)'), 'total'])->from('core_commission_entries')->where('status', '=', 'pending')->where('active', '=', 1)->execute()->current();
        $earned = \DB::select([\DB::expr('COALESCE(SUM(commission_amount),0)'), 'total'])->from('core_commission_entries')->where('status', '=', 'earned')->where('active', '=', 1)->execute()->current();
        $settled = \DB::select([\DB::expr('COALESCE(SUM(commission_amount),0)'), 'total'])->from('core_commission_entries')->where('status', '=', 'settled')->where('active', '=', 1)->execute()->current();

        return [
            'sellers' => (int) \DB::select()->from('core_sales_sellers')->where('active', '=', 1)->execute()->count(),
            'rules' => (int) \DB::select()->from('core_commission_rules')->where('active', '=', 1)->execute()->count(),
            'pending' => (float) $pending['total'],
            'earned' => (float) $earned['total'],
            'settled' => (float) $settled['total'],
        ];
    }

    protected function generate_commissions_for_order($order_id, $trigger_event)
    {
        $order = \DB::select()->from('core_sales_orders')->where('id', '=', (int) $order_id)->where('active', '=', 1)->execute()->current();
        if (!$order) {
            throw new \RuntimeException('Pedido no encontrado.');
        }

        $seller_id = (int) $order['seller_id'];
        if ($seller_id < 1) {
            $seller_id = $this->resolve_seller_id((int) $order['party_id'], (int) $order['created_by']);
            \DB::update('core_sales_orders')->set(['seller_id' => $seller_id, 'updated_at' => time()])->where('id', '=', (int) $order_id)->execute();
        }
        if ($seller_id < 1) {
            throw new \RuntimeException('El pedido no tiene vendedor asignado.');
        }

        $created = 0;
        $items = \DB::select(['i.id', 'id'], ['i.product_id', 'product_id'], ['i.quantity', 'quantity'], ['i.line_total', 'line_total'], ['i.currency_code', 'currency_code'], ['p.brand_id', 'brand_id'], ['p.category_id', 'category_id'], ['p.subcategory_id', 'subcategory_id'])
            ->from(['core_sales_order_items', 'i'])
            ->join(['core_commerce_products', 'p'], 'left')->on('i.product_id', '=', 'p.id')
            ->where('i.order_id', '=', (int) $order_id)
            ->execute()
            ->as_array();

        foreach ($items as $item) {
            $rules = $this->matching_rules($seller_id, (int) $order['party_id'], $item, $trigger_event);
            foreach ($rules as $rule) {
                $exists = \DB::select('id')
                    ->from('core_commission_entries')
                    ->where('source_entity_type', '=', 'sales_order')
                    ->where('source_entity_id', '=', (int) $order_id)
                    ->where('source_item_id', '=', (int) $item['id'])
                    ->where('rule_id', '=', (int) $rule['id'])
                    ->where('trigger_event', '=', $trigger_event)
                    ->execute()
                    ->current();
                if ($exists) {
                    continue;
                }

                $base = $this->commission_base($rule, $item);
                $amount = $rule['value_type'] === 'fixed' ? (float) $rule['value'] : round($base * ((float) $rule['value'] / 100), 2);
                if ($amount <= 0) {
                    continue;
                }

                \Model_Core_Commission_Entry::forge([
                    'seller_id' => $seller_id,
                    'plan_id' => (int) $rule['plan_id'],
                    'rule_id' => (int) $rule['id'],
                    'quota_id' => 0,
                    'trigger_event' => $trigger_event,
                    'source_module' => 'sales',
                    'source_entity_type' => 'sales_order',
                    'source_entity_id' => (int) $order_id,
                    'source_item_id' => (int) $item['id'],
                    'party_id' => (int) $order['party_id'],
                    'product_id' => (int) $item['product_id'],
                    'currency_code' => (string) $item['currency_code'],
                    'base_amount' => round($base, 2),
                    'commission_percent' => $rule['value_type'] === 'percent' ? (float) $rule['value'] : 0,
                    'commission_amount' => round($amount, 2),
                    'status' => $trigger_event === 'payment' ? 'pending' : 'earned',
                    'earned_at' => $trigger_event === 'payment' ? 0 : time(),
                    'settlement_id' => 0,
                    'notes' => 'Generada por regla '.$rule['name'],
                    'created_by' => $this->user_id,
                    'active' => 1,
                ])->save();
                $created++;

                if ((int) $rule['stackable'] === 0) {
                    break;
                }
            }
        }

        if ($created > 0) {
            \Helper_Core_Audit::log([
                'module' => 'commissions',
                'action' => 'generate_from_order',
                'business_event' => 'commissions.generated_from_order',
                'entity_type' => 'sales_order',
                'entity_id' => (int) $order_id,
                'summary' => 'Comisiones generadas desde pedido '.$order['folio'],
                'new_values' => ['created' => $created, 'trigger_event' => $trigger_event],
            ]);
        }

        return $created;
    }

    protected function matching_rules($seller_id, $party_id, array $item, $trigger_event)
    {
        $today = date('Y-m-d');
        $rules = \DB::select()
            ->from('core_commission_rules')
            ->where('active', '=', 1)
            ->where('trigger_event', '=', $trigger_event)
            ->order_by('priority', 'asc')
            ->order_by('id', 'desc')
            ->execute()
            ->as_array();

        $matches = [];
        foreach ($rules as $rule) {
            if ((int) $rule['seller_id'] > 0 && (int) $rule['seller_id'] !== (int) $seller_id) {
                continue;
            }
            if ((int) $rule['party_id'] > 0 && (int) $rule['party_id'] !== (int) $party_id) {
                continue;
            }
            foreach (['product_id', 'brand_id', 'category_id', 'subcategory_id'] as $field) {
                if ((int) $rule[$field] > 0 && (int) $rule[$field] !== (int) $item[$field]) {
                    continue 2;
                }
            }
            if ((float) $rule['min_quantity'] > 0 && (float) $item['quantity'] < (float) $rule['min_quantity']) {
                continue;
            }
            if ((float) $rule['min_amount'] > 0 && (float) $item['line_total'] < (float) $rule['min_amount']) {
                continue;
            }
            if ($rule['valid_from'] !== '' && $rule['valid_from'] > $today) {
                continue;
            }
            if ($rule['valid_until'] !== '' && $rule['valid_until'] < $today) {
                continue;
            }
            $matches[] = $rule;
        }

        return $matches;
    }

    protected function commission_base(array $rule, array $item)
    {
        if ($rule['calculation_base'] === 'quantity') {
            return (float) $item['quantity'];
        }
        return (float) $item['line_total'];
    }

    protected function resolve_seller_id($party_id, $user_id)
    {
        if ($party_id > 0 && \DBUtil::field_exists('core_parties', ['default_seller_id'])) {
            $party = \DB::select('default_seller_id', 'sales_user_id')->from('core_parties')->where('id', '=', (int) $party_id)->execute()->current();
            if ($party && (int) $party['default_seller_id'] > 0) {
                return (int) $party['default_seller_id'];
            }
            if ($party && (int) $party['sales_user_id'] > 0) {
                $seller = \DB::select('id')->from('core_sales_sellers')->where('user_id', '=', (int) $party['sales_user_id'])->where('active', '=', 1)->execute()->current();
                if ($seller) {
                    return (int) $seller['id'];
                }
            }
        }

        if ($user_id > 0) {
            $seller = \DB::select('id')->from('core_sales_sellers')->where('user_id', '=', (int) $user_id)->where('active', '=', 1)->execute()->current();
            if ($seller) {
                return (int) $seller['id'];
            }
        }

        return 0;
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

    protected function party_options(array $types)
    {
        $rows = [];
        if (!\DBUtil::table_exists('core_parties')) {
            return $rows;
        }
        foreach (\DB::select('id', 'name', 'party_type')->from('core_parties')->where('party_type', 'in', $types)->where('active', '=', 1)->order_by('name', 'asc')->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => $row['name'].' ('.$row['party_type'].')'];
        }
        return $rows;
    }

    protected function order_options()
    {
        $rows = [];
        foreach (\DB::select('id', 'folio', 'total', 'status')->from('core_sales_orders')->where('active', '=', 1)->order_by('id', 'desc')->limit(100)->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => $row['folio'].' - '.number_format((float) $row['total'], 2).' - '.$row['status']];
        }
        return $rows;
    }

    protected function unique_code($prefix, $table, $field, $value, $id)
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return $this->next_code($prefix, $table, $field);
        }

        $query = \DB::select('id')->from($table)->where($field, '=', $value);
        if ($id > 0) {
            $query->where('id', '!=', $id);
        }
        if ($query->execute()->current()) {
            return $this->next_code($prefix, $table, $field);
        }
        return $value;
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
            'module' => 'commissions',
            'action' => $action,
            'business_event' => 'commissions.'.$action,
            'entity_type' => $entity_type,
            'entity_id' => (int) $model->id,
            'summary' => ucfirst(str_replace('_', ' ', $action)).' '.$entity_type,
            'old_values' => $old,
            'new_values' => $model->to_array(),
        ]);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_sales_sellers', 'core_commission_plans', 'core_commission_rules', 'core_commission_quotas', 'core_commission_entries', 'core_commission_settlements', 'core_commission_adjustments'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de comisiones.');
            }
        }
        if (!\DBUtil::field_exists('core_sales_orders', ['seller_id'])) {
            throw new \RuntimeException('Falta actualizar ventas con vendedor.');
        }
    }

    protected function seller_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['employee', 'reseller', 'external'], true) ? $value : 'employee';
    }

    protected function applies_to($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['all', 'employees', 'resellers'], true) ? $value : 'all';
    }

    protected function rule_scope($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['general', 'seller', 'customer', 'product', 'brand', 'category', 'subcategory'], true) ? $value : 'general';
    }

    protected function trigger_event($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['sale', 'invoice', 'delivery', 'payment'], true) ? $value : 'sale';
    }

    protected function calculation_base($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['line_total', 'quantity'], true) ? $value : 'line_total';
    }

    protected function value_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['percent', 'fixed'], true) ? $value : 'percent';
    }

    protected function quota_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['open', 'met', 'missed', 'cancelled'], true) ? $value : 'open';
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
