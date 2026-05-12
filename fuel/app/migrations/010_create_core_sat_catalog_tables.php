<?php

namespace Fuel\Migrations;

class Create_core_sat_catalog_tables
{
    public function up()
    {
        \DBUtil::create_table('core_sat_payment_forms', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 10],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'banked' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_payment_forms', 'code', 'idx_core_sat_payment_forms_code', 'unique');

        \DBUtil::create_table('core_sat_payment_methods', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 10],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_payment_methods', 'code', 'idx_core_sat_payment_methods_code', 'unique');

        \DBUtil::create_table('core_sat_cfdi_uses', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 10],
            'name' => ['type' => 'varchar', 'constraint' => 220],
            'applies_person' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'applies_company' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_cfdi_uses', 'code', 'idx_core_sat_cfdi_uses_code', 'unique');

        \DBUtil::create_table('core_sat_tax_regimes', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 10],
            'name' => ['type' => 'varchar', 'constraint' => 220],
            'applies_person' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'applies_company' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_tax_regimes', 'code', 'idx_core_sat_tax_regimes_code', 'unique');

        \DBUtil::create_table('core_sat_unit_keys', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 20],
            'name' => ['type' => 'varchar', 'constraint' => 220],
            'symbol' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_unit_keys', 'code', 'idx_core_sat_unit_keys_code', 'unique');

        \DBUtil::create_table('core_sat_taxes', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 10],
            'name' => ['type' => 'varchar', 'constraint' => 120],
            'tax_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'traslado'],
            'factor_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'Tasa'],
            'default_rate' => ['type' => 'decimal', 'constraint' => '10,6', 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_taxes', 'code', 'idx_core_sat_taxes_code', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_sat_taxes');
        \DBUtil::drop_table('core_sat_unit_keys');
        \DBUtil::drop_table('core_sat_tax_regimes');
        \DBUtil::drop_table('core_sat_cfdi_uses');
        \DBUtil::drop_table('core_sat_payment_methods');
        \DBUtil::drop_table('core_sat_payment_forms');
    }
}
