<?php

class Service_Core_Commissions_RuleMatcher
{
    public function match(array $rule, array $context, array $exclusions = array(), array $beneficiaries = array())
    {
        $reasons = array();
        $warnings = array();

        $event = (string) \Arr::get($context, 'event_code', '');
        if ($event === '' || $event !== (string) \Arr::get($rule, 'event_code', '')) {
            return $this->ignored('event_mismatch', 'El evento no coincide con la regla.');
        }

        $date_result = $this->matches_date($rule, (string) \Arr::get($context, 'simulation_date', date('Y-m-d')));
        if (!$date_result['matches']) {
            return $this->ignored('date_out_of_range', $date_result['reason']);
        }

        $seller_result = $this->matches_seller($context, $beneficiaries);
        if (!$seller_result['matches']) {
            return $this->ignored('seller_mismatch', $seller_result['reason']);
        }
        if ($seller_result['warning'] !== '') {
            $warnings[] = $seller_result['warning'];
        }

        $exclusion = $this->matches_exclusion($context, $exclusions);
        if ($exclusion['excluded']) {
            return array(
                'matches' => false,
                'reason_code' => 'excluded',
                'reason' => $exclusion['reason'],
                'stop' => in_array($exclusion['behavior'], array('stop_group', 'stop_plan'), true),
                'behavior' => $exclusion['behavior'],
                'warnings' => $warnings,
            );
        }

        $reasons[] = 'Evento y vigencia coinciden.';
        if (!empty($beneficiaries)) {
            $reasons[] = 'Beneficiarios válidos para simulación.';
        }

        return array(
            'matches' => true,
            'reason_code' => 'matched',
            'reason' => implode(' ', $reasons),
            'stop' => false,
            'behavior' => '',
            'warnings' => $warnings,
        );
    }

    protected function matches_date(array $rule, $date)
    {
        $from = trim((string) \Arr::get($rule, 'valid_from', ''));
        $until = trim((string) \Arr::get($rule, 'valid_until', ''));

        if ($from !== '' && $date < $from) {
            return array('matches' => false, 'reason' => 'La fecha de simulación es anterior a la vigencia.');
        }
        if ($until !== '' && $date > $until) {
            return array('matches' => false, 'reason' => 'La fecha de simulación es posterior a la vigencia.');
        }

        return array('matches' => true, 'reason' => '');
    }

    protected function matches_seller(array $context, array $beneficiaries)
    {
        $restricted = array();
        foreach ($beneficiaries as $beneficiary) {
            $seller_id = (int) \Arr::get($beneficiary, 'seller_id', 0);
            if ($seller_id > 0) {
                $restricted[] = $seller_id;
            }
        }

        if (empty($restricted)) {
            return array('matches' => true, 'reason' => '', 'warning' => 'La regla no limita vendedor en beneficiarios; se evaluó como regla general.');
        }

        $seller_id = (int) \Arr::get($context, 'seller_id', 0);
        if ($seller_id > 0 && in_array($seller_id, $restricted, true)) {
            return array('matches' => true, 'reason' => '', 'warning' => '');
        }

        return array('matches' => false, 'reason' => 'El vendedor no coincide con los beneficiarios configurados.', 'warning' => '');
    }

    protected function matches_exclusion(array $context, array $exclusions)
    {
        foreach ($exclusions as $exclusion) {
            $type = (string) \Arr::get($exclusion, 'exclusion_type', '');
            $field = $type.'_id';
            $context_id = (int) \Arr::get($context, $field, 0);
            $entity_id = (int) \Arr::get($exclusion, 'entity_id', 0);
            $entity_code = trim((string) \Arr::get($exclusion, 'entity_code', ''));

            $id_match = $entity_id > 0 && $context_id > 0 && $entity_id === $context_id;
            $code_match = $entity_code !== '' && $this->context_code_matches($context, $type, $entity_code);

            if ($id_match || $code_match) {
                return array(
                    'excluded' => true,
                    'behavior' => (string) \Arr::get($exclusion, 'behavior', 'skip_rule'),
                    'reason' => 'Exclusión aplicada por '.$type.'.',
                );
            }
        }

        return array('excluded' => false, 'behavior' => '', 'reason' => '');
    }

    protected function context_code_matches(array $context, $type, $entity_code)
    {
        foreach (array($type.'_code', 'entity_code', 'sku', 'product_code') as $key) {
            if (strtoupper(trim((string) \Arr::get($context, $key, ''))) === strtoupper($entity_code)) {
                return true;
            }
        }
        return false;
    }

    protected function ignored($code, $reason)
    {
        return array(
            'matches' => false,
            'reason_code' => $code,
            'reason' => $reason,
            'stop' => false,
            'behavior' => '',
            'warnings' => array(),
        );
    }
}
