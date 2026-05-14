<?php

namespace Fuel\Migrations;

class Enhance_purchase_approval_flow
{
    public function up()
    {
        \DBUtil::add_fields('core_purchase_orders', [
            'approval_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'not_required', 'after' => 'status'],
            'approval_required' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'approval_status'],
            'approval_rule_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'approval_required'],
            'requested_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'requested_by'],
            'rejected_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'authorized_at'],
            'rejected_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'rejected_by'],
            'approval_notes' => ['type' => 'text', 'null' => true, 'after' => 'internal_notes'],
        ]);
        \DBUtil::create_index('core_purchase_orders', ['department_id', 'approval_status', 'status'], 'idx_core_purchase_orders_approval');
        \DBUtil::create_index('core_purchase_orders', ['requested_by', 'authorized_by'], 'idx_core_purchase_orders_users');

        \DBUtil::create_table('core_purchase_approval_rules', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'constraint' => 120],
            'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'min_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'max_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'approver_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'approver_group_id' => ['type' => 'int', 'constraint' => 11, 'default' => 70],
            'auto_approve' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'requires_document' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8mb4');
        \DBUtil::create_index('core_purchase_approval_rules', ['department_id', 'min_amount', 'max_amount'], 'idx_core_purchase_rules_amount');
    }

    public function down()
    {
        \DBUtil::drop_table('core_purchase_approval_rules');
        \DBUtil::drop_fields('core_purchase_orders', [
            'approval_status',
            'approval_required',
            'approval_rule_id',
            'requested_at',
            'rejected_by',
            'rejected_at',
            'approval_notes',
        ]);
    }
}
