<?php

class Model_Core_Communication_Account extends \Orm\Model
{
    protected static $_table_name = 'core_communication_accounts';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id',
        'code',
        'name',
        'email_address',
        'account_type',
        'owner_user_id',
        'owner_group_id',
        'mailbox_scope',
        'provider_code',
        'smtp_provider_code',
        'imap_provider_code',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password_encrypted',
        'imap_folder_inbox',
        'imap_folder_sent',
        'imap_folder_drafts',
        'imap_folder_trash',
        'sync_inbox',
        'sync_sent',
        'sync_drafts',
        'sync_trash',
        'append_sent',
        'sync_enabled',
        'last_sync_at',
        'last_sync_status',
        'last_sync_error',
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

    public static function by_code($code)
    {
        return static::query()
            ->where('code', trim((string) $code))
            ->get_one();
    }
}
