<?php

namespace Fuel\Migrations;

class Add_frontend_theme_seo_fields
{
    public function up()
    {
        \DBUtil::add_fields('core_frontend_themes', [
            'site_name' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
            'seo_title_suffix' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
            'default_seo_description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'og_image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'robots' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'index,follow'],
        ]);
    }

    public function down()
    {
        \DBUtil::drop_fields('core_frontend_themes', [
            'site_name',
            'seo_title_suffix',
            'default_seo_description',
            'og_image_path',
            'robots',
        ]);
    }
}
