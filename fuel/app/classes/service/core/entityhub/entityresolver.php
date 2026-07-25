<?php

class Service_Core_EntityHub_EntityResolver
{
    protected $types = array(
        'customer'        => array('table' => 'core_parties', 'prefix' => 'CUS', 'module' => 'CRM', 'icon' => 'fa-user', 'color' => '#0d6efd', 'party_type' => 'customer'),
        'supplier'        => array('table' => 'core_parties', 'prefix' => 'SUP', 'module' => 'Compras', 'icon' => 'fa-truck', 'color' => '#198754', 'party_type' => 'supplier'),
        'lead'            => array('table' => 'core_crm_prospects', 'prefix' => 'LEA', 'module' => 'CRM', 'icon' => 'fa-bullseye', 'color' => '#6610f2'),
        'prospect'        => array('table' => 'core_crm_prospects', 'prefix' => 'PRO', 'module' => 'CRM', 'icon' => 'fa-address-card', 'color' => '#6f42c1'),
        'employee'        => array('table' => 'core_employees', 'prefix' => 'EMP', 'module' => 'Recursos Humanos', 'icon' => 'fa-id-badge', 'color' => '#20c997'),
        'salesperson'     => array('table' => 'core_sales_sellers', 'prefix' => 'SEL', 'module' => 'Ventas', 'icon' => 'fa-user-tie', 'color' => '#fd7e14'),
        'company'         => array('table' => 'core_companies', 'prefix' => 'COM', 'module' => 'Configuración', 'icon' => 'fa-building', 'color' => '#495057'),
        'branch'          => array('table' => 'core_branches', 'prefix' => 'BRA', 'module' => 'Configuración', 'icon' => 'fa-code-branch', 'color' => '#6c757d'),
        'warehouse'       => array('table' => 'core_inventory_warehouses', 'prefix' => 'WH', 'module' => 'Inventario', 'icon' => 'fa-warehouse', 'color' => '#0dcaf0'),
        'contract'        => array('table' => 'core_contracts', 'prefix' => 'CON', 'module' => 'Contratos', 'icon' => 'fa-file-signature', 'color' => '#0d6efd'),
        'invoice'         => array('table' => 'core_billing_invoices', 'prefix' => 'INV', 'module' => 'Facturación', 'icon' => 'fa-file-invoice-dollar', 'color' => '#198754'),
        'payment'         => array('table' => 'core_payments', 'prefix' => 'PAY', 'module' => 'Cobranza/Pagos', 'icon' => 'fa-money-bill-wave', 'color' => '#198754'),
        'quotation'       => array('table' => 'core_sales_quotes', 'prefix' => 'QUO', 'module' => 'Ventas', 'icon' => 'fa-file-alt', 'color' => '#fd7e14'),
        'order'           => array('table' => 'core_sales_orders', 'prefix' => 'ORD', 'module' => 'Ventas', 'icon' => 'fa-shopping-cart', 'color' => '#fd7e14'),
        'opportunity'     => array('table' => 'core_crm_opportunities', 'prefix' => 'OPP', 'module' => 'CRM', 'icon' => 'fa-chart-line', 'color' => '#6610f2'),
        'activity'        => array('table' => 'core_crm_activities', 'prefix' => 'ACT', 'module' => 'CRM', 'icon' => 'fa-tasks', 'color' => '#6f42c1'),
        'ticket'          => array('table' => 'core_helpdesk_tickets', 'prefix' => 'TIC', 'module' => 'Helpdesk', 'icon' => 'fa-life-ring', 'color' => '#dc3545'),
        'communication'   => array('table' => 'core_communication_conversations', 'prefix' => 'MSG', 'module' => 'Comunicaciones', 'icon' => 'fa-envelope', 'color' => '#0dcaf0'),
        'document'        => array('table' => 'core_documents', 'prefix' => 'DOC', 'module' => 'Documentos', 'icon' => 'fa-file', 'color' => '#6c757d'),
        'recurring_billing' => array('table' => 'core_billing_recurring_profiles', 'prefix' => 'REC', 'module' => 'Facturacion recurrente', 'icon' => 'fa-sync-alt', 'color' => '#0d6efd'),
        'equipment'       => array('table' => 'core_commerce_products', 'prefix' => 'EQP', 'module' => 'Inventario', 'icon' => 'fa-print', 'color' => '#20c997'),
        'asset'           => array('table' => '', 'prefix' => 'AST', 'module' => 'Activos', 'icon' => 'fa-cubes', 'color' => '#6c757d', 'future' => true),
        'project'         => array('table' => '', 'prefix' => 'PRJ', 'module' => 'Proyectos', 'icon' => 'fa-project-diagram', 'color' => '#6c757d', 'future' => true),
        'rental_contract' => array('table' => 'core_contracts', 'prefix' => 'REN', 'module' => 'Rentas', 'icon' => 'fa-retweet', 'color' => '#0d6efd', 'contract_types' => array('rental', 'rental_contract', 'rent')),
    );

    public function supported_types()
    {
        return array_keys($this->types);
    }

    public function resolve($entity_type, $entity_id)
    {
        $entity_type = $this->normalize_type($entity_type);
        $entity_id = (int) $entity_id;

        if (!$entity_type || $entity_id <= 0 || !isset($this->types[$entity_type])) {
            return array();
        }

        $config = $this->types[$entity_type];
        if (!empty($config['future']) || empty($config['table']) || !$this->table_exists($config['table'])) {
            return array();
        }

        $query = \DB::select()->from($config['table'])->where('id', '=', $entity_id)->limit(1);
        if (!empty($config['party_type']) && $this->field_exists($config['table'], 'party_type')) {
            $query->where('party_type', '=', $config['party_type']);
        }
        if (!empty($config['contract_types']) && $this->field_exists($config['table'], 'contract_type')) {
            $query->where('contract_type', 'in', $config['contract_types']);
        }

        $row = $query->execute()->current();
        if (!$row) {
            return array();
        }

        $row = (array) $row;

        return array(
            'entity_type'      => $entity_type,
            'entity_id'        => $entity_id,
            'entity_code'      => $this->entity_code($config['prefix'], $entity_id),
            'entity_name'      => $this->entity_name($entity_type, $row),
            'entity_module'    => $config['module'],
            'status'           => $this->status($row),
            'active'           => $this->active($row),
            'visibility_scope' => $this->visibility_scope($entity_type),
            'owner_user_id'    => $this->owner_user_id($entity_type, $row),
            'owner_group_id'   => $this->owner_group_id($row),
            'primary_party_id' => $this->primary_party_id($entity_type, $row),
            'display_icon'     => $config['icon'],
            'display_color'    => $config['color'],
            'source_table'     => $config['table'],
        );
    }

    public function normalize_type($entity_type)
    {
        $entity_type = strtolower(trim((string) $entity_type));
        $aliases = array(
            'party' => 'customer',
            'client' => 'customer',
            'cliente' => 'customer',
            'proveedor' => 'supplier',
            'quote' => 'quotation',
            'sales_quote' => 'quotation',
            'sales_order' => 'order',
            'billing_invoice' => 'invoice',
            'core_billing_invoice' => 'invoice',
            'core_payment' => 'payment',
            'crm_opportunity' => 'opportunity',
            'crm_activity' => 'activity',
            'helpdesk_ticket' => 'ticket',
            'conversation' => 'communication',
            'communication_conversation' => 'communication',
            'billing_recurring_profile' => 'recurring_billing',
            'contract_rental' => 'rental_contract',
        );

        return isset($aliases[$entity_type]) ? $aliases[$entity_type] : $entity_type;
    }

    public function aliases_for($entity_type)
    {
        $entity_type = $this->normalize_type($entity_type);
        $aliases = array(
            $entity_type,
            'entityhub.'.$entity_type,
        );

        if ($entity_type === 'customer') {
            $aliases[] = 'party';
            $aliases[] = 'cliente';
        } elseif ($entity_type === 'supplier') {
            $aliases[] = 'party';
            $aliases[] = 'proveedor';
        } elseif ($entity_type === 'quotation') {
            $aliases[] = 'quote';
            $aliases[] = 'sales_quote';
        } elseif ($entity_type === 'order') {
            $aliases[] = 'sales_order';
        } elseif ($entity_type === 'invoice') {
            $aliases[] = 'billing_invoice';
            $aliases[] = 'core_billing_invoice';
        } elseif ($entity_type === 'payment') {
            $aliases[] = 'core_payment';
        } elseif ($entity_type === 'opportunity') {
            $aliases[] = 'crm_opportunity';
        } elseif ($entity_type === 'activity') {
            $aliases[] = 'crm_activity';
        } elseif ($entity_type === 'ticket') {
            $aliases[] = 'helpdesk_ticket';
        } elseif ($entity_type === 'communication') {
            $aliases[] = 'conversation';
            $aliases[] = 'communication_conversation';
        } elseif ($entity_type === 'recurring_billing') {
            $aliases[] = 'billing_recurring_profile';
        }

        return array_values(array_unique($aliases));
    }

    protected function entity_code($prefix, $id)
    {
        return $prefix.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    protected function entity_name($entity_type, array $row)
    {
        $candidates = array(
            'display_name', 'commercial_name', 'business_name', 'legal_name', 'name',
            'full_name', 'title', 'subject', 'folio', 'number', 'contract_number',
            'invoice_number', 'quote_number', 'order_number', 'sku', 'code', 'uuid',
            'email'
        );

        foreach ($candidates as $field) {
            if (!empty($row[$field])) {
                return trim((string) $row[$field]);
            }
        }

        return ucfirst($entity_type).' #'.(isset($row['id']) ? (int) $row['id'] : 0);
    }

    protected function status(array $row)
    {
        foreach (array('status', 'row_status', 'sat_status', 'payment_status') as $field) {
            if (isset($row[$field]) && $row[$field] !== '') {
                return (string) $row[$field];
            }
        }

        return $this->active($row) ? 'active' : 'inactive';
    }

    protected function active(array $row)
    {
        if (array_key_exists('active', $row)) {
            return (int) $row['active'] === 1;
        }
        return true;
    }

    protected function owner_user_id($entity_type, array $row)
    {
        $fields = array('owner_user_id', 'assigned_user_id', 'responsible_user_id', 'user_id', 'uploaded_by', 'reviewed_by');
        if ($entity_type === 'customer') {
            array_unshift($fields, 'sales_user_id', 'seller_user_id');
        }
        if ($entity_type === 'supplier') {
            array_unshift($fields, 'buyer_user_id');
        }

        foreach ($fields as $field) {
            if (!empty($row[$field])) {
                return (int) $row[$field];
            }
        }

        if (!empty($row['seller_id'])) {
            return $this->seller_user_id((int) $row['seller_id']);
        }

        return 0;
    }

    protected function owner_group_id(array $row)
    {
        foreach (array('owner_group_id', 'assigned_group_id', 'group_id') as $field) {
            if (!empty($row[$field])) {
                return (int) $row[$field];
            }
        }
        return 0;
    }

    protected function primary_party_id($entity_type, array $row)
    {
        if ($entity_type === 'customer' || $entity_type === 'supplier') {
            return isset($row['id']) ? (int) $row['id'] : 0;
        }

        foreach (array('party_id', 'customer_party_id', 'supplier_party_id', 'related_party_id', 'primary_party_id') as $field) {
            if (!empty($row[$field])) {
                return (int) $row[$field];
            }
        }

        return 0;
    }

    protected function visibility_scope($entity_type)
    {
        $map = array(
            'customer' => 'commercial',
            'supplier' => 'purchases',
            'lead' => 'commercial',
            'prospect' => 'commercial',
            'employee' => 'internal',
            'salesperson' => 'commercial',
            'company' => 'configuration',
            'branch' => 'configuration',
            'warehouse' => 'inventory',
            'contract' => 'contracts',
            'rental_contract' => 'contracts',
            'invoice' => 'billing',
            'payment' => 'finance',
            'quotation' => 'sales',
            'order' => 'sales',
            'opportunity' => 'commercial',
            'activity' => 'commercial',
            'ticket' => 'helpdesk',
            'communication' => 'communications',
            'document' => 'documents',
            'recurring_billing' => 'billing',
            'equipment' => 'inventory',
            'asset' => 'future',
            'project' => 'future',
        );

        return isset($map[$entity_type]) ? $map[$entity_type] : 'general';
    }

    protected function seller_user_id($seller_id)
    {
        if ($seller_id <= 0 || !$this->table_exists('core_sales_sellers')) {
            return 0;
        }

        foreach (array('user_id', 'assigned_user_id') as $field) {
            if ($this->field_exists('core_sales_sellers', $field)) {
                $row = \DB::select($field)->from('core_sales_sellers')->where('id', '=', $seller_id)->execute()->current();
                return !empty($row[$field]) ? (int) $row[$field] : 0;
            }
        }

        return 0;
    }

    protected function table_exists($table)
    {
        try {
            return \DBUtil::table_exists($table);
        } catch (\Exception $e) {
            \Log::warning('EntityHub: no fue posible validar tabla '.$table.' - '.$e->getMessage());
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
