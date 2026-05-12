<?php

/**
 * MODEL CORE_USER_CONSENT
 *
 * Registra el historico de aceptaciones legales por usuario.
 *
 * CONVENCION DE FLAGS:
 * - accepted: 1 = aceptado, 0 = rechazado
 *
 * @package  app
 * @extends  Orm\Model
 */
class Model_Core_User_Consent extends \Orm\Model
{
    protected static $_table_name = 'core_user_consents';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'user_id',
        'document_id',
        'version',
        'accepted',
        'channel',
        'extra_json',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => [
            'events' => ['before_insert'],
            'property' => 'accepted_at',
            'mysql_timestamp' => false,
        ],
    ];

    protected static $_belongs_to = [
        'document' => [
            'key_from' => 'document_id',
            'model_to' => 'Model_Core_Legal_Document',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];
}
