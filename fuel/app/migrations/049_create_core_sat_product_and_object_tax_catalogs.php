<?php

namespace Fuel\Migrations;

class Create_core_sat_product_and_object_tax_catalogs
{
    public function up()
    {
        \DBUtil::create_table('core_sat_product_service_keys', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 20],
            'name' => ['type' => 'varchar', 'constraint' => 255],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_product_service_keys', 'code', 'idx_core_sat_product_service_keys_code', 'unique');

        \DBUtil::create_table('core_sat_object_tax_codes', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 5],
            'name' => ['type' => 'varchar', 'constraint' => 180],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_sat_object_tax_codes', 'code', 'idx_core_sat_object_tax_codes_code', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_sat_object_tax_codes');
        \DBUtil::drop_table('core_sat_product_service_keys');
    }
}
