<?php

namespace Fuel\Migrations;

class Enhance_core_audit_logs
{
    public function up()
    {
        \DBUtil::add_fields('core_audit_logs', [
            'event_code' => ['type' => 'varchar', 'constraint' => 140, 'default' => '', 'after' => 'action'],
            'business_event' => ['type' => 'varchar', 'constraint' => 140, 'default' => '', 'after' => 'event_code'],
            'operation' => ['type' => 'varchar', 'constraint' => 40, 'default' => '', 'after' => 'business_event'],
            'table_name' => ['type' => 'varchar', 'constraint' => 120, 'default' => '', 'after' => 'operation'],
            'record_pk' => ['type' => 'varchar', 'constraint' => 80, 'default' => '', 'after' => 'table_name'],
            'changed_fields_json' => ['type' => 'text', 'null' => true, 'after' => 'new_values_json'],
            'route' => ['type' => 'varchar', 'constraint' => 180, 'default' => '', 'after' => 'metadata_json'],
            'http_method' => ['type' => 'varchar', 'constraint' => 12, 'default' => '', 'after' => 'route'],
            'request_id' => ['type' => 'varchar', 'constraint' => 80, 'default' => '', 'after' => 'http_method'],
            'session_id' => ['type' => 'varchar', 'constraint' => 80, 'default' => '', 'after' => 'request_id'],
            'severity' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'info', 'after' => 'session_id'],
        ]);

        \DBUtil::create_index('core_audit_logs', ['table_name', 'record_pk'], 'idx_core_audit_logs_table_record');
        \DBUtil::create_index('core_audit_logs', 'business_event', 'idx_core_audit_logs_business_event');
        \DBUtil::create_index('core_audit_logs', 'severity', 'idx_core_audit_logs_severity');
    }

    public function down()
    {
        \DBUtil::drop_fields('core_audit_logs', [
            'event_code',
            'business_event',
            'operation',
            'table_name',
            'record_pk',
            'changed_fields_json',
            'route',
            'http_method',
            'request_id',
            'session_id',
            'severity',
        ]);
    }
}
