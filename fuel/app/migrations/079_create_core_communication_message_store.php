<?php

namespace Fuel\Migrations;

class Create_core_communication_message_store
{
    public function up()
    {
        $this->create_conversations();
        $this->create_messages();
        $this->create_attachments();
        $this->create_links();
    }

    public function down()
    {
        foreach ([
            'core_communication_message_links',
            'core_communication_message_attachments',
            'core_communication_messages',
            'core_communication_conversations',
        ] as $table) {
            if (\DBUtil::table_exists($table)) {
                \DBUtil::drop_table($table);
            }
        }
    }

    protected function create_conversations()
    {
        if (\DBUtil::table_exists('core_communication_conversations')) {
            return;
        }

        \DBUtil::create_table('core_communication_conversations', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'channel_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'email'],
            'subject' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'normalized_subject' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'direction' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'incoming'],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
            'priority' => ['type' => 'tinyint', 'constraint' => 2, 'default' => 1],
            'owner_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'assigned_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'assigned_group_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'related_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'related_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'related_party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'last_message_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'message_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'unread_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_conversations', 'code', 'idx_comm_conversations_code', 'unique');
        \DBUtil::create_index('core_communication_conversations', ['channel_code', 'status'], 'idx_comm_conversations_channel_status');
        \DBUtil::create_index('core_communication_conversations', ['related_entity_type', 'related_entity_id'], 'idx_comm_conversations_entity');
        \DBUtil::create_index('core_communication_conversations', 'related_party_id', 'idx_comm_conversations_party');
        \DBUtil::create_index('core_communication_conversations', 'last_message_at', 'idx_comm_conversations_last_message');
    }

    protected function create_messages()
    {
        if (\DBUtil::table_exists('core_communication_messages')) {
            return;
        }

        \DBUtil::create_table('core_communication_messages', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'conversation_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'channel_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'email'],
            'direction' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'incoming'],
            'message_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'email'],
            'external_message_id' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'external_thread_id' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'in_reply_to' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'references_hash' => ['type' => 'varchar', 'constraint' => 64, 'default' => ''],
            'from_email' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'from_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'to_json' => ['type' => 'text', 'null' => true],
            'cc_json' => ['type' => 'text', 'null' => true],
            'bcc_json' => ['type' => 'text', 'null' => true],
            'subject' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'body_text' => ['type' => 'text', 'null' => true],
            'body_html_sanitized' => ['type' => 'mediumtext', 'null' => true],
            'snippet' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'received_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sent_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'new'],
            'provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'queue_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'related_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'related_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'related_party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'raw_headers_json' => ['type' => 'text', 'null' => true],
            'has_attachments' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'attachment_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'content_hash' => ['type' => 'varchar', 'constraint' => 64, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_messages', ['external_message_id', 'account_id'], 'idx_comm_messages_external_account');
        \DBUtil::create_index('core_communication_messages', 'conversation_id', 'idx_comm_messages_conversation');
        \DBUtil::create_index('core_communication_messages', ['related_entity_type', 'related_entity_id'], 'idx_comm_messages_entity');
        \DBUtil::create_index('core_communication_messages', 'related_party_id', 'idx_comm_messages_party');
        \DBUtil::create_index('core_communication_messages', 'content_hash', 'idx_comm_messages_content_hash');
    }

    protected function create_attachments()
    {
        if (\DBUtil::table_exists('core_communication_message_attachments')) {
            return;
        }

        \DBUtil::create_table('core_communication_message_attachments', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'message_id' => ['type' => 'int', 'constraint' => 11],
            'filename' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'mime_type' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'size_bytes' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'storage_ref' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'content_hash' => ['type' => 'varchar', 'constraint' => 64, 'default' => ''],
            'disposition' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'attachment'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_message_attachments', 'message_id', 'idx_comm_msg_attach_message');
        \DBUtil::create_index('core_communication_message_attachments', 'storage_ref', 'idx_comm_msg_attach_storage_ref');
    }

    protected function create_links()
    {
        if (\DBUtil::table_exists('core_communication_message_links')) {
            return;
        }

        \DBUtil::create_table('core_communication_message_links', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'message_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'conversation_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'relation_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'related'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_message_links', ['message_id', 'entity_type', 'entity_id'], 'idx_comm_msg_links_message_entity');
        \DBUtil::create_index('core_communication_message_links', ['conversation_id', 'entity_type', 'entity_id'], 'idx_comm_msg_links_conv_entity');
    }
}
