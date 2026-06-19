<?php

namespace Fuel\Migrations;

class Create_core_communication_account_assignments
{
    public function up()
    {
        if (\DBUtil::table_exists('core_communication_accounts')) {
            if (!\DBUtil::field_exists('core_communication_accounts', ['owner_user_id'])) {
                \DBUtil::add_fields('core_communication_accounts', [
                    'owner_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'account_type'],
                ]);
                \DBUtil::create_index('core_communication_accounts', 'owner_user_id', 'idx_comm_accounts_owner_user');
            }

            if (!\DBUtil::field_exists('core_communication_accounts', ['owner_group_id'])) {
                \DBUtil::add_fields('core_communication_accounts', [
                    'owner_group_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'owner_user_id'],
                ]);
                \DBUtil::create_index('core_communication_accounts', 'owner_group_id', 'idx_comm_accounts_owner_group');
            }

            if (!\DBUtil::field_exists('core_communication_accounts', ['mailbox_scope'])) {
                \DBUtil::add_fields('core_communication_accounts', [
                    'mailbox_scope' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'system', 'after' => 'owner_group_id'],
                ]);
                \DBUtil::create_index('core_communication_accounts', 'mailbox_scope', 'idx_comm_accounts_scope');
            }
        }

        if (\DBUtil::table_exists('core_communication_account_assignments')) {
            return;
        }

        \DBUtil::create_table('core_communication_account_assignments', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'account_id' => ['type' => 'int', 'constraint' => 11],
            'assignment_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'user'],
            'assignment_value' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'access_level' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'viewer'],
            'can_send' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'can_receive' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'can_sync' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'can_manage' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'default_sender' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_account_assignments', 'account_id', 'idx_comm_acc_assign_account');
        \DBUtil::create_index('core_communication_account_assignments', ['assignment_type', 'assignment_value'], 'idx_comm_acc_assign_subject');
        \DBUtil::create_index('core_communication_account_assignments', ['account_id', 'assignment_type', 'assignment_value'], 'idx_comm_acc_assign_unique_subject');
        \DBUtil::create_index('core_communication_account_assignments', 'active', 'idx_comm_acc_assign_active');
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_communication_account_assignments')) {
            \DBUtil::drop_table('core_communication_account_assignments');
        }

        if (\DBUtil::table_exists('core_communication_accounts')) {
            foreach (['mailbox_scope', 'owner_group_id', 'owner_user_id'] as $field) {
                if (\DBUtil::field_exists('core_communication_accounts', [$field])) {
                    \DBUtil::drop_fields('core_communication_accounts', [$field]);
                }
            }
        }
    }
}
