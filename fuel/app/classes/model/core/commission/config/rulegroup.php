<?php

class Model_Core_Commission_Config_RuleGroup extends \Orm\Model
{
    protected static $_table_name = 'core_commission_config_rule_groups';
    protected static $_primary_key = array('id');

    protected static $_properties = array(
        'id', 'version_id', 'code', 'name', 'description', 'priority', 'enabled',
        'owner_user_id', 'created_by', 'updated_by', 'active', 'created_at', 'updated_at',
    );

    protected static $_observers = array(
        'Orm\Observer_CreatedAt' => array('events' => array('before_insert'), 'property' => 'created_at', 'mysql_timestamp' => false),
        'Orm\Observer_UpdatedAt' => array('events' => array('before_save'), 'property' => 'updated_at', 'mysql_timestamp' => false),
    );
}
