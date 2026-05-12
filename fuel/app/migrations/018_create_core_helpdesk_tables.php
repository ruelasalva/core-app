<?php

namespace Fuel\Migrations;

class Create_core_helpdesk_tables
{
    public function up()
    {
        \DBUtil::create_table('core_helpdesk_categories', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 60],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_helpdesk_categories', 'code', 'idx_core_helpdesk_categories_code', 'unique');

        \DBUtil::create_table('core_helpdesk_statuses', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 60],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'color' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'secondary'],
            'is_closed' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_helpdesk_statuses', 'code', 'idx_core_helpdesk_statuses_code', 'unique');

        \DBUtil::create_table('core_helpdesk_tickets', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 30],
            'source' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'admin'],
            'portal_code' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'requester_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'assigned_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'category_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'status_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'priority' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'normal'],
            'subject' => ['type' => 'varchar', 'constraint' => 180],
            'description' => ['type' => 'text', 'null' => true],
            'last_message_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'closed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_helpdesk_tickets', 'folio', 'idx_core_helpdesk_tickets_folio', 'unique');
        \DBUtil::create_index('core_helpdesk_tickets', ['status_id', 'assigned_user_id'], 'idx_core_helpdesk_tickets_status_assigned');
        \DBUtil::create_index('core_helpdesk_tickets', ['party_id', 'portal_code'], 'idx_core_helpdesk_tickets_party_portal');

        \DBUtil::create_table('core_helpdesk_messages', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'ticket_id' => ['type' => 'int', 'constraint' => 11],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'author_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'admin'],
            'message' => ['type' => 'text'],
            'is_internal' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_helpdesk_messages', 'ticket_id', 'idx_core_helpdesk_messages_ticket_id');
    }

    public function down()
    {
        \DBUtil::drop_table('core_helpdesk_messages');
        \DBUtil::drop_table('core_helpdesk_tickets');
        \DBUtil::drop_table('core_helpdesk_statuses');
        \DBUtil::drop_table('core_helpdesk_categories');
    }
}
