<?php

class Model_Core_Frontend_Slider_Item extends \Orm\Model
{
    protected static $_table_name = 'core_frontend_slider_items';
    protected static $_primary_key = ['id'];
    protected static $_properties = ['id', 'slider_id', 'title', 'subtitle', 'image_path', 'button_text', 'button_url', 'sort_order', 'active', 'created_at', 'updated_at'];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
