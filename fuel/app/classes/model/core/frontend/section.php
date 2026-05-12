<?php

class Model_Core_Frontend_Section extends \Orm\Model
{
    protected static $_table_name = 'core_frontend_sections';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'page_id', 'section_key', 'section_type', 'title', 'subtitle', 'content', 'media_path', 'target_type', 'target_id', 'settings_json', 'sort_order', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
