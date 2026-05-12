<?php

namespace Fuel\Migrations;

class Create_core_frontend_tables
{
    public function up()
    {
        \DBUtil::create_table('core_frontend_pages', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'slug' => ['type' => 'varchar', 'constraint' => 200],
            'page_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'content'],
            'template_key' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'default'],
            'seo_title' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'seo_description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'published' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'is_home' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_pages', 'slug', 'idx_core_frontend_pages_slug', 'unique');

        \DBUtil::create_table('core_frontend_sections', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'page_id' => ['type' => 'int', 'constraint' => 11],
            'section_key' => ['type' => 'varchar', 'constraint' => 80],
            'section_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'content'],
            'title' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'subtitle' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'content' => ['type' => 'text', 'null' => true],
            'media_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'target_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'none'],
            'target_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'settings_json' => ['type' => 'text', 'null' => true],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_sections', ['page_id', 'sort_order'], 'idx_core_frontend_sections_page_order');

        \DBUtil::create_table('core_frontend_sliders', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'location' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'home'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_sliders', 'code', 'idx_core_frontend_sliders_code', 'unique');

        \DBUtil::create_table('core_frontend_slider_items', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'slider_id' => ['type' => 'int', 'constraint' => 11],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'subtitle' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'button_text' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'button_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_slider_items', ['slider_id', 'sort_order'], 'idx_core_frontend_slider_items_order');

        \DBUtil::create_table('core_frontend_banners', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'location' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'home'],
            'image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'target_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'none'],
            'target_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_banners', 'code', 'idx_core_frontend_banners_code', 'unique');

        \DBUtil::create_table('core_frontend_menus', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'location' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'header'],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_menus', 'code', 'idx_core_frontend_menus_code', 'unique');

        \DBUtil::create_table('core_frontend_menu_items', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'menu_id' => ['type' => 'int', 'constraint' => 11],
            'parent_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'label' => ['type' => 'varchar', 'constraint' => 120],
            'url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'target_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'url'],
            'target_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_menu_items', ['menu_id', 'sort_order'], 'idx_core_frontend_menu_items_order');

        \DBUtil::create_table('core_frontend_footer_columns', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'title' => ['type' => 'varchar', 'constraint' => 160],
            'content' => ['type' => 'text', 'null' => true],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_table('core_frontend_blocks', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'block_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'html'],
            'content' => ['type' => 'text', 'null' => true],
            'settings_json' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_frontend_blocks', 'code', 'idx_core_frontend_blocks_code', 'unique');
    }

    public function down()
    {
        \DBUtil::drop_table('core_frontend_blocks');
        \DBUtil::drop_table('core_frontend_footer_columns');
        \DBUtil::drop_table('core_frontend_menu_items');
        \DBUtil::drop_table('core_frontend_menus');
        \DBUtil::drop_table('core_frontend_banners');
        \DBUtil::drop_table('core_frontend_slider_items');
        \DBUtil::drop_table('core_frontend_sliders');
        \DBUtil::drop_table('core_frontend_sections');
        \DBUtil::drop_table('core_frontend_pages');
    }
}
