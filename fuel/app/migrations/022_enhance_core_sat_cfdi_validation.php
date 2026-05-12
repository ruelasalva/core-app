<?php

namespace Fuel\Migrations;

class Enhance_core_sat_cfdi_validation
{
    public function up()
    {
        \DBUtil::add_fields('core_sat_sync_requests', [
            'download_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'xml', 'after' => 'request_type'],
            'direction' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'received', 'after' => 'download_type'],
            'package_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'attempts'],
            'downloaded_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'package_count'],
            'missing_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'processed_count'],
            'cancelled_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'missing_count'],
        ]);
        \DBUtil::create_index('core_sat_sync_requests', ['download_type', 'direction', 'status'], 'idx_core_sat_sync_requests_type_direction');

        \DBUtil::add_fields('core_sat_packages', [
            'package_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'xml', 'after' => 'package_id'],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'downloaded', 'after' => 'xml_count'],
        ]);

        \DBUtil::add_fields('core_sat_cfdi', [
            'discount' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0, 'after' => 'subtotal'],
            'tax_transferred_total' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0, 'after' => 'discount'],
            'tax_withheld_total' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0, 'after' => 'tax_transferred_total'],
            'sat_status_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => '', 'after' => 'sat_status'],
            'sat_status_message' => ['type' => 'text', 'null' => true, 'after' => 'sat_status_code'],
            'cancelled_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'sat_status_message'],
            'last_validated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'cancelled_at'],
            'metadata_seen_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'last_validated_at'],
            'missing_xml' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'metadata_seen_at'],
        ]);
        \DBUtil::create_index('core_sat_cfdi', ['origin', 'missing_xml'], 'idx_core_sat_cfdi_origin_missing');
        \DBUtil::create_index('core_sat_cfdi', 'last_validated_at', 'idx_core_sat_cfdi_last_validated');
    }

    public function down()
    {
        \DBUtil::drop_fields('core_sat_cfdi', [
            'discount',
            'tax_transferred_total',
            'tax_withheld_total',
            'sat_status_code',
            'sat_status_message',
            'cancelled_at',
            'last_validated_at',
            'metadata_seen_at',
            'missing_xml',
        ]);

        \DBUtil::drop_fields('core_sat_packages', [
            'package_type',
            'status',
        ]);

        \DBUtil::drop_fields('core_sat_sync_requests', [
            'download_type',
            'direction',
            'package_count',
            'downloaded_count',
            'missing_count',
            'cancelled_count',
        ]);
    }
}
