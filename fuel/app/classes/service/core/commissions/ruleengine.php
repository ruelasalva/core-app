<?php

class Service_Core_Commissions_RuleEngine
{
    protected $matcher;
    protected $calculator;

    public function __construct(
        Service_Core_Commissions_RuleMatcher $matcher = null,
        Service_Core_Commissions_Calculator $calculator = null
    ) {
        $this->matcher = $matcher ?: new \Service_Core_Commissions_RuleMatcher();
        $this->calculator = $calculator ?: new \Service_Core_Commissions_Calculator();
    }

    public function simulate(array $context)
    {
        $context = $this->normalize_context($context);
        $warnings = array();
        $matched = array();
        $ignored = array();
        $estimated_total = 0.0;

        $versions = $this->published_versions($context);
        if (empty($versions)) {
            return $this->response($context, array(), array(), 0, array('No hay versiones publicadas disponibles para simular.'));
        }

        $rules = $this->published_rules($versions);
        if (empty($rules)) {
            return $this->response($context, array(), array(), 0, array('No hay reglas publicadas disponibles para simular.'));
        }

        foreach ($rules as $rule) {
            $rule_id = (int) $rule['id'];
            $exclusions = $this->rule_rows('core_commission_config_rule_exclusions', $rule_id);
            $beneficiaries = $this->rule_rows('core_commission_config_rule_beneficiaries', $rule_id);
            $match = $this->matcher->match($rule, $context, $exclusions, $beneficiaries);

            if (!$match['matches']) {
                $ignored[] = $this->ignored_rule($rule, $match);
                if (!empty($match['stop'])) {
                    $warnings[] = 'Una exclusión detuvo el procesamiento de reglas.';
                    break;
                }
                continue;
            }

            $calculation = $this->calculator->calculate($rule, $context);
            $warnings = array_merge($warnings, \Arr::get($match, 'warnings', array()), \Arr::get($calculation, 'warnings', array()));

            $matched[] = array(
                'rule_id' => $rule_id,
                'rule_code' => (string) $rule['code'],
                'rule_name' => (string) $rule['name'],
                'version_id' => (int) $rule['version_id'],
                'version_name' => (string) \Arr::get($rule, 'version_name', ''),
                'priority' => (int) $rule['priority'],
                'accumulated' => (int) $rule['accumulated'],
                'exclusive' => (int) $rule['exclusive'],
                'stop_processing' => (int) $rule['stop_processing'],
                'event_code' => (string) $rule['event_code'],
                'calculation_base' => (string) $rule['calculation_base'],
                'value_type' => (string) $rule['value_type'],
                'value' => (float) $rule['value'],
                'base_used' => \Arr::get($calculation, 'base_used', ''),
                'base_amount' => (float) \Arr::get($calculation, 'base_amount', 0),
                'estimated_amount' => (float) \Arr::get($calculation, 'estimated_amount', 0),
                'explanation' => $match['reason'],
                'warnings' => array_values(array_filter(\Arr::get($calculation, 'warnings', array()))),
            );

            $estimated_total += (float) \Arr::get($calculation, 'estimated_amount', 0);

            if ((int) $rule['stop_processing'] === 1) {
                $warnings[] = 'La regla '.$rule['name'].' detuvo el procesamiento.';
                break;
            }
            if ((int) $rule['exclusive'] === 1) {
                $warnings[] = 'La regla '.$rule['name'].' es exclusiva; no se evaluaron reglas posteriores.';
                break;
            }
            if ((int) $rule['accumulated'] === 0) {
                $warnings[] = 'La regla '.$rule['name'].' no es acumulable; no se evaluaron reglas posteriores.';
                break;
            }
        }

        return $this->response($context, $matched, $ignored, $estimated_total, $warnings);
    }

    protected function normalize_context(array $context)
    {
        $date = trim((string) \Arr::get($context, 'simulation_date', ''));
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        return array(
            'event_code' => trim((string) \Arr::get($context, 'event_code', '')),
            'seller_id' => max(0, (int) \Arr::get($context, 'seller_id', 0)),
            'customer_id' => max(0, (int) \Arr::get($context, 'customer_id', 0)),
            'product_id' => max(0, (int) \Arr::get($context, 'product_id', 0)),
            'brand_id' => max(0, (int) \Arr::get($context, 'brand_id', 0)),
            'category_id' => max(0, (int) \Arr::get($context, 'category_id', 0)),
            'contract_id' => max(0, (int) \Arr::get($context, 'contract_id', 0)),
            'subtotal' => max(0, (float) \Arr::get($context, 'subtotal', 0)),
            'total' => max(0, (float) \Arr::get($context, 'total', 0)),
            'quantity' => max(0, (float) \Arr::get($context, 'quantity', 0)),
            'recurring_amount' => max(0, (float) \Arr::get($context, 'recurring_amount', 0)),
            'simulation_date' => $date,
            'product_code' => trim((string) \Arr::get($context, 'product_code', '')),
            'sku' => trim((string) \Arr::get($context, 'sku', '')),
            'entity_code' => trim((string) \Arr::get($context, 'entity_code', '')),
        );
    }

    protected function published_versions(array $context)
    {
        $query = \DB::select()
            ->from('core_commission_config_versions')
            ->where('status', '=', 'published')
            ->where('active', '=', 1);

        $date = (string) $context['simulation_date'];
        $query->and_where_open()
            ->where('valid_from', '=', '')
            ->or_where('valid_from', '<=', $date)
            ->and_where_close();
        $query->and_where_open()
            ->where('valid_until', '=', '')
            ->or_where('valid_until', '>=', $date)
            ->and_where_close();

        return $query->order_by('id', 'desc')->execute()->as_array();
    }

    protected function published_rules(array $versions)
    {
        $version_ids = array();
        foreach ($versions as $version) {
            $version_ids[] = (int) $version['id'];
        }

        if (empty($version_ids)) {
            return array();
        }

        return \DB::select(\DB::expr('r.*'), array('v.name', 'version_name'))
            ->from(array('core_commission_config_rules', 'r'))
            ->join(array('core_commission_config_versions', 'v'), 'inner')->on('r.version_id', '=', 'v.id')
            ->where('r.version_id', 'in', $version_ids)
            ->where('r.active', '=', 1)
            ->where('r.enabled', '=', 1)
            ->order_by('r.priority', 'asc')
            ->order_by('r.id', 'asc')
            ->execute()
            ->as_array();
    }

    protected function rule_rows($table, $rule_id)
    {
        if (!\DBUtil::table_exists($table)) {
            return array();
        }

        return \DB::select()
            ->from($table)
            ->where('rule_id', '=', (int) $rule_id)
            ->where('active', '=', 1)
            ->execute()
            ->as_array();
    }

    protected function ignored_rule(array $rule, array $match)
    {
        return array(
            'rule_id' => (int) $rule['id'],
            'rule_code' => (string) $rule['code'],
            'rule_name' => (string) $rule['name'],
            'event_code' => (string) $rule['event_code'],
            'priority' => (int) $rule['priority'],
            'reason_code' => (string) $match['reason_code'],
            'reason' => (string) $match['reason'],
            'behavior' => (string) \Arr::get($match, 'behavior', ''),
        );
    }

    protected function response(array $context, array $matched, array $ignored, $estimated_total, array $warnings)
    {
        return array(
            'success' => true,
            'message' => '',
            'data' => array(
                'context' => $context,
                'matched_rules' => $matched,
                'ignored_rules' => $ignored,
                'estimated_total' => round((float) $estimated_total, 4),
                'warnings' => array_values(array_unique(array_filter($warnings))),
                'writes_performed' => 0,
            ),
            'errors' => array(),
        );
    }
}
