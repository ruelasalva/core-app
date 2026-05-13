<?php

namespace Fuel\Migrations;

class Create_core_purchase_tables
{
    public function up()
    {
        \DBUtil::create_table('core_purchase_orders', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 40],
            'source' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'admin'],
            'portal_code' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'party_id' => ['type' => 'int', 'constraint' => 11],
            'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'requested_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'authorized_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'authorized_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'order_date' => ['type' => 'varchar', 'constraint' => 10],
            'expected_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'payment_term_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'exchange_rate' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
            'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'tax_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'retention_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'invoiced_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'balance_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
            'notes' => ['type' => 'text', 'null' => true],
            'internal_notes' => ['type' => 'text', 'null' => true],
            'external_reference' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_purchase_orders', 'folio', 'idx_core_purchase_orders_folio', 'unique');
        \DBUtil::create_index('core_purchase_orders', ['party_id', 'status'], 'idx_core_purchase_orders_party_status');

        \DBUtil::create_table('core_purchase_order_items', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'order_id' => ['type' => 'int', 'constraint' => 11],
            'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sku' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'description' => ['type' => 'varchar', 'constraint' => 255],
            'quantity' => ['type' => 'decimal', 'constraint' => '14,4', 'default' => 1],
            'unit_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'H87'],
            'unit_price' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'discount_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'tax_rate' => ['type' => 'decimal', 'constraint' => '8,6', 'default' => 0.16],
            'tax_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'retention_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'line_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'received_quantity' => ['type' => 'decimal', 'constraint' => '14,4', 'default' => 0],
            'invoiced_quantity' => ['type' => 'decimal', 'constraint' => '14,4', 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_purchase_order_items', ['order_id', 'sort_order'], 'idx_core_purchase_order_items_order');

        \DBUtil::create_table('core_purchase_invoices', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 40],
            'party_id' => ['type' => 'int', 'constraint' => 11],
            'order_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'billing_invoice_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'cfdi_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'uuid' => ['type' => 'char', 'constraint' => 36, 'default' => ''],
            'invoice_date' => ['type' => 'varchar', 'constraint' => 10],
            'due_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'tax_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'retention_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'balance_due' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'submitted'],
            'validation_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
            'sat_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
            'message' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_purchase_invoices', 'folio', 'idx_core_purchase_invoices_folio', 'unique');
        \DBUtil::create_index('core_purchase_invoices', ['party_id', 'status'], 'idx_core_purchase_invoices_party_status');
        \DBUtil::create_index('core_purchase_invoices', 'order_id', 'idx_core_purchase_invoices_order');

        \DBUtil::create_table('core_purchase_receipts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 40],
            'party_id' => ['type' => 'int', 'constraint' => 11],
            'issue_date' => ['type' => 'varchar', 'constraint' => 10],
            'scheduled_payment_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'payment_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
            'notes' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_purchase_receipts', 'folio', 'idx_core_purchase_receipts_folio', 'unique');
        \DBUtil::create_index('core_purchase_receipts', ['party_id', 'status'], 'idx_core_purchase_receipts_party_status');

        \DBUtil::create_table('core_purchase_receipt_items', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'receipt_id' => ['type' => 'int', 'constraint' => 11],
            'invoice_id' => ['type' => 'int', 'constraint' => 11],
            'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_purchase_receipt_items', 'receipt_id', 'idx_core_purchase_receipt_items_receipt');
        \DBUtil::create_index('core_purchase_receipt_items', 'invoice_id', 'idx_core_purchase_receipt_items_invoice');
    }

    public function down()
    {
        \DBUtil::drop_table('core_purchase_receipt_items');
        \DBUtil::drop_table('core_purchase_receipts');
        \DBUtil::drop_table('core_purchase_invoices');
        \DBUtil::drop_table('core_purchase_order_items');
        \DBUtil::drop_table('core_purchase_orders');
    }
}
