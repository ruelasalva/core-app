<?php

class Model_Core_Frontend_Page extends \Orm\Model
{
    protected static $_table_name = 'core_frontend_pages';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'title', 'slug', 'page_type', 'template_key', 'seo_title', 'seo_description', 'published', 'is_home', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
