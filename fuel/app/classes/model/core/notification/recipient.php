<?php

/**
 * MODEL CORE_NOTIFICATION_RECIPIENT
 *
 * Destinatarios y estado de lectura de notificaciones.
 *
 * @package  app
 * @extends  Orm\Model
 */
class Model_Core_Notification_Recipient extends \Orm\Model
{
    protected static $_table_name = 'core_notification_recipients';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'notification_id',
        'user_id',
        'status',
        'read_at',
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

    protected static $_belongs_to = [
        'notification' => [
            'key_from' => 'notification_id',
            'model_to' => 'Model_Core_Notification',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
