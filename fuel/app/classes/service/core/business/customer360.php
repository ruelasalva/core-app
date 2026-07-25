<?php

/**
 * SERVICE CORE_BUSINESS_CUSTOMER360
 *
 * Base read-only de Customer 360 para Business Suite.
 *
 * @package  app
 */
class Service_Core_Business_Customer360
{
    protected $context = [];
    protected $warnings = [];
    protected $visibility;

    public function __construct(array $context = [])
    {
        $this->context = $context;
        $this->visibility = new \Service_Core_Crm_CustomerVisibility();
    }

    public function base(array $filters)
    {
        return [
            'clientes_visibles' => $this->visible_customers(),
            'actividad_comercial' => $this->commercial_activity($filters),
            'tickets' => $this->ticket_summary($filters),
            'comunicaciones' => $this->communication_summary($filters),
            'oportunidades' => $this->opportunity_summary($filters),
        ];
    }

    public function warnings()
    {
        return $this->warnings;
    }

    protected function visible_customers()
    {
        if (!$this->table('core_parties')) {
            $this->warnings[] = 'Customer 360 no disponible: falta core_parties.';
            return ['count' => 0, 'sample' => []];
        }

        $query = \DB::select('id', 'name', 'email', 'phone')
            ->from('core_parties')
            ->where('active', '=', 1)
            ->where('party_type', '=', 'customer')
            ->order_by('name', 'asc')
            ->limit(8);

        $ids = $this->allowed_customer_ids();
        if (is_array($ids)) {
            if (empty($ids)) {
                $query->where('id', '=', -1);
            } else {
                $query->where('id', 'in', $ids);
            }
        }

        $items = [];
        foreach ($query->execute() as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'has_email' => filter_var(trim((string) $row['email']), FILTER_VALIDATE_EMAIL) ? true : false,
                'has_phone' => trim((string) $row['phone']) !== '',
            ];
        }

        return [
            'count' => $this->visible_customer_count(),
            'sample' => $items,
        ];
    }

    protected function commercial_activity(array $filters)
    {
        if (!$this->table('core_billing_invoices')) {
            return ['invoices' => 0, 'balance_due' => 0.0, 'overdue' => 0.0];
        }

        $today = date('Y-m-d');
        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'invoices'],
                [\DB::expr('COALESCE(SUM(balance_due),0)'), 'balance_due'],
                [\DB::expr("COALESCE(SUM(CASE WHEN due_date <> '' AND due_date < '".$today."' THEN balance_due ELSE 0 END),0)"), 'overdue']
            )
            ->from('core_billing_invoices')
            ->where('invoice_type', '=', 'sale')
            ->where('active', '=', 1);

        $this->apply_party_scope($query, 'party_id');
        $row = $query->execute()->current() ?: [];

        return [
            'invoices' => (int) \Arr::get($row, 'invoices', 0),
            'balance_due' => $this->money(\Arr::get($row, 'balance_due', 0)),
            'overdue' => $this->money(\Arr::get($row, 'overdue', 0)),
        ];
    }

    protected function ticket_summary(array $filters)
    {
        if (!$this->table('core_helpdesk_tickets')) {
            return ['open' => 0, 'total_period' => 0];
        }

        $open = \DB::select([\DB::expr('COUNT(*)'), 'total'])
            ->from(['core_helpdesk_tickets', 't'])
            ->where('t.active', '=', 1);
        $this->apply_party_scope($open, 't.party_id');

        if ($this->table('core_helpdesk_statuses')) {
            $open->join(['core_helpdesk_statuses', 's'], 'left')->on('t.status_id', '=', 's.id')
                ->where_open()
                    ->where('s.is_closed', '=', 0)
                    ->or_where('t.status_id', '=', 0)
                ->where_close();
        }

        $period = \DB::select([\DB::expr('COUNT(*)'), 'total'])
            ->from(['core_helpdesk_tickets', 't'])
            ->where('t.active', '=', 1)
            ->where('t.created_at', '>=', strtotime($filters['start_date'].' 00:00:00'))
            ->where('t.created_at', '<=', strtotime($filters['end_date'].' 23:59:59'));
        $this->apply_party_scope($period, 't.party_id');

        return [
            'open' => (int) \Arr::get($open->execute()->current() ?: [], 'total', 0),
            'total_period' => (int) \Arr::get($period->execute()->current() ?: [], 'total', 0),
        ];
    }

    protected function communication_summary(array $filters)
    {
        if (!$this->table('core_communication_conversations')) {
            return ['conversations' => 0, 'messages' => 0];
        }

        $conversations = \DB::select([\DB::expr('COUNT(*)'), 'total'])
            ->from('core_communication_conversations')
            ->where('active', '=', 1);
        $this->apply_party_scope($conversations, 'related_party_id');

        $messages_total = 0;
        if ($this->table('core_communication_messages')) {
            $messages = \DB::select([\DB::expr('COUNT(*)'), 'total'])
                ->from('core_communication_messages')
                ->where('active', '=', 1);
            $this->apply_party_scope($messages, 'related_party_id');
            $messages_total = (int) \Arr::get($messages->execute()->current() ?: [], 'total', 0);
        }

        return [
            'conversations' => (int) \Arr::get($conversations->execute()->current() ?: [], 'total', 0),
            'messages' => $messages_total,
        ];
    }

    protected function opportunity_summary(array $filters)
    {
        if (!$this->table('core_crm_opportunities')) {
            return ['open' => 0, 'weighted_amount' => 0.0];
        }

        $query = \DB::select(
                [\DB::expr('COUNT(*)'), 'open'],
                [\DB::expr('COALESCE(SUM(estimated_amount * (probability / 100)),0)'), 'weighted_amount']
            )
            ->from('core_crm_opportunities')
            ->where('active', '=', 1);

        if ($this->field('core_crm_opportunities', 'stage')) {
            $query->where('stage', 'not in', ['won', 'lost', 'cancelled', 'closed']);
        }

        $this->apply_party_scope($query, 'party_id');
        $row = $query->execute()->current() ?: [];

        return [
            'open' => (int) \Arr::get($row, 'open', 0),
            'weighted_amount' => $this->money(\Arr::get($row, 'weighted_amount', 0)),
        ];
    }

    protected function visible_customer_count()
    {
        if (!$this->table('core_parties')) {
            return 0;
        }

        $query = \DB::select([\DB::expr('COUNT(*)'), 'total'])
            ->from('core_parties')
            ->where('active', '=', 1)
            ->where('party_type', '=', 'customer');

        $ids = $this->allowed_customer_ids();
        if (is_array($ids)) {
            if (empty($ids)) {
                $query->where('id', '=', -1);
            } else {
                $query->where('id', 'in', $ids);
            }
        }

        return (int) \Arr::get($query->execute()->current() ?: [], 'total', 0);
    }

    protected function allowed_customer_ids()
    {
        return $this->visibility->allowed_customer_ids((int) \Arr::get($this->context, 'user_id', 0));
    }

    protected function apply_party_scope($query, $field)
    {
        $ids = $this->allowed_customer_ids();
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
