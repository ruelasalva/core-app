<?php
namespace Fuel\Tasks;

/**
 * TAREA REPAIRFISCALACCOUNTS
 *
 * Repara cuentas fiscales base y mapeos contables del motor fiscal.
 *
 * Uso:
 * php oil refine repairfiscalaccounts
 *
 * @package  app
 */
class Repairfiscalaccounts
{
    protected $created = [];
    protected $existing = [];
    protected $skipped = [];

    /**
     * RUN
     *
     * CREA CUENTAS Y MAPEOS FISCALES FALTANTES SIN SOBRESCRIBIR DATOS.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        try {
            $this->assert_accounting_schema();
            $this->ensure_tax_accounts();
            $this->ensure_default_mappings();
            $this->ensure_preliminary_balance_config();
            $this->print_summary();

            \Log::info('Repairfiscalaccounts ejecutado creados='.count($this->created).' existentes='.count($this->existing).' omitidos='.count($this->skipped));
            \Service_Core_Fiscal_EventLogger::log([
                'event_type' => 'fiscal_accounts_repair',
                'event_status' => count($this->skipped) > 0 ? 'warning' : 'success',
                'source_module' => 'fiscal',
                'source_entity_type' => 'fiscal_accounts',
                'summary' => 'Reparacion de cuentas fiscales terminada.',
                'details' => [
                    'created' => $this->created,
                    'existing' => $this->existing,
                    'skipped' => $this->skipped,
                ],
                'executed_by' => 0,
            ]);
        } catch (\Exception $e) {
            \Log::error('Repairfiscalaccounts: '.$e->getMessage());
            \Service_Core_Fiscal_EventLogger::log([
                'event_type' => 'fiscal_accounts_repair',
                'event_status' => 'error',
                'source_module' => 'fiscal',
                'source_entity_type' => 'fiscal_accounts',
                'summary' => 'Error reparando cuentas fiscales.',
                'details' => ['error' => $e->getMessage()],
                'executed_by' => 0,
            ]);
            \Cli::write('Error reparando cuentas fiscales: '.$e->getMessage());
        }
    }

    protected function ensure_tax_accounts()
    {
        $parent_id = $this->account_id_by_code('2000');

        if ($this->account_id_by_code('2400')) {
            $this->existing[] = 'Cuenta 2400 ya existe; no se renombro ni sobrescribio.';
        } else {
            $this->insert_account('2400', 'IVA retenido por pagar', 'liability', $parent_id, 2, 'credit', 0);
            $this->created[] = 'Cuenta 2400 - IVA retenido por pagar';
        }

        if ($this->account_id_by_code('2410')) {
            $this->existing[] = 'Cuenta 2410 - ISR retenido por pagar ya existe.';
        } else {
            $this->insert_account('2410', 'ISR retenido por pagar', 'liability', $parent_id, 2, 'credit', 0);
            $this->created[] = 'Cuenta 2410 - ISR retenido por pagar';
        }

        if ($this->account_id_by_code('2500')) {
            $this->existing[] = 'Cuenta 2500 - Impuestos por pagar preliminares ya existe.';
        } else {
            $this->insert_account('2500', 'Impuestos por pagar preliminares', 'liability', $parent_id, 2, 'credit', 0);
            $this->created[] = 'Cuenta 2500 - Impuestos por pagar preliminares';
        }
    }

    protected function ensure_default_mappings()
    {
        if (!\DBUtil::table_exists('core_fiscal_account_mappings')) {
            $this->skipped[] = 'Tabla core_fiscal_account_mappings no existe; ejecuta migraciones fiscales antes de mapear.';
            return;
        }

        $this->insert_mapping_if_missing('002', 'retained', '', '2400', 'IVA retenido');
        $this->insert_mapping_if_missing('001', 'retained', '', '2410', 'ISR retenido');
    }

    protected function ensure_preliminary_balance_config()
    {
        if (!\DBUtil::table_exists('core_settings')) {
            $this->skipped[] = 'Tabla core_settings no existe; no se pudo guardar fiscal_preliminary_balance_account.';
            return;
        }

        $existing = \DB::select('id', 'value')
            ->from('core_settings')
            ->where('setting_group', '=', 'fiscal')
            ->where('setting_key', '=', 'preliminary_balance_account_id')
            ->execute()
            ->current();

        if ($existing) {
            $this->existing[] = 'Configuracion fiscal.preliminary_balance_account_id ya existe; no se sobrescribio.';
            return;
        }

        $account_id = $this->account_id_by_code('2500');
        if ($account_id < 1) {
            $this->skipped[] = 'No se pudo guardar fiscal.preliminary_balance_account_id porque falta la cuenta 2500.';
            return;
        }

        \DB::insert('core_settings')->set([
            'setting_group' => 'fiscal',
            'setting_key' => 'preliminary_balance_account_id',
            'value' => (string) $account_id,
            'value_type' => 'int',
            'updated_at' => time(),
        ])->execute();

        $this->created[] = 'Configuracion fiscal.preliminary_balance_account_id -> cuenta 2500';
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

    protected function insert_mapping_if_missing($tax_code, $tax_type, $direction, $account_code, $label)
    {
        $existing = \DB::select('id', 'account_id')
            ->from('core_fiscal_account_mappings')
            ->where('tax_code', '=', $tax_code)
            ->where('tax_type', '=', $tax_type)
            ->where('direction', '=', $direction)
            ->execute()
            ->current();

        if ($existing) {
            $this->existing[] = 'Mapeo '.$label.' ya existe; no se sobrescribio.';
            return;
        }

        $account_id = $this->account_id_by_code($account_code);
        if ($account_id < 1) {
            $this->skipped[] = 'No se pudo crear mapeo '.$label.' porque falta la cuenta '.$account_code.'.';
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

        $this->created[] = 'Mapeo '.$label.' -> cuenta '.$account_code;
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

    protected function assert_accounting_schema()
    {
        if (!\DBUtil::table_exists('core_accounting_accounts')) {
            throw new \RuntimeException('Falta la tabla core_accounting_accounts. Ejecuta primero php oil refine migrate.');
        }
    }

    protected function print_summary()
    {
        \Cli::write('Reparacion de cuentas fiscales terminada.');

        \Cli::write('Creados: '.count($this->created));
        foreach ($this->created as $message) {
            \Cli::write(' - '.$message);
        }

        \Cli::write('Ya existentes: '.count($this->existing));
        foreach ($this->existing as $message) {
            \Cli::write(' - '.$message);
        }

        \Cli::write('Omitidos: '.count($this->skipped));
        foreach ($this->skipped as $message) {
            \Cli::write(' - '.$message);
        }
    }
}
