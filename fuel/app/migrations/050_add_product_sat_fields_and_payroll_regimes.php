<?php

namespace Fuel\Migrations;

class Add_product_sat_fields_and_payroll_regimes
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_sat_payroll_regimes')) {
            \DBUtil::create_table('core_sat_payroll_regimes', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 10],
                'name' => ['type' => 'varchar', 'constraint' => 180],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sat_payroll_regimes', 'code', 'idx_core_sat_payroll_regimes_code', 'unique');
        }

        if (\DBUtil::table_exists('core_commerce_products') && !\DBUtil::field_exists('core_commerce_products', ['sat_product_service_code'])) {
            \DBUtil::add_fields('core_commerce_products', [
                'sat_product_service_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => '01010101', 'after' => 'unit_code'],
                'sat_unit_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'H87', 'after' => 'sat_product_service_code'],
                'sat_object_tax_code' => ['type' => 'varchar', 'constraint' => 5, 'default' => '02', 'after' => 'sat_unit_code'],
                'sat_tax_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => '002', 'after' => 'tax_code'],
                'sat_tax_factor_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'Tasa', 'after' => 'sat_tax_code'],
                'sat_tax_rate' => ['type' => 'decimal', 'constraint' => '8,6', 'default' => 0.160000, 'after' => 'sat_tax_factor_type'],
            ]);
            \DBUtil::create_index('core_commerce_products', ['sat_product_service_code', 'sat_unit_code'], 'idx_core_commerce_products_sat_keys');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_commerce_products') && \DBUtil::field_exists('core_commerce_products', ['sat_product_service_code'])) {
            \DBUtil::drop_fields('core_commerce_products', [
                'sat_product_service_code',
                'sat_unit_code',
                'sat_object_tax_code',
                'sat_tax_code',
                'sat_tax_factor_type',
                'sat_tax_rate',
            ]);
        }

        if (\DBUtil::table_exists('core_sat_payroll_regimes')) {
            \DBUtil::drop_table('core_sat_payroll_regimes');
        }
    }
}
