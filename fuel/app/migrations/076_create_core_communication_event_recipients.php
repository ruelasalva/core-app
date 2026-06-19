<?php

namespace Fuel\Migrations;

class Create_core_communication_event_recipients
{
    public function up()
    {
        if (\DBUtil::table_exists('core_communication_event_recipients')) {
            return;
        }

        \DBUtil::create_table('core_communication_event_recipients', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'event_code' => ['type' => 'varchar', 'constraint' => 100],
            'channel_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'internal'],
            'recipient_type' => ['type' => 'varchar', 'constraint' => 30],
            'recipient_value' => ['type' => 'varchar', 'constraint' => 180],
            'mode' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'include'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_event_recipients', ['event_code', 'channel_code'], 'idx_comm_event_recipients_event_channel');
        \DBUtil::create_index('core_communication_event_recipients', ['recipient_type', 'recipient_value'], 'idx_comm_event_recipients_type_value');
        \DBUtil::create_index('core_communication_event_recipients', 'active', 'idx_comm_event_recipients_active');
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_communication_event_recipients')) {
            \DBUtil::drop_table('core_communication_event_recipients');
        }
    }
}
