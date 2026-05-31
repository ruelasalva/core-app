<?php

namespace Fuel\Migrations;

class Create_core_fiscal_engine_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_fiscal_periods')) {
            \DBUtil::create_table('core_fiscal_periods', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'company_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'taxpayer_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'fiscal_year' => ['type' => 'smallint', 'constraint' => 4, 'default' => 0],
                'fiscal_month' => ['type' => 'tinyint', 'constraint' => 2, 'default' => 0],
                'period_key' => ['type' => 'char', 'constraint' => 7, 'default' => ''],
                'date_from' => ['type' => 'date'],
                'date_to' => ['type' => 'date'],
                'status' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'open'],
                'locked_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'locked_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'closed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'closed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_fiscal_periods', ['taxpayer_rfc', 'period_key'], 'uidx_core_fiscal_periods_rfc_period', 'unique');
            \DBUtil::create_index('core_fiscal_periods', ['company_id', 'fiscal_year', 'fiscal_month'], 'idx_core_fiscal_periods_company_date');
            \DBUtil::create_index('core_fiscal_periods', ['status', 'active'], 'idx_core_fiscal_periods_status');
        }

        if (!\DBUtil::table_exists('core_fiscal_ledger_builds')) {
            \DBUtil::create_table('core_fiscal_ledger_builds', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'fiscal_period_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'taxpayer_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'build_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'initial'],
                'source_module' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'sat_cfdi'],
                'date_from' => ['type' => 'date'],
                'date_to' => ['type' => 'date'],
                'status' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
                'cfdi_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'detail_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'line_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'error_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'error_message' => ['type' => 'text', 'null' => true],
                'started_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'finished_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_fiscal_ledger_builds', ['fiscal_period_id'], 'idx_core_fiscal_builds_period');
            \DBUtil::create_index('core_fiscal_ledger_builds', ['taxpayer_rfc', 'date_from', 'date_to'], 'idx_core_fiscal_builds_rfc_dates');
            \DBUtil::create_index('core_fiscal_ledger_builds', ['status', 'active'], 'idx_core_fiscal_builds_status');
        }

        if (!\DBUtil::table_exists('core_fiscal_ledger_lines')) {
            \DBUtil::create_table('core_fiscal_ledger_lines', [
                'id' => ['type' => 'bigint', 'constraint' => 20, 'auto_increment' => true],
                'source_hash' => ['type' => 'char', 'constraint' => 64],
                'fiscal_period_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'build_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'cfdi_detail_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'payment_detail_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'taxpayer_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'counterparty_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'emitter_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'receiver_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => ''],
                'uuid' => ['type' => 'char', 'constraint' => 36, 'default' => ''],
                'related_uuid' => ['type' => 'char', 'constraint' => 36, 'default' => ''],
                'direction' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'cfdi_type' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
                'payment_method' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'payment_form' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'payment_policy' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'line_number' => ['type' => 'smallint', 'constraint' => 5, 'default' => 0],
                'line_type' => ['type' => 'varchar', 'constraint' => 25, 'default' => 'concept'],
                'product_service_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'identification_number' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'description' => ['type' => 'text', 'null' => true],
                'tax_object' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
                'base_amount' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'discount_amount' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'tax_code' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
                'tax_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'transferred'],
                'tax_factor_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'tax_rate' => ['type' => 'decimal', 'constraint' => '12,6', 'default' => 0],
                'tax_amount' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'currency' => ['type' => 'varchar', 'constraint' => 5, 'default' => 'MXN'],
                'exchange_rate' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 1],
                'base_amount_mxn' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'tax_amount_mxn' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'issue_date' => ['type' => 'datetime'],
                'stamped_at' => ['type' => 'datetime', 'null' => true],
                'fiscal_period' => ['type' => 'char', 'constraint' => 7, 'default' => ''],
                'sat_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
                'source_origin' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'sat_cfdi'],
                'xml_available' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_fiscal_ledger_lines', ['source_hash'], 'uidx_core_fiscal_ledger_source_hash', 'unique');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['fiscal_period_id'], 'idx_core_fiscal_ledger_period');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['taxpayer_rfc', 'fiscal_period'], 'idx_core_fiscal_ledger_rfc_period');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['uuid'], 'idx_core_fiscal_ledger_uuid');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['direction', 'issue_date'], 'idx_core_fiscal_ledger_direction_date');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['cfdi_type', 'payment_method'], 'idx_core_fiscal_ledger_type_method');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['tax_code', 'tax_type', 'tax_rate'], 'idx_core_fiscal_ledger_tax');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['counterparty_rfc'], 'idx_core_fiscal_ledger_counterparty');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['sat_status'], 'idx_core_fiscal_ledger_sat_status');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['cfdi_id'], 'idx_core_fiscal_ledger_cfdi');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['cfdi_detail_id'], 'idx_core_fiscal_ledger_detail');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['payment_detail_id'], 'idx_core_fiscal_ledger_payment_detail');
            \DBUtil::create_index('core_fiscal_ledger_lines', ['build_id'], 'idx_core_fiscal_ledger_build');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_fiscal_ledger_lines');
        \DBUtil::drop_table('core_fiscal_ledger_builds');
        \DBUtil::drop_table('core_fiscal_periods');
    }
}
