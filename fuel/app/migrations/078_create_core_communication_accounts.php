<?php

namespace Fuel\Migrations;

class Create_core_communication_accounts
{
    public function up()
    {
        if (\DBUtil::table_exists('core_communication_accounts')) {
            return;
        }

        \DBUtil::create_table('core_communication_accounts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'email_address' => ['type' => 'varchar', 'constraint' => 180],
            'account_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'support'],
            'provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'smtp_provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'imap_provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'imap_host' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'imap_port' => ['type' => 'int', 'constraint' => 11, 'default' => 993],
            'imap_encryption' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'ssl'],
            'imap_username' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'imap_password_encrypted' => ['type' => 'text', 'null' => true],
            'imap_folder_inbox' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'INBOX'],
            'imap_folder_sent' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'Sent'],
            'imap_folder_drafts' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'Drafts'],
            'imap_folder_trash' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'Trash'],
            'sync_inbox' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'sync_sent' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sync_drafts' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sync_trash' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'append_sent' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sync_enabled' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'last_sync_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'last_sync_status' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'never'],
            'last_sync_error' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_accounts', 'code', 'idx_comm_accounts_code', 'unique');
        \DBUtil::create_index('core_communication_accounts', 'email_address', 'idx_comm_accounts_email');
        \DBUtil::create_index('core_communication_accounts', 'account_type', 'idx_comm_accounts_type');
        \DBUtil::create_index('core_communication_accounts', 'active', 'idx_comm_accounts_active');
        \DBUtil::create_index('core_communication_accounts', 'sync_enabled', 'idx_comm_accounts_sync_enabled');
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_communication_accounts')) {
            \DBUtil::drop_table('core_communication_accounts');
        }
    }
}
