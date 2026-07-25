<?php

class Service_Core_Commissions_ConfigValidator
{
    protected $events = array(
        'quotation_created', 'quotation_approved', 'sales_order_created', 'order_delivered',
        'invoice_issued', 'partial_payment', 'full_payment', 'contract_signed',
        'contract_activated', 'recurring_billing', 'manual_event', 'event_bus',
    );

    protected $bases = array('subtotal', 'total', 'margin', 'fixed_amount', 'recurring_amount', 'quantity', 'future_formula');
    protected $statuses = array('draft', 'testing', 'published', 'archived');
    protected $behaviors = array('skip_rule', 'stop_group', 'stop_plan', 'allow_fallback');
    protected $beneficiary_types = array('salesperson', 'supervisor', 'commercial_manager', 'partner', 'external_agent');
    protected $exclusion_types = array('product', 'category', 'brand', 'customer', 'supplier', 'contract', 'campaign', 'salesperson', 'region');

    public function code($value, $fallback)
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            $value = strtoupper((string) $fallback);
        }
        $value = preg_replace('/[^A-Z0-9_\-]+/', '_', $value);
        return trim($value, '_-') ?: strtoupper((string) $fallback);
    }

    public function text($value, $max = 180)
    {
        $value = trim(strip_tags((string) $value));
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }
        return substr($value, 0, $max);
    }

    public function long_text($value)
    {
        $value = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#is', '', (string) $value);
        return trim(strip_tags($value));
    }

    public function status($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->statuses, true) ? $value : 'draft';
    }

    public function event($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->events, true) ? $value : 'invoice_issued';
    }

    public function calculation_base($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->bases, true) ? $value : 'subtotal';
    }

    public function value_type($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, array('percent', 'fixed'), true) ? $value : 'percent';
    }

    public function beneficiary_type($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->beneficiary_types, true) ? $value : 'salesperson';
    }

    public function exclusion_type($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->exclusion_types, true) ? $value : 'product';
    }

    public function behavior($value)
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->behaviors, true) ? $value : 'skip_rule';
    }

    public function date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]) ? $value : '';
    }

    public function bool($value)
    {
        return (int) (bool) $value;
    }

    public function positive_number($value, $max = 999999999)
    {
        return min($max, max(0, (float) $value));
    }

    public function int($value)
    {
        return max(0, (int) $value);
    }

    public function options()
    {
        return array(
            'events' => $this->option_list($this->events, array(
                'quotation_created' => 'Cotización creada',
                'quotation_approved' => 'Cotización aprobada',
                'sales_order_created' => 'Pedido creado',
                'order_delivered' => 'Entrega',
                'invoice_issued' => 'Factura emitida',
                'partial_payment' => 'Pago parcial',
                'full_payment' => 'Pago completo',
                'contract_signed' => 'Contrato firmado',
                'contract_activated' => 'Contrato activado',
                'recurring_billing' => 'Facturación recurrente',
                'manual_event' => 'Evento manual',
                'event_bus' => 'Event Bus',
            )),
            'calculation_bases' => $this->option_list($this->bases, array(
                'subtotal' => 'Subtotal',
                'total' => 'Total',
                'margin' => 'Margen',
                'fixed_amount' => 'Monto fijo',
                'recurring_amount' => 'Monto recurrente',
                'quantity' => 'Cantidad',
                'future_formula' => 'Fórmula futura',
            )),
            'statuses' => $this->option_list($this->statuses, array(
                'draft' => 'Borrador',
                'testing' => 'Pruebas',
                'published' => 'Publicado',
                'archived' => 'Archivado',
            )),
            'behaviors' => $this->option_list($this->behaviors, array(
                'skip_rule' => 'Omitir regla',
                'stop_group' => 'Detener grupo',
                'stop_plan' => 'Detener plan',
                'allow_fallback' => 'Permitir regla de respaldo',
            )),
            'value_types' => $this->option_list(array('percent', 'fixed'), array(
                'percent' => 'Porcentaje',
                'fixed' => 'Monto fijo',
            )),
            'beneficiary_types' => $this->option_list($this->beneficiary_types, array(
                'salesperson' => 'Vendedor',
                'supervisor' => 'Supervisor',
                'commercial_manager' => 'Gerente comercial',
                'partner' => 'Socio',
                'external_agent' => 'Agente externo',
            )),
            'exclusion_types' => $this->option_list($this->exclusion_types, array(
                'product' => 'Producto',
                'category' => 'Categoría',
                'brand' => 'Marca',
                'customer' => 'Cliente',
                'supplier' => 'Proveedor',
                'contract' => 'Contrato',
                'campaign' => 'Campaña',
                'salesperson' => 'Vendedor',
                'region' => 'Región',
            )),
        );
    }

    protected function option_list(array $values, array $labels)
    {
        $options = array();
        foreach ($values as $value) {
            $options[] = array(
                'value' => $value,
                'label' => isset($labels[$value]) ? $labels[$value] : $value,
            );
        }
        return $options;
    }
}
