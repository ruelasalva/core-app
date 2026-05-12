<?php

namespace Fuel\Migrations;

class Create_core_calendar_tables
{
    public function up()
    {
        \DBUtil::add_fields('core_helpdesk_tickets', [
            'due_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'closed_at'],
            'scheduled_start_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'due_at'],
            'scheduled_end_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'scheduled_start_at'],
        ]);

        \DBUtil::create_index('core_helpdesk_tickets', ['assigned_user_id', 'due_at'], 'idx_core_helpdesk_tickets_assigned_due');

        \DBUtil::create_table('core_calendar_resources', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'resource_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'meeting_room'],
            'location' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'capacity' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'color' => ['type' => 'varchar', 'constraint' => 30, 'default' => '#007bff'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_calendar_resources', 'code', 'idx_core_calendar_resources_code', 'unique');
        \DBUtil::create_index('core_calendar_resources', ['resource_type', 'active'], 'idx_core_calendar_resources_type_active');

        \DBUtil::create_table('core_calendar_events', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'description' => ['type' => 'text', 'null' => true],
            'event_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'general'],
            'resource_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'assigned_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'organizer_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'related_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'related_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'start_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'end_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'all_day' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'scheduled'],
            'visibility' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'internal'],
            'color' => ['type' => 'varchar', 'constraint' => 30, 'default' => '#007bff'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_calendar_events', ['resource_id', 'start_at', 'end_at'], 'idx_core_calendar_events_resource_dates');
        \DBUtil::create_index('core_calendar_events', ['assigned_user_id', 'start_at'], 'idx_core_calendar_events_assigned_start');
        \DBUtil::create_index('core_calendar_events', ['related_entity_type', 'related_entity_id'], 'idx_core_calendar_events_related');
        \DBUtil::create_index('core_calendar_events', ['status', 'active'], 'idx_core_calendar_events_status_active');
    }

    public function down()
    {
        \DBUtil::drop_table('core_calendar_events');
        \DBUtil::drop_table('core_calendar_resources');
        \DBUtil::drop_fields('core_helpdesk_tickets', [
            'due_at',
            'scheduled_start_at',
            'scheduled_end_at',
        ]);
    }
}
