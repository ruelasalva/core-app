<?php

class Model_Core_Commerce_Product extends \Orm\Model
{
    protected static $_table_name = 'core_commerce_products';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'sku', 'name', 'slug', 'short_description', 'description', 'brand_id', 'category_id', 'subcategory_id',
        'unit_code', 'currency_code', 'price', 'cost', 'tax_code', 'main_image_path', 'show_in_home', 'featured',
        'published', 'active', 'sort_order', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
