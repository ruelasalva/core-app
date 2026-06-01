<?php

namespace Fuel\Migrations;

class Enhance_fiscal_tax_account_defaults
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_accounting_accounts')) {
            return;
        }

        $this->ensure_tax_accounts();
        $this->ensure_default_mappings();
    }

    public function down()
    {
        // Rollback conservador: no se eliminan cuentas ni mapeos porque pueden
        // haber sido usados o ajustados manualmente despues de aplicar la migracion.
        return;
    }

    protected function ensure_tax_accounts()
    {
        $parent_id = $this->account_id_by_code('2000');

        // Si 2400 ya existe como "Retenciones por pagar", se conserva sin renombrar.
        if (!$this->account_id_by_code('2400')) {
            $this->insert_account('2400', 'IVA retenido por pagar', 'liability', $parent_id, 2, 'credit', 0);
        }

        if (!$this->account_id_by_code('2410')) {
            $this->insert_account('2410', 'ISR retenido por pagar', 'liability', $parent_id, 2, 'credit', 0);
        }
    }

    protected function ensure_default_mappings()
    {
        if (!\DBUtil::table_exists('core_fiscal_account_mappings')) {
            return;
        }

        $this->insert_mapping_if_missing('002', 'retained', '', '2400');
        $this->insert_mapping_if_missing('001', 'retained', '', '2410');
    }

    protected function insert_account($code, $name, $account_type, $parent_id, $level, $nature, $requires_party)
    {
        \DB::insert('core_accounting_accounts')->set([
            'code' => $code,
            'name' => $name,
            'account_type' => $account_type,
            'parent_id' => (int) $parent_id,
            'level' => (int) $level,
            'nature' => $nature,
            'currency_code' => 'MXN',
            'sat_group_code' => '',
            'requires_party' => (int) $requires_party,
            'requires_cost_center' => 0,
            'is_postable' => 1,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function insert_mapping_if_missing($tax_code, $tax_type, $direction, $account_code)
    {
        $existing = \DB::select('id')
            ->from('core_fiscal_account_mappings')
            ->where('tax_code', '=', $tax_code)
            ->where('tax_type', '=', $tax_type)
            ->where('direction', '=', $direction)
            ->execute()
            ->current();

        if ($existing) {
            return;
        }

        $account_id = $this->account_id_by_code($account_code);
        if ($account_id < 1) {
            return;
        }

        \DB::insert('core_fiscal_account_mappings')->set([
            'tax_code' => $tax_code,
            'tax_type' => $tax_type,
            'direction' => $direction,
            'account_id' => $account_id,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function account_id_by_code($code)
    {
        $row = \DB::select('id')
            ->from('core_accounting_accounts')
            ->where('code', '=', $code)
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }
}
