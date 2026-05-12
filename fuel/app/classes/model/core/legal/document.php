<?php

/**
 * MODEL CORE_LEGAL_DOCUMENT
 *
 * Administra documentos legales versionados del sistema.
 *
 * CONVENCION DE FLAGS:
 * - active: 1 = activo, 0 = inactivo
 * - required: 1 = obligatorio, 0 = opcional
 *
 * @package  app
 * @extends  Orm\Model
 */
class Model_Core_Legal_Document extends \Orm\Model
{
    protected static $_table_name = 'core_legal_documents';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'category',
        'document_type',
        'shortcode',
        'title',
        'content',
        'version',
        'required',
        'active',
        'allow_download',
        'valid_from',
        'valid_until',
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
        'consents' => [
            'key_from' => 'id',
            'model_to' => 'Model_Core_User_Consent',
            'key_to' => 'document_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ],
    ];

    public static function list_for_admin()
    {
        return static::query()
            ->order_by('category', 'asc')
            ->order_by('document_type', 'asc')
            ->order_by('title', 'asc')
            ->get();
    }

    public static function active_by_shortcode($shortcode)
    {
        return static::query()
            ->where('shortcode', trim((string) $shortcode))
            ->where('active', 1)
            ->order_by('version', 'desc')
            ->get_one();
    }
}
