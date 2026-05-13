<?php

namespace Fuel\Migrations;

class Extend_core_sat_cfdi_audit_tables
{
    public function up()
    {
        \DBUtil::add_fields('core_sat_cfdi', [
            'emitter_regime' => ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'emitter_name'],
            'receiver_regime' => ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'receiver_name'],
            'receiver_zip' => ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'receiver_regime'],
            'export_code' => ['type' => 'varchar', 'constraint' => 5, 'default' => '', 'after' => 'voucher_type'],
            'place_of_issue' => ['type' => 'varchar', 'constraint' => 10, 'default' => '', 'after' => 'export_code'],
            'conditions_payment' => ['type' => 'varchar', 'constraint' => 255, 'default' => '', 'after' => 'payment_form'],
            'certificate_number' => ['type' => 'varchar', 'constraint' => 30, 'default' => '', 'after' => 'conditions_payment'],
            'certificate_sat_number' => ['type' => 'varchar', 'constraint' => 30, 'default' => '', 'after' => 'certificate_number'],
            'pac_rfc' => ['type' => 'varchar', 'constraint' => 13, 'default' => '', 'after' => 'certificate_sat_number'],
            'seal_cfdi' => ['type' => 'text', 'null' => true, 'after' => 'pac_rfc'],
            'seal_sat' => ['type' => 'text', 'null' => true, 'after' => 'seal_cfdi'],
            'complements_json' => ['type' => 'text', 'null' => true, 'after' => 'tax_withheld_total'],
            'has_payment_complement' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'complements_json'],
            'has_waybill' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'has_payment_complement'],
        ]);

        \DBUtil::create_index('core_sat_cfdi', 'voucher_type', 'idx_core_sat_cfdi_voucher_type');
        \DBUtil::create_index('core_sat_cfdi', ['voucher_type', 'direction', 'issued_at'], 'idx_core_sat_cfdi_type_direction_date');

        \DBUtil::create_table('core_sat_cfdi_details', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'cfdi_id' => ['type' => 'int', 'constraint' => 11],
            'line_type' => ['type' => 'varchar', 'constraint' => 25, 'default' => 'concept'],
            'line_number' => ['type' => 'smallint', 'default' => 0],
            'product_service_code' => ['type' => 'varchar', 'constraint' => 15, 'default' => ''],
            'identification_number' => ['type' => 'varchar', 'constraint' => 100, 'default' => ''],
            'unit_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'unit_name' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'description' => ['type' => 'text', 'null' => true],
            'tax_object' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
            'quantity' => ['type' => 'decimal', 'constraint' => '14,6', 'null' => true],
            'unit_value' => ['type' => 'decimal', 'constraint' => '14,6', 'null' => true],
            'discount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'vat_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'vat_rate' => ['type' => 'varchar', 'constraint' => 12, 'default' => ''],
            'vat_base' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'ieps_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'ieps_rate' => ['type' => 'varchar', 'constraint' => 12, 'default' => ''],
            'ieps_base' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'retention_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'ret_vat_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'ret_vat_rate' => ['type' => 'varchar', 'constraint' => 12, 'default' => ''],
            'ret_vat_base' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'ret_isr_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'ret_isr_rate' => ['type' => 'varchar', 'constraint' => 12, 'default' => ''],
            'ret_isr_base' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'related_uuid' => ['type' => 'char', 'constraint' => 36, 'default' => ''],
            'relation_type' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'payment_uuid' => ['type' => 'char', 'constraint' => 36, 'default' => ''],
            'payment_series' => ['type' => 'varchar', 'constraint' => 25, 'default' => ''],
            'payment_folio' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
            'payment_currency' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
            'payment_equivalence' => ['type' => 'decimal', 'constraint' => '19,5', 'null' => true],
            'payment_method' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
            'payment_partiality' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'payment_previous_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'payment_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'payment_remaining_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'metadata_json' => ['type' => 'text', 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_sat_cfdi_details', 'cfdi_id', 'idx_core_sat_cfdi_details_cfdi');
        \DBUtil::create_index('core_sat_cfdi_details', ['cfdi_id', 'line_type'], 'idx_core_sat_cfdi_details_type');
        \DBUtil::create_index('core_sat_cfdi_details', 'product_service_code', 'idx_core_sat_cfdi_details_product_service');
        \DBUtil::create_index('core_sat_cfdi_details', 'identification_number', 'idx_core_sat_cfdi_details_identification');
        \DBUtil::create_index('core_sat_cfdi_details', 'related_uuid', 'idx_core_sat_cfdi_details_related_uuid');
        \DBUtil::create_index('core_sat_cfdi_details', 'payment_uuid', 'idx_core_sat_cfdi_details_payment_uuid');

        \DBUtil::create_table('core_sat_payment_details', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'payment_cfdi_id' => ['type' => 'int', 'constraint' => 11],
            'invoice_cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'invoice_uuid' => ['type' => 'char', 'constraint' => 36],
            'series' => ['type' => 'varchar', 'constraint' => 25, 'default' => ''],
            'folio' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
            'currency' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
            'equivalence' => ['type' => 'decimal', 'constraint' => '19,5', 'null' => true],
            'partiality_number' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'previous_balance' => ['type' => 'decimal', 'constraint' => '19,2', 'default' => 0],
            'paid_amount' => ['type' => 'decimal', 'constraint' => '19,2', 'default' => 0],
            'remaining_balance' => ['type' => 'decimal', 'constraint' => '19,2', 'default' => 0],
            'tax_object' => ['type' => 'varchar', 'constraint' => 5, 'default' => ''],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_sat_payment_details', 'payment_cfdi_id', 'idx_core_sat_payment_details_payment');
        \DBUtil::create_index('core_sat_payment_details', 'invoice_cfdi_id', 'idx_core_sat_payment_details_invoice');
        \DBUtil::create_index('core_sat_payment_details', 'invoice_uuid', 'idx_core_sat_payment_details_uuid');
    }

    public function down()
    {
        \DBUtil::drop_table('core_sat_payment_details');
        \DBUtil::drop_table('core_sat_cfdi_details');

        \DBUtil::drop_fields('core_sat_cfdi', [
            'emitter_regime',
            'receiver_regime',
            'receiver_zip',
            'export_code',
            'place_of_issue',
            'conditions_payment',
            'certificate_number',
            'certificate_sat_number',
            'pac_rfc',
            'seal_cfdi',
            'seal_sat',
            'complements_json',
            'has_payment_complement',
            'has_waybill',
        ]);
    }
}
