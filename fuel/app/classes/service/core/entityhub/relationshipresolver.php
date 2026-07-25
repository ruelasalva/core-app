<?php

class Service_Core_EntityHub_RelationshipResolver
{
    protected $entity_resolver;
    protected $security_scope;

    public function __construct(
        Service_Core_EntityHub_EntityResolver $entity_resolver = null,
        Service_Core_EntityHub_SecurityScope $security_scope = null
    ) {
        $this->entity_resolver = $entity_resolver ?: new Service_Core_EntityHub_EntityResolver();
        $this->security_scope = $security_scope ?: new Service_Core_EntityHub_SecurityScope($this->entity_resolver);
    }

    public function relationships($entity_type, $entity_id, $user_id = null, $limit = 100)
    {
        $limit = max(1, min((int) $limit, 500));
        $entity = $this->entity_resolver->resolve($entity_type, $entity_id);

        if (!$entity) {
            return array('found' => false, 'visible' => false, 'relationships' => array(), 'total' => 0);
        }

        if (!$this->security_scope->can_view_relationships($entity['entity_type'], $entity['entity_id'], $entity, $user_id)) {
            return array('found' => true, 'visible' => false, 'relationships' => array(), 'total' => 0);
        }

        $relationships = array();
        $this->append_primary_party($relationships, $entity, $user_id);
        $this->append_document_links($relationships, $entity, $user_id, $limit);
        $this->append_contract_relations($relationships, $entity, $user_id, $limit);
        $this->append_communication_links($relationships, $entity, $user_id, $limit);
        $this->append_party_operational_links($relationships, $entity, $user_id, $limit);

        $relationships = $this->dedupe($relationships);

        return array(
            'found' => true,
            'visible' => true,
            'entity' => $entity,
            'relationships' => array_slice($relationships, 0, $limit),
            'total' => count($relationships),
        );
    }

    protected function append_primary_party(array &$relationships, array $entity, $user_id)
    {
        $party_id = (int) $entity['primary_party_id'];
        if ($party_id <= 0 || in_array($entity['entity_type'], array('customer', 'supplier'), true)) {
            return;
        }

        $this->add_entity_relationship($relationships, 'customer', $party_id, 'primary_party', 'primary_party_id', 'outgoing', $user_id);
        if (empty($relationships)) {
            $this->add_entity_relationship($relationships, 'supplier', $party_id, 'primary_party', 'primary_party_id', 'outgoing', $user_id);
        }
    }

    protected function append_document_links(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!$this->table_exists('core_document_links')) {
            return;
        }

        $rows = \DB::select('document_id', 'entity_type', 'entity_id', 'link_type', 'created_at')
            ->from('core_document_links')
            ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
            ->where('entity_id', '=', (int) $entity['entity_id'])
            ->where('active', '=', 1)
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $this->add_entity_relationship(
                $relationships,
                'document',
                (int) $row['document_id'],
                !empty($row['link_type']) ? (string) $row['link_type'] : 'document',
                'core_document_links',
                'outgoing',
                $user_id,
                array('created_at' => isset($row['created_at']) ? (int) $row['created_at'] : 0)
            );
        }
    }

    protected function append_contract_relations(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!$this->table_exists('core_contract_relations')) {
            return;
        }

        if (in_array($entity['entity_type'], array('contract', 'rental_contract'), true)) {
            $rows = \DB::select('related_entity_type', 'related_entity_id', 'relation_type', 'created_at')
                ->from('core_contract_relations')
                ->where('contract_id', '=', (int) $entity['entity_id'])
                ->where('active', '=', 1)
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $this->add_entity_relationship($relationships, $row['related_entity_type'], (int) $row['related_entity_id'], $row['relation_type'], 'core_contract_relations', 'outgoing', $user_id);
            }
        }

        $rows = \DB::select('contract_id', 'relation_type', 'created_at')
            ->from('core_contract_relations')
            ->where('related_entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
            ->where('related_entity_id', '=', (int) $entity['entity_id'])
            ->where('active', '=', 1)
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $this->add_entity_relationship($relationships, 'contract', (int) $row['contract_id'], $row['relation_type'], 'core_contract_relations', 'incoming', $user_id);
        }
    }

    protected function append_communication_links(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!$this->table_exists('core_communication_message_links')) {
            return;
        }

        $rows = \DB::select(\DB::expr('DISTINCT conversation_id'), 'entity_type', 'entity_id', 'link_type', 'created_at')
            ->from('core_communication_message_links')
            ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity['entity_type']))
            ->where('entity_id', '=', (int) $entity['entity_id'])
            ->limit($limit)
            ->execute()
            ->as_array();

        foreach ($rows as $row) {
            $this->add_entity_relationship($relationships, 'communication', (int) $row['conversation_id'], !empty($row['link_type']) ? $row['link_type'] : 'communication', 'core_communication_message_links', 'outgoing', $user_id);
        }

        $party_id = (int) $entity['primary_party_id'];
        if ($party_id > 0 && $this->field_exists('core_communication_message_links', 'party_id')) {
            $rows = \DB::select(\DB::expr('DISTINCT conversation_id'))
                ->from('core_communication_message_links')
                ->where('party_id', '=', $party_id)
                ->limit($limit)
                ->execute()
                ->as_array();

            foreach ($rows as $row) {
                $this->add_entity_relationship($relationships, 'communication', (int) $row['conversation_id'], 'party_communication', 'core_communication_message_links', 'outgoing', $user_id);
            }
        }
    }

    protected function append_party_operational_links(array &$relationships, array $entity, $user_id, $limit)
    {
        if (!in_array($entity['entity_type'], array('customer', 'supplier'), true)) {
            return;
        }

        $party_id = (int) $entity['entity_id'];
        $maps = array(
            array('table' => 'core_contracts', 'entity_type' => 'contract', 'relation' => 'contract'),
            array('table' => 'core_billing_invoices', 'entity_type' => 'invoice', 'relation' => 'invoice'),
            array('table' => 'core_payments', 'entity_type' => 'payment', 'relation' => 'payment'),
            array('table' => 'core_sales_quotes', 'entity_type' => 'quotation', 'relation' => 'quotation'),
            array('table' => 'core_sales_orders', 'entity_type' => 'order', 'relation' => 'order'),
            array('table' => 'core_helpdesk_tickets', 'entity_type' => 'ticket', 'relation' => 'ticket'),
        );

        foreach ($maps as $map) {
            if (!$this->table_exists($map['table']) || !$this->field_exists($map['table'], 'party_id')) {
                continue;
            }

            $query = \DB::select('id')->from($map['table'])->where('party_id', '=', $party_id)->limit($limit);
            if ($this->field_exists($map['table'], 'active')) {
                $query->where('active', '=', 1);
            }

            foreach ($query->execute()->as_array() as $row) {
                $this->add_entity_relationship($relationships, $map['entity_type'], (int) $row['id'], $map['relation'], $map['table'], 'outgoing', $user_id);
            }
        }
    }

    protected function add_entity_relationship(array &$relationships, $entity_type, $entity_id, $relation_type, $relation_source, $direction, $user_id, array $metadata = array())
    {
        $related = $this->entity_resolver->resolve($entity_type, $entity_id);
        if (!$related || !$this->security_scope->can_view($related['entity_type'], $related['entity_id'], $related, $user_id)) {
            return;
        }

        $relationships[] = array(
            'entity_type' => $related['entity_type'],
            'entity_id' => $related['entity_id'],
            'entity_code' => $related['entity_code'],
            'entity_name' => $related['entity_name'],
            'entity_module' => $related['entity_module'],
            'relation_type' => (string) $relation_type,
            'relation_source' => (string) $relation_source,
            'direction' => (string) $direction,
            'metadata' => $metadata,
        );
    }

    protected function dedupe(array $relationships)
    {
        $seen = array();
        $deduped = array();
        foreach ($relationships as $relationship) {
            $key = $relationship['entity_type'].'|'.$relationship['entity_id'].'|'.$relationship['relation_type'].'|'.$relationship['relation_source'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $relationship;
        }

        return $deduped;
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
