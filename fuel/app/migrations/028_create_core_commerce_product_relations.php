<?php

namespace Fuel\Migrations;

class Create_core_commerce_product_relations
{
    public function up()
    {
        \DBUtil::create_table('core_commerce_product_relations', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'product_id' => ['type' => 'int', 'constraint' => 11],
            'related_product_id' => ['type' => 'int', 'constraint' => 11],
            'relation_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'manual'],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commerce_product_relations', ['product_id', 'related_product_id'], 'idx_core_commerce_product_relations_unique', 'unique');
        \DBUtil::create_index('core_commerce_product_relations', 'product_id', 'idx_core_commerce_product_relations_product');
    }

    public function down()
    {
        \DBUtil::drop_table('core_commerce_product_relations');
    }
}
