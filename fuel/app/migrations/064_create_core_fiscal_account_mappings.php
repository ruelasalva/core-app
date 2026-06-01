<?php

namespace Fuel\Migrations;

class Create_core_fiscal_account_mappings
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_fiscal_account_mappings')) {
            \DBUtil::create_table('core_fiscal_account_mappings', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'tax_code' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
                'tax_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'direction' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_fiscal_account_mappings', ['tax_code', 'tax_type', 'direction'], 'uidx_core_fiscal_account_mappings_key', 'unique');
            \DBUtil::create_index('core_fiscal_account_mappings', ['account_id'], 'idx_core_fiscal_account_mappings_account');
            \DBUtil::create_index('core_fiscal_account_mappings', ['active'], 'idx_core_fiscal_account_mappings_active');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_fiscal_account_mappings');
    }
}
