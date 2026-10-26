<?php

/**
 * Servicio transversal para Biblioteca de Software.
 *
 * Gestiona metadatos, cargas grandes por partes, reglas de acceso y descargas
 * controladas. No depende de un portal especifico: el acceso se define por
 * alcance, portal_code, party_type y party_id.
 */
class Service_Core_Software_Library
{
    const CHUNK_SIZE = 5242880;

    protected $tables = [
        'core_software_library_categories',
        'core_software_library_items',
        'core_software_library_versions',
        'core_software_library_assets',
        'core_software_library_access_rules',
        'core_software_library_download_logs',
        'core_software_library_upload_sessions',
    ];

    public function assert_schema_ready()
    {
        foreach ($this->tables as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta la tabla '.$table.'. Ejecuta la migracion de Biblioteca de Software.');
            }
        }
    }

    public function dashboard_data()
    {
        $this->assert_schema_ready();

        return [
            'categories' => $this->categories(),
            'items' => $this->items(),
            'versions' => $this->versions(),
            'assets' => $this->assets(),
            'access_rules' => $this->access_rules(),
            'stats' => $this->stats(),
            'options' => $this->options(),
        ];
    }

    public function save_category(array $input)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $name = trim((string) \Arr::get($input, 'name', ''));
        if ($name === '') {
            return $this->fail('Captura el nombre de la categoria.', ['name_required']);
        }

        $data = [
            'code' => $this->codeify(\Arr::get($input, 'code', $name)),
            'name' => $name,
            'description' => trim((string) \Arr::get($input, 'description', '')),
            'sort_order' => (int) \Arr::get($input, 'sort_order', 0),
            'active' => (int) \Arr::get($input, 'active', 1),
        ];

        if ($id > 0) {
            \DB::update('core_software_library_categories')->set($data)->where('id', '=', $id)->execute();
        } else {
            $data['created_at'] = time();
            $data['updated_at'] = time();
            \DB::insert('core_software_library_categories')->set($data)->execute();
        }

        return $this->ok('Categoria guardada.');
    }

    public function save_item(array $input, $user_id)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $name = trim((string) \Arr::get($input, 'name', ''));
        if ($name === '') {
            return $this->fail('Captura el nombre del software.', ['name_required']);
        }

        $data = [
            'code' => $this->codeify(\Arr::get($input, 'code', $name)),
            'name' => $name,
            'description' => trim((string) \Arr::get($input, 'description', '')),
            'category_id' => (int) \Arr::get($input, 'category_id', 0),
            'vendor' => trim((string) \Arr::get($input, 'vendor', '')),
            'software_type' => $this->codeify(\Arr::get($input, 'software_type', 'tool')),
            'platform' => $this->codeify(\Arr::get($input, 'platform', 'windows')),
            'architecture' => $this->codeify(\Arr::get($input, 'architecture', 'x64')),
            'license_mode' => $this->codeify(\Arr::get($input, 'license_mode', 'none')),
            'support_notes' => trim((string) \Arr::get($input, 'support_notes', '')),
            'status' => $this->codeify(\Arr::get($input, 'status', 'draft')),
            'visibility' => $this->codeify(\Arr::get($input, 'visibility', 'restricted')),
            'updated_by' => (int) $user_id,
            'active' => (int) \Arr::get($input, 'active', 1),
        ];

        if ($id > 0) {
            \DB::update('core_software_library_items')->set($data)->where('id', '=', $id)->execute();
        } else {
            $data['created_by'] = (int) $user_id;
            $data['created_at'] = time();
            $data['updated_at'] = time();
            \DB::insert('core_software_library_items')->set($data)->execute();
        }

        return $this->ok('Software guardado.');
    }

    public function save_version(array $input, $user_id)
    {
        $software_id = (int) \Arr::get($input, 'software_id', 0);
        $version = trim((string) \Arr::get($input, 'version', ''));
        if ($software_id < 1 || $version === '') {
            return $this->fail('Selecciona software y captura version.', ['version_required']);
        }

        $id = (int) \Arr::get($input, 'id', 0);
        $is_latest = (int) \Arr::get($input, 'is_latest', 0);

        if ($is_latest === 1) {
            \DB::update('core_software_library_versions')
                ->set(['is_latest' => 0, 'updated_at' => time()])
                ->where('software_id', '=', $software_id)
                ->execute();
        }

        $data = [
            'software_id' => $software_id,
            'version' => $version,
            'release_date' => trim((string) \Arr::get($input, 'release_date', '')),
            'changelog' => trim((string) \Arr::get($input, 'changelog', '')),
            'is_latest' => $is_latest,
            'status' => $this->codeify(\Arr::get($input, 'status', 'published')),
            'updated_by' => (int) $user_id,
            'active' => (int) \Arr::get($input, 'active', 1),
        ];

        if ($id > 0) {
            \DB::update('core_software_library_versions')->set($data)->where('id', '=', $id)->execute();
        } else {
            $data['created_by'] = (int) $user_id;
            $data['created_at'] = time();
            $data['updated_at'] = time();
            \DB::insert('core_software_library_versions')->set($data)->execute();
        }

        return $this->ok('Version guardada.');
    }

    public function save_asset(array $input, $user_id)
    {
        $software_id = (int) \Arr::get($input, 'software_id', 0);
        $title = trim((string) \Arr::get($input, 'title', ''));
        $asset_type = $this->codeify(\Arr::get($input, 'asset_type', 'link'));
        if ($software_id < 1 || $title === '') {
            return $this->fail('Selecciona software y captura titulo.', ['asset_required']);
        }
        if ($asset_type === 'link' && trim((string) \Arr::get($input, 'external_url', '')) === '') {
            return $this->fail('Captura el enlace externo.', ['url_required']);
        }

        $id = (int) \Arr::get($input, 'id', 0);
        $data = [
            'software_id' => $software_id,
            'version_id' => (int) \Arr::get($input, 'version_id', 0),
            'asset_type' => $asset_type,
            'title' => $title,
            'filename' => trim((string) \Arr::get($input, 'filename', '')),
            'mime_type' => trim((string) \Arr::get($input, 'mime_type', '')),
            'size_bytes' => (int) \Arr::get($input, 'size_bytes', 0),
            'external_url' => trim((string) \Arr::get($input, 'external_url', '')),
            'requires_login' => (int) \Arr::get($input, 'requires_login', 1),
            'expires_at' => (int) \Arr::get($input, 'expires_at', 0),
            'status' => $this->codeify(\Arr::get($input, 'status', 'published')),
            'updated_by' => (int) $user_id,
            'active' => (int) \Arr::get($input, 'active', 1),
        ];

        if ($id > 0) {
            \DB::update('core_software_library_assets')->set($data)->where('id', '=', $id)->execute();
        } else {
            $data['storage_ref'] = '';
            $data['checksum_sha256'] = '';
            $data['created_by'] = (int) $user_id;
            $data['created_at'] = time();
            $data['updated_at'] = time();
            \DB::insert('core_software_library_assets')->set($data)->execute();
        }

        return $this->ok('Recurso guardado.');
    }

    public function save_access_rule(array $input, $user_id)
    {
        $software_id = (int) \Arr::get($input, 'software_id', 0);
        $asset_id = (int) \Arr::get($input, 'asset_id', 0);
        $scope_type = $this->codeify(\Arr::get($input, 'scope_type', 'portal'));
        if ($software_id < 1 && $asset_id < 1) {
            return $this->fail('Selecciona software o recurso.', ['target_required']);
        }

        $id = (int) \Arr::get($input, 'id', 0);
        $data = [
            'software_id' => $software_id,
            'asset_id' => $asset_id,
            'scope_type' => $scope_type,
            'scope_id' => (int) \Arr::get($input, 'scope_id', 0),
            'portal_code' => $this->codeify(\Arr::get($input, 'portal_code', '')),
            'party_type' => $this->codeify(\Arr::get($input, 'party_type', '')),
            'permission' => $this->codeify(\Arr::get($input, 'permission', 'download')),
            'valid_from' => trim((string) \Arr::get($input, 'valid_from', '')),
            'valid_until' => trim((string) \Arr::get($input, 'valid_until', '')),
            'notes' => trim((string) \Arr::get($input, 'notes', '')),
            'created_by' => (int) $user_id,
            'active' => (int) \Arr::get($input, 'active', 1),
        ];

        if ($id > 0) {
            unset($data['created_by']);
            \DB::update('core_software_library_access_rules')->set($data)->where('id', '=', $id)->execute();
        } else {
            $data['created_at'] = time();
            $data['updated_at'] = time();
            \DB::insert('core_software_library_access_rules')->set($data)->execute();
        }

        return $this->ok('Regla de acceso guardada.');
    }

    public function start_upload(array $input, $user_id)
    {
        $software_id = (int) \Arr::get($input, 'software_id', 0);
        $filename = trim((string) \Arr::get($input, 'filename', ''));
        $size = (int) \Arr::get($input, 'size_bytes', 0);
        if ($software_id < 1 || $filename === '' || $size < 1) {
            return $this->fail('Selecciona software y archivo.', ['upload_required']);
        }

        $code = date('YmdHis').'-'.\Str::random('alnum', 18);
        $dir = $this->upload_dir($code);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        list($id) = \DB::insert('core_software_library_upload_sessions')->set([
            'upload_code' => $code,
            'software_id' => $software_id,
            'version_id' => (int) \Arr::get($input, 'version_id', 0),
            'title' => trim((string) \Arr::get($input, 'title', '')) ?: $filename,
            'filename' => $this->clean_filename($filename),
            'mime_type' => trim((string) \Arr::get($input, 'mime_type', 'application/octet-stream')),
            'size_bytes' => $size,
            'chunk_size' => self::CHUNK_SIZE,
            'total_chunks' => max(1, (int) ceil($size / self::CHUNK_SIZE)),
            'received_chunks' => 0,
            'checksum_sha256' => trim((string) \Arr::get($input, 'checksum_sha256', '')),
            'status' => 'uploading',
            'created_by' => (int) $user_id,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        return [
            'success' => true,
            'message' => 'Carga iniciada.',
            'data' => [
                'upload_id' => (int) $id,
                'upload_code' => $code,
                'chunk_size' => self::CHUNK_SIZE,
            ],
            'errors' => [],
        ];
    }

    public function save_chunk($upload_code, $chunk_index, array $file, $user_id)
    {
        $session = $this->upload_session($upload_code, $user_id);
        if (!$session) {
            return $this->fail('Sesion de carga no encontrada.', ['upload_not_found']);
        }

        $chunk_index = (int) $chunk_index;
        if ($chunk_index < 0 || $chunk_index >= (int) $session['total_chunks']) {
            return $this->fail('Parte de archivo no valida.', ['invalid_chunk']);
        }
        if ((int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->fail('No se recibio la parte del archivo.', ['chunk_upload_error']);
        }

        $dir = $this->upload_dir($upload_code);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $target = $dir.DS.sprintf('%06d.part', $chunk_index);
        if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
            return $this->fail('No se pudo guardar la parte del archivo.', ['chunk_store_error']);
        }

        $received = $this->count_chunks($dir);
        \DB::update('core_software_library_upload_sessions')
            ->set(['received_chunks' => $received, 'updated_at' => time()])
            ->where('id', '=', (int) $session['id'])
            ->execute();

        return [
            'success' => true,
            'message' => 'Parte recibida.',
            'data' => [
                'received_chunks' => $received,
                'total_chunks' => (int) $session['total_chunks'],
            ],
            'errors' => [],
        ];
    }

    public function finish_upload($upload_code, $user_id)
    {
        $session = $this->upload_session($upload_code, $user_id);
        if (!$session) {
            return $this->fail('Sesion de carga no encontrada.', ['upload_not_found']);
        }

        $dir = $this->upload_dir($upload_code);
        if ($this->count_chunks($dir) < (int) $session['total_chunks']) {
            return $this->fail('La carga aun no esta completa.', ['upload_incomplete']);
        }

        $ref = $this->storage_ref($session);
        $absolute = $this->absolute_from_ref($ref);
        $target_dir = dirname($absolute);
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0750, true);
        }

        $out = fopen($absolute, 'wb');
        if (!$out) {
            return $this->fail('No se pudo preparar el archivo final.', ['final_open_error']);
        }

        for ($i = 0; $i < (int) $session['total_chunks']; $i++) {
            $part = $dir.DS.sprintf('%06d.part', $i);
            $in = fopen($part, 'rb');
            if (!$in) {
                fclose($out);
                return $this->fail('Falta una parte del archivo.', ['missing_chunk']);
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        $checksum = is_file($absolute) ? hash_file('sha256', $absolute) : '';
        if ((string) $session['checksum_sha256'] !== '' && strtolower((string) $session['checksum_sha256']) !== strtolower($checksum)) {
            @unlink($absolute);
            return $this->fail('El checksum no coincide.', ['checksum_mismatch']);
        }

        list($asset_id) = \DB::insert('core_software_library_assets')->set([
            'software_id' => (int) $session['software_id'],
            'version_id' => (int) $session['version_id'],
            'asset_type' => 'file',
            'title' => (string) $session['title'],
            'filename' => (string) $session['filename'],
            'mime_type' => (string) $session['mime_type'],
            'size_bytes' => (int) $session['size_bytes'],
            'storage_ref' => $ref,
            'external_url' => '',
            'checksum_sha256' => $checksum,
            'requires_login' => 1,
            'expires_at' => 0,
            'download_count' => 0,
            'status' => 'published',
            'created_by' => (int) $user_id,
            'updated_by' => (int) $user_id,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        \DB::update('core_software_library_upload_sessions')
            ->set(['status' => 'completed', 'updated_at' => time()])
            ->where('id', '=', (int) $session['id'])
            ->execute();

        $this->remove_dir($dir);

        return [
            'success' => true,
            'message' => 'Archivo disponible en la biblioteca.',
            'data' => ['asset_id' => (int) $asset_id],
            'errors' => [],
        ];
    }

    public function asset_for_download($asset_id, array $context)
    {
        $asset = \DB::select('a.*', ['i.name', 'software_name'])
            ->from(['core_software_library_assets', 'a'])
            ->join(['core_software_library_items', 'i'], 'left')
            ->on('i.id', '=', 'a.software_id')
            ->where('a.id', '=', (int) $asset_id)
            ->where('a.active', '=', 1)
            ->where('a.status', '=', 'published')
            ->execute()
            ->current();

        if (!$asset) {
            return $this->fail('Recurso no encontrado.', ['asset_not_found'], 404);
        }
        if (!$this->is_allowed($asset, $context)) {
            $this->log_download($asset, $context, 'denied');
            return $this->fail('No tienes acceso a este recurso.', ['access_denied'], 403);
        }

        $this->log_download($asset, $context, 'success');

        return [
            'success' => true,
            'message' => '',
            'data' => $asset,
            'errors' => [],
        ];
    }

    public function absolute_from_asset(array $asset)
    {
        return $this->absolute_from_ref((string) \Arr::get($asset, 'storage_ref', ''));
    }

    public function portal_assets(array $context)
    {
        $this->assert_schema_ready();
        $rows = \DB::select('a.*', ['i.name', 'software_name'], ['v.version', 'version_label'])
            ->from(['core_software_library_assets', 'a'])
            ->join(['core_software_library_items', 'i'], 'left')
            ->on('i.id', '=', 'a.software_id')
            ->join(['core_software_library_versions', 'v'], 'left')
            ->on('v.id', '=', 'a.version_id')
            ->where('a.active', '=', 1)
            ->where('a.status', '=', 'published')
            ->order_by('i.name', 'asc')
            ->order_by('a.title', 'asc')
            ->execute()
            ->as_array();

        $allowed = [];
        foreach ($rows as $row) {
            if ($this->is_allowed($row, $context)) {
                $allowed[] = $this->asset_public_array($row);
            }
        }

        return $allowed;
    }

    protected function categories()
    {
        return \DB::select()->from('core_software_library_categories')->order_by('sort_order', 'asc')->order_by('name', 'asc')->execute()->as_array();
    }

    protected function items()
    {
        return \DB::select('i.*', ['c.name', 'category_name'])
            ->from(['core_software_library_items', 'i'])
            ->join(['core_software_library_categories', 'c'], 'left')
            ->on('c.id', '=', 'i.category_id')
            ->order_by('i.name', 'asc')
            ->execute()
            ->as_array();
    }

    protected function versions()
    {
        return \DB::select('v.*', ['i.name', 'software_name'])
            ->from(['core_software_library_versions', 'v'])
            ->join(['core_software_library_items', 'i'], 'left')
            ->on('i.id', '=', 'v.software_id')
            ->order_by('i.name', 'asc')
            ->order_by('v.created_at', 'desc')
            ->execute()
            ->as_array();
    }

    protected function assets()
    {
        $rows = \DB::select('a.*', ['i.name', 'software_name'], ['v.version', 'version_label'])
            ->from(['core_software_library_assets', 'a'])
            ->join(['core_software_library_items', 'i'], 'left')
            ->on('i.id', '=', 'a.software_id')
            ->join(['core_software_library_versions', 'v'], 'left')
            ->on('v.id', '=', 'a.version_id')
            ->order_by('a.created_at', 'desc')
            ->limit(200)
            ->execute()
            ->as_array();

        return array_map([$this, 'asset_public_array'], $rows);
    }

    protected function access_rules()
    {
        return \DB::select('r.*', ['i.name', 'software_name'], ['a.title', 'asset_title'])
            ->from(['core_software_library_access_rules', 'r'])
            ->join(['core_software_library_items', 'i'], 'left')
            ->on('i.id', '=', 'r.software_id')
            ->join(['core_software_library_assets', 'a'], 'left')
            ->on('a.id', '=', 'r.asset_id')
            ->order_by('r.created_at', 'desc')
            ->execute()
            ->as_array();
    }

    protected function stats()
    {
        return [
            'items' => (int) \DB::count_records('core_software_library_items', ['active' => 1]),
            'assets' => (int) \DB::count_records('core_software_library_assets', ['active' => 1]),
            'rules' => (int) \DB::count_records('core_software_library_access_rules', ['active' => 1]),
            'downloads' => (int) \DB::count_records('core_software_library_download_logs', ['status' => 'success']),
        ];
    }

    protected function options()
    {
        return [
            'portal_codes' => [
                ['code' => 'clientes', 'label' => 'Clientes'],
                ['code' => 'proveedores', 'label' => 'Proveedores'],
                ['code' => 'revendedores', 'label' => 'Revendedores'],
                ['code' => 'socios', 'label' => 'Socios'],
                ['code' => 'empleados', 'label' => 'Empleados'],
                ['code' => 'partners', 'label' => 'Partners'],
            ],
            'party_types' => [
                ['code' => 'customer', 'label' => 'Cliente'],
                ['code' => 'supplier', 'label' => 'Proveedor'],
                ['code' => 'reseller', 'label' => 'Revendedor'],
                ['code' => 'partner', 'label' => 'Socio'],
                ['code' => 'employee', 'label' => 'Empleado'],
            ],
            'scope_types' => [
                ['code' => 'admin', 'label' => 'Administracion'],
                ['code' => 'portal', 'label' => 'Portal completo'],
                ['code' => 'party', 'label' => 'Entidad especifica'],
                ['code' => 'party_type', 'label' => 'Tipo de entidad'],
                ['code' => 'public', 'label' => 'Publico con enlace'],
            ],
        ];
    }

    protected function asset_public_array(array $row)
    {
        unset($row['storage_ref']);
        $row['download_url'] = ((string) \Arr::get($row, 'asset_type', '') === 'file')
            ? \Uri::create('admin/software-library/download/'.(int) $row['id'])
            : (string) \Arr::get($row, 'external_url', '');
        return $row;
    }

    protected function is_allowed(array $asset, array $context)
    {
        if (!empty($context['is_super_admin']) || !empty($context['admin_access'])) {
            return true;
        }

        $now = date('Y-m-d');
        $query = \DB::select()->from('core_software_library_access_rules')
            ->where('active', '=', 1)
            ->where_open()
                ->where('asset_id', '=', (int) $asset['id'])
                ->or_where('software_id', '=', (int) $asset['software_id'])
            ->where_close()
            ->where_open()
                ->where('valid_from', '=', '')
                ->or_where('valid_from', '<=', $now)
            ->where_close()
            ->where_open()
                ->where('valid_until', '=', '')
                ->or_where('valid_until', '>=', $now)
            ->where_close();

        $rules = $query->execute()->as_array();
        foreach ($rules as $rule) {
            if ($this->rule_matches($rule, $context)) {
                return true;
            }
        }

        return false;
    }

    protected function rule_matches(array $rule, array $context)
    {
        $scope = (string) $rule['scope_type'];
        if ($scope === 'public') {
            return true;
        }
        if ($scope === 'admin') {
            return !empty($context['admin_access']);
        }
        if ($scope === 'portal') {
            return (string) $rule['portal_code'] !== '' && (string) $rule['portal_code'] === (string) \Arr::get($context, 'portal_code', '');
        }
        if ($scope === 'party') {
            return (int) $rule['scope_id'] > 0 && (int) $rule['scope_id'] === (int) \Arr::get($context, 'party_id', 0);
        }
        if ($scope === 'party_type') {
            return (string) $rule['party_type'] !== '' && (string) $rule['party_type'] === (string) \Arr::get($context, 'party_type', '');
        }

        return false;
    }

    protected function log_download(array $asset, array $context, $status)
    {
        \DB::insert('core_software_library_download_logs')->set([
            'asset_id' => (int) $asset['id'],
            'software_id' => (int) $asset['software_id'],
            'version_id' => (int) $asset['version_id'],
            'user_id' => (int) \Arr::get($context, 'user_id', 0),
            'party_id' => (int) \Arr::get($context, 'party_id', 0),
            'portal_code' => (string) \Arr::get($context, 'portal_code', ''),
            'status' => (string) $status,
            'ip_hash' => hash('sha256', (string) \Input::ip()),
            'user_agent_hash' => hash('sha256', (string) \Input::user_agent()),
            'created_at' => time(),
        ])->execute();
    }

    protected function upload_session($upload_code, $user_id)
    {
        return \DB::select()->from('core_software_library_upload_sessions')
            ->where('upload_code', '=', (string) $upload_code)
            ->where('created_by', '=', (int) $user_id)
            ->where('status', '=', 'uploading')
            ->execute()
            ->current();
    }

    protected function storage_base()
    {
        return APPPATH.'storage'.DS.'software-library';
    }

    protected function upload_dir($code)
    {
        return $this->storage_base().DS.'chunks'.DS.$this->codeify($code);
    }

    protected function storage_ref(array $session)
    {
        $ext = strtolower(pathinfo((string) $session['filename'], PATHINFO_EXTENSION));
        $name = $this->codeify(pathinfo((string) $session['filename'], PATHINFO_FILENAME));
        return 'software-library/'.date('Y').'/'.date('m').'/'.(int) $session['software_id'].'/'.\Str::random('alnum', 18).'_'.$name.($ext ? '.'.$ext : '');
    }

    protected function absolute_from_ref($ref)
    {
        $ref = trim(str_replace(['\\', '..'], ['/', ''], (string) $ref), '/');
        if ($ref === '' || strpos($ref, 'software-library/') !== 0) {
            return '';
        }

        return APPPATH.'storage'.DS.str_replace('/', DS, $ref);
    }

    protected function count_chunks($dir)
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $files = glob($dir.DS.'*.part');
        return is_array($files) ? count($files) : 0;
    }

    protected function remove_dir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir.DS.'*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    protected function clean_filename($filename)
    {
        $filename = basename((string) $filename);
        return preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename);
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9_\\-]+/', '_', $value);
        return trim($value, '_-');
    }

    protected function ok($message)
    {
        return ['success' => true, 'message' => $message, 'data' => [], 'errors' => []];
    }

    protected function fail($message, array $errors = [], $status = 400)
    {
        return ['success' => false, 'message' => $message, 'data' => ['status' => $status], 'errors' => $errors];
    }
}
