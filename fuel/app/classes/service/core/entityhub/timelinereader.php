<?php

class Service_Core_EntityHub_TimelineReader
{
    protected $entity_resolver;
    protected $security_scope;

    protected $default_categories = array(
        'crm',
        'sales',
        'billing',
        'payments',
        'contracts',
        'documents',
        'communications',
        'helpdesk',
        'collections',
        'rentals_placeholder',
        'system',
    );

    public function __construct(
        Service_Core_EntityHub_EntityResolver $entity_resolver = null,
        Service_Core_EntityHub_SecurityScope $security_scope = null
    ) {
        $this->entity_resolver = $entity_resolver ?: new Service_Core_EntityHub_EntityResolver();
        $this->security_scope = $security_scope ?: new Service_Core_EntityHub_SecurityScope($this->entity_resolver);
    }

    public function timeline($entity_type, $entity_id, array $categories = array(), $user_id = null, $limit = 100, $date_from = '', $date_to = '')
    {
        $limit = max(1, min((int) $limit, 500));
        $categories = $this->normalize_categories($categories);
        $range = $this->date_range($date_from, $date_to);
        $entity = $this->entity_resolver->resolve($entity_type, $entity_id);

        if (!$entity) {
            return array('found' => false, 'visible' => false, 'timeline' => array(), 'counts' => array(), 'hidden_count' => 0);
        }

        if (!$this->security_scope->can_view($entity['entity_type'], $entity['entity_id'], $entity, $user_id)) {
            return array('found' => true, 'visible' => false, 'entity' => $entity, 'timeline' => array(), 'counts' => array(), 'hidden_count' => 0);
        }

        $entries = array();
        $hidden_count = 0;

        $this->append_contract_events($entries, $hidden_count, $entity, $categories, $range, $user_id, $limit);
        $this->append_documents($entries, $hidden_count, $entity, $categories, $range, $user_id, $limit);
        $this->append_communications($entries, $hidden_count, $entity, $categories, $range, $user_id, $limit);
        $this->append_party_records($entries, $hidden_count, $entity, $categories, $range, $user_id, $limit);
        $this->append_collection_actions($entries, $hidden_count, $entity, $categories, $range, $user_id, $limit);

        $entries = $this->dedupe($entries);
        usort($entries, function ($a, $b) {
            return (int) $b['_ts'] - (int) $a['_ts'];
        });

        $entries = array_slice($entries, 0, $limit);
        foreach ($entries as &$entry) {
            unset($entry['_ts']);
        }

        return array(
            'found' => true,
            'visible' => true,
            'entity' => $entity,
            'timeline' => $entries,
            'counts' => $this->counts($entries),
            'hidden_count' => $hidden_count,
        );
    }

    protected function append_contract_events(array &$entries, &$hidden_count, array $entity, array $categories, array $range, $user_id, $limit)
    {
        if (!$this->category_enabled('contracts', $categories) || !$this->table_exists('core_contract_events')) {
            return;
        }

        $contract_ids = array();
        if (in_array($entity['entity_type'], array('contract', 'rental_contract'), true)) {
            $contract_ids[] = (int) $entity['entity_id'];
        } elseif (in_array($entity['entity_type'], array('customer', 'supplier'), true) && $this->table_exists('core_contracts')) {
            $query = \DB::select('id')->from('core_contracts')->where('party_id', '=', (int) $entity['entity_id'])->limit($limit);
            if ($this->field_exists('core_contracts', 'active')) {
                $query->where('active', '=', 1);
            }
            foreach ($query->execute()->as_array() as $row) {
                $contract_ids[] = (int) $row['id'];
            }
        }

        $contract_ids = array_values(array_unique(array_filter($contract_ids)));
        if (!$contract_ids) {
            return;
        }

        $rows = \DB::select('id', 'contract_id', 'event_type', 'old_status', 'new_status', 'created_by', 'created_at')
            ->from('core_contract_events')
            ->where('contract_id', 'in', $contract_ids)
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $ts = (int) $row['created_at'];
            if (!$this->in_range($ts, $range)) {
                continue;
            }
            $contract = $this->entity_resolver->resolve('contract', (int) $row['contract_id']);
            if (!$contract || !$this->security_scope->can_view('contract', (int) $row['contract_id'], $contract, $user_id)) {
                $hidden_count++;
                continue;
            }
            $this->add_entry($entries, array(
                'event_type' => 'contract_event',
                'event_label' => 'Evento de contrato',
                'event_date' => $this->date_string($ts),
                'source_module' => 'Contratos',
                'source_entity_type' => 'contract',
                'source_entity_id' => (int) $row['contract_id'],
                'related_entity_type' => $entity['entity_type'],
                'related_entity_id' => (int) $entity['entity_id'],
                'actor_user_id' => (int) $row['created_by'],
                'title' => 'Contrato: '.$this->safe_text((string) $row['event_type'], 80),
                'description' => 'Evento registrado en contrato.',
                'icon' => 'fa-file-signature',
                'color' => '#0d6efd',
                'metadata' => array(
                    'event_id' => (int) $row['id'],
                    'old_status' => (string) $row['old_status'],
                    'new_status' => (string) $row['new_status'],
                    'category' => 'contracts',
                ),
                'visible' => true,
                '_ts' => $ts,
            ));
        }
    }

    protected function append_documents(array &$entries, &$hidden_count, array $entity, array $categories, array $range, $user_id, $limit)
    {
        if (!$this->category_enabled('documents', $categories) || !$this->table_exists('core_documents')) {
            return;
        }

        $document_ids = array();
        if ($entity['entity_type'] === 'document') {
            $document_ids[] = (int) $entity['entity_id'];
        }

        if ($this->table_exists('core_document_links')) {
            $rows = \DB::select('document_id')
                ->from('core_document_links')
                ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
                ->where('entity_id', '=', (int) $entity['entity_id'])
                ->where('active', '=', 1)
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $document_ids[] = (int) $row['document_id'];
            }
        }

        $document_ids = array_values(array_unique(array_filter($document_ids)));
        if (!$document_ids) {
            return;
        }

        $rows = \DB::select('id', 'title', 'document_type', 'uploaded_by', 'created_at', 'updated_at')
            ->from('core_documents')
            ->where('id', 'in', $document_ids)
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $ts = (int) (!empty($row['created_at']) ? $row['created_at'] : $row['updated_at']);
            if (!$this->in_range($ts, $range)) {
                continue;
            }
            $document = $this->entity_resolver->resolve('document', (int) $row['id']);
            if (!$document || !$this->security_scope->can_view('document', (int) $row['id'], $document, $user_id)) {
                $hidden_count++;
                continue;
            }
            $this->add_entry($entries, array(
                'event_type' => 'document_linked',
                'event_label' => 'Documento relacionado',
                'event_date' => $this->date_string($ts),
                'source_module' => 'Documentos',
                'source_entity_type' => 'document',
                'source_entity_id' => (int) $row['id'],
                'related_entity_type' => $entity['entity_type'],
                'related_entity_id' => (int) $entity['entity_id'],
                'actor_user_id' => (int) $row['uploaded_by'],
                'title' => $this->safe_text(!empty($row['title']) ? $row['title'] : 'Documento', 160),
                'description' => 'Documento disponible mediante acceso controlado.',
                'icon' => 'fa-file',
                'color' => '#6c757d',
                'metadata' => array('document_type' => (string) $row['document_type'], 'category' => 'documents'),
                'visible' => true,
                '_ts' => $ts,
            ));
        }
    }

    protected function append_communications(array &$entries, &$hidden_count, array $entity, array $categories, array $range, $user_id, $limit)
    {
        if (!$this->category_enabled('communications', $categories) || !$this->table_exists('core_communication_messages')) {
            return;
        }

        $conversation_ids = array();
        if ($entity['entity_type'] === 'communication') {
            $conversation_ids[] = (int) $entity['entity_id'];
        }

        if ($this->table_exists('core_communication_message_links')) {
            $rows = \DB::select(\DB::expr('DISTINCT conversation_id'))
                ->from('core_communication_message_links')
                ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
                ->where('entity_id', '=', (int) $entity['entity_id'])
                ->limit($limit)
                ->execute()
                ->as_array();
            foreach ($rows as $row) {
                $conversation_ids[] = (int) $row['conversation_id'];
            }
        }

        if ($this->table_exists('core_communication_conversations')) {
            $query = \DB::select('id')->from('core_communication_conversations')->limit($limit);
            if ($entity['entity_type'] === 'customer' || $entity['entity_type'] === 'supplier') {
                $query->where('related_party_id', '=', (int) $entity['entity_id']);
            } else {
                $query->where('related_entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
                    ->where('related_entity_id', '=', (int) $entity['entity_id']);
            }
            foreach ($query->execute()->as_array() as $row) {
                $conversation_ids[] = (int) $row['id'];
            }
        }

        $conversation_ids = array_values(array_unique(array_filter($conversation_ids)));
        if (!$conversation_ids) {
            return;
        }

        $rows = \DB::select('id', 'conversation_id', 'direction', 'message_type', 'subject', 'received_at', 'sent_at', 'created_at')
            ->from('core_communication_messages')
            ->where('conversation_id', 'in', $conversation_ids)
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $ts = (int) (!empty($row['received_at']) ? $row['received_at'] : (!empty($row['sent_at']) ? $row['sent_at'] : $row['created_at']));
            if (!$this->in_range($ts, $range)) {
                continue;
            }
            $conversation = $this->entity_resolver->resolve('communication', (int) $row['conversation_id']);
            if (!$conversation || !$this->security_scope->can_view('communication', (int) $row['conversation_id'], $conversation, $user_id)) {
                $hidden_count++;
                continue;
            }
            $this->add_entry($entries, array(
                'event_type' => 'communication_message',
                'event_label' => 'Mensaje de comunicacion',
                'event_date' => $this->date_string($ts),
                'source_module' => 'Comunicaciones',
                'source_entity_type' => 'communication',
                'source_entity_id' => (int) $row['conversation_id'],
                'related_entity_type' => $entity['entity_type'],
                'related_entity_id' => (int) $entity['entity_id'],
                'actor_user_id' => 0,
                'title' => $this->safe_text(!empty($row['subject']) ? $row['subject'] : 'Mensaje', 180),
                'description' => 'Mensaje registrado en conversacion.',
                'icon' => 'fa-envelope',
                'color' => '#0dcaf0',
                'metadata' => array(
                    'message_id' => (int) $row['id'],
                    'direction' => (string) $row['direction'],
                    'message_type' => (string) $row['message_type'],
                    'category' => 'communications',
                ),
                'visible' => true,
                '_ts' => $ts,
            ));
        }
    }

    protected function append_party_records(array &$entries, &$hidden_count, array $entity, array $categories, array $range, $user_id, $limit)
    {
        $party_id = in_array($entity['entity_type'], array('customer', 'supplier'), true) ? (int) $entity['entity_id'] : (int) $entity['primary_party_id'];

        $maps = array(
            'helpdesk' => array('table' => 'core_helpdesk_tickets', 'type' => 'ticket', 'date' => 'created_at', 'actor' => 'assigned_user_id', 'title' => 'subject', 'status' => 'status', 'label' => 'Ticket de soporte', 'icon' => 'fa-life-ring', 'color' => '#dc3545'),
            'billing' => array('table' => 'core_billing_invoices', 'type' => 'invoice', 'date' => 'created_at', 'actor' => 'created_by', 'title' => 'folio', 'status' => 'status', 'label' => 'Factura', 'icon' => 'fa-file-invoice-dollar', 'color' => '#198754'),
            'payments' => array('table' => 'core_payments', 'type' => 'payment', 'date' => 'payment_date', 'actor' => 'created_by', 'title' => 'folio', 'status' => 'status', 'label' => 'Pago', 'icon' => 'fa-money-bill-wave', 'color' => '#198754'),
            'sales_quotes' => array('table' => 'core_sales_quotes', 'type' => 'quotation', 'date' => 'created_at', 'actor' => 'seller_id', 'title' => 'folio', 'status' => 'status', 'label' => 'Cotizacion', 'icon' => 'fa-file-alt', 'color' => '#fd7e14', 'category' => 'sales'),
            'sales_orders' => array('table' => 'core_sales_orders', 'type' => 'order', 'date' => 'created_at', 'actor' => 'seller_id', 'title' => 'folio', 'status' => 'status', 'label' => 'Pedido', 'icon' => 'fa-shopping-cart', 'color' => '#fd7e14', 'category' => 'sales'),
            'crm_opportunities' => array('table' => 'core_crm_opportunities', 'type' => 'opportunity', 'date' => 'created_at', 'actor' => 'owner_user_id', 'title' => 'title', 'status' => 'stage', 'label' => 'Oportunidad CRM', 'icon' => 'fa-chart-line', 'color' => '#6610f2', 'category' => 'crm'),
            'crm_activities' => array('table' => 'core_crm_activities', 'type' => 'activity', 'date' => 'created_at', 'actor' => 'assigned_user_id', 'title' => 'subject', 'status' => 'status', 'label' => 'Actividad CRM', 'icon' => 'fa-tasks', 'color' => '#6f42c1', 'category' => 'crm'),
            'recurring_billing' => array('table' => 'core_billing_recurring_profiles', 'type' => 'recurring_billing', 'date' => 'created_at', 'actor' => 'created_by', 'title' => 'folio', 'status' => 'status', 'label' => 'Facturacion recurrente', 'icon' => 'fa-sync-alt', 'color' => '#0d6efd', 'category' => 'billing'),
        );

        foreach ($maps as $map_key => $map) {
            $category = isset($map['category']) ? $map['category'] : $map_key;
            if (!$this->category_enabled($category, $categories) || !$this->table_exists($map['table'])) {
                continue;
            }

            $ids = array();
            if ($entity['entity_type'] === $map['type']) {
                $ids[] = (int) $entity['entity_id'];
            }
            if ($party_id > 0 && $this->field_exists($map['table'], 'party_id')) {
                $query = \DB::select('id')->from($map['table'])->where('party_id', '=', $party_id)->limit($limit);
                if ($this->field_exists($map['table'], 'active')) {
                    $query->where('active', '=', 1);
                }
                foreach ($query->execute()->as_array() as $row) {
                    $ids[] = (int) $row['id'];
                }
            }

            $ids = array_values(array_unique(array_filter($ids)));
            if (!$ids) {
                continue;
            }

            $select = array('id');
            foreach (array($map['date'], $map['actor'], $map['title'], $map['status'], 'updated_at') as $field) {
                if ($field && $this->field_exists($map['table'], $field) && !in_array($field, $select, true)) {
                    $select[] = $field;
                }
            }

            $rows = \DB::select_array($select)->from($map['table'])->where('id', 'in', $ids)->limit($limit)->execute()->as_array();
            foreach ($rows as $row) {
                $ts = $this->timestamp_from_row($row, $map['date']);
                if (!$this->in_range($ts, $range)) {
                    continue;
                }
                $source = $this->entity_resolver->resolve($map['type'], (int) $row['id']);
                if (!$source || !$this->security_scope->can_view($map['type'], (int) $row['id'], $source, $user_id)) {
                    $hidden_count++;
                    continue;
                }
                $this->add_entry($entries, array(
                    'event_type' => $map['type'].'_record',
                    'event_label' => $map['label'],
                    'event_date' => $this->date_string($ts),
                    'source_module' => $source['entity_module'],
                    'source_entity_type' => $source['entity_type'],
                    'source_entity_id' => (int) $source['entity_id'],
                    'related_entity_type' => $entity['entity_type'],
                    'related_entity_id' => (int) $entity['entity_id'],
                    'actor_user_id' => isset($row[$map['actor']]) ? (int) $row[$map['actor']] : 0,
                    'title' => $this->safe_text(!empty($row[$map['title']]) ? $row[$map['title']] : $map['label'], 180),
                    'description' => !empty($row[$map['status']]) ? 'Estado: '.$this->safe_text($row[$map['status']], 60) : '',
                    'icon' => $map['icon'],
                    'color' => $map['color'],
                    'metadata' => array('category' => $category),
                    'visible' => true,
                    '_ts' => $ts,
                ));
            }
        }

        $this->append_rental_contracts($entries, $hidden_count, $entity, $categories, $range, $user_id, $limit);
    }

    protected function append_rental_contracts(array &$entries, &$hidden_count, array $entity, array $categories, array $range, $user_id, $limit)
    {
        if (!$this->category_enabled('rentals_placeholder', $categories) || !$this->table_exists('core_contracts') || !$this->field_exists('core_contracts', 'contract_type')) {
            return;
        }

        $party_id = in_array($entity['entity_type'], array('customer', 'supplier'), true) ? (int) $entity['entity_id'] : (int) $entity['primary_party_id'];
        if ($party_id <= 0) {
            return;
        }

        $rows = \DB::select('id', 'contract_number', 'title', 'status', 'created_at', 'responsible_user_id')
            ->from('core_contracts')
            ->where('party_id', '=', $party_id)
            ->where('contract_type', 'in', array('rental', 'rental_contract', 'rent'))
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $ts = (int) $row['created_at'];
            if (!$this->in_range($ts, $range)) {
                continue;
            }
            $source = $this->entity_resolver->resolve('rental_contract', (int) $row['id']);
            if (!$source || !$this->security_scope->can_view('rental_contract', (int) $row['id'], $source, $user_id)) {
                $hidden_count++;
                continue;
            }
            $this->add_entry($entries, array(
                'event_type' => 'rental_contract_record',
                'event_label' => 'Contrato de renta',
                'event_date' => $this->date_string($ts),
                'source_module' => 'Rentas',
                'source_entity_type' => 'rental_contract',
                'source_entity_id' => (int) $row['id'],
                'related_entity_type' => $entity['entity_type'],
                'related_entity_id' => (int) $entity['entity_id'],
                'actor_user_id' => (int) $row['responsible_user_id'],
                'title' => $this->safe_text(!empty($row['title']) ? $row['title'] : $row['contract_number'], 180),
                'description' => !empty($row['status']) ? 'Estado: '.$this->safe_text($row['status'], 60) : '',
                'icon' => 'fa-retweet',
                'color' => '#0d6efd',
                'metadata' => array('category' => 'rentals_placeholder'),
                'visible' => true,
                '_ts' => $ts,
            ));
        }
    }

    protected function append_collection_actions(array &$entries, &$hidden_count, array $entity, array $categories, array $range, $user_id, $limit)
    {
        if (!$this->category_enabled('collections', $categories) || !$this->table_exists('core_ar_collection_actions')) {
            return;
        }

        $party_id = in_array($entity['entity_type'], array('customer', 'supplier'), true) ? (int) $entity['entity_id'] : (int) $entity['primary_party_id'];
        if ($party_id <= 0) {
            return;
        }

        $party = $this->entity_resolver->resolve('customer', $party_id);
        if (!$party || !$this->security_scope->can_view('customer', $party_id, $party, $user_id)) {
            $hidden_count++;
            return;
        }

        $query = \DB::select('id', 'folio', 'action_type', 'status', 'priority', 'assigned_user_id', 'action_date', 'created_at')
            ->from('core_ar_collection_actions')
            ->where('party_id', '=', $party_id)
            ->limit($limit);

        if ($this->field_exists('core_ar_collection_actions', 'active')) {
            $query->where('active', '=', 1);
        }

        foreach ($query->execute()->as_array() as $row) {
            $ts = $this->timestamp_from_row($row, 'action_date');
            if (!$this->in_range($ts, $range)) {
                continue;
            }

            $this->add_entry($entries, array(
                'event_type' => 'collection_action',
                'event_label' => 'Accion de cobranza',
                'event_date' => $this->date_string($ts),
                'source_module' => 'Cuentas por cobrar',
                'source_entity_type' => 'customer',
                'source_entity_id' => $party_id,
                'related_entity_type' => $entity['entity_type'],
                'related_entity_id' => (int) $entity['entity_id'],
                'actor_user_id' => (int) $row['assigned_user_id'],
                'title' => $this->safe_text(!empty($row['folio']) ? $row['folio'] : $row['action_type'], 120),
                'description' => 'Estado: '.$this->safe_text((string) $row['status'], 60),
                'icon' => 'fa-phone',
                'color' => '#ffc107',
                'metadata' => array(
                    'collection_action_id' => (int) $row['id'],
                    'action_type' => (string) $row['action_type'],
                    'priority' => (string) $row['priority'],
                    'category' => 'collections',
                ),
                'visible' => true,
                '_ts' => $ts,
            ));
        }
    }

    protected function add_entry(array &$entries, array $entry)
    {
        $entry['metadata'] = $this->safe_metadata(isset($entry['metadata']) ? $entry['metadata'] : array());
        $entries[] = $entry;
    }

    protected function safe_metadata(array $metadata)
    {
        $safe = array();
        foreach ($metadata as $key => $value) {
            $key = (string) $key;
            if (preg_match('/(password|secret|token|path|xml|certificate|key|payload|body|sql|trace)/i', $key)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $safe[$key] = $value;
            }
        }
        return $safe;
    }

    protected function dedupe(array $entries)
    {
        $seen = array();
        $deduped = array();
        foreach ($entries as $entry) {
            $key = implode('|', array($entry['event_type'], $entry['source_entity_type'], $entry['source_entity_id'], $entry['related_entity_type'], $entry['related_entity_id'], $entry['event_date']));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $entry;
        }
        return $deduped;
    }

    protected function counts(array $entries)
    {
        $counts = array();
        foreach ($entries as $entry) {
            $category = isset($entry['metadata']['category']) ? (string) $entry['metadata']['category'] : (string) $entry['source_module'];
            if (!isset($counts[$category])) {
                $counts[$category] = 0;
            }
            $counts[$category]++;
        }
        return $counts;
    }

    protected function normalize_categories(array $categories)
    {
        if (!$categories) {
            return $this->default_categories;
        }

        $normalized = array();
        foreach ($categories as $category) {
            $category = strtolower(trim((string) $category));
            if ($category !== '' && in_array($category, $this->default_categories, true)) {
                $normalized[] = $category;
            }
        }
        return $normalized ?: $this->default_categories;
    }

    protected function category_enabled($category, array $categories)
    {
        return in_array($category, $categories, true);
    }

    protected function date_range($date_from, $date_to)
    {
        $from = $date_from ? strtotime($date_from.' 00:00:00') : 0;
        $to = $date_to ? strtotime($date_to.' 23:59:59') : 0;
        return array('from' => $from ?: 0, 'to' => $to ?: 0);
    }

    protected function in_range($timestamp, array $range)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return false;
        }
        if (!empty($range['from']) && $timestamp < (int) $range['from']) {
            return false;
        }
        if (!empty($range['to']) && $timestamp > (int) $range['to']) {
            return false;
        }
        return true;
    }

    protected function timestamp_from_row(array $row, $date_field)
    {
        if ($date_field && isset($row[$date_field])) {
            $value = $row[$date_field];
            if (is_numeric($value)) {
                return (int) $value;
            }
            $time = strtotime((string) $value);
            if ($time) {
                return $time;
            }
        }
        return isset($row['updated_at']) ? (int) $row['updated_at'] : 0;
    }

    protected function date_string($timestamp)
    {
        return $timestamp > 0 ? date('Y-m-d H:i:s', (int) $timestamp) : '';
    }

    protected function safe_text($text, $limit)
    {
        $text = trim(strip_tags((string) $text));
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $limit, 'UTF-8');
        }
        return substr($text, 0, $limit);
    }

    protected function table_exists($table)
    {
        try {
            return \DBUtil::table_exists($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function field_exists($table, $field)
    {
        try {
            return \DBUtil::field_exists($table, array($field));
        } catch (\Exception $e) {
            return false;
        }
    }
}
