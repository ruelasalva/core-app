<?php

namespace Fuel\Migrations;

class Create_sales_cfdi_product_mappings
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_sales_cfdi_product_mappings')) {
            \DBUtil::create_table('core_sales_cfdi_product_mappings', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'fiscal_sku' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'fiscal_description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'fiscal_description_hash' => ['type' => 'char', 'constraint' => 40, 'default' => ''],
                'sat_product_service_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
                'sat_unit_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'internal_sku' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'internal_name' => ['type' => 'varchar', 'constraint' => 200, 'default' => ''],
                'unit_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
                'last_unit_price' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
                'last_seen_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');

            \DBUtil::create_index('core_sales_cfdi_product_mappings', ['fiscal_sku'], 'idx_sales_cfdi_product_sku');
            \DBUtil::create_index('core_sales_cfdi_product_mappings', ['fiscal_description_hash'], 'idx_sales_cfdi_product_desc_hash');
            \DBUtil::create_index('core_sales_cfdi_product_mappings', ['product_id'], 'idx_sales_cfdi_product_product');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_sales_cfdi_product_mappings');
    }
}
