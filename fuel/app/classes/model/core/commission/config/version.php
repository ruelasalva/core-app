<?php

class Model_Core_Commission_Config_Version extends \Orm\Model
{
    protected static $_table_name = 'core_commission_config_versions';
    protected static $_primary_key = array('id');

    protected static $_properties = array(
        'id', 'commercial_plan_id', 'version_number', 'code', 'name', 'status',
        'valid_from', 'valid_until', 'notes', 'publish_reason', 'config_snapshot_json',
        'created_by', 'updated_by', 'approved_by', 'published_by', 'approved_at',
        'published_at', 'archived_at', 'active', 'created_at', 'updated_at',
    );

    protected static $_observers = array(
        'Orm\Observer_CreatedAt' => array('events' => array('before_insert'), 'property' => 'created_at', 'mysql_timestamp' => false),
        'Orm\Observer_UpdatedAt' => array('events' => array('before_save'), 'property' => 'updated_at', 'mysql_timestamp' => false),
    );
}
