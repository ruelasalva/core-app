<?php

class Service_Core_EntityHub_ProfileResolver
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

    public function profile($entity_type, $entity_id, $user_id = null)
    {
        $entity = $this->entity_resolver->resolve($entity_type, $entity_id);
        if (!$entity) {
            return array('found' => false, 'visible' => false);
        }

        $visible = $this->security_scope->can_view($entity['entity_type'], $entity['entity_id'], $entity, $user_id);
        if (!$visible) {
            return array('found' => true, 'visible' => false, 'entity' => $entity);
        }

        return array(
            'found' => true,
            'visible' => true,
            'entity' => $entity,
            'general_information' => array(
                'nombre' => $entity['entity_name'],
                'codigo' => $entity['entity_code'],
                'tipo' => $entity['entity_type'],
                'modulo' => $entity['entity_module'],
                'alcance' => $entity['visibility_scope'],
            ),
            'status' => array(
                'estado' => $entity['status'],
                'activo' => (bool) $entity['active'],
            ),
            'owner' => $this->owner($entity),
            'assigned_group' => $this->group($entity['owner_group_id']),
            'primary_party' => $this->primary_party($entity),
            'entity_module' => $entity['entity_module'],
            'basic_kpis' => $this->basic_kpis($entity),
            'business_labels' => $this->business_labels($entity),
        );
    }

    protected function owner(array $entity)
    {
        $user_id = (int) $entity['owner_user_id'];
        if ($user_id <= 0 || !$this->table_exists('users')) {
            return array('user_id' => 0, 'user_name' => '', 'department' => '', 'supervisor' => '');
        }

        $row = \DB::select('id', 'username', 'email')->from('users')->where('id', '=', $user_id)->execute()->current();
        return array(
            'user_id' => $user_id,
            'user_name' => $row ? (string) (!empty($row['username']) ? $row['username'] : $row['email']) : '',
            'department' => '',
            'supervisor' => '',
        );
    }

    protected function group($group_id)
    {
        $group_id = (int) $group_id;
        if ($group_id <= 0 || !$this->table_exists('users_groups')) {
            return array('group_id' => 0, 'group_name' => '');
        }

        $row = \DB::select('id', 'name')->from('users_groups')->where('id', '=', $group_id)->execute()->current();
        return array(
            'group_id' => $group_id,
            'group_name' => $row && !empty($row['name']) ? (string) $row['name'] : '',
        );
    }

    protected function primary_party(array $entity)
    {
        $party_id = (int) $entity['primary_party_id'];
        if ($party_id <= 0) {
            return null;
        }

        $party = $this->entity_resolver->resolve('customer', $party_id);
        if (!$party) {
            $party = $this->entity_resolver->resolve('supplier', $party_id);
        }

        return $party ?: null;
    }

    protected function basic_kpis(array $entity)
    {
        $kpis = array();
        $entity_type = $entity['entity_type'];
        $entity_id = (int) $entity['entity_id'];

        $kpis['documentos_relacionados'] = $this->count_document_links($entity_type, $entity_id);
        $kpis['comunicaciones_relacionadas'] = $this->count_communication_links($entity_type, $entity_id, $entity['primary_party_id']);

        if (in_array($entity_type, array('customer', 'supplier'), true)) {
            $kpis['contratos'] = $this->count_by_party('core_contracts', $entity_id);
            $kpis['tickets'] = $this->count_by_party('core_helpdesk_tickets', $entity_id);
            $kpis['cotizaciones'] = $this->count_by_party('core_sales_quotes', $entity_id);
            $kpis['pedidos'] = $this->count_by_party('core_sales_orders', $entity_id);
        }

        if (in_array($entity_type, array('contract', 'rental_contract'), true)) {
            $kpis['relaciones_de_contrato'] = $this->count_contract_relations($entity_id);
            $kpis['eventos_de_contrato'] = $this->count_contract_events($entity_id);
        }

        return $kpis;
    }

    protected function business_labels(array $entity)
    {
        $labels = array();
        $type = $entity['entity_type'];
        $name = strtolower($entity['entity_name']);

        if ($type === 'customer') {
            $labels[] = 'Customer';
        } elseif ($type === 'supplier') {
            $labels[] = 'Supplier';
        } elseif (in_array($type, array('lead', 'prospect'), true)) {
            $labels[] = 'Prospect';
        } elseif (in_array($type, array('employee', 'company', 'branch', 'warehouse'), true)) {
            $labels[] = 'Internal';
        }

        if (strpos($name, 'distribuidor') !== false || strpos($name, 'distributor') !== false) {
            $labels[] = 'Distributor';
        }
        if (strpos($name, 'gobierno') !== false || strpos($name, 'municipio') !== false || strpos($name, 'gob') !== false) {
            $labels[] = 'Government';
        }
        if (strpos($name, 'hospital') !== false || strpos($name, 'clinica') !== false) {
            $labels[] = 'Hospital';
        }
        if (strpos($name, 'escuela') !== false || strpos($name, 'universidad') !== false || strpos($name, 'colegio') !== false) {
            $labels[] = 'School';
        }

        return array_values(array_unique($labels));
    }

    protected function count_document_links($entity_type, $entity_id)
    {
        if (!$this->table_exists('core_document_links')) {
            return 0;
        }

        return (int) \DB::select(\DB::expr('COUNT(*) AS total'))->from('core_document_links')
            ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity_type))
            ->where('entity_id', '=', (int) $entity_id)
            ->where('active', '=', 1)
            ->execute()->get('total', 0);
    }

    protected function count_communication_links($entity_type, $entity_id, $party_id)
    {
        if (!$this->table_exists('core_communication_message_links')) {
            return 0;
        }

        $query = \DB::select(\DB::expr('COUNT(DISTINCT conversation_id) AS total'))->from('core_communication_message_links')
            ->where('entity_type', 'in', $this->entity_resolver->aliases_for($entity_type))
            ->where('entity_id', '=', (int) $entity_id);

        if ($party_id > 0 && $this->field_exists('core_communication_message_links', 'party_id')) {
            $query->or_where_open()
                ->where('party_id', '=', (int) $party_id)
                ->or_where_close();
        }

        return (int) $query->execute()->get('total', 0);
    }

    protected function count_by_party($table, $party_id)
    {
        if (!$this->table_exists($table) || !$this->field_exists($table, 'party_id')) {
            return 0;
        }

        $query = \DB::select(\DB::expr('COUNT(*) AS total'))->from($table)->where('party_id', '=', (int) $party_id);
        if ($this->field_exists($table, 'active')) {
            $query->where('active', '=', 1);
        }

        return (int) $query->execute()->get('total', 0);
    }

    protected function count_contract_relations($contract_id)
    {
        if (!$this->table_exists('core_contract_relations')) {
            return 0;
        }

        return (int) \DB::select(\DB::expr('COUNT(*) AS total'))->from('core_contract_relations')
            ->where('contract_id', '=', (int) $contract_id)
            ->where('active', '=', 1)
            ->execute()->get('total', 0);
    }

    protected function count_contract_events($contract_id)
    {
        if (!$this->table_exists('core_contract_events')) {
            return 0;
        }

        return (int) \DB::select(\DB::expr('COUNT(*) AS total'))->from('core_contract_events')
            ->where('contract_id', '=', (int) $contract_id)
            ->where('active', '=', 1)
            ->execute()->get('total', 0);
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
