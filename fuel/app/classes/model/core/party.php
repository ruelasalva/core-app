<?php

class Model_Core_Party extends \Orm\Model
{
    protected static $_table_name = 'core_parties';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'party_type', 'code', 'name', 'legal_name', 'rfc', 'email', 'phone',
        'department_id', 'sales_user_id', 'buyer_user_id',
        'price_list_id', 'payment_term_id', 'sat_cfdi_use_code', 'sat_tax_regime_code',
        'fiscal_operation_type_id', 'shipping_method_id', 'credit_limit', 'credit_days',
        'notes', 'onboarding_status', 'onboarding_notes', 'reviewed_by', 'reviewed_at',
        'active', 'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
