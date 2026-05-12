<?php

namespace Fuel\Migrations;

class Create_core_billing_tables
{
    public function up()
    {
        \DBUtil::create_table('core_billing_invoices', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 40],
            'invoice_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'sale'],
            'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'source_module' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'manual'],
            'source_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'source_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'issue_date' => ['type' => 'varchar', 'constraint' => 10],
            'due_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'exchange_rate' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
            'payment_term_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sat_cfdi_use_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'G03'],
            'sat_payment_form_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => '99'],
            'sat_payment_method_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'PPD'],
            'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'discount_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'tax_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'retention_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'balance_due' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
            'notes' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_billing_invoices', 'folio', 'idx_core_billing_invoices_folio', 'unique');
        \DBUtil::create_index('core_billing_invoices', ['party_id', 'status'], 'idx_core_billing_invoices_party_status');
        \DBUtil::create_index('core_billing_invoices', ['invoice_type', 'issue_date'], 'idx_core_billing_invoices_type_date');
        \DBUtil::create_index('core_billing_invoices', 'cfdi_id', 'idx_core_billing_invoices_cfdi');

        \DBUtil::create_table('core_billing_invoice_items', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'invoice_id' => ['type' => 'int', 'constraint' => 11],
            'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sat_product_service_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => '01010101'],
            'description' => ['type' => 'varchar', 'constraint' => 255],
            'quantity' => ['type' => 'decimal', 'constraint' => '14,4', 'default' => 1],
            'unit_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'H87'],
            'unit_price' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'discount_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'tax_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'iva_16'],
            'tax_rate' => ['type' => 'decimal', 'constraint' => '8,6', 'default' => 0],
            'tax_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'retention_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'line_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_billing_invoice_items', ['invoice_id', 'sort_order'], 'idx_core_billing_invoice_items_invoice');
        \DBUtil::create_index('core_billing_invoice_items', 'product_id', 'idx_core_billing_invoice_items_product');

        \DBUtil::create_table('core_billing_invoice_events', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'invoice_id' => ['type' => 'int', 'constraint' => 11],
            'event_type' => ['type' => 'varchar', 'constraint' => 40],
            'summary' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'payload_json' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_billing_invoice_events', 'invoice_id', 'idx_core_billing_invoice_events_invoice');
    }

    public function down()
    {
        \DBUtil::drop_table('core_billing_invoice_events');
        \DBUtil::drop_table('core_billing_invoice_items');
        \DBUtil::drop_table('core_billing_invoices');
    }
}
