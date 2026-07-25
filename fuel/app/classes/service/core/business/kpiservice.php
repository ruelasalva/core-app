<?php

/**
 * SERVICE CORE_BUSINESS_KPISERVICE
 *
 * Calcula KPIs read-only para Business Suite sin modificar datos fuente.
 *
 * @package  app
 */
class Service_Core_Business_KpiService
{
    protected $context = [];
    protected $warnings = [];
    protected $visibility;

    public function __construct(array $context = [])
    {
        $this->context = $context;
        $this->visibility = new \Service_Core_Crm_CustomerVisibility();
    }

    public function dashboard(array $filters)
    {
        return [
            'ventas_facturadas' => $this->sales_invoiced($filters),
            'cobranza' => $this->collections($filters),
            'cartera_pendiente' => $this->open_receivables($filters),
            'margen_estimado' => $this->estimated_margin($filters),
            'comisiones_actuales' => $this->commissions($filters),
            'rentas' => $this->rentals($filters),
            'gastos' => $this->expenses($filters),
            'flujo_efectivo' => $this->cashflow($filters),
        ];
    }

    public function warnings()
    {
        return $this->warnings;
    }

    protected function sales_invoiced(array $filters)
    {
        if (!$this->table('core_billing_invoices')) {
            return $this->empty_metric('Facturacion no disponible');
        }

        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'count'],
                [\DB::expr('COALESCE(SUM(total),0)'), 'total'],
                [\DB::expr('COALESCE(SUM(subtotal),0)'), 'subtotal'],
                [\DB::expr('COALESCE(SUM(tax_total),0)'), 'tax_total']
            )
            ->from('core_billing_invoices')
            ->where('invoice_type', '=', 'sale')
            ->where('active', '=', 1)
            ->where('issue_date', '>=', $filters['start_date'])
            ->where('issue_date', '<=', $filters['end_date']);

        $this->apply_party_scope($query, 'party_id');
        $row = $query->execute()->current() ?: [];

        return [
            'count' => (int) \Arr::get($row, 'count', 0),
            'subtotal' => $this->money(\Arr::get($row, 'subtotal', 0)),
            'tax_total' => $this->money(\Arr::get($row, 'tax_total', 0)),
            'total' => $this->money(\Arr::get($row, 'total', 0)),
        ];
    }

    protected function collections(array $filters)
    {
        if (!$this->table('core_payments')) {
            return $this->empty_metric('Pagos no disponibles');
        }

        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'count'],
                [\DB::expr('COALESCE(SUM(amount),0)'), 'total']
            )
            ->from('core_payments')
            ->where('payment_type', '=', 'received')
            ->where('active', '=', 1)
            ->where('payment_date', '>=', $filters['start_date'])
            ->where('payment_date', '<=', $filters['end_date']);

        if ($this->field('core_payments', 'status')) {
            $query->where('status', '!=', 'cancelled');
        }

        $this->apply_party_scope($query, 'party_id');
        $row = $query->execute()->current() ?: [];

        return [
            'count' => (int) \Arr::get($row, 'count', 0),
            'total' => $this->money(\Arr::get($row, 'total', 0)),
        ];
    }

    protected function open_receivables(array $filters)
    {
        if (!$this->table('core_billing_invoices')) {
            return $this->empty_metric('Cartera no disponible');
        }

        $today = date('Y-m-d');
        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'count'],
                [\DB::expr('COALESCE(SUM(balance_due),0)'), 'total'],
                [\DB::expr("COALESCE(SUM(CASE WHEN due_date <> '' AND due_date < '".$today."' THEN balance_due ELSE 0 END),0)"), 'overdue']
            )
            ->from('core_billing_invoices')
            ->where('invoice_type', '=', 'sale')
            ->where('active', '=', 1)
            ->where('balance_due', '>', 0);

        $this->apply_party_scope($query, 'party_id');
        $row = $query->execute()->current() ?: [];

        return [
            'count' => (int) \Arr::get($row, 'count', 0),
            'total' => $this->money(\Arr::get($row, 'total', 0)),
            'overdue' => $this->money(\Arr::get($row, 'overdue', 0)),
        ];
    }

    protected function estimated_margin(array $filters)
    {
        if (!$this->table('core_billing_invoice_items') || !$this->table('core_billing_invoices')) {
            return $this->empty_metric('Margen no disponible');
        }

        $cost_expr = $this->product_cost_expression();
        if ($cost_expr === null) {
            $this->warnings[] = 'Margen estimado limitado: no se encontro un campo de costo seguro en productos.';
            return [
                'sales_base' => 0.0,
                'estimated_cost' => 0.0,
                'estimated_margin' => 0.0,
                'margin_percent' => 0.0,
                'available' => false,
            ];
        }

        $query = \DB::select(
                [\DB::expr('COALESCE(SUM(ii.line_total),0)'), 'sales_base'],
                [\DB::expr('COALESCE(SUM(ii.quantity * '.$cost_expr.'),0)'), 'estimated_cost']
            )
            ->from(['core_billing_invoice_items', 'ii'])
            ->join(['core_billing_invoices', 'i'], 'inner')->on('ii.invoice_id', '=', 'i.id')
            ->join(['core_commerce_products', 'p'], 'left')->on('ii.product_id', '=', 'p.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('ii.active', '=', 1)
            ->where('i.issue_date', '>=', $filters['start_date'])
            ->where('i.issue_date', '<=', $filters['end_date']);

        $this->apply_party_scope($query, 'i.party_id');
        $row = $query->execute()->current() ?: [];
        $sales_base = $this->money(\Arr::get($row, 'sales_base', 0));
        $estimated_cost = $this->money(\Arr::get($row, 'estimated_cost', 0));
        $margin = $this->money($sales_base - $estimated_cost);

        return [
            'sales_base' => $sales_base,
            'estimated_cost' => $estimated_cost,
            'estimated_margin' => $margin,
            'margin_percent' => $sales_base > 0 ? round(($margin / $sales_base) * 100, 2) : 0.0,
            'available' => true,
        ];
    }

    protected function commissions(array $filters)
    {
        if (!$this->table('core_commission_entries')) {
            return $this->empty_metric('Comisiones no disponibles');
        }

        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'count'],
                [\DB::expr('COALESCE(SUM(commission_amount),0)'), 'total']
            )
            ->from('core_commission_entries')
            ->where('active', '=', 1)
            ->where('earned_at', '>=', strtotime($filters['start_date'].' 00:00:00'))
            ->where('earned_at', '<=', strtotime($filters['end_date'].' 23:59:59'));

        $this->apply_party_scope($query, 'party_id');
        $row = $query->execute()->current() ?: [];

        return [
            'count' => (int) \Arr::get($row, 'count', 0),
            'total' => $this->money(\Arr::get($row, 'total', 0)),
        ];
    }

    protected function rentals(array $filters)
    {
        if (!$this->table('core_billing_recurring_profiles')) {
            return $this->empty_metric('Rentas recurrentes no disponibles');
        }

        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'active_profiles']
            )
            ->from('core_billing_recurring_profiles')
            ->where('active', '=', 1)
            ->where('invoice_type', '=', 'sale')
            ->where('status', '=', 'active');

        $this->apply_party_scope($query, 'party_id');
        $profiles = $query->execute()->current() ?: [];

        $monthly = 0.0;
        if ($this->table('core_billing_recurring_items')) {
            $items = \DB::select([\DB::expr('COALESCE(SUM(ri.quantity * ri.unit_price),0)'), 'monthly_base'])
                ->from(['core_billing_recurring_items', 'ri'])
                ->join(['core_billing_recurring_profiles', 'rp'], 'inner')->on('ri.profile_id', '=', 'rp.id')
                ->where('ri.active', '=', 1)
                ->where('rp.active', '=', 1)
                ->where('rp.invoice_type', '=', 'sale')
                ->where('rp.status', '=', 'active');
            $this->apply_party_scope($items, 'rp.party_id');
            $monthly = $this->money(\Arr::get($items->execute()->current() ?: [], 'monthly_base', 0));
        }

        return [
            'active_profiles' => (int) \Arr::get($profiles, 'active_profiles', 0),
            'monthly_base' => $monthly,
        ];
    }

    protected function expenses(array $filters)
    {
        if (!$this->table('core_treasury_cashflow_items')) {
            return $this->empty_metric('Gastos/flujo no disponibles');
        }

        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'count'],
                [\DB::expr('COALESCE(SUM(amount),0)'), 'total']
            )
            ->from('core_treasury_cashflow_items')
            ->where('active', '=', 1)
            ->where('flow_type', '=', 'outflow')
            ->where('planned_date', '>=', $filters['start_date'])
            ->where('planned_date', '<=', $filters['end_date']);

        $row = $query->execute()->current() ?: [];

        return [
            'count' => (int) \Arr::get($row, 'count', 0),
            'total' => $this->money(\Arr::get($row, 'total', 0)),
        ];
    }

    protected function cashflow(array $filters)
    {
        if (!$this->table('core_treasury_cashflow_items')) {
            return $this->empty_metric('Flujo no disponible');
        }

        $rows = \DB::select('flow_type', [\DB::expr('COALESCE(SUM(amount * (probability / 100)),0)'), 'weighted_total'])
            ->from('core_treasury_cashflow_items')
            ->where('active', '=', 1)
            ->where('planned_date', '>=', $filters['start_date'])
            ->where('planned_date', '<=', $filters['end_date'])
            ->group_by('flow_type')
            ->execute();

        $inflow = 0.0;
        $outflow = 0.0;
        foreach ($rows as $row) {
            if ($row['flow_type'] === 'inflow') {
                $inflow += (float) $row['weighted_total'];
            } elseif ($row['flow_type'] === 'outflow') {
                $outflow += (float) $row['weighted_total'];
            }
        }

        return [
            'expected_income' => $this->money($inflow),
            'expected_expenses' => $this->money($outflow),
            'net_flow' => $this->money($inflow - $outflow),
        ];
    }

    protected function product_cost_expression()
    {
        if (!$this->table('core_commerce_products')) {
            return null;
        }
        foreach (['cost', 'unit_cost', 'last_cost', 'purchase_cost'] as $field) {
            if ($this->field('core_commerce_products', $field)) {
                return 'COALESCE(p.'.$field.',0)';
            }
        }
        return null;
    }

    protected function apply_party_scope($query, $field)
    {
        $ids = $this->visibility->allowed_customer_ids((int) \Arr::get($this->context, 'user_id', 0));
        if ($ids === null) {
            return $query;
        }
        if (empty($ids)) {
            return $query->where($field, '=', -1);
        }
        return $query->where($field, 'in', $ids);
    }

    protected function empty_metric($warning)
    {
        $this->warnings[] = $warning;
        return [
            'count' => 0,
            'total' => 0.0,
            'available' => false,
        ];
    }

    protected function table($table)
    {
        return \DBUtil::table_exists($table);
    }

    protected function field($table, $field)
    {
        return \DBUtil::table_exists($table) && \DBUtil::field_exists($table, [$field]);
    }

    protected function money($value)
    {
        return round((float) $value, 2);
    }
}
