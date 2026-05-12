<?php

namespace Fuel\Migrations;

class Create_core_operational_catalog_tables
{
    public function up()
    {
        \DBUtil::create_table('core_catalog_shipping_carriers', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'tracking_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'requires_account' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_shipping_carriers', 'code', 'idx_core_catalog_shipping_carriers_code', 'unique');

        \DBUtil::create_table('core_catalog_shipping_zones', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'country_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MX'],
            'state_codes' => ['type' => 'text', 'null' => true],
            'postal_codes' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_shipping_zones', 'code', 'idx_core_catalog_shipping_zones_code', 'unique');

        \DBUtil::create_table('core_catalog_shipping_methods', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'delivery_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'parcel'],
            'requires_address' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_shipping_methods', 'code', 'idx_core_catalog_shipping_methods_code', 'unique');

        \DBUtil::create_table('core_catalog_carrier_services', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'carrier_id' => ['type' => 'int', 'constraint' => 11],
            'shipping_method_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'code' => ['type' => 'varchar', 'constraint' => 60],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'estimated_days' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_carrier_services', ['carrier_id', 'code'], 'idx_core_catalog_carrier_services_code', 'unique');

        \DBUtil::create_table('core_catalog_shipment_statuses', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'color' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'secondary'],
            'is_final' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_shipment_statuses', 'code', 'idx_core_catalog_shipment_statuses_code', 'unique');

        \DBUtil::create_table('core_catalog_fiscal_operation_types', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 40],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'operation_scope' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'sales'],
            'requires_cfdi' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_fiscal_operation_types', 'code', 'idx_core_catalog_fiscal_operation_types_code', 'unique');

        \DBUtil::create_table('core_catalog_fiscal_document_rules', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 60],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'document_type_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'operation_type_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sat_cfdi_use_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'sat_payment_form_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'sat_payment_method_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'sat_tax_regime_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
            'requires_rfc' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'requires_fiscal_address' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_catalog_fiscal_document_rules', 'code', 'idx_core_catalog_fiscal_document_rules_code', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_catalog_fiscal_document_rules');
        \DBUtil::drop_table('core_catalog_fiscal_operation_types');
        \DBUtil::drop_table('core_catalog_shipment_statuses');
        \DBUtil::drop_table('core_catalog_carrier_services');
        \DBUtil::drop_table('core_catalog_shipping_methods');
        \DBUtil::drop_table('core_catalog_shipping_zones');
        \DBUtil::drop_table('core_catalog_shipping_carriers');
    }
}
