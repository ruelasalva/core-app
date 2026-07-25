<?php

class Service_Core_EntityHub_SecurityScope
{
    protected $entity_resolver;

    public function __construct(Service_Core_EntityHub_EntityResolver $entity_resolver = null)
    {
        $this->entity_resolver = $entity_resolver ?: new Service_Core_EntityHub_EntityResolver();
    }

    public function can_view($entity_type, $entity_id, array $entity = null, $user_id = null)
    {
        $user_id = $user_id ?: $this->current_user_id();
        $entity_type = $this->entity_resolver->normalize_type($entity_type);
        $entity = $entity ?: $this->entity_resolver->resolve($entity_type, $entity_id);

        if (!$entity || !$user_id) {
            return false;
        }

        if ($this->is_super_admin()) {
            return true;
        }

        switch ($entity_type) {
            case 'customer':
                return $this->can_view_customer($user_id, (int) $entity['entity_id']);

            case 'supplier':
                return $this->has_any_access(array('parties.access[view]', 'suppliers.access[view]', 'purchases.access[view]'));

            case 'lead':
            case 'prospect':
                return $this->has_any_access(array('crm.access[view]'));

            case 'opportunity':
            case 'activity':
                return $this->can_view_party_entity($user_id, $entity, array('crm.access[view]'));

            case 'employee':
                return $this->has_any_access(array('hr.access[view]', 'employees.access[view]'));

            case 'salesperson':
                return $this->has_any_access(array('sales.access[view]', 'commissions.access[view]'));

            case 'company':
            case 'branch':
                return $this->has_any_access(array('config.access[view]', 'company.access[view]'));

            case 'warehouse':
            case 'equipment':
                return $this->has_any_access(array('inventory.access[view]', 'commerce.access[view]'));

            case 'contract':
            case 'rental_contract':
                return $this->has_any_access(array('contracts.access[view]'));

            case 'invoice':
            case 'recurring_billing':
                return $this->can_view_party_entity($user_id, $entity, array('billing.access[view]', 'receivables.access[view]', 'sales.access[view]'));

            case 'payment':
                return $this->can_view_party_entity($user_id, $entity, array('payments.access[view]', 'receivables.access[view]', 'payables.access[view]', 'treasury.access[view]'));

            case 'quotation':
            case 'order':
                return $this->can_view_party_entity($user_id, $entity, array('sales.access[view]'));

            case 'ticket':
                if ((int) $entity['owner_user_id'] === (int) $user_id) {
                    return true;
                }
                return $this->can_view_party_entity($user_id, $entity, array('helpdesk.access[view]'));

            case 'communication':
                return $this->can_view_conversation($user_id, (int) $entity['entity_id']);

            case 'document':
                return $this->has_any_access(array('documents.access[view]', 'contracts.access[view]', 'crm.access[view]'));
        }

        return false;
    }

    public function can_view_relationships($entity_type, $entity_id, array $entity = null, $user_id = null)
    {
        return $this->can_view($entity_type, $entity_id, $entity, $user_id);
    }

    public function current_user_id()
    {
        $user_id = \Auth::get_user_id();
        return is_array($user_id) ? (int) end($user_id) : (int) $user_id;
    }

    public function is_super_admin()
    {
        try {
            return \Auth::member(100);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function can_view_party_entity($user_id, array $entity, array $permissions)
    {
        if (!$this->has_any_access($permissions)) {
            return false;
        }

        $party_id = !empty($entity['primary_party_id']) ? (int) $entity['primary_party_id'] : 0;
        if ($party_id <= 0) {
            return true;
        }

        $party = $this->entity_resolver->resolve('customer', $party_id);
        if ($party) {
            return $this->can_view_customer($user_id, $party_id);
        }

        $supplier = $this->entity_resolver->resolve('supplier', $party_id);
        if ($supplier) {
            return $this->has_any_access(array('parties.access[view]', 'suppliers.access[view]', 'purchases.access[view]'));
        }

        return false;
    }

    protected function can_view_customer($user_id, $party_id)
    {
        if ($this->has_any_access(array('parties.access[view]', 'customers.access[view]')) === false) {
            return false;
        }

        if (class_exists('Service_Core_Crm_CustomerVisibility')) {
            try {
                $visibility = new Service_Core_Crm_CustomerVisibility();
                return (bool) $visibility->can_view_customer($user_id, $party_id);
            } catch (\Exception $e) {
                \Log::warning('EntityHub: CustomerVisibility falló para party '.$party_id.' - '.$e->getMessage());
                return false;
            }
        }

        return $this->has_any_access(array('parties.access[view]'));
    }

    protected function can_view_conversation($user_id, $conversation_id)
    {
        if (!$this->has_any_access(array('communications.access[view]'))) {
            return false;
        }

        if (class_exists('Service_Core_Communications_MailboxAccess')) {
            try {
                $access = new Service_Core_Communications_MailboxAccess();
                if (method_exists($access, 'can_view_conversation')) {
                    return (bool) $access->can_view_conversation($user_id, $conversation_id);
                }
            } catch (\Exception $e) {
                \Log::warning('EntityHub: MailboxAccess falló para conversación '.$conversation_id.' - '.$e->getMessage());
                return false;
            }
        }

        return false;
    }

    protected function has_any_access(array $permissions)
    {
        foreach ($permissions as $permission) {
            try {
                if (\Auth::has_access($permission)) {
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return false;
    }
}
