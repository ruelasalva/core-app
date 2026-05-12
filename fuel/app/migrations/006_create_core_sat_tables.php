<?php

namespace Fuel\Migrations;

class Create_core_sat_tables
{
    public function up()
    {
        \DBUtil::create_table('core_sat_config', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'mode' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'test'],
            'enabled' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'storage_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'last_sync_at' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_table('core_sat_credentials', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'credential_type' => ['type' => 'varchar', 'constraint' => 20],
            'rfc' => ['type' => 'varchar', 'constraint' => 13],
            'cer_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'key_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'password_encrypted' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'valid_from' => ['type' => 'date', 'null' => true],
            'valid_until' => ['type' => 'date', 'null' => true],
            'notes' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_credentials', ['credential_type', 'active'], 'idx_core_sat_credentials_type_active');
        \DBUtil::create_index('core_sat_credentials', 'rfc', 'idx_core_sat_credentials_rfc');

        \DBUtil::create_table('core_sat_sync_requests', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'request_type' => ['type' => 'varchar', 'constraint' => 20],
            'date_from' => ['type' => 'date'],
            'date_to' => ['type' => 'date'],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
            'sat_request_id' => ['type' => 'varchar', 'constraint' => 100, 'default' => ''],
            'attempts' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'processed_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'error_message' => ['type' => 'text', 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_sync_requests', 'status', 'idx_core_sat_sync_requests_status');
        \DBUtil::create_index('core_sat_sync_requests', ['date_from', 'date_to'], 'idx_core_sat_sync_requests_dates');

        \DBUtil::create_table('core_sat_packages', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'sync_request_id' => ['type' => 'int', 'constraint' => 11],
            'package_id' => ['type' => 'varchar', 'constraint' => 100],
            'xml_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'sha256_hash' => ['type' => 'varchar', 'constraint' => 64, 'default' => ''],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_packages', 'sync_request_id', 'idx_core_sat_packages_request_id');
        \DBUtil::create_index('core_sat_packages', 'package_id', 'idx_core_sat_packages_package_id', 'unique');

        \DBUtil::create_table('core_sat_cfdi', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'uuid' => ['type' => 'char', 'constraint' => 36],
            'direction' => ['type' => 'varchar', 'constraint' => 10],
            'version' => ['type' => 'varchar', 'constraint' => 5, 'null' => true],
            'serie' => ['type' => 'varchar', 'constraint' => 25, 'null' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 40, 'null' => true],
            'emitter_rfc' => ['type' => 'varchar', 'constraint' => 13],
            'emitter_name' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'receiver_rfc' => ['type' => 'varchar', 'constraint' => 13],
            'receiver_name' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'issued_at' => ['type' => 'datetime'],
            'stamped_at' => ['type' => 'datetime', 'null' => true],
            'total' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
            'subtotal' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
            'currency' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'exchange_rate' => ['type' => 'decimal', 'constraint' => '10,6', 'null' => true],
            'voucher_type' => ['type' => 'varchar', 'constraint' => 5, 'null' => true],
            'payment_method' => ['type' => 'varchar', 'constraint' => 10, 'null' => true],
            'payment_form' => ['type' => 'varchar', 'constraint' => 10, 'null' => true],
            'cfdi_use' => ['type' => 'varchar', 'constraint' => 10, 'null' => true],
            'sat_status' => ['type' => 'varchar', 'constraint' => 30, 'null' => true],
            'origin' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'sat'],
            'processed' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'accounted' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'xml_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_cfdi', 'uuid', 'idx_core_sat_cfdi_uuid', 'unique');
        \DBUtil::create_index('core_sat_cfdi', ['direction', 'issued_at'], 'idx_core_sat_cfdi_direction_date');
        \DBUtil::create_index('core_sat_cfdi', 'emitter_rfc', 'idx_core_sat_cfdi_emitter_rfc');
        \DBUtil::create_index('core_sat_cfdi', 'receiver_rfc', 'idx_core_sat_cfdi_receiver_rfc');
        \DBUtil::create_index('core_sat_cfdi', 'sat_status', 'idx_core_sat_cfdi_status');

        \DBUtil::create_table('core_sat_cfdi_events', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'cfdi_id' => ['type' => 'int', 'constraint' => 11],
            'event_type' => ['type' => 'varchar', 'constraint' => 40],
            'payload_json' => ['type' => 'text', 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_cfdi_events', 'cfdi_id', 'idx_core_sat_cfdi_events_cfdi_id');

        \DBUtil::create_table('core_sat_cfdi_relations', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'cfdi_id' => ['type' => 'int', 'constraint' => 11],
            'related_uuid' => ['type' => 'char', 'constraint' => 36],
            'relation_type' => ['type' => 'varchar', 'constraint' => 10],
            'related_cfdi_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'exists_in_system' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_cfdi_relations', ['cfdi_id', 'related_uuid'], 'idx_core_sat_cfdi_relations_cfdi_uuid', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_sat_cfdi_relations');
        \DBUtil::drop_table('core_sat_cfdi_events');
        \DBUtil::drop_table('core_sat_cfdi');
        \DBUtil::drop_table('core_sat_packages');
        \DBUtil::drop_table('core_sat_sync_requests');
        \DBUtil::drop_table('core_sat_credentials');
        \DBUtil::drop_table('core_sat_config');
    }
}
