<?php

class Model_Core_Crm_Survey_Response extends \Orm\Model
{
    protected static $_table_name = 'core_crm_survey_responses';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'survey_id', 'party_id', 'portal_code', 'score', 'answers_json', 'comments', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
