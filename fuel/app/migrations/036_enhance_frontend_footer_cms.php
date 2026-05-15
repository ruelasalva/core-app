<?php

namespace Fuel\Migrations;

class Enhance_frontend_footer_cms
{
    public function up()
    {
        if (!\DBUtil::field_exists('core_frontend_footer_columns', ['column_type'])) {
            \DBUtil::add_fields('core_frontend_footer_columns', [
                'column_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'text', 'after' => 'title'],
                'icon' => ['type' => 'varchar', 'constraint' => 80, 'default' => '', 'after' => 'column_type'],
                'url' => ['type' => 'varchar', 'constraint' => 255, 'default' => '', 'after' => 'icon'],
                'settings_json' => ['type' => 'text', 'null' => true, 'after' => 'content'],
            ]);
            \DBUtil::create_index('core_frontend_footer_columns', ['column_type', 'sort_order'], 'idx_core_frontend_footer_type_order');
        }
    }

    public function down()
    {
        if (\DBUtil::field_exists('core_frontend_footer_columns', ['column_type'])) {
            \DBUtil::drop_fields('core_frontend_footer_columns', [
                'column_type',
                'icon',
                'url',
                'settings_json',
            ]);
        }
    }
}
