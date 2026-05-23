<?php

namespace Fuel\Migrations;

class Create_accounts_payable_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_ap_supplier_statuses')) {
            \DBUtil::create_table('core_ap_supplier_statuses', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'party_id' => ['type' => 'int', 'constraint' => 11],
                'payment_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'normal'],
                'payment_priority' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'normal'],
                'credit_limit' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'credit_days' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'current_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'overdue_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'next_payment_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'reviewed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'last_review_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_ap_supplier_statuses', 'party_id', 'idx_core_ap_supplier_status_party', 'unique');
            \DBUtil::create_index('core_ap_supplier_statuses', ['payment_status', 'active'], 'idx_core_ap_supplier_status_state');
        }

        if (!\DBUtil::table_exists('core_ap_payment_actions')) {
            \DBUtil::create_table('core_ap_payment_actions', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'party_id' => ['type' => 'int', 'constraint' => 11],
                'purchase_invoice_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'action_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'schedule'],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'priority' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'normal'],
                'assigned_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'action_date' => ['type' => 'varchar', 'constraint' => 10],
                'scheduled_payment_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'planned_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'result' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'completed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'completed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_ap_payment_actions', 'folio', 'idx_core_ap_payment_action_folio', 'unique');
            \DBUtil::create_index('core_ap_payment_actions', ['party_id', 'status'], 'idx_core_ap_payment_action_party_status');
            \DBUtil::create_index('core_ap_payment_actions', ['purchase_invoice_id', 'active'], 'idx_core_ap_payment_action_invoice');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_ap_payment_actions');
        \DBUtil::drop_table('core_ap_supplier_statuses');
    }
}
