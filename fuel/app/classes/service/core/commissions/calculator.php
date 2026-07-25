<?php

class Service_Core_Commissions_Calculator
{
    public function calculate(array $rule, array $context)
    {
        $base = (string) \Arr::get($rule, 'calculation_base', 'subtotal');
        $value_type = (string) \Arr::get($rule, 'value_type', 'percent');
        $value = (float) \Arr::get($rule, 'value', 0);
        $warnings = array();
        $base_amount = 0.0;
        $estimated = 0.0;

        if ($base === 'margin') {
            return array(
                'success' => false,
                'base_used' => 'margin',
                'base_amount' => 0,
                'estimated_amount' => 0,
                'warnings' => array('La simulación por margen permanece deshabilitada hasta contar con permiso explícito.'),
            );
        }

        if ($base === 'subtotal') {
            $base_amount = $this->number($context, 'subtotal');
        } elseif ($base === 'total') {
            $base_amount = $this->number($context, 'total');
        } elseif ($base === 'quantity') {
            $base_amount = $this->number($context, 'quantity');
        } elseif ($base === 'recurring_amount') {
            $base_amount = $this->number($context, 'recurring_amount');
            if ($base_amount <= 0) {
                $warnings[] = 'No se recibió monto recurrente; se usó 0 para la simulación.';
            }
        } elseif ($base === 'fixed_amount') {
            $base_amount = 1;
        } else {
            return array(
                'success' => false,
                'base_used' => $base,
                'base_amount' => 0,
                'estimated_amount' => 0,
                'warnings' => array('Base de cálculo no soportada en esta fase.'),
            );
        }

        if ($value_type === 'fixed' || $base === 'fixed_amount') {
            $estimated = $value;
        } else {
            $estimated = $base_amount * ($value / 100);
        }

        return array(
            'success' => true,
            'base_used' => $base,
            'base_amount' => round($base_amount, 4),
            'value_type' => $value_type,
            'value' => $value,
            'estimated_amount' => round($estimated, 4),
            'warnings' => $warnings,
        );
    }

    protected function number(array $context, $key)
    {
        return max(0, (float) \Arr::get($context, $key, 0));
    }
}
