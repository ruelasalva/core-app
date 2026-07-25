<?php

class Service_Core_Commissions_Simulator
{
    protected $engine;

    public function __construct(Service_Core_Commissions_RuleEngine $engine = null)
    {
        $this->engine = $engine ?: new \Service_Core_Commissions_RuleEngine();
    }

    public function simulate(array $input)
    {
        $input = $this->sanitize_input($input);
        if ($input['event_code'] === '') {
            return array(
                'success' => false,
                'message' => 'Selecciona un evento para simular.',
                'data' => array(),
                'errors' => array('event_required'),
            );
        }

        return $this->engine->simulate($input);
    }

    protected function sanitize_input(array $input)
    {
        return array(
            'event_code' => trim((string) \Arr::get($input, 'event_code', '')),
            'seller_id' => max(0, (int) \Arr::get($input, 'seller_id', 0)),
            'customer_id' => max(0, (int) \Arr::get($input, 'customer_id', 0)),
            'product_id' => max(0, (int) \Arr::get($input, 'product_id', 0)),
            'brand_id' => max(0, (int) \Arr::get($input, 'brand_id', 0)),
            'category_id' => max(0, (int) \Arr::get($input, 'category_id', 0)),
            'contract_id' => max(0, (int) \Arr::get($input, 'contract_id', 0)),
            'subtotal' => max(0, (float) \Arr::get($input, 'subtotal', 0)),
            'total' => max(0, (float) \Arr::get($input, 'total', 0)),
            'quantity' => max(0, (float) \Arr::get($input, 'quantity', 0)),
            'recurring_amount' => max(0, (float) \Arr::get($input, 'recurring_amount', 0)),
            'simulation_date' => trim((string) \Arr::get($input, 'simulation_date', '')),
            'product_code' => trim((string) \Arr::get($input, 'product_code', '')),
            'sku' => trim((string) \Arr::get($input, 'sku', '')),
            'entity_code' => trim((string) \Arr::get($input, 'entity_code', '')),
        );
    }
}
