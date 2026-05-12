<?php

namespace Fuel\Migrations;

class Create_core_frontend_theme_tables
{
    public function up()
    {
        \DBUtil::create_table('core_frontend_themes', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'layout_key' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'commerce_default'],
            'color_primary' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#0f766e'],
            'color_secondary' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#172033'],
            'color_accent' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#b7791f'],
            'color_background' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#ffffff'],
            'color_surface' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#f4f7fa'],
            'color_text' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#172033'],
            'color_muted' => ['type' => 'varchar', 'constraint' => 20, 'default' => '#657084'],
            'font_family' => ['type' => 'varchar', 'constraint' => 160, 'default' => 'Arial, Helvetica, sans-serif'],
            'heading_font_family' => ['type' => 'varchar', 'constraint' => 160, 'default' => 'Arial, Helvetica, sans-serif'],
            'logo_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'favicon_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'header_style' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'standard'],
            'footer_style' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'standard'],
            'custom_css' => ['type' => 'text', 'null' => true],
            'is_active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_frontend_themes', 'code', 'idx_core_frontend_themes_code', 'unique');
        \DBUtil::create_index('core_frontend_themes', ['is_active', 'active'], 'idx_core_frontend_themes_active');
    }

    public function down()
    {
        \DBUtil::drop_table('core_frontend_themes');
    }
}
