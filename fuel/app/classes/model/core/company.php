<?php

class Model_Core_Company extends \Orm\Model
{
    protected static $_table_name = 'core_companies';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'name',
        'legal_name',
        'rfc',
        'postal_code',
        'tax_regime_id',
        'contact_email',
        'contact_phone',
        'invoice_receive_days',
        'invoice_receive_limit_time',
        'payment_days',
        'payment_terms_days',
        'payment_frequency',
        'payment_days_of_month',
        'announcement_message',
        'blocked_reception',
        'holidays',
        'policy_file',
        'active',
        'created_at',
        'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'created_at',
            'mysql_timestamp' => false,
        ],
        'Orm\Observer_UpdatedAt' => [
            'events' => ['before_save'],
            'property' => 'updated_at',
            'mysql_timestamp' => false,
        ],
    ];

    protected static $_has_many = [
        'branches' => [
            'key_from' => 'id',
            'model_to' => 'Model_Core_Branch',
            'key_to' => 'company_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];

    public static function get_current()
    {
        $company = static::query()->order_by('id', 'asc')->get_one();

        if ($company) {
            return $company;
        }

        return static::forge([
            'name' => 'Core-App',
            'active' => 1,
        ]);
    }
}
