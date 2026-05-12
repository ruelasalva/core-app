<?php

namespace Fuel\Migrations;

class Create_core_integration_audit_tables
{
    public function up()
    {
        \DBUtil::create_table('core_integration_providers', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 140],
            'category' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'general'],
            'description' => ['type' => 'text', 'null' => true],
            'website_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'adapter_class' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'requires_install' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'install_notes' => ['type' => 'text', 'null' => true],
            'config_schema_json' => ['type' => 'text', 'null' => true],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_integration_providers', 'code', 'idx_core_integration_providers_code', 'unique');
        \DBUtil::create_index('core_integration_providers', 'category', 'idx_core_integration_providers_category');

        \DBUtil::create_table('core_integration_connections', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'provider_id' => ['type' => 'int', 'constraint' => 11],
            'code' => ['type' => 'varchar', 'constraint' => 100],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'environment' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'sandbox'],
            'public_key' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'public_value' => ['type' => 'text', 'null' => true],
            'secret_value' => ['type' => 'text', 'null' => true],
            'webhook_secret' => ['type' => 'text', 'null' => true],
            'config_json' => ['type' => 'text', 'null' => true],
            'enabled' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_integration_connections', 'code', 'idx_core_integration_connections_code', 'unique');
        \DBUtil::create_index('core_integration_connections', ['provider_id', 'environment'], 'idx_core_integration_connections_provider_env');

        \DBUtil::create_table('core_integration_webhooks', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'connection_id' => ['type' => 'int', 'constraint' => 11],
            'code' => ['type' => 'varchar', 'constraint' => 100],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'endpoint_route' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'events_json' => ['type' => 'text', 'null' => true],
            'verify_signature' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_integration_webhooks', 'code', 'idx_core_integration_webhooks_code', 'unique');
        \DBUtil::create_index('core_integration_webhooks', 'connection_id', 'idx_core_integration_webhooks_connection');

        \DBUtil::create_table('core_integration_events', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'provider_code' => ['type' => 'varchar', 'constraint' => 80],
            'connection_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'event_type' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'external_id' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'direction' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'incoming'],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
            'payload_json' => ['type' => 'text', 'null' => true],
            'response_json' => ['type' => 'text', 'null' => true],
            'error_message' => ['type' => 'text', 'null' => true],
            'received_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'processed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_integration_events', 'provider_code', 'idx_core_integration_events_provider');
        \DBUtil::create_index('core_integration_events', ['status', 'created_at'], 'idx_core_integration_events_status_created');

        \DBUtil::create_table('core_audit_logs', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'portal_code' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'backend' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'admin'],
            'module' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'action' => ['type' => 'varchar', 'constraint' => 120],
            'entity_type' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'summary' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'old_values_json' => ['type' => 'text', 'null' => true],
            'new_values_json' => ['type' => 'text', 'null' => true],
            'metadata_json' => ['type' => 'text', 'null' => true],
            'ip' => ['type' => 'varchar', 'constraint' => 45, 'default' => ''],
            'user_agent' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_audit_logs', ['module', 'action'], 'idx_core_audit_logs_module_action');
        \DBUtil::create_index('core_audit_logs', ['entity_type', 'entity_id'], 'idx_core_audit_logs_entity');
        \DBUtil::create_index('core_audit_logs', 'created_at', 'idx_core_audit_logs_created_at');
    }

    public function down()
    {
        \DBUtil::drop_table('core_audit_logs');
        \DBUtil::drop_table('core_integration_events');
        \DBUtil::drop_table('core_integration_webhooks');
        \DBUtil::drop_table('core_integration_connections');
        \DBUtil::drop_table('core_integration_providers');
    }
}
