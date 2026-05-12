<?php

/**
 * MODEL CORE_NOTIFICATION
 *
 * Notificacion maestra del sistema.
 *
 * @package  app
 * @extends  Orm\Model
 */
class Model_Core_Notification extends \Orm\Model
{
    protected static $_table_name = 'core_notifications';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'event_code',
        'notification_type',
        'title',
        'message',
        'url',
        'icon',
        'priority',
        'payload_json',
        'created_by',
        'active',
        'expires_at',
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
        'recipients' => [
            'key_from' => 'id',
            'model_to' => 'Model_Core_Notification_Recipient',
            'key_to' => 'notification_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
