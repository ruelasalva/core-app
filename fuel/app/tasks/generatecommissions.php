<?php

namespace Fuel\Tasks;

class Generatecommissions
{
    public function run()
    {
        $options = $this->options();
        $invoice_id = (int) \Arr::get($options, 'invoice', 0);
        $apply = $this->enabled(\Arr::get($options, 'apply', '0'));
        $dry_run = $this->enabled(\Arr::get($options, 'dry-run', $apply ? '0' : '1'));
        $test_fixture = $this->enabled(\Arr::get($options, 'test-fixture', '0'));
        $keep_fixture = $this->enabled(\Arr::get($options, 'keep-fixture', '0'));
        if ($dry_run) {
            $apply = false;
        }

        try {
            $fixture = null;
            $generator = new \Service_Core_Commissions_EntryGenerator();
            if ($invoice_id > 0) {
                if ($test_fixture) {
                    $fixture = $this->prepare_test_fixture($invoice_id);
                    $this->print_fixture($fixture);
                }
                $result = $generator->generate_invoice($invoice_id, $apply, 0);
                if ($fixture) {
                    $result['fixture'] = $fixture;
                    $this->validate_fixture_entries($result, $fixture);
                    $this->cleanup_fixture($fixture, $result, $keep_fixture);
                }
                $this->print_invoice($result);
                return;
            }

            $date_from = trim((string) \Arr::get($options, 'date-from', ''));
            $date_to = trim((string) \Arr::get($options, 'date-to', ''));
            if ($date_from === '' || $date_to === '') {
                $this->usage();
                return;
            }

            $this->print_batch($generator->generate_batch($date_from, $date_to, $apply, 0));
        } catch (\Exception $e) {
            \Log::error('Generatecommissions: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function print_invoice(array $result)
    {
        \Cli::write('Generacion de comisiones pendientes por factura');
        \Cli::write(' - Factura ID: '.(int) \Arr::get($result, 'invoice_id', 0));
        \Cli::write(' - Folio: '.(string) \Arr::get($result, 'invoice_folio', ''));
        \Cli::write(' - Modo: '.(!empty($result['dry_run']) ? 'dry-run' : 'apply'));
        \Cli::write(' - Elegible: '.(!empty($result['eligible']) ? 'si' : 'no'));
        \Cli::write(' - Partidas: '.(int) \Arr::get($result, 'items_found', 0));
        \Cli::write(' - Reglas coincidentes: '.(int) \Arr::get($result, 'matched_rules', 0));
        \Cli::write(' - Reglas ignoradas: '.(int) \Arr::get($result, 'ignored_rules', 0));
        \Cli::write(' - Entradas por crear: '.(int) \Arr::get($result, 'would_create', 0));
        \Cli::write(' - Entradas creadas: '.(int) \Arr::get($result, 'created', 0));
        \Cli::write(' - Duplicados omitidos: '.(int) \Arr::get($result, 'duplicates', 0));
        \Cli::write(' - Canceladas omitidas: '.(int) \Arr::get($result, 'cancelled_skipped', 0));
        \Cli::write(' - Escrituras realizadas: '.(int) \Arr::get($result, 'writes_performed', 0));

        foreach ((array) \Arr::get($result, 'entries', array()) as $entry) {
            \Cli::write(' [ENTRADA] regla='.(int) $entry['config_rule_id']
                .' beneficiario='.(string) $entry['beneficiary_type'].'#'.(int) $entry['beneficiary_id']
                .' base='.number_format((float) $entry['base_amount'], 2)
                .' comision='.number_format((float) $entry['calculated_amount'], 2)
                .' estado='.(string) $entry['status']);
        }
        if (isset($result['fixture_validation'])) {
            $validation = (array) $result['fixture_validation'];
            \Cli::write('Validacion fixture:');
            \Cli::write(' - Entradas fixture encontradas: '.(int) \Arr::get($validation, 'entries_found', 0));
            \Cli::write(' - status=pending: '.$this->yes_no(\Arr::get($validation, 'all_pending', false)));
            \Cli::write(' - earned_at=0: '.$this->yes_no(\Arr::get($validation, 'earned_at_zero', false)));
            \Cli::write(' - settlement_id=0: '.$this->yes_no(\Arr::get($validation, 'settlement_id_zero', false)));
            \Cli::write(' - released_amount=0: '.$this->yes_no(\Arr::get($validation, 'released_amount_zero', false)));
            \Cli::write(' - source_hash presente: '.$this->yes_no(\Arr::get($validation, 'source_hash_present', false)));
            \Cli::write(' - calculation_snapshot_json presente: '.$this->yes_no(\Arr::get($validation, 'snapshot_present', false)));
            \Cli::write(' - config_version_id presente: '.$this->yes_no(\Arr::get($validation, 'config_version_present', false)));
        }
        $this->print_messages($result);
    }

    protected function print_batch(array $result)
    {
        \Cli::write('Generacion de comisiones pendientes por periodo');
        \Cli::write(' - Periodo: '.$result['date_from'].' a '.$result['date_to']);
        \Cli::write(' - Modo: '.(!empty($result['dry_run']) ? 'dry-run' : 'apply'));
        \Cli::write(' - Facturas encontradas: '.(int) $result['invoices_found']);
        \Cli::write(' - Facturas elegibles: '.(int) $result['eligible_invoices']);
        \Cli::write(' - Reglas coincidentes: '.(int) $result['matched_rules']);
        \Cli::write(' - Entradas por crear: '.(int) $result['would_create']);
        \Cli::write(' - Entradas creadas: '.(int) $result['created']);
        \Cli::write(' - Duplicados omitidos: '.(int) $result['duplicates']);
        \Cli::write(' - Canceladas omitidas: '.(int) $result['cancelled_skipped']);
        \Cli::write(' - Escrituras realizadas: '.(int) $result['writes_performed']);
        $this->print_messages($result);
    }

    protected function print_messages(array $result)
    {
        foreach ((array) \Arr::get($result, 'warnings', array()) as $warning) {
            \Cli::write('[ADVERTENCIA] '.$warning);
        }
        foreach ((array) \Arr::get($result, 'errors', array()) as $error) {
            \Cli::write('[ERROR] '.$error);
        }
    }

    protected function print_fixture(array $fixture)
    {
        \Cli::write('Fixture de prueba de comisiones');
        \Cli::write(' - Plan: '.(int) \Arr::get($fixture, 'plan_id', 0));
        \Cli::write(' - Version: '.(int) \Arr::get($fixture, 'version_id', 0));
        \Cli::write(' - Regla: '.(int) \Arr::get($fixture, 'rule_id', 0));
        \Cli::write(' - Vendedor fixture: '.(int) \Arr::get($fixture, 'seller_id', 0));
        \Cli::write(' - Fecha valida: '.(string) \Arr::get($fixture, 'invoice_date', ''));
    }

    protected function usage()
    {
        \Cli::write('Uso:');
        \Cli::write(' $env:FUEL_ENV=\'development\'; php oil refine generatecommissions --invoice=ID --dry-run=1');
        \Cli::write(' $env:FUEL_ENV=\'development\'; php oil refine generatecommissions --invoice=ID --apply=1');
        \Cli::write(' $env:FUEL_ENV=\'development\'; php oil refine generatecommissions --invoice=ID --dry-run=1 --test-fixture=1');
        \Cli::write(' $env:FUEL_ENV=\'development\'; php oil refine generatecommissions --invoice=ID --apply=1 --test-fixture=1');
        \Cli::write(' $env:FUEL_ENV=\'development\'; php oil refine generatecommissions --date-from=YYYY-MM-DD --date-to=YYYY-MM-DD --dry-run=1');
    }

    protected function enabled($value)
    {
        return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'si'), true);
    }

    protected function yes_no($value)
    {
        return !empty($value) ? 'si' : 'no';
    }

    protected function options()
    {
        $options = array();
        foreach ((array) \Arr::get($_SERVER, 'argv', array()) as $arg) {
            if (strpos($arg, '--') !== 0) {
                continue;
            }
            $parts = explode('=', substr($arg, 2), 2);
            $key = trim((string) $parts[0]);
            if ($key !== '') {
                $options[$key] = isset($parts[1]) ? trim((string) $parts[1]) : '1';
            }
        }
        return $options;
    }

    protected function prepare_test_fixture($invoice_id)
    {
        if (\Fuel::$env !== \Fuel::DEVELOPMENT) {
            throw new \RuntimeException('El fixture de comisiones solo puede ejecutarse con FUEL_ENV=development.');
        }

        $invoice = \DB::select()
            ->from('core_billing_invoices')
            ->where('id', '=', (int) $invoice_id)
            ->execute()
            ->current();
        if (!$invoice) {
            throw new \RuntimeException('Factura no encontrada para fixture.');
        }

        $invoice_date = $this->valid_date(\Arr::get($invoice, 'issue_date', ''))
            ? (string) $invoice['issue_date']
            : date('Y-m-d');

        $fixture = array(
            'invoice_id' => (int) $invoice_id,
            'invoice_date' => $invoice_date,
            'created' => array(),
            'cleanup' => array(),
        );

        $seller_id = $this->resolve_invoice_seller($invoice);
        if ($seller_id < 1) {
            $seller_id = $this->ensure_test_seller($invoice, $fixture);
        }
        $fixture['seller_id'] = $seller_id;

        $plan_id = $this->ensure_test_plan($fixture);
        $fixture['plan_id'] = $plan_id;

        $existing = $this->existing_fixture_version($plan_id, $invoice_date);
        if ($existing) {
            $fixture['version_id'] = (int) $existing['version_id'];
            $fixture['group_id'] = (int) $existing['group_id'];
            $fixture['rule_id'] = (int) $existing['rule_id'];
            $fixture['stage_id'] = (int) $existing['stage_id'];
            $fixture['beneficiary_id'] = (int) $existing['beneficiary_id'];
            return $fixture;
        }

        $config = new \Service_Core_Commissions_Configuration();
        $version_id = $config->save_version(array(
            'commercial_plan_id' => $plan_id,
            'code' => 'TEST_COMMISSION_ENGINE_DO_NOT_USE_'.str_replace('-', '', $invoice_date),
            'name' => 'TEST DO NOT USE '.$invoice_date,
            'valid_from' => $invoice_date,
            'valid_until' => $invoice_date,
            'notes' => 'Fixture temporal para validar generacion pendiente. No usar en negocio.',
        ), 0);
        $fixture['created']['version_id'] = $version_id;

        $group_id = $config->save_group(array(
            'version_id' => $version_id,
            'code' => 'TEST_GROUP_DO_NOT_USE_'.str_replace('-', '', $invoice_date),
            'name' => 'TEST grupo fixture',
            'description' => 'Fixture temporal de pruebas.',
            'priority' => 1,
        ), 0);
        $fixture['created']['group_id'] = $group_id;

        $rule_id = $config->save_rule(array(
            'version_id' => $version_id,
            'rule_group_id' => $group_id,
            'code' => 'TEST_RULE_DO_NOT_USE_'.str_replace('-', '', $invoice_date),
            'name' => 'TEST regla factura fixture',
            'event_code' => 'invoice_issued',
            'calculation_base' => 'subtotal',
            'value_type' => 'percent',
            'value' => 1,
            'priority' => 1,
            'accumulated' => 1,
        ), 0);
        $fixture['created']['rule_id'] = $rule_id;

        $stage_id = $config->save_stage(array(
            'rule_id' => $rule_id,
            'code' => 'TEST_STAGE_DO_NOT_USE_'.str_replace('-', '', $invoice_date),
            'name' => 'TEST etapa factura',
            'trigger_event' => 'invoice_issued',
            'release_percent' => 100,
            'sort_order' => 1,
        ), 0);
        $fixture['created']['stage_id'] = $stage_id;

        $beneficiary_id = $config->save_beneficiary(array(
            'rule_id' => $rule_id,
            'beneficiary_type' => 'salesperson',
            'seller_id' => $seller_id,
            'percentage' => 100,
        ), 0);
        $fixture['created']['beneficiary_id'] = $beneficiary_id;

        $config->publish_version($version_id, 0, 'TEST_COMMISSION_ENGINE_DO_NOT_USE fixture de generacion pendiente');

        $fixture['version_id'] = $version_id;
        $fixture['group_id'] = $group_id;
        $fixture['rule_id'] = $rule_id;
        $fixture['stage_id'] = $stage_id;
        $fixture['beneficiary_id'] = $beneficiary_id;
        return $fixture;
    }

    protected function ensure_test_plan(array &$fixture)
    {
        $row = \DB::select('id')
            ->from('core_commission_config_commercial_plans')
            ->where('code', '=', 'TEST_COMMISSION_ENGINE_DO_NOT_USE')
            ->execute()
            ->current();
        if ($row) {
            return (int) $row['id'];
        }

        $plan_id = (new \Service_Core_Commissions_Configuration())->save_plan(array(
            'code' => 'TEST_COMMISSION_ENGINE_DO_NOT_USE',
            'name' => 'TEST COMMISSION ENGINE DO NOT USE',
            'description' => 'Plan temporal para pruebas diagnosticas de generacion de comisiones pendientes.',
        ), 0);
        $fixture['created']['plan_id'] = $plan_id;
        return $plan_id;
    }

    protected function existing_fixture_version($plan_id, $invoice_date)
    {
        $version = \DB::select('id')
            ->from('core_commission_config_versions')
            ->where('commercial_plan_id', '=', (int) $plan_id)
            ->where('status', '=', 'published')
            ->where('valid_from', '=', $invoice_date)
            ->where('valid_until', '=', $invoice_date)
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->execute()
            ->current();
        if (!$version) {
            return null;
        }

        $rule = \DB::select('id', 'rule_group_id')
            ->from('core_commission_config_rules')
            ->where('version_id', '=', (int) $version['id'])
            ->where('event_code', '=', 'invoice_issued')
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->current();
        if (!$rule) {
            return null;
        }

        $stage = \DB::select('id')
            ->from('core_commission_config_rule_stages')
            ->where('rule_id', '=', (int) $rule['id'])
            ->where('trigger_event', '=', 'invoice_issued')
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        $beneficiary = \DB::select('id')
            ->from('core_commission_config_rule_beneficiaries')
            ->where('rule_id', '=', (int) $rule['id'])
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        if (!$stage || !$beneficiary) {
            return null;
        }

        return array(
            'version_id' => (int) $version['id'],
            'group_id' => (int) $rule['rule_group_id'],
            'rule_id' => (int) $rule['id'],
            'stage_id' => (int) $stage['id'],
            'beneficiary_id' => (int) $beneficiary['id'],
        );
    }

    protected function ensure_test_seller(array $invoice, array &$fixture)
    {
        $user_id = max(1, (int) \Arr::get($invoice, 'created_by', 1));
        $code = 'TEST_COMMISSION_SELLER_DO_NOT_USE_'.$user_id;
        $row = \DB::select('id')
            ->from('core_sales_sellers')
            ->where('code', '=', $code)
            ->execute()
            ->current();
        if ($row) {
            return (int) $row['id'];
        }

        $now = time();
        $result = \DB::insert('core_sales_sellers')->set(array(
            'code' => $code,
            'name' => 'TEST COMMISSION SELLER DO NOT USE',
            'seller_type' => 'internal',
            'employee_id' => 0,
            'party_id' => 0,
            'user_id' => $user_id,
            'default_commission_plan_id' => 0,
            'base_commission_percent' => 0,
            'quota_commission_percent' => 0,
            'payment_commission_percent' => 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ))->execute();

        $seller_id = (int) $result[0];
        $fixture['created']['seller_id'] = $seller_id;
        return $seller_id;
    }

    protected function cleanup_fixture(array $fixture, array &$result, $keep_fixture)
    {
        if ($keep_fixture) {
            $result['warnings'][] = 'Fixture conservado por --keep-fixture=1.';
            return;
        }

        if (empty($fixture['created'])) {
            if ((int) \Arr::get($result, 'duplicates', 0) > 0) {
                $deleted_entries = $this->delete_fixture_entries($fixture);
                $this->delete_fixture_config($fixture);
                $this->delete_fixture_seller_if_unused($fixture);
                $result['warnings'][] = 'Fixture reutilizado eliminado despues de validar idempotencia. Entradas de prueba eliminadas: '.$deleted_entries.'.';
                return;
            }
            $result['warnings'][] = 'Fixture reutilizado; no se eliminaron registros existentes.';
            return;
        }

        if ((int) \Arr::get($result, 'created', 0) > 0 || (int) \Arr::get($result, 'duplicates', 0) > 0) {
            $result['warnings'][] = 'Fixture conservado porque existen entradas pendientes o duplicados que dependen de la configuracion de prueba.';
            return;
        }

        $this->delete_created_fixture($fixture);
        $result['warnings'][] = 'Fixture temporal eliminado; no se crearon entradas.';
    }

    protected function delete_fixture_entries(array $fixture)
    {
        $version_id = (int) \Arr::get($fixture, 'version_id', 0);
        if ($version_id < 1) {
            return 0;
        }

        return (int) \DB::delete('core_commission_entries')
            ->where('source_entity_type', '=', 'billing_invoice')
            ->where('source_entity_id', '=', (int) \Arr::get($fixture, 'invoice_id', 0))
            ->where('config_version_id', '=', $version_id)
            ->where('generated_by_engine', '=', \Service_Core_Commissions_EntryGenerator::ENGINE_CODE)
            ->execute();
    }

    protected function delete_fixture_config(array $fixture)
    {
        $map = array(
            'beneficiary_id' => 'core_commission_config_rule_beneficiaries',
            'stage_id' => 'core_commission_config_rule_stages',
            'rule_id' => 'core_commission_config_rules',
            'group_id' => 'core_commission_config_rule_groups',
            'version_id' => 'core_commission_config_versions',
            'plan_id' => 'core_commission_config_commercial_plans',
        );

        foreach ($map as $key => $table) {
            $id = (int) \Arr::get($fixture, $key, 0);
            if ($id > 0) {
                \DB::delete($table)->where('id', '=', $id)->execute();
            }
        }
    }

    protected function delete_created_fixture(array $fixture)
    {
        $created = (array) \Arr::get($fixture, 'created', array());
        $map = array(
            'beneficiary_id' => 'core_commission_config_rule_beneficiaries',
            'stage_id' => 'core_commission_config_rule_stages',
            'rule_id' => 'core_commission_config_rules',
            'group_id' => 'core_commission_config_rule_groups',
            'version_id' => 'core_commission_config_versions',
            'plan_id' => 'core_commission_config_commercial_plans',
            'seller_id' => 'core_sales_sellers',
        );
        foreach ($map as $key => $table) {
            if (!empty($created[$key])) {
                \DB::delete($table)->where('id', '=', (int) $created[$key])->execute();
            }
        }
    }

    protected function delete_fixture_seller_if_unused(array $fixture)
    {
        $seller_id = (int) \Arr::get($fixture, 'seller_id', 0);
        if ($seller_id < 1) {
            return;
        }

        $seller = \DB::select('id', 'code')
            ->from('core_sales_sellers')
            ->where('id', '=', $seller_id)
            ->execute()
            ->current();
        if (!$seller || strpos((string) $seller['code'], 'TEST_COMMISSION_SELLER_DO_NOT_USE_') !== 0) {
            return;
        }

        $entries = \DB::select(\DB::expr('COUNT(*) AS total'))
            ->from('core_commission_entries')
            ->where('seller_id', '=', $seller_id)
            ->execute()
            ->current();
        if ((int) \Arr::get($entries, 'total', 0) > 0) {
            return;
        }

        \DB::delete('core_sales_sellers')->where('id', '=', $seller_id)->execute();
    }

    protected function validate_fixture_entries(array &$result, array $fixture)
    {
        $version_id = (int) \Arr::get($fixture, 'version_id', 0);
        if ($version_id < 1) {
            return;
        }

        $entries = \DB::select()
            ->from('core_commission_entries')
            ->where('source_entity_type', '=', 'billing_invoice')
            ->where('source_entity_id', '=', (int) \Arr::get($fixture, 'invoice_id', 0))
            ->where('config_version_id', '=', $version_id)
            ->where('generated_by_engine', '=', \Service_Core_Commissions_EntryGenerator::ENGINE_CODE)
            ->execute()
            ->as_array();

        $result['fixture_validation'] = array(
            'entries_found' => count($entries),
            'all_pending' => true,
            'earned_at_zero' => true,
            'settlement_id_zero' => true,
            'released_amount_zero' => true,
            'source_hash_present' => true,
            'snapshot_present' => true,
            'config_version_present' => $version_id > 0,
        );

        foreach ($entries as $entry) {
            $result['fixture_validation']['all_pending'] = $result['fixture_validation']['all_pending'] && (string) $entry['status'] === 'pending';
            $result['fixture_validation']['earned_at_zero'] = $result['fixture_validation']['earned_at_zero'] && (int) $entry['earned_at'] === 0;
            $result['fixture_validation']['settlement_id_zero'] = $result['fixture_validation']['settlement_id_zero'] && (int) $entry['settlement_id'] === 0;
            $result['fixture_validation']['released_amount_zero'] = $result['fixture_validation']['released_amount_zero'] && (float) $entry['released_amount'] == 0.0;
            $result['fixture_validation']['source_hash_present'] = $result['fixture_validation']['source_hash_present'] && trim((string) $entry['source_hash']) !== '';
            $result['fixture_validation']['snapshot_present'] = $result['fixture_validation']['snapshot_present'] && trim((string) $entry['calculation_snapshot_json']) !== '';
        }
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

    protected function valid_date($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) === 1;
    }
}
