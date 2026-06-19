<?php

/**
 * Sincronizador controlado de cuentas IMAP.
 *
 * No conecta modulos ERP ni crea tickets/conversaciones de negocio. Solo lee
 * mensajes IMAP de cuentas autorizadas y los entrega al Message Store.
 */
class Service_Core_Communications_ImapManager
{
    protected $allowed_encryptions = ['', 'ssl', 'tls', 'none'];

    public function validate_account_config(array $account)
    {
        $errors = [];

        $code = trim((string) \Arr::get($account, 'code', ''));
        $name = trim((string) \Arr::get($account, 'name', ''));
        $email = trim((string) \Arr::get($account, 'email_address', ''));
        $host = trim((string) \Arr::get($account, 'imap_host', ''));
        $port = (int) \Arr::get($account, 'imap_port', 0);
        $username = trim((string) \Arr::get($account, 'imap_username', ''));
        $encryption = trim((string) \Arr::get($account, 'imap_encryption', 'ssl'));

        if ($code === '' || !preg_match('/^[a-z0-9_\-\.]+$/i', $code)) {
            $errors[] = 'El codigo de la cuenta es obligatorio y solo debe usar letras, numeros, guion, punto o guion bajo.';
        }
        if ($name === '') {
            $errors[] = 'El nombre de la cuenta es obligatorio.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo de la cuenta no es valido.';
        }
        if ($host === '') {
            $errors[] = 'El host IMAP es obligatorio.';
        }
        if ($port <= 0 || $port > 65535) {
            $errors[] = 'El puerto IMAP no es valido.';
        }
        if ($username === '') {
            $errors[] = 'El usuario IMAP es obligatorio.';
        }
        if (!in_array($encryption, $this->allowed_encryptions, true)) {
            $errors[] = 'El cifrado IMAP no es valido.';
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Configuracion IMAP valida.' : 'Revisa la configuracion IMAP.',
            'errors' => $errors,
        ];
    }

    public function sync_accounts(array $options = [])
    {
        $limit = max(1, min(100, (int) \Arr::get($options, 'limit', 20)));
        $account_code = trim((string) \Arr::get($options, 'account', ''));
        $summary = [
            'success' => true,
            'message' => 'Sincronizacion IMAP procesada.',
            'found' => 0,
            'processed_accounts' => 0,
            'stored' => 0,
            'duplicates' => 0,
            'skipped' => 0,
            'failed' => 0,
            'accounts' => [],
            'errors' => [],
        ];

        if (!\DBUtil::table_exists('core_communication_accounts')) {
            return [
                'success' => false,
                'message' => 'Falta la tabla core_communication_accounts.',
                'found' => 0,
                'processed_accounts' => 0,
                'stored' => 0,
                'duplicates' => 0,
                'skipped' => 0,
                'failed' => 1,
                'accounts' => [],
                'errors' => ['missing_accounts_table'],
            ];
        }

        if ($account_code !== '') {
            $account = Model_Core_Communication_Account::by_code($account_code);
            if (!$account) {
                $summary['success'] = false;
                $summary['failed'] = 1;
                $summary['errors'][] = 'account_not_found';
                return $summary;
            }
            $accounts = [$account];
        } else {
            $accounts = Model_Core_Communication_Account::query()
                ->where('active', 1)
                ->where('sync_enabled', 1)
                ->order_by('code', 'asc')
                ->limit($limit)
                ->get();
        }

        $summary['found'] = count($accounts);
        if (empty($accounts)) {
            $summary['message'] = $account_code !== '' ? 'No se encontro la cuenta indicada.' : 'No hay cuentas activas con sincronizacion habilitada.';
            return $summary;
        }

        foreach ($accounts as $account) {
            $result = $this->sync_account((int) $account->id, ['limit' => $limit]);
            $summary['accounts'][] = $result;
            $summary['processed_accounts']++;
            $summary['stored'] += (int) \Arr::get($result, 'stored', 0);
            $summary['duplicates'] += (int) \Arr::get($result, 'duplicates', 0);
            $summary['skipped'] += (int) \Arr::get($result, 'skipped', 0);

            if (empty($result['success']) && (string) \Arr::get($result, 'status', '') !== 'skipped') {
                $summary['failed']++;
                $summary['success'] = false;
                foreach ((array) \Arr::get($result, 'errors', []) as $error) {
                    $summary['errors'][] = (string) $error;
                }
            }
        }

        return $summary;
    }

    public function sync_account($account_id, array $options = [])
    {
        $limit = max(1, min(100, (int) \Arr::get($options, 'limit', 20)));
        $account = $this->load_account((int) $account_id);
        if (!$account) {
            return $this->account_result(null, false, 'error', 'Cuenta IMAP no encontrada.', ['account_not_found']);
        }

        if ((int) $account->active !== 1) {
            return $this->mark_account($account, 'skipped', 'Cuenta inactiva; no se sincronizo.', true, ['account_inactive']);
        }

        if ((int) $account->sync_enabled !== 1) {
            return $this->mark_account($account, 'skipped', 'Cuenta sin sync habilitado; no se sincronizo.', true, ['sync_disabled']);
        }

        $validation = $this->validate_account_config($this->model_to_array($account));
        if (empty($validation['success'])) {
            return $this->mark_account($account, 'error', 'Configuracion IMAP invalida.', false, $validation['errors']);
        }

        if (!function_exists('imap_open')) {
            return $this->mark_account($account, 'error', 'PHP IMAP extension is not installed.', false, ['imap_extension_missing']);
        }

        $folders = $this->enabled_folders($account);
        if (empty($folders)) {
            return $this->mark_account($account, 'skipped', 'No hay carpetas habilitadas para sincronizar.', true, ['no_enabled_folders']);
        }

        $result = $this->account_result($account, true, 'success', 'Cuenta sincronizada.', []);
        foreach ($folders as $folder_key => $folder_name) {
            $folder_result = $this->sync_folder($account, $folder_key, $folder_name, $limit);
            $result['folders'][$folder_key] = $folder_result;
            $result['stored'] += (int) \Arr::get($folder_result, 'stored', 0);
            $result['duplicates'] += (int) \Arr::get($folder_result, 'duplicates', 0);
            $result['errors'] = array_merge($result['errors'], (array) \Arr::get($folder_result, 'errors', []));
            if (empty($folder_result['success'])) {
                $result['success'] = false;
                $result['status'] = 'error';
            }
        }

        $account->last_sync_at = time();
        $account->last_sync_status = $result['success'] ? 'success' : 'error';
        $account->last_sync_error = empty($result['errors']) ? '' : substr(implode(', ', $result['errors']), 0, 255);
        $account->save();

        $result['status'] = $account->last_sync_status;
        $result['message'] = $result['success'] ? 'Cuenta sincronizada correctamente.' : 'Cuenta procesada con errores.';
        return $result;
    }

    public function test_connection(array $account)
    {
        $validation = $this->validate_account_config($account);
        if (empty($validation['success'])) {
            return [
                'success' => false,
                'message' => 'No se puede probar IMAP hasta corregir la configuracion.',
                'data' => [
                    'account' => $this->mask_account($account),
                    'extension_loaded' => function_exists('imap_open'),
                ],
                'errors' => $validation['errors'],
            ];
        }

        if (!function_exists('imap_open')) {
            return [
                'success' => false,
                'message' => 'PHP IMAP extension is not installed.',
                'data' => [
                    'account' => $this->mask_account($account),
                    'extension_loaded' => false,
                    'simulation' => true,
                ],
                'errors' => ['imap_extension_missing'],
            ];
        }

        return [
            'success' => false,
            'message' => 'La extension IMAP esta disponible. La prueba real se ejecuta desde el worker controlado.',
            'data' => [
                'account' => $this->mask_account($account),
                'extension_loaded' => true,
                'simulation' => true,
            ],
            'errors' => ['imap_test_deferred_to_worker'],
        ];
    }

    public function mask_account(array $account)
    {
        $password_configured = trim((string) \Arr::get($account, 'imap_password_encrypted', '')) !== '';

        unset(
            $account['password'],
            $account['imap_password'],
            $account['new_imap_password'],
            $account['imap_password_encrypted'],
            $account['token'],
            $account['secret'],
            $account['client_secret']
        );

        $account['imap_password_configured'] = $password_configured;
        return $account;
    }

    public function get_default_folders()
    {
        return [
            'inbox' => 'INBOX',
            'sent' => 'Sent',
            'drafts' => 'Drafts',
            'trash' => 'Trash',
        ];
    }

    public function capabilities()
    {
        return [
            'supports_inbox' => true,
            'supports_sent' => true,
            'supports_drafts' => true,
            'supports_trash' => true,
            'supports_append_sent' => true,
            'supports_sync' => true,
            'sync_implemented' => true,
            'requires_php_imap' => true,
            'extension_loaded' => function_exists('imap_open'),
        ];
    }

    protected function sync_folder(Model_Core_Communication_Account $account, $folder_key, $folder_name, $limit)
    {
        $result = [
            'success' => true,
            'folder' => $folder_key,
            'stored' => 0,
            'duplicates' => 0,
            'scanned' => 0,
            'errors' => [],
        ];

        $mailbox = $this->mailbox_name($account, $folder_name);
        $password = $this->decode_secret((string) $account->imap_password_encrypted);
        if ($password === '') {
            return [
                'success' => false,
                'folder' => $folder_key,
                'stored' => 0,
                'duplicates' => 0,
                'scanned' => 0,
                'errors' => ['imap_password_missing'],
            ];
        }

        $imap = @imap_open($mailbox, (string) $account->imap_username, $password, OP_READONLY);
        if (!$imap) {
            return [
                'success' => false,
                'folder' => $folder_key,
                'stored' => 0,
                'duplicates' => 0,
                'scanned' => 0,
                'errors' => [$this->safe_imap_error()],
            ];
        }

        try {
            $uids = @imap_search($imap, 'ALL', SE_UID);
            $uids = is_array($uids) ? $uids : [];
            rsort($uids, SORT_NUMERIC);
            $uids = array_slice($uids, 0, $limit);
            $store = new Service_Core_Communications_MessageStore();

            foreach ($uids as $uid) {
                $result['scanned']++;
                $message = $this->message_from_uid($imap, (int) $uid, $account, $folder_key);
                $stored = $store->store_message($message);
                if (!empty($stored['data']['duplicate'])) {
                    $result['duplicates']++;
                } elseif (!empty($stored['success'])) {
                    $result['stored']++;
                }
            }
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['errors'][] = 'imap_folder_processing_error';
            \Log::error('Error procesando carpeta IMAP '.$account->code.'/'.$folder_key.': '.$e->getMessage());
        }

        @imap_close($imap);
        return $result;
    }

    protected function load_account($account_id)
    {
        $row = \DB::select()
            ->from('core_communication_accounts')
            ->where('id', '=', (int) $account_id)
            ->execute()
            ->current();

        return $row ? Model_Core_Communication_Account::forge($row, false, null, false) : null;
    }

    protected function message_from_uid($imap, $uid, Model_Core_Communication_Account $account, $folder_key)
    {
        $overview = @imap_fetch_overview($imap, (string) $uid, FT_UID);
        $overview = is_array($overview) && isset($overview[0]) ? $overview[0] : new \stdClass();
        $msgno = @imap_msgno($imap, $uid);
        $header = $msgno ? @imap_headerinfo($imap, $msgno) : null;
        $structure = @imap_fetchstructure($imap, (string) $uid, FT_UID);
        $plain = $this->decode_part(@imap_fetchbody($imap, (string) $uid, '1', FT_UID | FT_PEEK), $structure, '1');
        $html = $this->decode_part(@imap_fetchbody($imap, (string) $uid, '2', FT_UID | FT_PEEK), $structure, '2');

        if (trim($plain) === '' && trim($html) === '') {
            $plain = (string) @imap_body($imap, (string) $uid, FT_UID | FT_PEEK);
        }

        $timestamp = $this->message_timestamp($overview, $header);
        $direction = $this->direction_for_folder($folder_key);
        $status = $direction === 'outgoing' ? 'sent' : ($direction === 'draft' ? 'draft' : ($direction === 'trash' ? 'trash' : 'new'));

        return [
            'account_id' => (int) $account->id,
            'channel_code' => 'email',
            'direction' => $direction,
            'message_type' => 'email',
            'external_message_id' => $this->external_message_id($overview, $header, $account, $folder_key, $uid),
            'external_thread_id' => $this->external_thread_id($overview, $header),
            'in_reply_to' => $this->header_value($header, 'in_reply_to'),
            'references' => $this->header_value($header, 'references'),
            'from_email' => $this->address_email($header, 'from'),
            'from_name' => $this->address_name($header, 'from'),
            'to' => $this->addresses($header, 'to'),
            'cc' => $this->addresses($header, 'cc'),
            'bcc' => [],
            'subject' => $this->decode_mime((string) \Arr::get((array) $overview, 'subject', '')),
            'body_text' => $plain,
            'body_html' => $html,
            'raw_headers' => $this->safe_headers($header),
            'received_at' => $direction === 'incoming' || $direction === 'trash' ? $timestamp : 0,
            'sent_at' => $direction === 'outgoing' || $direction === 'draft' ? $timestamp : 0,
            'status' => $status,
            'provider_code' => (string) $account->imap_provider_code,
            'related_entity_type' => '',
            'related_entity_id' => 0,
            'related_party_id' => 0,
            'attachments' => $this->attachment_metadata($structure),
        ];
    }

    protected function enabled_folders(Model_Core_Communication_Account $account)
    {
        $folders = [];
        if ((int) $account->sync_inbox === 1) {
            $folders['inbox'] = (string) $account->imap_folder_inbox;
        }
        if ((int) $account->sync_sent === 1) {
            $folders['sent'] = (string) $account->imap_folder_sent;
        }
        if ((int) $account->sync_drafts === 1) {
            $folders['drafts'] = (string) $account->imap_folder_drafts;
        }
        if ((int) $account->sync_trash === 1) {
            $folders['trash'] = (string) $account->imap_folder_trash;
        }

        return array_filter($folders);
    }

    protected function mailbox_name(Model_Core_Communication_Account $account, $folder)
    {
        $flags = '/imap';
        $encryption = (string) $account->imap_encryption;
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        return '{'.(string) $account->imap_host.':'.(int) $account->imap_port.$flags.'}'.(string) $folder;
    }

    protected function mark_account(Model_Core_Communication_Account $account, $status, $message, $success, array $errors)
    {
        $account->last_sync_at = time();
        $account->last_sync_status = $status;
        $account->last_sync_error = empty($errors) ? '' : substr(implode(', ', $errors), 0, 255);
        $account->save();

        return $this->account_result($account, $success, $status, $message, $errors);
    }

    protected function account_result($account, $success, $status, $message, array $errors)
    {
        return [
            'success' => (bool) $success,
            'status' => $status,
            'message' => $message,
            'account' => $account ? $this->mask_account($this->model_to_array($account)) : [],
            'stored' => 0,
            'duplicates' => 0,
            'skipped' => $status === 'skipped' ? 1 : 0,
            'folders' => [],
            'errors' => $errors,
        ];
    }

    protected function direction_for_folder($folder_key)
    {
        if ($folder_key === 'sent') {
            return 'outgoing';
        }
        if ($folder_key === 'drafts') {
            return 'draft';
        }
        if ($folder_key === 'trash') {
            return 'trash';
        }

        return 'incoming';
    }

    protected function external_message_id($overview, $header, Model_Core_Communication_Account $account, $folder_key, $uid)
    {
        $message_id = trim((string) \Arr::get((array) $overview, 'message_id', ''));
        if ($message_id === '') {
            $message_id = $this->header_value($header, 'message_id');
        }

        return $message_id !== '' ? $message_id : 'imap:'.$account->id.':'.$folder_key.':'.$uid;
    }

    protected function external_thread_id($overview, $header)
    {
        $thread = $this->header_value($header, 'references');
        if ($thread === '') {
            $thread = $this->header_value($header, 'in_reply_to');
        }
        if ($thread === '') {
            $thread = trim((string) \Arr::get((array) $overview, 'subject', ''));
        }

        return substr(hash('sha256', $thread), 0, 64);
    }

    protected function message_timestamp($overview, $header)
    {
        $date = trim((string) \Arr::get((array) $overview, 'date', ''));
        if ($date === '' && $header && !empty($header->date)) {
            $date = (string) $header->date;
        }

        $time = $date !== '' ? strtotime($date) : false;
        return $time ? (int) $time : time();
    }

    protected function decode_part($body, $structure, $part)
    {
        $body = (string) $body;
        if ($body === '') {
            return '';
        }

        $encoding = 0;
        if ($structure && isset($structure->parts)) {
            $index = max(0, ((int) $part) - 1);
            if (isset($structure->parts[$index]->encoding)) {
                $encoding = (int) $structure->parts[$index]->encoding;
            }
        } elseif ($structure && isset($structure->encoding)) {
            $encoding = (int) $structure->encoding;
        }

        if ($encoding === 3) {
            return (string) base64_decode($body);
        }
        if ($encoding === 4) {
            return quoted_printable_decode($body);
        }

        return $body;
    }

    protected function attachment_metadata($structure)
    {
        $items = [];
        if (!$structure || empty($structure->parts) || !is_array($structure->parts)) {
            return $items;
        }

        foreach ($structure->parts as $part) {
            $params = [];
            foreach (['parameters', 'dparameters'] as $key) {
                if (!empty($part->{$key}) && is_array($part->{$key})) {
                    foreach ($part->{$key} as $param) {
                        $params[strtolower((string) $param->attribute)] = (string) $param->value;
                    }
                }
            }

            $filename = \Arr::get($params, 'filename', \Arr::get($params, 'name', ''));
            $disposition = isset($part->disposition) ? strtolower((string) $part->disposition) : '';
            if ($filename === '' && $disposition !== 'attachment' && $disposition !== 'inline') {
                continue;
            }

            $items[] = [
                'filename' => $this->decode_mime($filename ?: 'attachment'),
                'mime_type' => $this->mime_type($part),
                'size_bytes' => isset($part->bytes) ? (int) $part->bytes : 0,
                'storage_ref' => '',
                'content_hash' => '',
                'disposition' => $disposition === 'inline' ? 'inline' : 'attachment',
            ];
        }

        return $items;
    }

    protected function mime_type($part)
    {
        $types = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
        $type = isset($part->type) && isset($types[(int) $part->type]) ? $types[(int) $part->type] : 'OTHER';
        $subtype = isset($part->subtype) ? (string) $part->subtype : 'octet-stream';
        return strtolower($type.'/'.$subtype);
    }

    protected function addresses($header, $field)
    {
        if (!$header || empty($header->{$field}) || !is_array($header->{$field})) {
            return [];
        }

        $items = [];
        foreach ($header->{$field} as $address) {
            $mailbox = isset($address->mailbox) ? (string) $address->mailbox : '';
            $host = isset($address->host) ? (string) $address->host : '';
            $email = $mailbox !== '' && $host !== '' ? $mailbox.'@'.$host : '';
            $items[] = [
                'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
                'name' => isset($address->personal) ? $this->decode_mime((string) $address->personal) : '',
            ];
        }

        return $items;
    }

    protected function address_email($header, $field)
    {
        $items = $this->addresses($header, $field);
        return !empty($items[0]['email']) ? $items[0]['email'] : '';
    }

    protected function address_name($header, $field)
    {
        $items = $this->addresses($header, $field);
        return !empty($items[0]['name']) ? $items[0]['name'] : '';
    }

    protected function safe_headers($header)
    {
        if (!$header) {
            return [];
        }

        return [
            'message_id' => $this->header_value($header, 'message_id'),
            'in_reply_to' => $this->header_value($header, 'in_reply_to'),
            'references' => $this->header_value($header, 'references'),
            'date' => !empty($header->date) ? (string) $header->date : '',
        ];
    }

    protected function header_value($header, $field)
    {
        if (!$header || !isset($header->{$field})) {
            return '';
        }

        return substr(trim((string) $header->{$field}), 0, 255);
    }

    protected function decode_mime($value)
    {
        $value = (string) $value;
        if (function_exists('imap_mime_header_decode')) {
            $parts = @imap_mime_header_decode($value);
            if (is_array($parts)) {
                $decoded = '';
                foreach ($parts as $part) {
                    $decoded .= (string) $part->text;
                }
                return $decoded;
            }
        }

        return $value;
    }

    protected function safe_imap_error()
    {
        $error = function_exists('imap_last_error') ? (string) @imap_last_error() : '';
        $error = preg_replace('/(password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redacted]', $error);
        return $error !== '' ? substr($error, 0, 180) : 'imap_connection_failed';
    }

    protected function decode_secret($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        try {
            return (string) \Crypt::decode($value);
        } catch (\Exception $e) {
            \Log::warning('No se pudo decodificar secreto IMAP para sincronizacion.');
            return '';
        }
    }

    protected function model_to_array($model)
    {
        return $model ? $model->to_array() : [];
    }
}
