<?php

namespace Fuel\Migrations;

class Create_core_communication_tables
{
    public function up()
    {
        \DBUtil::add_fields('core_notification_events', [
            'title_template' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'message_template' => ['type' => 'text', 'null' => true],
            'url_template' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'icon' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'bi bi-bell'],
            'priority' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
            'notify_internal' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'notify_email' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'email_role' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'email_template_code' => ['type' => 'varchar', 'constraint' => 100, 'default' => ''],
        ]);

        \DBUtil::create_table('core_notifications', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'event_code' => ['type' => 'varchar', 'constraint' => 100, 'default' => ''],
            'notification_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'event'],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'message' => ['type' => 'text', 'null' => true],
            'url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'icon' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'bi bi-bell'],
            'priority' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
            'payload_json' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'expires_at' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_notifications', 'event_code', 'idx_core_notifications_event_code');
        \DBUtil::create_index('core_notifications', 'created_at', 'idx_core_notifications_created_at');

        \DBUtil::create_table('core_notification_recipients', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'notification_id' => ['type' => 'int', 'constraint' => 11],
            'user_id' => ['type' => 'int', 'constraint' => 11],
            'status' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'unread'],
            'read_at' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_notification_recipients', 'notification_id', 'idx_core_notification_recipients_notification_id');
        \DBUtil::create_index('core_notification_recipients', ['user_id', 'status'], 'idx_core_notification_recipients_user_status');

        \DBUtil::create_table('core_email_roles', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'from_email' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'from_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'reply_to_email' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'reply_to_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'to_emails' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_email_roles', 'code', 'idx_core_email_roles_code', 'unique');

        \DBUtil::create_table('core_email_templates', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 100],
            'email_role' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'system'],
            'subject' => ['type' => 'varchar', 'constraint' => 180],
            'view_path' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'content' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_email_templates', 'code', 'idx_core_email_templates_code', 'unique');

        \DBUtil::create_table('core_email_queue', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'event_code' => ['type' => 'varchar', 'constraint' => 100, 'default' => ''],
            'template_code' => ['type' => 'varchar', 'constraint' => 100, 'default' => ''],
            'email_role' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'to_email' => ['type' => 'varchar', 'constraint' => 180],
            'to_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'subject' => ['type' => 'varchar', 'constraint' => 180],
            'body' => ['type' => 'text', 'null' => true],
            'status' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
            'attempts' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'max_attempts' => ['type' => 'int', 'constraint' => 11, 'default' => 3],
            'last_error' => ['type' => 'text', 'null' => true],
            'scheduled_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sent_at' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_email_queue', 'status', 'idx_core_email_queue_status');
        \DBUtil::create_index('core_email_queue', 'scheduled_at', 'idx_core_email_queue_scheduled_at');
    }

    public function down()
    {
        \DBUtil::drop_table('core_email_queue');
        \DBUtil::drop_table('core_email_templates');
        \DBUtil::drop_table('core_email_roles');
        \DBUtil::drop_table('core_notification_recipients');
        \DBUtil::drop_table('core_notifications');

        \DBUtil::drop_fields('core_notification_events', [
            'title_template',
            'message_template',
            'url_template',
            'icon',
            'priority',
            'notify_internal',
            'notify_email',
            'email_role',
            'email_template_code',
        ]);
    }
}
