<?php

namespace Fuel\Migrations;

class Create_core_knowledge_tables
{
    public function up()
    {
        \DBUtil::create_table('core_knowledge_articles', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 100],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'category' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'general'],
            'summary' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'content' => ['type' => 'text', 'null' => true],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_knowledge_articles', 'code', 'idx_core_knowledge_articles_code', 'unique');
        \DBUtil::create_index('core_knowledge_articles', ['category', 'active'], 'idx_core_knowledge_articles_category');
    }

    public function down()
    {
        \DBUtil::drop_table('core_knowledge_articles');
    }
}
