<?php

/**
 * MODEL CORE_SAT_CREDENTIAL
 *
 * Credenciales FIEL/CSD cifradas para SAT.
 *
 * @package  app
 * @extends  Orm\Model
 */
class Model_Core_Sat_Credential extends \Orm\Model
{
    protected static $_table_name = 'core_sat_credentials';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'credential_type',
        'rfc',
        'cer_path',
        'key_path',
        'password_encrypted',
        'valid_from',
        'valid_until',
        'notes',
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

    public static function list_for_admin()
    {
        return static::query()
            ->order_by('active', 'desc')
            ->order_by('id', 'desc')
            ->get();
    }
}
