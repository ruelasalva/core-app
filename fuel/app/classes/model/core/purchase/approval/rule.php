<?php

class Model_Core_Purchase_Approval_Rule extends \Orm\Model
{
    protected static $_table_name = 'core_purchase_approval_rules';
    protected static $_primary_key = ['id'];
    protected static $_properties = [
        'id', 'name', 'department_id', 'min_amount', 'max_amount', 'approver_user_id',
        'approver_group_id', 'auto_approve', 'requires_document', 'sort_order',
        'active', 'created_at', 'updated_at',
    ];
    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
