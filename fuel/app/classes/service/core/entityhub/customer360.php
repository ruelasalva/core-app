<?php

class Service_Core_EntityHub_Customer360
{
    protected $entity_resolver;
    protected $profile_resolver;
    protected $relationship_engine;
    protected $timeline_reader;
    protected $visibility;
    protected $warnings = array();

    public function __construct()
    {
        $this->entity_resolver = new Service_Core_EntityHub_EntityResolver();
        $this->profile_resolver = new Service_Core_EntityHub_ProfileResolver();
        $this->relationship_engine = new Service_Core_EntityHub_RelationshipEngine();
        $this->timeline_reader = new Service_Core_EntityHub_TimelineReader();
        $this->visibility = new Service_Core_Crm_CustomerVisibility();
    }

    public function data($party_id, $user_id)
    {
        $party_id = (int) $party_id;
        $user_id = (int) $user_id;

        if (!$this->table('core_parties')) {
            return array('found' => false, 'visible' => false, 'message' => 'Falta core_parties.');
        }

        $party = $this->party($party_id);
        if (!$party) {
            return array('found' => false, 'visible' => false, 'message' => 'Cliente no encontrado.');
        }

        if (!$this->visibility->can_view_customer($user_id, $party_id)) {
            return array('found' => true, 'visible' => false, 'message' => 'No tienes permiso para ver este cliente.');
        }

        $profile = $this->profile_resolver->profile('customer', $party_id, $user_id);
        $relationships = $this->relationship_engine->aggregate('customer', $party_id, array(), $user_id, 120);
        $timeline = $this->timeline_reader->timeline('customer', $party_id, array(), $user_id, 30);

        return array(
            'found' => true,
            'visible' => true,
            'customer' => $this->customer_header($party, $profile),
            'general' => $this->general_information($party, $user_id),
            'commercial_summary' => $this->commercial_summary($party_id),
            'communications' => $this->communications($party_id, $user_id),
            'timeline' => !empty($timeline['timeline']) ? $timeline['timeline'] : array(),
            'timeline_counts' => !empty($timeline['counts']) ? $timeline['counts'] : array(),
            'timeline_hidden_count' => !empty($timeline['hidden_count']) ? (int) $timeline['hidden_count'] : 0,
            'documents' => $this->documents($party_id, $user_id),
            'helpdesk' => $this->helpdesk($party_id),
            'contracts' => $this->contracts($party_id),
            'kpis' => $this->kpis($party_id),
            'relationships' => array(
                'counts' => !empty($relationships['counts']) ? $relationships['counts'] : array(),
                'hidden_count' => !empty($relationships['hidden_count']) ? (int) $relationships['hidden_count'] : 0,
            ),
            'warnings' => $this->warnings,
        );
    }

    protected function party($party_id)
    {
        return \DB::select('id', 'party_type', 'code', 'name', 'legal_name', 'rfc', 'email', 'phone', 'credit_limit', 'credit_days', 'active', 'created_at', 'updated_at')
            ->from('core_parties')
            ->where('id', '=', (int) $party_id)
            ->where('party_type', '=', 'customer')
            ->where('active', '=', 1)
            ->execute()
            ->current();
    }

    protected function customer_header(array $party, array $profile)
    {
        $entity = !empty($profile['entity']) ? $profile['entity'] : $this->entity_resolver->resolve('customer', (int) $party['id']);

        return array(
            'id' => (int) $party['id'],
            'name' => (string) $party['name'],
            'legal_name' => (string) $party['legal_name'],
            'code' => !empty($party['code']) ? (string) $party['code'] : (string) \Arr::get($entity, 'entity_code', ''),
            'status' => !empty($entity['status']) ? (string) $entity['status'] : 'active',
            'active' => (int) $party['active'] === 1,
            'labels' => !empty($profile['business_labels']) ? $profile['business_labels'] : array('Customer'),
            'owner' => !empty($profile['owner']) ? $profile['owner'] : array(),
            'primary_contact' => $this->primary_contact((int) $party['id']),
        );
    }

    protected function general_information(array $party, $user_id)
    {
        return array(
            'rfc' => $this->can_view_rfc($user_id) ? (string) $party['rfc'] : '',
            'rfc_visible' => $this->can_view_rfc($user_id),
            'email' => (string) $party['email'],
            'phone' => (string) $party['phone'],
            'address' => $this->primary_address((int) $party['id']),
            'customer_type' => (string) $party['party_type'],
            'active' => (int) $party['active'] === 1,
            'credit_limit' => $this->money(\Arr::get($party, 'credit_limit', 0)),
            'credit_days' => (int) \Arr::get($party, 'credit_days', 0),
        );
    }

    protected function commercial_summary($party_id)
    {
        return array(
            'quotes' => $this->aggregate_table('core_sales_quotes', $party_id, 'total'),
            'orders' => $this->aggregate_table('core_sales_orders', $party_id, 'total'),
            'invoices' => $this->aggregate_table('core_billing_invoices', $party_id, 'total', array('invoice_type' => 'sale')),
            'payments' => $this->aggregate_table('core_payments', $party_id, 'amount', array('payment_type' => 'received')),
            'pending_balance' => $this->sum_field('core_billing_invoices', $party_id, 'balance_due', array('invoice_type' => 'sale')),
            'overdue_balance' => $this->overdue_balance($party_id),
            'opportunities' => $this->opportunities_summary($party_id),
        );
    }

    protected function communications($party_id, $user_id)
    {
        $result = array('recent' => array(), 'total' => 0, 'last_email_at' => 0, 'unread_count' => 0, 'url' => \Uri::create('admin/communications'));
        if (!$this->table('core_communication_conversations') || !\Auth::has_access('communications.access[view]')) {
            return $result;
        }

        $access = class_exists('Service_Core_Communications_MailboxAccess') ? new Service_Core_Communications_MailboxAccess() : null;
        $rows = \DB::select('id', 'subject', 'channel_code', 'last_message_at', 'unread_count', 'status')
            ->from('core_communication_conversations')
            ->where('related_party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('last_message_at', 'desc')
            ->limit(10)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            if ($access && !$access->can_view_conversation($user_id, (int) $row['id'])) {
                continue;
            }
            $result['recent'][] = array(
                'id' => (int) $row['id'],
                'subject' => $this->text($row['subject'], 160),
                'channel_code' => (string) $row['channel_code'],
                'last_message_at' => (int) $row['last_message_at'],
                'unread_count' => (int) $row['unread_count'],
                'status' => (string) $row['status'],
            );
            $result['unread_count'] += (int) $row['unread_count'];
            if ((int) $row['last_message_at'] > (int) $result['last_email_at']) {
                $result['last_email_at'] = (int) $row['last_message_at'];
            }
        }

        $result['total'] = count($result['recent']);
        return $result;
    }

    protected function documents($party_id, $user_id)
    {
        if (!$this->table('core_document_links') || !$this->table('core_documents')) {
            return array();
        }

        $rows = \DB::select('d.id', 'd.title', 'd.document_type', 'd.mime_type', 'd.file_size', 'd.created_at')
            ->from(array('core_document_links', 'l'))
            ->join(array('core_documents', 'd'), 'inner')->on('d.id', '=', 'l.document_id')
            ->where('l.entity_type', 'in', array('customer', 'party', 'cliente'))
            ->where('l.entity_id', '=', (int) $party_id)
            ->where('l.active', '=', 1)
            ->where('d.active', '=', 1)
            ->order_by('d.created_at', 'desc')
            ->limit(12)
            ->execute()
            ->as_array();

        $items = array();
        foreach ($rows as $row) {
            $document = $this->entity_resolver->resolve('document', (int) $row['id']);
            $scope = new Service_Core_EntityHub_SecurityScope($this->entity_resolver);
            if (!$document || !$scope->can_view('document', (int) $row['id'], $document, $user_id)) {
                continue;
            }
            $items[] = array(
                'id' => (int) $row['id'],
                'title' => $this->text($row['title'], 160),
                'document_type' => (string) $row['document_type'],
                'mime_type' => (string) $row['mime_type'],
                'file_size' => (int) $row['file_size'],
                'created_at' => (int) $row['created_at'],
                'download_url' => \Uri::create('admin/documents/download/'.(int) $row['id']),
            );
        }

        return $items;
    }

    protected function helpdesk($party_id)
    {
        $result = array('open' => 0, 'recent' => array());
        if (!$this->table('core_helpdesk_tickets')) {
            return $result;
        }

        $rows = \DB::select('id', 'folio', 'subject', 'status_id', 'priority', 'last_message_at', 'created_at', 'updated_at')
            ->from('core_helpdesk_tickets')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('updated_at', 'desc')
            ->limit(10)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $closed = $this->ticket_closed((int) $row['status_id']);
            if (!$closed) {
                $result['open']++;
            }
            $result['recent'][] = array(
                'id' => (int) $row['id'],
                'folio' => (string) $row['folio'],
                'subject' => $this->text($row['subject'], 160),
                'priority' => (string) $row['priority'],
                'closed' => $closed,
                'last_message_at' => (int) $row['last_message_at'],
                'created_at' => (int) $row['created_at'],
            );
        }

        return $result;
    }

    protected function contracts($party_id)
    {
        $result = array('active' => 0, 'expiring' => 0, 'rental_contracts' => 0, 'recent' => array());
        if (!$this->table('core_contracts')) {
            return $result;
        }

        $today = date('Y-m-d');
        $soon = date('Y-m-d', strtotime('+60 days'));
        $rows = \DB::select('id', 'contract_number', 'contract_type', 'title', 'status', 'end_date', 'responsible_user_id')
            ->from('core_contracts')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('updated_at', 'desc')
            ->limit(12)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            if (!in_array((string) $row['status'], array('closed', 'cancelled', 'expired'), true)) {
                $result['active']++;
            }
            if (!empty($row['end_date']) && $row['end_date'] >= $today && $row['end_date'] <= $soon) {
                $result['expiring']++;
            }
            if (in_array((string) $row['contract_type'], array('rental', 'rental_contract', 'rent'), true)) {
                $result['rental_contracts']++;
            }
            $result['recent'][] = array(
                'id' => (int) $row['id'],
                'contract_number' => (string) $row['contract_number'],
                'contract_type' => (string) $row['contract_type'],
                'title' => $this->text($row['title'], 160),
                'status' => (string) $row['status'],
                'end_date' => (string) $row['end_date'],
            );
        }

        return $result;
    }

    protected function kpis($party_id)
    {
        $commercial = $this->commercial_summary($party_id);
        $helpdesk = $this->helpdesk($party_id);
        $contracts = $this->contracts($party_id);

        return array(
            'sales_total' => $this->money((float) $commercial['invoices']['amount']),
            'paid_total' => $this->money((float) $commercial['payments']['amount']),
            'pending_balance' => $commercial['pending_balance'],
            'overdue_balance' => $commercial['overdue_balance'],
            'open_tickets' => (int) $helpdesk['open'],
            'active_contracts' => (int) $contracts['active'],
            'last_activity_date' => $this->last_activity_date($party_id),
        );
    }

    protected function primary_contact($party_id)
    {
        if (!$this->table('core_party_contacts')) {
            return array();
        }

        $row = \DB::select('id', 'name', 'position', 'email', 'phone')
            ->from('core_party_contacts')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('receives_notifications', 'desc')
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        return $row ? array(
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'position' => (string) $row['position'],
            'email' => (string) $row['email'],
            'phone' => (string) $row['phone'],
        ) : array();
    }

    protected function primary_address($party_id)
    {
        if (!$this->table('core_party_addresses')) {
            return array();
        }

        $row = \DB::select('id', 'address_type', 'name', 'street', 'exterior_number', 'interior_number', 'neighborhood', 'city', 'state', 'country_code', 'postal_code')
            ->from('core_party_addresses')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('is_default', 'desc')
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        return $row ? array(
            'id' => (int) $row['id'],
            'address_type' => (string) $row['address_type'],
            'label' => trim((string) $row['street'].' '.(string) $row['exterior_number'].' '.(string) $row['interior_number'].', '.(string) $row['neighborhood'].', '.(string) $row['city'].', '.(string) $row['state'].' '.(string) $row['postal_code']),
            'city' => (string) $row['city'],
            'state' => (string) $row['state'],
            'country_code' => (string) $row['country_code'],
        ) : array();
    }

    protected function aggregate_table($table, $party_id, $amount_field, array $extra = array())
    {
        if (!$this->table($table)) {
            return array('count' => 0, 'amount' => 0.0);
        }

        $query = \DB::select(array(\DB::expr('COUNT(*)'), 'total_count'), array(\DB::expr('COALESCE(SUM('.$amount_field.'),0)'), 'total_amount'))
            ->from($table)
            ->where('party_id', '=', (int) $party_id);
        if ($this->field($table, 'active')) {
            $query->where('active', '=', 1);
        }
        foreach ($extra as $field => $value) {
            if ($this->field($table, $field)) {
                $query->where($field, '=', $value);
            }
        }

        $row = $query->execute()->current() ?: array();
        return array('count' => (int) \Arr::get($row, 'total_count', 0), 'amount' => $this->money(\Arr::get($row, 'total_amount', 0)));
    }

    protected function sum_field($table, $party_id, $field, array $extra = array())
    {
        if (!$this->table($table) || !$this->field($table, $field)) {
            return 0.0;
        }
        $result = $this->aggregate_table($table, $party_id, $field, $extra);
        return $result['amount'];
    }

    protected function overdue_balance($party_id)
    {
        if (!$this->table('core_billing_invoices')) {
            return 0.0;
        }
        $today = date('Y-m-d');
        $query = \DB::select(array(\DB::expr('COALESCE(SUM(balance_due),0)'), 'total'))
            ->from('core_billing_invoices')
            ->where('party_id', '=', (int) $party_id)
            ->where('invoice_type', '=', 'sale')
            ->where('active', '=', 1)
            ->where('balance_due', '>', 0)
            ->where('due_date', '!=', '')
            ->where('due_date', '<', $today);
        $row = $query->execute()->current() ?: array();
        return $this->money(\Arr::get($row, 'total', 0));
    }

    protected function opportunities_summary($party_id)
    {
        if (!$this->table('core_crm_opportunities')) {
            return array('count' => 0, 'open' => 0, 'amount' => 0.0);
        }
        $query = \DB::select(array(\DB::expr('COUNT(*)'), 'total_count'), array(\DB::expr('COALESCE(SUM(estimated_amount),0)'), 'total_amount'))
            ->from('core_crm_opportunities')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1);
        $row = $query->execute()->current() ?: array();

        $open = \DB::select(array(\DB::expr('COUNT(*)'), 'total'))
            ->from('core_crm_opportunities')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->where('stage', 'not in', array('won', 'lost', 'cancelled', 'closed'))
            ->execute()
            ->current() ?: array();

        return array(
            'count' => (int) \Arr::get($row, 'total_count', 0),
            'open' => (int) \Arr::get($open, 'total', 0),
            'amount' => $this->money(\Arr::get($row, 'total_amount', 0)),
        );
    }

    protected function ticket_closed($status_id)
    {
        if ($status_id <= 0 || !$this->table('core_helpdesk_statuses')) {
            return false;
        }
        $row = \DB::select('is_closed')->from('core_helpdesk_statuses')->where('id', '=', (int) $status_id)->execute()->current();
        return $row ? (int) $row['is_closed'] === 1 : false;
    }

    protected function last_activity_date($party_id)
    {
        $timestamps = array();
        $tables = array(
            array('core_billing_invoices', 'updated_at'),
            array('core_payments', 'created_at'),
            array('core_sales_quotes', 'updated_at'),
            array('core_sales_orders', 'updated_at'),
            array('core_helpdesk_tickets', 'updated_at'),
            array('core_crm_activities', 'updated_at'),
        );

        foreach ($tables as $item) {
            list($table, $field) = $item;
            if (!$this->table($table) || !$this->field($table, 'party_id') || !$this->field($table, $field)) {
                continue;
            }
            $query = \DB::select(array(\DB::expr('MAX('.$field.')'), 'last_at'))->from($table)->where('party_id', '=', (int) $party_id);
            if ($this->field($table, 'active')) {
                $query->where('active', '=', 1);
            }
            $row = $query->execute()->current();
            if ($row && (int) $row['last_at'] > 0) {
                $timestamps[] = (int) $row['last_at'];
            }
        }

        return $timestamps ? max($timestamps) : 0;
    }

    protected function can_view_rfc($user_id)
    {
        try {
            return \Auth::member(100) || \Auth::has_access('parties.access[view]') || \Auth::has_access('customers.access[view]');
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function text($value, $limit)
    {
        $value = trim(strip_tags((string) $value));
        return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
    }

    protected function money($value)
    {
        return round((float) $value, 2);
    }

    protected function table($table)
    {
        return \DBUtil::table_exists($table);
    }

    protected function field($table, $field)
    {
        return \DBUtil::table_exists($table) && \DBUtil::field_exists($table, array($field));
    }
}
