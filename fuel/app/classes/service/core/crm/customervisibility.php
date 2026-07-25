<?php

/**
 * Alcance de visibilidad de clientes para CRM.
 */
class Service_Core_Crm_CustomerVisibility
{
    protected $allowed_cache = [];

    public function can_view_all_customers($user_id)
    {
        $user_id = (int) $user_id;
        $groups = \Auth::get_groups();
        foreach ((array) $groups as $group) {
            $group_data = isset($group[1]) ? $group[1] : 0;
            $group_id = is_object($group_data) ? (int) $group_data->id : (int) $group_data;
            if ($group_id === 100) {
                return true;
            }
        }

        return \Auth::has_access('parties.access[view]') || \Auth::has_access('customers.access[view]');
    }

    public function allowed_customer_ids($user_id)
    {
        $user_id = (int) $user_id;
        if ($this->can_view_all_customers($user_id)) {
            return null;
        }
        if (isset($this->allowed_cache[$user_id])) {
            return $this->allowed_cache[$user_id];
        }

        $ids = [];
        $seller_ids = $this->seller_ids_for_user($user_id);

        if (\DBUtil::table_exists('core_parties')) {
            $query = \DB::select('id')
                ->from('core_parties')
                ->where('active', '=', 1)
                ->where('party_type', '=', 'customer')
                ->where_open()
                    ->where('sales_user_id', '=', $user_id);

            if (!empty($seller_ids)) {
                $query->or_where('default_seller_id', 'in', $seller_ids);
            }

            $query->where_close();

            foreach ($query->execute() as $row) {
                $ids[] = (int) $row['id'];
            }
        }

        $ids = array_merge($ids, $this->party_ids_from_helpdesk($user_id));
        $ids = array_merge($ids, $this->party_ids_from_crm($user_id));
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (empty($ids)) {
            \Log::warning('CRM customer visibility: no se pudo determinar cartera para user_id='.(int) $user_id.'. Se aplica alcance cerrado.');
        }

        $this->allowed_cache[$user_id] = $ids;
        return $ids;
    }

    public function apply_customer_scope($query, $user_id, $party_alias = 'p')
    {
        $query->where($party_alias.'.active', '=', 1);
        $query->where($party_alias.'.party_type', '=', 'customer');

        $ids = $this->allowed_customer_ids($user_id);
        if (is_array($ids)) {
            if (empty($ids)) {
                $query->where($party_alias.'.id', '=', -1);
            } else {
                $query->where($party_alias.'.id', 'in', $ids);
            }
        }

        return $query;
    }

    public function apply_party_id_scope($query, $field, $user_id, $allow_empty_party = true)
    {
        $ids = $this->allowed_customer_ids($user_id);
        if ($ids === null) {
            return $query;
        }

        $query->where_open();
        if ($allow_empty_party) {
            $query->where($field, '=', 0);
            if (!empty($ids)) {
                $query->or_where($field, 'in', $ids);
            }
        } elseif (empty($ids)) {
            $query->where($field, '=', -1);
        } else {
            $query->where($field, 'in', $ids);
        }
        $query->where_close();

        return $query;
    }

    public function can_view_customer($user_id, $party_id)
    {
        $party_id = (int) $party_id;
        if ($party_id <= 0) {
            return true;
        }
        if ($this->can_view_all_customers((int) $user_id)) {
            return $this->is_active_customer($party_id);
        }

        $ids = $this->allowed_customer_ids((int) $user_id);
        return in_array($party_id, (array) $ids, true) && $this->is_active_customer($party_id);
    }

    public function apply_email_eligible($query, $party_alias = 'p')
    {
        $query->where($party_alias.'.email', '!=', '');
        return $query;
    }

    public function has_valid_email(array $party)
    {
        return filter_var(trim((string) \Arr::get($party, 'email', '')), FILTER_VALIDATE_EMAIL) ? true : false;
    }

    protected function seller_ids_for_user($user_id)
    {
        if (!\DBUtil::table_exists('core_sales_sellers')) {
            return [];
        }

        $ids = [];
        foreach (\DB::select('id')->from('core_sales_sellers')->where('user_id', '=', (int) $user_id)->where('active', '=', 1)->execute() as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }

    protected function party_ids_from_helpdesk($user_id)
    {
        if (!\DBUtil::table_exists('core_helpdesk_tickets')) {
            return [];
        }

        $ids = [];
        $query = \DB::select('party_id')
            ->from('core_helpdesk_tickets')
            ->where('active', '=', 1)
            ->where('party_id', '>', 0)
            ->where('assigned_user_id', '=', (int) $user_id)
            ->where_open()
                ->where('portal_code', '=', 'clientes')
                ->or_where('source', '=', 'clientes')
            ->where_close();

        foreach ($query->execute() as $row) {
            $ids[] = (int) $row['party_id'];
        }

        return $ids;
    }

    protected function party_ids_from_crm($user_id)
    {
        $ids = [];
        if (\DBUtil::table_exists('core_crm_opportunities')) {
            foreach (\DB::select('party_id')->from('core_crm_opportunities')->where('active', '=', 1)->where('party_id', '>', 0)->where('owner_user_id', '=', (int) $user_id)->execute() as $row) {
                $ids[] = (int) $row['party_id'];
            }
        }
        if (\DBUtil::table_exists('core_crm_activities')) {
            foreach (\DB::select('party_id')->from('core_crm_activities')->where('active', '=', 1)->where('party_id', '>', 0)->where('assigned_user_id', '=', (int) $user_id)->execute() as $row) {
                $ids[] = (int) $row['party_id'];
            }
        }

        return $ids;
    }

    protected function is_active_customer($party_id)
    {
        if (!\DBUtil::table_exists('core_parties')) {
            return false;
        }

        $row = \DB::select('id')
            ->from('core_parties')
            ->where('id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->where('party_type', '=', 'customer')
            ->execute()
            ->current();

        return $row ? true : false;
    }
}
