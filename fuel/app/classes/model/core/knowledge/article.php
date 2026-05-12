<?php

class Model_Core_Knowledge_Article extends \Orm\Model
{
    protected static $_table_name = 'core_knowledge_articles';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'code', 'title', 'category', 'summary', 'content',
        'sort_order', 'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
