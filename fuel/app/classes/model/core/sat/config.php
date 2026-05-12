<?php

/**
 * MODEL CORE_SAT_CONFIG
 *
 * Configuracion general del modulo SAT.
 *
 * @package  app
 * @extends  Orm\Model
 */
class Model_Core_Sat_Config extends \Orm\Model
{
    protected static $_table_name = 'core_sat_config';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'mode',
        'enabled',
        'storage_path',
        'last_sync_at',
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

    public static function get_current()
    {
        $config = static::query()->order_by('id', 'asc')->get_one();
        if ($config) {
            return $config;
        }

        return static::forge([
            'mode' => 'test',
            'enabled' => 0,
            'storage_path' => 'fuel/app/storage/sat',
        ]);
    }
}
