<?php

namespace Fuel\Migrations;

class Create_accounts_receivable_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_ar_customer_statuses')) {
            \DBUtil::create_table('core_ar_customer_statuses', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'party_id' => ['type' => 'int', 'constraint' => 11],
                'credit_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'normal'],
                'credit_limit' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'credit_days' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'current_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'overdue_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'last_review_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'reviewed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_ar_customer_statuses', 'party_id', 'idx_core_ar_customer_status_party', 'unique');
            \DBUtil::create_index('core_ar_customer_statuses', ['credit_status', 'active'], 'idx_core_ar_customer_status_state');
        }

        if (!\DBUtil::table_exists('core_ar_collection_actions')) {
            \DBUtil::create_table('core_ar_collection_actions', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'party_id' => ['type' => 'int', 'constraint' => 11],
                'invoice_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'action_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'call'],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'priority' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'normal'],
                'assigned_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'action_date' => ['type' => 'varchar', 'constraint' => 10],
                'next_action_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'promise_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'promise_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'result' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'completed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'completed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_ar_collection_actions', 'folio', 'idx_core_ar_collection_folio', 'unique');
            \DBUtil::create_index('core_ar_collection_actions', ['party_id', 'status'], 'idx_core_ar_collection_party_status');
            \DBUtil::create_index('core_ar_collection_actions', ['invoice_id', 'active'], 'idx_core_ar_collection_invoice');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_ar_collection_actions');
        \DBUtil::drop_table('core_ar_customer_statuses');
    }
}
