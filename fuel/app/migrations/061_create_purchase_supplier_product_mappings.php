<?php

namespace Fuel\Migrations;

class Create_purchase_supplier_product_mappings
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_purchase_supplier_product_mappings')) {
            \DBUtil::create_table('core_purchase_supplier_product_mappings', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'supplier_rfc' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'supplier_sku' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'supplier_description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'supplier_description_hash' => ['type' => 'char', 'constraint' => 40, 'default' => ''],
                'sat_product_service_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'sat_unit_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'internal_sku' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'internal_name' => ['type' => 'varchar', 'constraint' => 200, 'default' => ''],
                'unit_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
                'conversion_factor' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
                'last_unit_cost' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'last_seen_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_purchase_supplier_product_mappings', ['party_id', 'supplier_sku'], 'idx_supplier_product_party_sku');
            \DBUtil::create_index('core_purchase_supplier_product_mappings', ['supplier_rfc', 'supplier_sku'], 'idx_supplier_product_rfc_sku');
            \DBUtil::create_index('core_purchase_supplier_product_mappings', ['supplier_description_hash'], 'idx_supplier_product_desc_hash');
            \DBUtil::create_index('core_purchase_supplier_product_mappings', ['product_id'], 'idx_supplier_product_product');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_purchase_supplier_product_mappings');
    }
}
