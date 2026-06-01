<?php

namespace Fuel\Migrations;

class Create_core_sat_payment_taxes
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_sat_payment_taxes')) {
            \DBUtil::create_table('core_sat_payment_taxes', [
                'id' => ['type' => 'bigint', 'constraint' => 20, 'auto_increment' => true],
                'source_hash' => ['type' => 'char', 'constraint' => 64],
                'payment_cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'payment_detail_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'invoice_cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'payment_uuid' => ['type' => 'char', 'constraint' => 36, 'default' => ''],
                'invoice_uuid' => ['type' => 'char', 'constraint' => 36, 'default' => ''],
                'tax_scope' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
                'tax_code' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
                'tax_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'tax_factor_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'tax_rate' => ['type' => 'decimal', 'constraint' => '12,6', 'default' => 0],
                'base_amount' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'tax_amount' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'currency' => ['type' => 'varchar', 'constraint' => 5, 'default' => 'MXN'],
                'exchange_rate' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 1],
                'payment_date' => ['type' => 'datetime', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_sat_payment_taxes', ['source_hash'], 'uidx_core_sat_payment_taxes_hash', 'unique');
            \DBUtil::create_index('core_sat_payment_taxes', ['payment_cfdi_id'], 'idx_core_sat_payment_taxes_payment_cfdi');
            \DBUtil::create_index('core_sat_payment_taxes', ['payment_detail_id'], 'idx_core_sat_payment_taxes_payment_detail');
            \DBUtil::create_index('core_sat_payment_taxes', ['invoice_cfdi_id'], 'idx_core_sat_payment_taxes_invoice_cfdi');
            \DBUtil::create_index('core_sat_payment_taxes', ['payment_uuid'], 'idx_core_sat_payment_taxes_payment_uuid');
            \DBUtil::create_index('core_sat_payment_taxes', ['invoice_uuid'], 'idx_core_sat_payment_taxes_invoice_uuid');
            \DBUtil::create_index('core_sat_payment_taxes', ['tax_scope', 'tax_code', 'tax_type', 'tax_rate'], 'idx_core_sat_payment_taxes_tax');
            \DBUtil::create_index('core_sat_payment_taxes', ['payment_date'], 'idx_core_sat_payment_taxes_payment_date');
            \DBUtil::create_index('core_sat_payment_taxes', ['active'], 'idx_core_sat_payment_taxes_active');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_sat_payment_taxes')) {
            \DBUtil::drop_table('core_sat_payment_taxes');
        }
    }
}
