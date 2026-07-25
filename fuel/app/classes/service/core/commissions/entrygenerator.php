<?php

class Service_Core_Commissions_EntryGenerator
{
    const ENGINE_CODE = 'commission_config_v1';
    const SOURCE_MODULE = 'commission_config';
    const SOURCE_ENTITY_TYPE = 'billing_invoice';
    const TRIGGER_EVENT = 'invoice_issued';

    protected $simulator;
    protected $metadata_cache = array();
    protected $beneficiary_cache = array();
    protected $stage_cache = array();

    public function __construct(Service_Core_Commissions_Simulator $simulator = null)
    {
        $this->simulator = $simulator ?: new \Service_Core_Commissions_Simulator();
    }

    public function generate_invoice($invoice_id, $apply = false, $created_by = 0)
    {
        $this->assert_schema((bool) $apply);

        $invoice_id = (int) $invoice_id;
        $result = $this->empty_result($invoice_id, !$apply);
        if ($invoice_id < 1) {
            $result['errors'][] = 'Captura un ID de factura valido.';
            return $this->finish($result);
        }

        $invoice = $this->invoice($invoice_id);
        if (!$invoice) {
            $result['errors'][] = 'Factura no encontrada.';
            return $this->finish($result);
        }

        $result['invoice_folio'] = (string) \Arr::get($invoice, 'folio', '');
        $eligibility = $this->invoice_eligibility($invoice);
        if (!$eligibility['eligible']) {
            $result['skipped']++;
            if ($eligibility['cancelled']) {
                $result['cancelled_skipped']++;
            }
            $result['warnings'][] = $eligibility['reason'];
            return $this->finish($result);
        }
        $result['eligible'] = true;

        $seller_id = $this->resolve_invoice_seller($invoice);
        if ($seller_id < 1) {
            $result['skipped']++;
            $result['warnings'][] = 'La factura no tiene un vendedor resoluble; no se generaron entradas.';
            return $this->finish($result);
        }

        $items = $this->invoice_items($invoice_id);
        $result['items_found'] = count($items);
        if (empty($items)) {
            $result['skipped']++;
            $result['warnings'][] = 'La factura no tiene partidas activas.';
            return $this->finish($result);
        }

        foreach ($items as $item) {
            $simulation = $this->simulate_item($invoice, $item, $seller_id);
            $result['simulations']++;

            if (empty($simulation['success'])) {
                $result['errors'][] = (string) \Arr::get($simulation, 'message', 'No se pudo simular la partida.');
                continue;
            }

            $data = (array) \Arr::get($simulation, 'data', array());
            $result['ignored_rules'] += count((array) \Arr::get($data, 'ignored_rules', array()));
            $result['warnings'] = array_merge($result['warnings'], (array) \Arr::get($data, 'warnings', array()));

            foreach ((array) \Arr::get($data, 'matched_rules', array()) as $matched_rule) {
                $result['matched_rules']++;
                $candidates = $this->entry_candidates($invoice, $item, $seller_id, $matched_rule);
                if (empty($candidates)) {
                    $result['skipped']++;
                    $result['warnings'][] = 'La regla '.(string) \Arr::get($matched_rule, 'rule_name', '').' no produjo beneficiarios persistibles.';
                    continue;
                }

                foreach ($candidates as $candidate) {
                    $result['would_create']++;
                    $result['entries'][] = $this->entry_explanation($candidate);

                    if (!$apply) {
                        continue;
                    }

                    if ($this->entry_exists($candidate['source_hash'])) {
                        $result['duplicates']++;
                        continue;
                    }

                    try {
                        $this->insert_entry($candidate, (int) $created_by);
                        $result['created']++;
                        $result['writes_performed']++;
                    } catch (\Exception $e) {
                        if ($this->is_duplicate_exception($e)) {
                            $result['duplicates']++;
                            continue;
                        }
                        $result['errors'][] = 'No se pudo crear una entrada para la partida '.(int) $item['id'].'.';
                        \Log::error('EntryGenerator invoice_id='.$invoice_id.' item_id='.(int) $item['id'].' error='.$e->getMessage());
                    }
                }
            }
        }

        if ($result['matched_rules'] === 0) {
            $result['warnings'][] = 'No hubo reglas publicadas coincidentes para la factura.';
        }

        if ($apply && $result['created'] > 0) {
            \Helper_Core_Audit::log(array(
                'module' => 'commissions',
                'action' => 'generate_pending_from_invoice',
                'business_event' => 'commissions.entries.provisioned',
                'entity_type' => 'billing_invoice',
                'entity_id' => $invoice_id,
                'summary' => 'Entradas de comision pendientes generadas desde factura '.$result['invoice_folio'],
                'new_values' => array(
                    'created' => (int) $result['created'],
                    'duplicates' => (int) $result['duplicates'],
                    'status' => 'pending',
                ),
            ));
        }

        return $this->finish($result);
    }

    public function generate_batch($date_from, $date_to, $apply = false, $created_by = 0)
    {
        $date_from = trim((string) $date_from);
        $date_to = trim((string) $date_to);
        if (!$this->valid_date($date_from) || !$this->valid_date($date_to) || $date_from > $date_to) {
            throw new \InvalidArgumentException('Captura un rango de fechas valido en formato YYYY-MM-DD.');
        }

        $summary = array(
            'date_from' => $date_from,
            'date_to' => $date_to,
            'dry_run' => !$apply,
            'invoices_found' => 0,
            'eligible_invoices' => 0,
            'items_found' => 0,
            'matched_rules' => 0,
            'ignored_rules' => 0,
            'would_create' => 0,
            'created' => 0,
            'duplicates' => 0,
            'cancelled_skipped' => 0,
            'skipped' => 0,
            'writes_performed' => 0,
            'warnings' => array(),
            'errors' => array(),
            'invoices' => array(),
        );

        $rows = \DB::select('id')
            ->from('core_billing_invoices')
            ->where('invoice_type', '=', 'sale')
            ->where('issue_date', '>=', $date_from)
            ->where('issue_date', '<=', $date_to)
            ->where('active', '=', 1)
            ->order_by('issue_date', 'asc')
            ->order_by('id', 'asc')
            ->execute()
            ->as_array();

        $summary['invoices_found'] = count($rows);
        foreach ($rows as $row) {
            $invoice_result = $this->generate_invoice((int) $row['id'], (bool) $apply, (int) $created_by);
            $summary['invoices'][] = $invoice_result;
            $summary['eligible_invoices'] += !empty($invoice_result['eligible']) ? 1 : 0;
            foreach (array('items_found', 'matched_rules', 'ignored_rules', 'would_create', 'created', 'duplicates', 'cancelled_skipped', 'skipped', 'writes_performed') as $counter) {
                $summary[$counter] += (int) \Arr::get($invoice_result, $counter, 0);
            }
            $summary['warnings'] = array_merge($summary['warnings'], (array) \Arr::get($invoice_result, 'warnings', array()));
            $summary['errors'] = array_merge($summary['errors'], (array) \Arr::get($invoice_result, 'errors', array()));
        }

        $summary['warnings'] = array_values(array_unique(array_filter($summary['warnings'])));
        $summary['errors'] = array_values(array_unique(array_filter($summary['errors'])));
        return $summary;
    }

    public function source_hash(array $identity)
    {
        $parts = array(
            strtolower(trim((string) \Arr::get($identity, 'source_module', self::SOURCE_MODULE))),
            strtolower(trim((string) \Arr::get($identity, 'source_entity_type', self::SOURCE_ENTITY_TYPE))),
            (string) max(0, (int) \Arr::get($identity, 'source_entity_id', 0)),
            (string) max(0, (int) \Arr::get($identity, 'source_item_id', 0)),
            (string) max(0, (int) \Arr::get($identity, 'config_rule_id', 0)),
            (string) max(0, (int) \Arr::get($identity, 'config_version_id', 0)),
            (string) max(0, (int) \Arr::get($identity, 'config_rule_stage_id', 0)),
            (string) max(0, (int) \Arr::get($identity, 'config_beneficiary_id', 0)),
            strtolower(trim((string) \Arr::get($identity, 'beneficiary_type', ''))),
            (string) max(0, (int) \Arr::get($identity, 'beneficiary_id', 0)),
            strtolower(trim((string) \Arr::get($identity, 'trigger_event', self::TRIGGER_EVENT))),
        );
        return hash('sha256', implode('|', $parts));
    }

    protected function entry_candidates(array $invoice, array $item, $source_seller_id, array $matched_rule)
    {
        $rule_id = (int) \Arr::get($matched_rule, 'rule_id', 0);
        $metadata = $this->rule_metadata($rule_id);
        if (!$metadata) {
            return array();
        }

        $stages = $this->rule_stages($rule_id);
        $generation_stage = null;
        $release_event = null;
        foreach ($stages as $stage) {
            if ($generation_stage === null && (string) $stage['trigger_event'] === self::TRIGGER_EVENT) {
                $generation_stage = $stage;
                continue;
            }
            if ($release_event === null && (string) $stage['trigger_event'] !== self::TRIGGER_EVENT) {
                $release_event = (string) $stage['trigger_event'];
            }
        }

        $beneficiaries = $this->rule_beneficiaries($rule_id);
        if (empty($beneficiaries)) {
            $beneficiaries[] = array(
                'id' => 0,
                'beneficiary_type' => 'salesperson',
                'seller_id' => (int) $source_seller_id,
                'user_id' => 0,
                'party_id' => 0,
                'percentage' => 100,
                'fixed_amount' => 0,
            );
        }

        $candidates = array();
        foreach ($beneficiaries as $beneficiary) {
            $resolved = $this->resolve_beneficiary($beneficiary, (int) $source_seller_id);
            if (!$resolved) {
                continue;
            }

            $rule_amount = (float) \Arr::get($matched_rule, 'estimated_amount', 0);
            $fixed = max(0, (float) \Arr::get($beneficiary, 'fixed_amount', 0));
            $share = max(0, (float) \Arr::get($beneficiary, 'percentage', 100));
            $calculated_amount = $fixed > 0 ? $fixed : ($rule_amount * ($share / 100));
            if ($calculated_amount <= 0) {
                continue;
            }

            $identity = array(
                'source_module' => self::SOURCE_MODULE,
                'source_entity_type' => self::SOURCE_ENTITY_TYPE,
                'source_entity_id' => (int) $invoice['id'],
                'source_item_id' => (int) $item['id'],
                'config_rule_id' => $rule_id,
                'config_version_id' => (int) $metadata['version_id'],
                'config_rule_stage_id' => $generation_stage ? (int) $generation_stage['id'] : 0,
                'config_beneficiary_id' => (int) \Arr::get($beneficiary, 'id', 0),
                'beneficiary_type' => $resolved['beneficiary_type'],
                'beneficiary_id' => $resolved['beneficiary_id'],
                'trigger_event' => self::TRIGGER_EVENT,
            );

            $snapshot = $this->snapshot(
                $invoice,
                $item,
                $metadata,
                $matched_rule,
                $beneficiary,
                $resolved,
                $stages,
                $calculated_amount
            );

            $candidates[] = array(
                'seller_id' => (int) $resolved['seller_id'],
                'plan_id' => 0,
                'rule_id' => 0,
                'quota_id' => 0,
                'trigger_event' => self::TRIGGER_EVENT,
                'source_module' => self::SOURCE_MODULE,
                'source_entity_type' => self::SOURCE_ENTITY_TYPE,
                'source_entity_id' => (int) $invoice['id'],
                'source_item_id' => (int) $item['id'],
                'party_id' => (int) $invoice['party_id'],
                'product_id' => (int) $item['product_id'],
                'currency_code' => (string) $invoice['currency_code'],
                'base_amount' => round((float) \Arr::get($matched_rule, 'base_amount', 0), 2),
                'commission_percent' => (string) \Arr::get($matched_rule, 'value_type', '') === 'percent'
                    ? round((float) \Arr::get($matched_rule, 'value', 0), 4)
                    : 0,
                'commission_amount' => round($calculated_amount, 2),
                'status' => 'pending',
                'earned_at' => 0,
                'settlement_id' => 0,
                'notes' => 'Provisionada desde factura '.$invoice['folio'].' por regla '.$metadata['rule_name'].'.',
                'config_plan_id' => (int) $metadata['config_plan_id'],
                'config_version_id' => (int) $metadata['version_id'],
                'config_rule_id' => $rule_id,
                'config_rule_stage_id' => $generation_stage ? (int) $generation_stage['id'] : null,
                'config_beneficiary_id' => (int) \Arr::get($beneficiary, 'id', 0) ?: null,
                'beneficiary_type' => $resolved['beneficiary_type'],
                'beneficiary_id' => (int) $resolved['beneficiary_id'],
                'release_event' => $release_event,
                'calculation_base' => (string) \Arr::get($matched_rule, 'base_used', ''),
                'calculation_rate' => round((float) \Arr::get($matched_rule, 'value', 0), 6),
                'calculated_amount' => round($calculated_amount, 6),
                'released_amount' => 0,
                'released_percent' => 0,
                'source_hash' => $this->source_hash($identity),
                'calculation_snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'generated_by_engine' => self::ENGINE_CODE,
                'generated_at' => time(),
                'approved_by' => null,
                'approved_at' => null,
                'paid_at' => null,
                'reversed_entry_id' => null,
                'active' => 1,
            );
        }

        return $candidates;
    }

    protected function snapshot(array $invoice, array $item, array $metadata, array $rule, array $beneficiary, array $resolved, array $stages, $calculated_amount)
    {
        return array(
            'schema_version' => 1,
            'engine' => self::ENGINE_CODE,
            'plan' => array(
                'id' => (int) $metadata['config_plan_id'],
                'code' => (string) $metadata['plan_code'],
                'name' => (string) $metadata['plan_name'],
            ),
            'version' => array(
                'id' => (int) $metadata['version_id'],
                'code' => (string) $metadata['version_code'],
                'name' => (string) $metadata['version_name'],
            ),
            'rule' => array(
                'id' => (int) $metadata['rule_id'],
                'code' => (string) $metadata['rule_code'],
                'name' => (string) $metadata['rule_name'],
                'calculation_base' => (string) \Arr::get($rule, 'base_used', ''),
                'value_type' => (string) \Arr::get($rule, 'value_type', ''),
                'value' => (float) \Arr::get($rule, 'value', 0),
            ),
            'event' => array(
                'code' => self::TRIGGER_EVENT,
                'date' => $this->simulation_date($invoice),
            ),
            'beneficiary' => array(
                'config_beneficiary_id' => (int) \Arr::get($beneficiary, 'id', 0),
                'type' => (string) $resolved['beneficiary_type'],
                'id' => (int) $resolved['beneficiary_id'],
                'seller_id' => (int) $resolved['seller_id'],
                'percentage' => (float) \Arr::get($beneficiary, 'percentage', 100),
                'fixed_amount' => (float) \Arr::get($beneficiary, 'fixed_amount', 0),
            ),
            'invoice' => array(
                'id' => (int) $invoice['id'],
                'folio' => (string) $invoice['folio'],
                'customer_id' => (int) $invoice['party_id'],
                'currency' => (string) $invoice['currency_code'],
            ),
            'item' => array(
                'id' => (int) $item['id'],
                'product_id' => (int) $item['product_id'],
                'product_code' => (string) \Arr::get($item, 'sku', ''),
                'brand_id' => (int) \Arr::get($item, 'brand_id', 0),
                'category_id' => (int) \Arr::get($item, 'category_id', 0),
                'quantity' => (float) $item['quantity'],
            ),
            'calculation' => array(
                'base' => (string) \Arr::get($rule, 'base_used', ''),
                'base_amount' => (float) \Arr::get($rule, 'base_amount', 0),
                'rule_amount' => (float) \Arr::get($rule, 'estimated_amount', 0),
                'calculated_amount' => round((float) $calculated_amount, 6),
            ),
            'stages' => array_values(array_map(function ($stage) {
                return array(
                    'id' => (int) $stage['id'],
                    'trigger_event' => (string) $stage['trigger_event'],
                    'release_percent' => (float) $stage['release_percent'],
                    'sort_order' => (int) $stage['sort_order'],
                );
            }, $stages)),
            'warnings' => array_values(array_filter((array) \Arr::get($rule, 'warnings', array()))),
            'generated_at' => time(),
        );
    }

    protected function simulate_item(array $invoice, array $item, $seller_id)
    {
        $subtotal = ((float) $item['unit_price'] * (float) $item['quantity']) - (float) $item['discount_amount'];
        return $this->simulator->simulate(array(
            'event_code' => self::TRIGGER_EVENT,
            'seller_id' => (int) $seller_id,
            'customer_id' => (int) $invoice['party_id'],
            'product_id' => (int) $item['product_id'],
            'brand_id' => (int) \Arr::get($item, 'brand_id', 0),
            'category_id' => (int) \Arr::get($item, 'category_id', 0),
            'contract_id' => $this->invoice_contract_id($invoice),
            'subtotal' => max(0, $subtotal),
            'total' => max(0, (float) $item['line_total']),
            'quantity' => max(0, (float) $item['quantity']),
            'recurring_amount' => 0,
            'simulation_date' => $this->simulation_date($invoice),
            'product_code' => (string) \Arr::get($item, 'sku', ''),
            'sku' => (string) \Arr::get($item, 'sku', ''),
        ));
    }

    protected function invoice($invoice_id)
    {
        return \DB::select()
            ->from('core_billing_invoices')
            ->where('id', '=', (int) $invoice_id)
            ->execute()
            ->current();
    }

    protected function invoice_items($invoice_id)
    {
        $query = \DB::select(\DB::expr('i.*'))
            ->from(array('core_billing_invoice_items', 'i'));

        if (\DBUtil::table_exists('core_commerce_products')) {
            $query->select_array(array(
                array('p.sku', 'sku'),
                array('p.brand_id', 'brand_id'),
                array('p.category_id', 'category_id'),
            ));
            $query->join(array('core_commerce_products', 'p'), 'left')->on('i.product_id', '=', 'p.id');
        }

        $query->where('i.invoice_id', '=', (int) $invoice_id);
        if (\DBUtil::field_exists('core_billing_invoice_items', array('active'))) {
            $query->where('i.active', '=', 1);
        }
        return $query->order_by('i.sort_order', 'asc')->order_by('i.id', 'asc')->execute()->as_array();
    }

    protected function invoice_eligibility(array $invoice)
    {
        if ((int) \Arr::get($invoice, 'active', 0) !== 1) {
            return array('eligible' => false, 'cancelled' => false, 'reason' => 'La factura esta inactiva.');
        }
        if ((string) \Arr::get($invoice, 'invoice_type', '') !== 'sale') {
            return array('eligible' => false, 'cancelled' => false, 'reason' => 'Solo se procesan facturas de venta.');
        }

        $status = strtolower(trim((string) \Arr::get($invoice, 'status', '')));
        $sat_status = strtolower(trim((string) \Arr::get($invoice, 'sat_status', '')));
        $cancelled = in_array($status, array('cancelled', 'canceled', 'cancelado'), true)
            || in_array($sat_status, array('cancelled', 'canceled', 'cancelado'), true)
            || (int) \Arr::get($invoice, 'cancelled_at', 0) > 0;
        if ($cancelled) {
            return array('eligible' => false, 'cancelled' => true, 'reason' => 'Factura cancelada omitida.');
        }
        if ($status !== 'stamped') {
            return array('eligible' => false, 'cancelled' => false, 'reason' => 'La factura no esta timbrada; estado actual: '.($status ?: 'sin estado').'.');
        }

        return array('eligible' => true, 'cancelled' => false, 'reason' => '');
    }

    protected function resolve_invoice_seller(array $invoice)
    {
        if ((string) \Arr::get($invoice, 'source_entity_type', '') === 'sales_order'
            && (int) \Arr::get($invoice, 'source_entity_id', 0) > 0
            && \DBUtil::table_exists('core_sales_orders')) {
            $order = \DB::select('seller_id')
                ->from('core_sales_orders')
                ->where('id', '=', (int) $invoice['source_entity_id'])
                ->where('active', '=', 1)
                ->execute()
                ->current();
            if ($order && (int) $order['seller_id'] > 0) {
                return (int) $order['seller_id'];
            }
        }

        $party = \DB::select('default_seller_id', 'sales_user_id')
            ->from('core_parties')
            ->where('id', '=', (int) $invoice['party_id'])
            ->where('active', '=', 1)
            ->execute()
            ->current();
        if ($party && (int) $party['default_seller_id'] > 0) {
            return (int) $party['default_seller_id'];
        }
        if ($party && (int) $party['sales_user_id'] > 0) {
            $seller_id = $this->seller_by('user_id', (int) $party['sales_user_id']);
            if ($seller_id > 0) {
                return $seller_id;
            }
        }

        return $this->seller_by('user_id', (int) \Arr::get($invoice, 'created_by', 0));
    }

    protected function resolve_beneficiary(array $beneficiary, $source_seller_id)
    {
        $seller_id = (int) \Arr::get($beneficiary, 'seller_id', 0);
        if ($seller_id < 1 && (int) \Arr::get($beneficiary, 'user_id', 0) > 0) {
            $seller_id = $this->seller_by('user_id', (int) $beneficiary['user_id']);
        }
        if ($seller_id < 1 && (int) \Arr::get($beneficiary, 'party_id', 0) > 0) {
            $seller_id = $this->seller_by('party_id', (int) $beneficiary['party_id']);
        }
        if ($seller_id < 1
            && (string) \Arr::get($beneficiary, 'beneficiary_type', 'salesperson') === 'salesperson'
            && (int) $source_seller_id > 0) {
            $seller_id = (int) $source_seller_id;
        }
        if ($seller_id < 1) {
            return null;
        }

        $beneficiary_id = (int) \Arr::get($beneficiary, 'user_id', 0);
        if ($beneficiary_id < 1) {
            $beneficiary_id = (int) \Arr::get($beneficiary, 'party_id', 0);
        }
        if ($beneficiary_id < 1) {
            $beneficiary_id = $seller_id;
        }

        return array(
            'seller_id' => $seller_id,
            'beneficiary_type' => (string) \Arr::get($beneficiary, 'beneficiary_type', 'salesperson'),
            'beneficiary_id' => $beneficiary_id,
        );
    }

    protected function seller_by($field, $value)
    {
        $value = (int) $value;
        if ($value < 1 || !in_array($field, array('user_id', 'party_id'), true)) {
            return 0;
        }
        $row = \DB::select('id')
            ->from('core_sales_sellers')
            ->where($field, '=', $value)
            ->where('active', '=', 1)
            ->execute()
            ->current();
        return $row ? (int) $row['id'] : 0;
    }

    protected function rule_metadata($rule_id)
    {
        $rule_id = (int) $rule_id;
        if (array_key_exists($rule_id, $this->metadata_cache)) {
            return $this->metadata_cache[$rule_id];
        }

        $row = \DB::select(
                array('r.id', 'rule_id'), array('r.code', 'rule_code'), array('r.name', 'rule_name'),
                array('v.id', 'version_id'), array('v.code', 'version_code'), array('v.name', 'version_name'),
                array('p.id', 'config_plan_id'), array('p.code', 'plan_code'), array('p.name', 'plan_name')
            )
            ->from(array('core_commission_config_rules', 'r'))
            ->join(array('core_commission_config_versions', 'v'), 'inner')->on('r.version_id', '=', 'v.id')
            ->join(array('core_commission_config_commercial_plans', 'p'), 'inner')->on('v.commercial_plan_id', '=', 'p.id')
            ->where('r.id', '=', $rule_id)
            ->where('r.active', '=', 1)
            ->where('v.active', '=', 1)
            ->where('v.status', '=', 'published')
            ->where('p.active', '=', 1)
            ->execute()
            ->current();

        $this->metadata_cache[$rule_id] = $row ?: null;
        return $this->metadata_cache[$rule_id];
    }

    protected function rule_beneficiaries($rule_id)
    {
        $rule_id = (int) $rule_id;
        if (!isset($this->beneficiary_cache[$rule_id])) {
            $this->beneficiary_cache[$rule_id] = \DB::select()
                ->from('core_commission_config_rule_beneficiaries')
                ->where('rule_id', '=', $rule_id)
                ->where('active', '=', 1)
                ->order_by('sort_order', 'asc')
                ->order_by('id', 'asc')
                ->execute()
                ->as_array();
        }
        return $this->beneficiary_cache[$rule_id];
    }

    protected function rule_stages($rule_id)
    {
        $rule_id = (int) $rule_id;
        if (!isset($this->stage_cache[$rule_id])) {
            $this->stage_cache[$rule_id] = \DB::select()
                ->from('core_commission_config_rule_stages')
                ->where('rule_id', '=', $rule_id)
                ->where('active', '=', 1)
                ->where('enabled', '=', 1)
                ->order_by('sort_order', 'asc')
                ->order_by('id', 'asc')
                ->execute()
                ->as_array();
        }
        return $this->stage_cache[$rule_id];
    }

    protected function insert_entry(array $candidate, $created_by)
    {
        $candidate['created_by'] = (int) $created_by;
        \Model_Core_Commission_Entry::forge($candidate)->save();
    }

    protected function entry_exists($source_hash)
    {
        return (bool) \DB::select('id')
            ->from('core_commission_entries')
            ->where('source_hash', '=', (string) $source_hash)
            ->execute()
            ->current();
    }

    protected function entry_explanation(array $candidate)
    {
        return array(
            'source_hash' => (string) $candidate['source_hash'],
            'invoice_id' => (int) $candidate['source_entity_id'],
            'source_item_id' => (int) $candidate['source_item_id'],
            'config_version_id' => (int) $candidate['config_version_id'],
            'config_rule_id' => (int) $candidate['config_rule_id'],
            'config_beneficiary_id' => (int) $candidate['config_beneficiary_id'],
            'beneficiary_type' => (string) $candidate['beneficiary_type'],
            'beneficiary_id' => (int) $candidate['beneficiary_id'],
            'seller_id' => (int) $candidate['seller_id'],
            'base_amount' => (float) $candidate['base_amount'],
            'calculated_amount' => (float) $candidate['calculated_amount'],
            'status' => 'pending',
            'released_amount' => 0,
        );
    }

    protected function invoice_contract_id(array $invoice)
    {
        return in_array((string) \Arr::get($invoice, 'source_entity_type', ''), array('contract', 'rental_contract'), true)
            ? max(0, (int) \Arr::get($invoice, 'source_entity_id', 0))
            : 0;
    }

    protected function simulation_date(array $invoice)
    {
        $date = trim((string) \Arr::get($invoice, 'issue_date', ''));
        return $this->valid_date($date) ? $date : date('Y-m-d');
    }

    protected function valid_date($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) === 1;
    }

    protected function is_duplicate_exception(\Exception $e)
    {
        $message = strtolower($e->getMessage());
        return strpos($message, 'duplicate') !== false || strpos($message, '1062') !== false;
    }

    protected function assert_schema($apply)
    {
        foreach (array(
            'core_billing_invoices', 'core_billing_invoice_items', 'core_parties', 'core_sales_sellers',
            'core_commission_entries', 'core_commission_config_commercial_plans',
            'core_commission_config_versions', 'core_commission_config_rules',
            'core_commission_config_rule_stages', 'core_commission_config_rule_beneficiaries',
        ) as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla requerida '.$table.'.');
            }
        }

        if ($apply) {
            foreach (array('source_hash', 'config_version_id', 'config_rule_id', 'calculation_snapshot_json') as $field) {
                if (!\DBUtil::field_exists('core_commission_entries', array($field))) {
                    throw new \RuntimeException('Falta el campo core_commission_entries.'.$field.'. Ejecuta la migracion 082 en el ambiente correcto.');
                }
            }
        }
    }

    protected function empty_result($invoice_id, $dry_run)
    {
        return array(
            'invoice_id' => (int) $invoice_id,
            'invoice_folio' => '',
            'dry_run' => (bool) $dry_run,
            'eligible' => false,
            'items_found' => 0,
            'simulations' => 0,
            'matched_rules' => 0,
            'ignored_rules' => 0,
            'would_create' => 0,
            'created' => 0,
            'duplicates' => 0,
            'cancelled_skipped' => 0,
            'skipped' => 0,
            'writes_performed' => 0,
            'warnings' => array(),
            'errors' => array(),
            'entries' => array(),
        );
    }

    protected function finish(array $result)
    {
        $result['warnings'] = array_values(array_unique(array_filter($result['warnings'])));
        $result['errors'] = array_values(array_unique(array_filter($result['errors'])));
        return $result;
    }
}
