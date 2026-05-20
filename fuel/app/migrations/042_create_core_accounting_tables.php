<?php

namespace Fuel\Migrations;

class Create_core_accounting_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_accounting_accounts')) {
            \DBUtil::create_table('core_accounting_accounts', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'account_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'asset'],
                'parent_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'level' => ['type' => 'tinyint', 'constraint' => 2, 'default' => 1],
                'nature' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'debit'],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'sat_group_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'requires_party' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'requires_cost_center' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'is_postable' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_accounting_accounts', 'code', 'idx_core_accounting_accounts_code', 'unique');
            \DBUtil::create_index('core_accounting_accounts', ['account_type', 'active'], 'idx_core_accounting_accounts_type');
            \DBUtil::create_index('core_accounting_accounts', 'parent_id', 'idx_core_accounting_accounts_parent');
        }

        if (!\DBUtil::table_exists('core_accounting_journal_entries')) {
            \DBUtil::create_table('core_accounting_journal_entries', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'entry_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'diario'],
                'entry_date' => ['type' => 'varchar', 'constraint' => 10],
                'period' => ['type' => 'varchar', 'constraint' => 7, 'default' => ''],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'source_module' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'manual'],
                'source_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'source_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'exchange_rate' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
                'total_debit' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'total_credit' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'posted_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'posted_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_accounting_journal_entries', 'folio', 'idx_core_accounting_entries_folio', 'unique');
            \DBUtil::create_index('core_accounting_journal_entries', ['entry_date', 'status'], 'idx_core_accounting_entries_date');
            \DBUtil::create_index('core_accounting_journal_entries', ['source_module', 'source_entity_type', 'source_entity_id'], 'idx_core_accounting_entries_source');
        }

        if (!\DBUtil::table_exists('core_accounting_journal_lines')) {
            \DBUtil::create_table('core_accounting_journal_lines', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'entry_id' => ['type' => 'int', 'constraint' => 11],
                'account_id' => ['type' => 'int', 'constraint' => 11],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'cost_center' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'debit' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'credit' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'exchange_rate' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
                'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_accounting_journal_lines', ['entry_id', 'sort_order'], 'idx_core_accounting_lines_entry');
            \DBUtil::create_index('core_accounting_journal_lines', 'account_id', 'idx_core_accounting_lines_account');
            \DBUtil::create_index('core_accounting_journal_lines', 'party_id', 'idx_core_accounting_lines_party');
        }

        if (!\DBUtil::table_exists('core_accounting_posting_rules')) {
            \DBUtil::create_table('core_accounting_posting_rules', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'rule_code' => ['type' => 'varchar', 'constraint' => 80],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'source_module' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'source_event' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'debit_account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'credit_account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'amount_source' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'total'],
                'requires_party' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'auto_post' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'priority' => ['type' => 'int', 'constraint' => 11, 'default' => 100],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_accounting_posting_rules', 'rule_code', 'idx_core_accounting_rules_code', 'unique');
            \DBUtil::create_index('core_accounting_posting_rules', ['source_module', 'source_event', 'active'], 'idx_core_accounting_rules_source');
        }

        $this->seed_accounts();
        $this->seed_posting_rules();
        $this->seed_help();
    }

    public function down()
    {
        \DBUtil::drop_table('core_accounting_posting_rules');
        \DBUtil::drop_table('core_accounting_journal_lines');
        \DBUtil::drop_table('core_accounting_journal_entries');
        \DBUtil::drop_table('core_accounting_accounts');
    }

    protected function seed_accounts()
    {
        $accounts = [
            ['1000', 'Activo', 'asset', 0, 1, 'debit', 0],
            ['1100', 'Bancos', 'asset', '1000', 2, 'debit', 0],
            ['1200', 'Clientes / cuentas por cobrar', 'asset', '1000', 2, 'debit', 1],
            ['1300', 'Inventarios', 'asset', '1000', 2, 'debit', 0],
            ['2000', 'Pasivo', 'liability', 0, 1, 'credit', 0],
            ['2100', 'Proveedores / cuentas por pagar', 'liability', '2000', 2, 'credit', 1],
            ['2200', 'IVA trasladado por pagar', 'liability', '2000', 2, 'credit', 0],
            ['2300', 'IVA acreditable', 'asset', '1000', 2, 'debit', 0],
            ['2400', 'Retenciones por pagar', 'liability', '2000', 2, 'credit', 0],
            ['3000', 'Capital contable', 'equity', 0, 1, 'credit', 0],
            ['4000', 'Ingresos por ventas', 'income', 0, 1, 'credit', 0],
            ['5000', 'Costo de ventas', 'expense', 0, 1, 'debit', 0],
            ['6000', 'Gastos generales', 'expense', 0, 1, 'debit', 0],
        ];

        $ids = [];
        foreach ($accounts as $row) {
            $existing = \DB::select('id')->from('core_accounting_accounts')->where('code', '=', $row[0])->execute()->current();
            if ($existing) {
                $ids[$row[0]] = (int) $existing['id'];
                continue;
            }
            $parent_id = $row[3] === 0 ? 0 : (int) \Arr::get($ids, (string) $row[3], 0);
            list($id,) = \DB::insert('core_accounting_accounts')->set([
                'code' => $row[0],
                'name' => $row[1],
                'account_type' => $row[2],
                'parent_id' => $parent_id,
                'level' => $row[4],
                'nature' => $row[5],
                'currency_code' => 'MXN',
                'requires_party' => $row[6],
                'is_postable' => $row[4] > 1 ? 1 : 0,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
            $ids[$row[0]] = (int) $id;
        }
    }

    protected function seed_posting_rules()
    {
        $account_ids = [];
        foreach (\DB::select('id', 'code')->from('core_accounting_accounts')->execute() as $row) {
            $account_ids[$row['code']] = (int) $row['id'];
        }

        $rules = [
            ['billing_sale_total', 'Venta a credito: cliente contra ingresos', 'billing', 'invoice_posted', '1200', '4000', 'subtotal', 1, 0],
            ['billing_sale_tax', 'Venta: IVA trasladado', 'billing', 'invoice_posted', '1200', '2200', 'tax', 1, 0],
            ['purchases_invoice_total', 'Compra a credito: gasto contra proveedor', 'purchases', 'invoice_validated', '6000', '2100', 'subtotal', 1, 0],
            ['purchases_invoice_tax', 'Compra: IVA acreditable contra proveedor', 'purchases', 'invoice_validated', '2300', '2100', 'tax', 1, 0],
            ['payments_received', 'Cobro recibido: banco contra cliente', 'payments', 'payment_confirmed_received', '1100', '1200', 'total', 1, 0],
            ['payments_sent', 'Pago enviado: proveedor contra banco', 'payments', 'payment_confirmed_sent', '2100', '1100', 'total', 1, 0],
            ['inventory_delivery_cost', 'Entrega: costo de venta contra inventario', 'inventory', 'delivery_out', '5000', '1300', 'cost', 0, 0],
        ];

        foreach ($rules as $rule) {
            if (\DB::select('id')->from('core_accounting_posting_rules')->where('rule_code', '=', $rule[0])->execute()->current()) {
                continue;
            }
            \DB::insert('core_accounting_posting_rules')->set([
                'rule_code' => $rule[0],
                'name' => $rule[1],
                'source_module' => $rule[2],
                'source_event' => $rule[3],
                'debit_account_id' => (int) \Arr::get($account_ids, $rule[4], 0),
                'credit_account_id' => (int) \Arr::get($account_ids, $rule[5], 0),
                'amount_source' => $rule[6],
                'requires_party' => $rule[7],
                'auto_post' => $rule[8],
                'priority' => 100,
                'notes' => 'Regla base sugerida. Revisar antes de activar contabilizacion automatica.',
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
        }
    }

    protected function seed_help()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            return;
        }
        if (\DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'contabilidad-base')->execute()->current()) {
            return;
        }

        \DB::insert('core_knowledge_articles')->set([
            'code' => 'contabilidad-base',
            'title' => 'Contabilidad base',
            'category' => 'Finanzas',
            'summary' => 'Catalogo de cuentas, polizas, partidas y reglas para conectar operaciones con contabilidad.',
            'content' => '<h3>Objetivo</h3><p>Este modulo crea la base contable del ERP: catalogo de cuentas, polizas, partidas debe/haber y reglas de contabilizacion por evento.</p><h3>Flujo recomendado</h3><ol><li>Revisa el catalogo de cuentas base y ajusta codigos segun tu contador.</li><li>Crea o edita reglas para ventas, compras, pagos e inventario.</li><li>Captura polizas manuales solo cuando la operacion no venga de un modulo.</li><li>Contabiliza una poliza solo cuando el debe y haber esten cuadrados.</li></ol><h3>Regla importante</h3><p>Ventas, compras, bancos e inventario no deben escribir directo a contabilidad sin regla; cada afectacion debe pasar por una poliza auditable.</p>',
            'sort_order' => 45,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}
