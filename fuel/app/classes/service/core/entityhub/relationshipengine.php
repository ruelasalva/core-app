<?php

class Service_Core_EntityHub_RelationshipEngine
{
    protected $entity_resolver;
    protected $security_scope;

    protected $default_categories = array(
        'documents',
        'communications',
        'contracts',
        'tickets',
        'invoices',
        'payments',
        'quotes',
        'orders',
        'opportunities',
        'activities',
        'recurring_billing',
        'rentals_placeholder',
    );

    public function __construct(
        Service_Core_EntityHub_EntityResolver $entity_resolver = null,
        Service_Core_EntityHub_SecurityScope $security_scope = null
    ) {
        $this->entity_resolver = $entity_resolver ?: new Service_Core_EntityHub_EntityResolver();
        $this->security_scope = $security_scope ?: new Service_Core_EntityHub_SecurityScope($this->entity_resolver);
    }

    public function aggregate($entity_type, $entity_id, array $categories = array(), $user_id = null, $limit = 100)
    {
        $limit = max(1, min((int) $limit, 500));
        $entity = $this->entity_resolver->resolve($entity_type, $entity_id);

        if (!$entity) {
            return array('found' => false, 'visible' => false, 'relationships' => array(), 'counts' => array(), 'hidden_count' => 0);
        }

        if (!$this->security_scope->can_view($entity['entity_type'], $entity['entity_id'], $entity, $user_id)) {
            return array('found' => true, 'visible' => false, 'entity' => $entity, 'relationships' => array(), 'counts' => array(), 'hidden_count' => 0);
        }

        $categories = $this->normalize_categories($categories);
        $relationships = array();

        if ($this->category_enabled('documents', $categories)) {
            $this->append_document_relationships($relationships, $entity, $user_id, $limit);
        }
        if ($this->category_enabled('contracts', $categories)) {
            $this->append_contract_relationships($relationships, $entity, $user_id, $limit);
        }
        if ($this->category_enabled('communications', $categories)) {
            $this->append_communication_relationships($relationships, $entity, $user_id, $limit);
        }
        $this->append_party_category_relationships($relationships, $entity, $categories, $user_id, $limit);
        if ($this->category_enabled('payments', $categories)) {
            $this->append_payment_allocation_relationships($relationships, $entity, $user_id, $limit);
        }
        if ($this->category_enabled('rentals_placeholder', $categories)) {
            $this->append_rental_placeholder_relationships($relationships, $entity, $user_id, $limit);
        }

        $relationships = $this->dedupe($relationships);
        usort($relationships, function ($a, $b) {
            if ((int) $a['confidence'] === (int) $b['confidence']) {
                return strcmp((string) $a['relation_type'], (string) $b['relation_type']);
            }
            return (int) $b['confidence'] - (int) $a['confidence'];
        });

        $counts = $this->counts($relationships);
        $hidden_count = 0;
        foreach ($relationships as $relationship) {
            if (empty($relationship['visible'])) {
                $hidden_count++;
            }
        }

        return array(
            'found' => true,
            'visible' => true,
            'entity' => $entity,
            'relationships' => array_slice($relationships, 0, $limit),
            'counts' => $counts,
            'hidden_count' => $hidden_count,
        );
    }

    protected function append_document_relationships(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!$this->table_exists('core_document_links')) {
            return;
        }

        $rows = \DB::select('document_id', 'link_type', 'created_at')
            ->from('core_document_links')
            ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
            ->where('entity_id', '=', (int) $entity['entity_id'])
            ->where('active', '=', 1)
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $this->add_relationship($relationships, $entity, 'document', (int) $row['document_id'], 'documents', 'document_link', 'Documento relacionado', 'core_document_links', 95, 'outgoing', $user_id, array(
                'link_type' => !empty($row['link_type']) ? (string) $row['link_type'] : 'document',
                'created_at' => isset($row['created_at']) ? (int) $row['created_at'] : 0,
            ));
        }
    }

    protected function append_contract_relationships(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!$this->table_exists('core_contract_relations')) {
            return;
        }

        if (in_array($entity['entity_type'], array('contract', 'rental_contract'), true)) {
            $rows = \DB::select('related_entity_type', 'related_entity_id', 'relation_type')
                ->from('core_contract_relations')
                ->where('contract_id', '=', (int) $entity['entity_id'])
                ->where('active', '=', 1)
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $this->add_relationship($relationships, $entity, $row['related_entity_type'], (int) $row['related_entity_id'], 'contracts', $row['relation_type'], 'Relacion de contrato', 'core_contract_relations', 95, 'outgoing', $user_id);
            }
        }

        $rows = \DB::select('contract_id', 'relation_type')
            ->from('core_contract_relations')
            ->where('related_entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
            ->where('related_entity_id', '=', (int) $entity['entity_id'])
            ->where('active', '=', 1)
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $this->add_relationship($relationships, $entity, 'contract', (int) $row['contract_id'], 'contracts', $row['relation_type'], 'Contrato relacionado', 'core_contract_relations', 95, 'incoming', $user_id);
        }
    }

    protected function append_communication_relationships(array &$relationships, array $entity, $user_id, $limit)
    {
        if ($this->table_exists('core_communication_message_links')) {
            $rows = \DB::select(\DB::expr('DISTINCT conversation_id'))
                ->from('core_communication_message_links')
                ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
                ->where('entity_id', '=', (int) $entity['entity_id'])
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $this->add_relationship($relationships, $entity, 'communication', (int) $row['conversation_id'], 'communications', 'message_link', 'Conversacion relacionada', 'core_communication_message_links', 95, 'outgoing', $user_id);
            }
        }

        if ($this->table_exists('core_communication_conversations')) {
            $rows = \DB::select('id')
                ->from('core_communication_conversations')
                ->where('related_entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
                ->where('related_entity_id', '=', (int) $entity['entity_id'])
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $this->add_relationship($relationships, $entity, 'communication', (int) $row['id'], 'communications', 'conversation_entity', 'Conversacion relacionada', 'core_communication_conversations', 90, 'outgoing', $user_id);
            }
        }

        $party_id = (int) $entity['primary_party_id'];
        if ($party_id > 0 && $this->table_exists('core_communication_conversations') && $this->field_exists('core_communication_conversations', 'related_party_id')) {
            $rows = \DB::select('id')
                ->from('core_communication_conversations')
                ->where('related_party_id', '=', $party_id)
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $this->add_relationship($relationships, $entity, 'communication', (int) $row['id'], 'communications', 'party_communication', 'Conversacion del tercero', 'core_communication_conversations', 80, 'outgoing', $user_id);
            }
        }
    }

    protected function append_party_category_relationships(array &$relationships, array $entity, array $categories, $user_id, $limit)
    {
        if (!in_array($entity['entity_type'], array('customer', 'supplier'), true)) {
            return;
        }

        $party_id = (int) $entity['entity_id'];
        $maps = array(
            'tickets' => array('table' => 'core_helpdesk_tickets', 'target' => 'ticket', 'label' => 'Ticket relacionado', 'relation' => 'party_ticket', 'confidence' => 75),
            'invoices' => array('table' => 'core_billing_invoices', 'target' => 'invoice', 'label' => 'Factura relacionada', 'relation' => 'party_invoice', 'confidence' => 75),
            'payments' => array('table' => 'core_payments', 'target' => 'payment', 'label' => 'Pago relacionado', 'relation' => 'party_payment', 'confidence' => 75),
            'quotes' => array('table' => 'core_sales_quotes', 'target' => 'quotation', 'label' => 'Cotizacion relacionada', 'relation' => 'party_quote', 'confidence' => 75),
            'orders' => array('table' => 'core_sales_orders', 'target' => 'order', 'label' => 'Pedido relacionado', 'relation' => 'party_order', 'confidence' => 75),
            'opportunities' => array('table' => 'core_crm_opportunities', 'target' => 'opportunity', 'label' => 'Oportunidad relacionada', 'relation' => 'party_opportunity', 'confidence' => 75),
            'activities' => array('table' => 'core_crm_activities', 'target' => 'activity', 'label' => 'Actividad relacionada', 'relation' => 'party_activity', 'confidence' => 75),
            'recurring_billing' => array('table' => 'core_billing_recurring_profiles', 'target' => 'recurring_billing', 'label' => 'Facturacion recurrente', 'relation' => 'party_recurring_billing', 'confidence' => 70),
        );

        foreach ($maps as $category => $map) {
            if (!$this->category_enabled($category, $categories) || !$this->table_exists($map['table']) || !$this->field_exists($map['table'], 'party_id')) {
                continue;
            }

            $query = \DB::select('id')->from($map['table'])->where('party_id', '=', $party_id)->limit($limit);
            if ($this->field_exists($map['table'], 'active')) {
                $query->where('active', '=', 1);
            }

            foreach ($query->execute()->as_array() as $row) {
                $this->add_relationship($relationships, $entity, $map['target'], (int) $row['id'], $category, $map['relation'], $map['label'], $map['table'], $map['confidence'], 'outgoing', $user_id);
            }
        }
    }

    protected function append_payment_allocation_relationships(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!$this->table_exists('core_payment_allocations')) {
            return;
        }

        if ($entity['entity_type'] === 'payment') {
            $rows = \DB::select('entity_type', 'entity_id', 'amount', 'created_at')
                ->from('core_payment_allocations')
                ->where('payment_id', '=', (int) $entity['entity_id'])
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $this->add_relationship($relationships, $entity, $row['entity_type'], (int) $row['entity_id'], 'payments', 'payment_allocation', 'Aplicacion de pago', 'core_payment_allocations', 95, 'outgoing', $user_id, array(
                    'created_at' => isset($row['created_at']) ? (int) $row['created_at'] : 0,
                ));
            }
            return;
        }

        $rows = \DB::select('payment_id', 'amount', 'created_at')
            ->from('core_payment_allocations')
            ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
            ->where('entity_id', '=', (int) $entity['entity_id'])
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $this->add_relationship($relationships, $entity, 'payment', (int) $row['payment_id'], 'payments', 'payment_allocation', 'Pago aplicado', 'core_payment_allocations', 95, 'incoming', $user_id, array(
                'created_at' => isset($row['created_at']) ? (int) $row['created_at'] : 0,
            ));
        }
    }

    protected function append_rental_placeholder_relationships(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!in_array($entity['entity_type'], array('customer', 'supplier'), true) || !$this->table_exists('core_contracts') || !$this->field_exists('core_contracts', 'party_id')) {
            return;
        }

        $query = \DB::select('id')->from('core_contracts')
            ->where('party_id', '=', (int) $entity['entity_id'])
            ->limit($limit);

        if ($this->field_exists('core_contracts', 'contract_type')) {
            $query->where('contract_type', 'in', array('rental', 'rental_contract', 'rent'));
        }
        if ($this->field_exists('core_contracts', 'active')) {
            $query->where('active', '=', 1);
        }

        foreach ($query->execute()->as_array() as $row) {
            $this->add_relationship($relationships, $entity, 'rental_contract', (int) $row['id'], 'rentals_placeholder', 'rental_contract', 'Contrato de renta', 'core_contracts', 70, 'outgoing', $user_id);
        }
    }

    protected function add_relationship(array &$relationships, array $source, $target_type, $target_id, $category, $relation_type, $relation_label, $source_module, $confidence, $direction, $user_id, array $metadata = array())
    {
        $target = $this->entity_resolver->resolve($target_type, $target_id);
        if (!$target) {
            return;
        }

        if (!$this->security_scope->can_view($target['entity_type'], $target['entity_id'], $target, $user_id)) {
            $relationships[] = array(
                'source_entity_type' => $source['entity_type'],
                'source_entity_id' => (int) $source['entity_id'],
                'target_entity_type' => '',
                'target_entity_id' => 0,
                'relation_type' => (string) $relation_type,
                'relation_label' => 'Relacion restringida',
                'category' => (string) $category,
                'source_module' => (string) $source_module,
                'confidence' => (int) $confidence,
                'direction' => (string) $direction,
                'metadata' => array(),
                'visible' => false,
                'reason_if_hidden' => 'Sin permiso para ver esta relacion.',
            );
            return;
        }

        $relationships[] = array(
            'source_entity_type' => $source['entity_type'],
            'source_entity_id' => (int) $source['entity_id'],
            'target_entity_type' => $target['entity_type'],
            'target_entity_id' => (int) $target['entity_id'],
            'relation_type' => (string) $relation_type,
            'relation_label' => (string) $relation_label,
            'category' => (string) $category,
            'source_module' => (string) $source_module,
            'confidence' => (int) $confidence,
            'direction' => (string) $direction,
            'metadata' => $this->safe_metadata($metadata),
            'visible' => true,
            'reason_if_hidden' => '',
        );
    }

    protected function safe_metadata(array $metadata)
    {
        $safe = array();
        foreach ($metadata as $key => $value) {
            $key = (string) $key;
            if (preg_match('/(password|secret|token|path|xml|certificate|key)/i', $key)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $safe[$key] = $value;
            }
        }
        return $safe;
    }

    protected function dedupe(array $relationships)
    {
        $seen = array();
        $deduped = array();

        foreach ($relationships as $relationship) {
            $key = implode('|', array(
                $relationship['source_entity_type'],
                $relationship['source_entity_id'],
                $relationship['target_entity_type'],
                $relationship['target_entity_id'],
                $relationship['relation_type'],
                $relationship['category'],
                $relationship['visible'] ? '1' : '0',
            ));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $relationship;
        }

        return $deduped;
    }

    protected function counts(array $relationships)
    {
        $counts = array();
        foreach ($relationships as $relationship) {
            $category = (string) $relationship['category'];
            if (!isset($counts[$category])) {
                $counts[$category] = array('visible' => 0, 'hidden' => 0, 'total' => 0);
            }
            $counts[$category]['total']++;
            if (!empty($relationship['visible'])) {
                $counts[$category]['visible']++;
            } else {
                $counts[$category]['hidden']++;
            }
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
