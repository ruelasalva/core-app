<?php

class Model_Core_Software_Library_DownloadLog extends \Orm\Model
{
    protected static $_table_name = 'core_software_library_download_logs';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'asset_id', 'software_id', 'version_id', 'user_id',
        'party_id', 'portal_code', 'status', 'ip_hash',
        'user_agent_hash', 'created_at',
    ];
}
