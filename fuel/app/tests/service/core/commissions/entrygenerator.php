<?php

class Test_Service_Core_Commissions_EntryGenerator extends TestCase
{
    public function test_source_hash_is_stable_for_same_identity()
    {
        $generator = new \Service_Core_Commissions_EntryGenerator();
        $identity = array(
            'source_module' => 'commission_config',
            'source_entity_type' => 'billing_invoice',
            'source_entity_id' => 25,
            'source_item_id' => 77,
            'config_rule_id' => 12,
            'config_version_id' => 4,
            'config_beneficiary_id' => 9,
            'beneficiary_type' => 'salesperson',
            'beneficiary_id' => 3,
            'trigger_event' => 'invoice_issued',
        );

        $this->assertSame($generator->source_hash($identity), $generator->source_hash($identity));
        $this->assertSame(64, strlen($generator->source_hash($identity)));
    }

    public function test_source_hash_changes_for_version_or_beneficiary()
    {
        $generator = new \Service_Core_Commissions_EntryGenerator();
        $identity = array(
            'source_module' => 'commission_config',
            'source_entity_type' => 'billing_invoice',
            'source_entity_id' => 25,
            'source_item_id' => 77,
            'config_rule_id' => 12,
            'config_version_id' => 4,
            'config_beneficiary_id' => 9,
            'beneficiary_type' => 'salesperson',
            'beneficiary_id' => 3,
            'trigger_event' => 'invoice_issued',
        );

        $different_version = $identity;
        $different_version['config_version_id'] = 5;
        $different_beneficiary = $identity;
        $different_beneficiary['config_beneficiary_id'] = 10;

        $this->assertNotSame($generator->source_hash($identity), $generator->source_hash($different_version));
        $this->assertNotSame($generator->source_hash($identity), $generator->source_hash($different_beneficiary));
    }
}
