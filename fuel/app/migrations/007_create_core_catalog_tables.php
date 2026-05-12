<?php

namespace Fuel\Migrations;

class Create_core_catalog_tables
{
    public function up()
    {
        \DBUtil::create_table('core_catalog_currencies', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 3],
            'name' => ['type' => 'varchar', 'constraint' => 120],
            'symbol' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'decimals' => ['type' => 'tinyint', 'constraint' => 2, 'default' => 2],
            'is_base' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_currencies', 'code', 'idx_core_catalog_currencies_code', 'unique');

        \DBUtil::create_table('core_catalog_exchange_rates', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3],
            'rate_date' => ['type' => 'date'],
            'rate' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 1],
            'source' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'manual'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_exchange_rates', ['currency_code', 'rate_date'], 'idx_core_catalog_exchange_rates_currency_date', 'unique');

        \DBUtil::create_table('core_catalog_banks', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 20],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'sat_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_banks', 'code', 'idx_core_catalog_banks_code', 'unique');

        \DBUtil::create_table('core_catalog_bank_accounts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'bank_id' => ['type' => 'int', 'constraint' => 11],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'account_number' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'clabe' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_bank_accounts', 'bank_id', 'idx_core_catalog_bank_accounts_bank_id');

        \DBUtil::create_table('core_catalog_taxes', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'rate' => ['type' => 'decimal', 'constraint' => '10,6', 'default' => 0],
            'sat_tax_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_taxes', 'code', 'idx_core_catalog_taxes_code', 'unique');

        \DBUtil::create_table('core_catalog_retentions', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'rate' => ['type' => 'decimal', 'constraint' => '10,6', 'default' => 0],
            'sat_tax_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_retentions', 'code', 'idx_core_catalog_retentions_code', 'unique');

        \DBUtil::create_table('core_catalog_discounts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'discount_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'percent'],
            'value' => ['type' => 'decimal', 'constraint' => '10,6', 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_discounts', 'code', 'idx_core_catalog_discounts_code', 'unique');

        \DBUtil::create_table('core_catalog_units', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 30],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'sat_unit_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_units', 'code', 'idx_core_catalog_units_code', 'unique');

        \DBUtil::create_table('core_catalog_document_types', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'module' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'general'],
            'affects_inventory' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'affects_accounting' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_document_types', 'code', 'idx_core_catalog_document_types_code', 'unique');

        \DBUtil::create_table('core_catalog_payment_terms', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'days' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'requires_credit' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_payment_terms', 'code', 'idx_core_catalog_payment_terms_code', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_catalog_payment_terms');
        \DBUtil::drop_table('core_catalog_document_types');
        \DBUtil::drop_table('core_catalog_units');
        \DBUtil::drop_table('core_catalog_discounts');
        \DBUtil::drop_table('core_catalog_retentions');
        \DBUtil::drop_table('core_catalog_taxes');
        \DBUtil::drop_table('core_catalog_bank_accounts');
        \DBUtil::drop_table('core_catalog_banks');
        \DBUtil::drop_table('core_catalog_exchange_rates');
        \DBUtil::drop_table('core_catalog_currencies');
    }
}
