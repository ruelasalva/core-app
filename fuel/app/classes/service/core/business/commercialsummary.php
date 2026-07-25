<?php

/**
 * SERVICE CORE_BUSINESS_COMMERCIALSUMMARY
 *
 * Construye resumen comercial read-only para Business Suite.
 *
 * @package  app
 */
class Service_Core_Business_CommercialSummary
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
            'top_clientes' => $this->top_customers($filters),
            'top_vendedores' => $this->top_sellers($filters),
            'facturas_recientes' => $this->recent_invoices($filters),
            'alertas_datos' => $this->data_alerts($filters),
        ];
    }

    public function warnings()
    {
        return $this->warnings;
    }

    protected function top_customers(array $filters)
    {
        if (!$this->table('core_billing_invoices') || !$this->table('core_parties')) {
            $this->warnings[] = 'Top clientes no disponible: faltan facturas o clientes.';
            return [];
        }

        $query = \DB::select(
                ['p.id', 'party_id'],
                ['p.name', 'name'],
                [\DB::expr('COUNT(i.id)'), 'invoice_count'],
                [\DB::expr('COALESCE(SUM(i.total),0)'), 'total'],
                [\DB::expr('COALESCE(SUM(i.balance_due),0)'), 'balance_due']
            )
            ->from(['core_billing_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('i.issue_date', '>=', $filters['start_date'])
            ->where('i.issue_date', '<=', $filters['end_date'])
            ->group_by('p.id')
            ->group_by('p.name')
            ->order_by('total', 'desc')
            ->limit(10);

        $this->apply_party_scope($query, 'i.party_id');

        $items = [];
        foreach ($query->execute() as $row) {
            $items[] = [
                'party_id' => (int) $row['party_id'],
                'name' => (string) $row['name'],
                'invoice_count' => (int) $row['invoice_count'],
                'total' => $this->money($row['total']),
                'balance_due' => $this->money($row['balance_due']),
            ];
        }
        return $items;
    }

    protected function top_sellers(array $filters)
    {
        if (!$this->table('core_sales_sellers')) {
            $this->warnings[] = 'Top vendedores no disponible: falta catalogo de vendedores.';
            return [];
        }

        if ($this->table('core_billing_invoices') && $this->field('core_billing_invoices', 'seller_id')) {
            return $this->top_sellers_from_invoices($filters);
        }

        if ($this->table('core_commission_entries')) {
            return $this->top_sellers_from_commissions($filters);
        }

        $this->warnings[] = 'Top vendedores limitado: no hay seller_id en facturas ni entradas de comision.';
        return [];
    }

    protected function top_sellers_from_invoices(array $filters)
    {
        $query = \DB::select(
                ['s.id', 'seller_id'],
                ['s.name', 'name'],
                [\DB::expr('COUNT(i.id)'), 'invoice_count'],
                [\DB::expr('COALESCE(SUM(i.total),0)'), 'total']
            )
            ->from(['core_billing_invoices', 'i'])
            ->join(['core_sales_sellers', 's'], 'left')->on('i.seller_id', '=', 's.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('i.issue_date', '>=', $filters['start_date'])
            ->where('i.issue_date', '<=', $filters['end_date'])
            ->group_by('s.id')
            ->group_by('s.name')
            ->order_by('total', 'desc')
            ->limit(10);

        $this->apply_party_scope($query, 'i.party_id');
        return $this->format_seller_rows($query->execute());
    }

    protected function top_sellers_from_commissions(array $filters)
    {
        $query = \DB::select(
                ['s.id', 'seller_id'],
                ['s.name', 'name'],
                [\DB::expr('COUNT(e.id)'), 'invoice_count'],
                [\DB::expr('COALESCE(SUM(e.base_amount),0)'), 'total'],
                [\DB::expr('COALESCE(SUM(e.commission_amount),0)'), 'commission_total']
            )
            ->from(['core_commission_entries', 'e'])
            ->join(['core_sales_sellers', 's'], 'left')->on('e.seller_id', '=', 's.id')
            ->where('e.active', '=', 1)
            ->where('e.earned_at', '>=', strtotime($filters['start_date'].' 00:00:00'))
            ->where('e.earned_at', '<=', strtotime($filters['end_date'].' 23:59:59'))
            ->group_by('s.id')
            ->group_by('s.name')
            ->order_by('total', 'desc')
            ->limit(10);

        $this->apply_party_scope($query, 'e.party_id');
        return $this->format_seller_rows($query->execute());
    }

    protected function recent_invoices(array $filters)
    {
        if (!$this->table('core_billing_invoices')) {
            return [];
        }

        $query = \DB::select(
                ['i.id', 'id'],
                ['i.folio', 'folio'],
                ['i.issue_date', 'issue_date'],
                ['i.total', 'total'],
                ['i.balance_due', 'balance_due'],
                ['i.status', 'status'],
                ['p.name', 'party_name']
            )
            ->from(['core_billing_invoices', 'i'])
            ->join(['core_parties', 'p'], 'left')->on('i.party_id', '=', 'p.id')
            ->where('i.invoice_type', '=', 'sale')
            ->where('i.active', '=', 1)
            ->where('i.issue_date', '>=', $filters['start_date'])
            ->where('i.issue_date', '<=', $filters['end_date'])
            ->order_by('i.issue_date', 'desc')
            ->order_by('i.id', 'desc')
            ->limit(10);

        $this->apply_party_scope($query, 'i.party_id');

        $items = [];
        foreach ($query->execute() as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'folio' => (string) $row['folio'],
                'issue_date' => (string) $row['issue_date'],
                'party_name' => (string) $row['party_name'],
                'status' => (string) $row['status'],
                'total' => $this->money($row['total']),
                'balance_due' => $this->money($row['balance_due']),
            ];
        }
        return $items;
    }

    protected function data_alerts(array $filters)
    {
        $alerts = [];

        if ($this->table('core_billing_invoices')) {
            $missing_party = \DB::select([\DB::expr('COUNT(*)'), 'total'])
                ->from('core_billing_invoices')
                ->where('invoice_type', '=', 'sale')
                ->where('active', '=', 1)
                ->where('party_id', '=', 0)
                ->execute()
                ->current();
            if ((int) \Arr::get($missing_party, 'total', 0) > 0) {
                $alerts[] = [
                    'level' => 'warning',
                    'message' => 'Existen facturas de venta sin cliente relacionado.',
                    'count' => (int) $missing_party['total'],
                ];
            }

            if (!$this->field('core_billing_invoices', 'seller_id')) {
                $alerts[] = [
                    'level' => 'info',
                    'message' => 'Las facturas no tienen seller_id; el top de vendedores puede depender de comisiones.',
                    'count' => 0,
                ];
            }
        }

        if ($this->table('core_parties')) {
            $missing_seller_field = !$this->field('core_parties', 'default_seller_id');
            if ($missing_seller_field) {
                $alerts[] = [
                    'level' => 'info',
                    'message' => 'Clientes sin campo default_seller_id disponible en esta instalacion.',
                    'count' => 0,
                ];
            }
        }

        if (!$this->table('core_commission_entries')) {
            $alerts[] = [
                'level' => 'info',
                'message' => 'No se encontro tabla de movimientos de comision; las comisiones se muestran como no disponibles.',
                'count' => 0,
            ];
        }

        if (!$this->table('core_billing_recurring_profiles')) {
            $alerts[] = [
                'level' => 'info',
                'message' => 'No se encontro facturacion recurrente; el resumen de rentas queda pendiente.',
                'count' => 0,
            ];
        }

        return $alerts;
    }

    protected function format_seller_rows($rows)
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'seller_id' => (int) \Arr::get($row, 'seller_id', 0),
                'name' => (string) \Arr::get($row, 'name', 'Sin vendedor'),
                'invoice_count' => (int) \Arr::get($row, 'invoice_count', 0),
                'total' => $this->money(\Arr::get($row, 'total', 0)),
                'commission_total' => $this->money(\Arr::get($row, 'commission_total', 0)),
            ];
        }
        return $items;
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
