<?php

namespace Fuel\Migrations;

class Enhance_billing_pac_cfdi
{
    public function up()
    {
        if (!\DBUtil::field_exists('core_billing_invoices', ['pac_provider_code'])) {
            \DBUtil::add_fields('core_billing_invoices', [
                'pac_provider_code' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'factura_com', 'after' => 'cfdi_id'],
                'pac_connection_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'pac_provider_code'],
                'pac_series_id' => ['type' => 'varchar', 'constraint' => 40, 'default' => '', 'after' => 'pac_connection_id'],
                'pac_receptor_uid' => ['type' => 'varchar', 'constraint' => 80, 'default' => '', 'after' => 'pac_series_id'],
                'pac_uid' => ['type' => 'varchar', 'constraint' => 120, 'default' => '', 'after' => 'pac_receptor_uid'],
                'uuid' => ['type' => 'varchar', 'constraint' => 60, 'default' => '', 'after' => 'pac_uid'],
                'sat_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => '', 'after' => 'uuid'],
                'stamped_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'sat_status'],
                'cancelled_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'stamped_at'],
                'cancel_motive' => ['type' => 'varchar', 'constraint' => 5, 'default' => '', 'after' => 'cancelled_at'],
                'cancel_substitute_uuid' => ['type' => 'varchar', 'constraint' => 60, 'default' => '', 'after' => 'cancel_motive'],
                'pac_request_json' => ['type' => 'text', 'null' => true, 'after' => 'cancel_substitute_uuid'],
                'pac_response_json' => ['type' => 'text', 'null' => true, 'after' => 'pac_request_json'],
                'xml_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => '', 'after' => 'pac_response_json'],
                'pdf_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => '', 'after' => 'xml_path'],
            ]);
            \DBUtil::create_index('core_billing_invoices', 'uuid', 'idx_core_billing_invoices_uuid');
            \DBUtil::create_index('core_billing_invoices', ['pac_provider_code', 'pac_connection_id'], 'idx_core_billing_invoices_pac');
        }

        if (!\DBUtil::field_exists('core_billing_invoice_items', ['sat_object_tax_code'])) {
            \DBUtil::add_fields('core_billing_invoice_items', [
                'sat_object_tax_code' => ['type' => 'varchar', 'constraint' => 5, 'default' => '02', 'after' => 'unit_code'],
                'tax_factor_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'Tasa', 'after' => 'tax_code'],
                'retention_tax_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'retention_amount'],
                'retention_rate' => ['type' => 'decimal', 'constraint' => '8,6', 'default' => 0, 'after' => 'retention_tax_code'],
            ]);
        }
    }

    public function down()
    {
        if (\DBUtil::field_exists('core_billing_invoice_items', ['sat_object_tax_code'])) {
            \DBUtil::drop_fields('core_billing_invoice_items', [
                'sat_object_tax_code',
                'tax_factor_type',
                'retention_tax_code',
                'retention_rate',
            ]);
        }
        if (\DBUtil::field_exists('core_billing_invoices', ['pac_provider_code'])) {
            \DBUtil::drop_fields('core_billing_invoices', [
                'pac_provider_code',
                'pac_connection_id',
                'pac_series_id',
                'pac_receptor_uid',
                'pac_uid',
                'uuid',
                'sat_status',
                'stamped_at',
                'cancelled_at',
                'cancel_motive',
                'cancel_substitute_uuid',
                'pac_request_json',
                'pac_response_json',
                'xml_path',
                'pdf_path',
            ]);
        }
    }
}
