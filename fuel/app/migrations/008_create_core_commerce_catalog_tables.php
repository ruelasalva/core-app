<?php

namespace Fuel\Migrations;

class Create_core_commerce_catalog_tables
{
    public function up()
    {
        \DBUtil::create_table('core_commerce_brands', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'slug' => ['type' => 'varchar', 'constraint' => 180],
            'description' => ['type' => 'text', 'null' => true],
            'logo_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'show_in_home' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_brands', 'slug', 'idx_core_commerce_brands_slug', 'unique');

        \DBUtil::create_table('core_commerce_categories', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'slug' => ['type' => 'varchar', 'constraint' => 180],
            'description' => ['type' => 'text', 'null' => true],
            'image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'show_in_home' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_categories', 'slug', 'idx_core_commerce_categories_slug', 'unique');

        \DBUtil::create_table('core_commerce_subcategories', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'category_id' => ['type' => 'int', 'constraint' => 11],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'slug' => ['type' => 'varchar', 'constraint' => 180],
            'description' => ['type' => 'text', 'null' => true],
            'image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'show_in_home' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_subcategories', 'category_id', 'idx_core_commerce_subcategories_category_id');
        \DBUtil::create_index('core_commerce_subcategories', 'slug', 'idx_core_commerce_subcategories_slug', 'unique');

        \DBUtil::create_table('core_commerce_tags', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'constraint' => 120],
            'slug' => ['type' => 'varchar', 'constraint' => 140],
            'tag_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'general'],
            'color' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_tags', 'slug', 'idx_core_commerce_tags_slug', 'unique');

        \DBUtil::create_table('core_commerce_products', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'sku' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 200],
            'slug' => ['type' => 'varchar', 'constraint' => 220],
            'short_description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'description' => ['type' => 'text', 'null' => true],
            'brand_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'category_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'subcategory_id' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'unit_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pieza'],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'price' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
            'cost' => ['type' => 'decimal', 'constraint' => '18,6', 'default' => 0],
            'tax_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'iva_16'],
            'main_image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'show_in_home' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'featured' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'published' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_products', 'sku', 'idx_core_commerce_products_sku', 'unique');
        \DBUtil::create_index('core_commerce_products', 'slug', 'idx_core_commerce_products_slug', 'unique');
        \DBUtil::create_index('core_commerce_products', ['published', 'show_in_home'], 'idx_core_commerce_products_home');

        \DBUtil::create_table('core_commerce_product_tags', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'product_id' => ['type' => 'int', 'constraint' => 11],
            'tag_id' => ['type' => 'int', 'constraint' => 11],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_product_tags', ['product_id', 'tag_id'], 'idx_core_commerce_product_tags_unique', 'unique');

        \DBUtil::create_table('core_commerce_product_images', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'product_id' => ['type' => 'int', 'constraint' => 11],
            'image_path' => ['type' => 'varchar', 'constraint' => 255],
            'alt_text' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
            'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_commerce_product_images', 'product_id', 'idx_core_commerce_product_images_product_id');
    }

    public function down()
    {
        \DBUtil::drop_table('core_commerce_product_images');
        \DBUtil::drop_table('core_commerce_product_tags');
        \DBUtil::drop_table('core_commerce_products');
        \DBUtil::drop_table('core_commerce_tags');
        \DBUtil::drop_table('core_commerce_subcategories');
        \DBUtil::drop_table('core_commerce_categories');
        \DBUtil::drop_table('core_commerce_brands');
    }
}
