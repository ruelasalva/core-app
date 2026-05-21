<?php

class Service_Core_Sat_Catalog_Sync
{
    public function sync($source_id, array $definition, $user_id = 0)
    {
        $source = \DB::select()->from('core_sat_catalog_sync_sources')->where('id', '=', (int) $source_id)->execute()->current();
        if (!$source) {
            throw new \RuntimeException('Fuente de catalogo SAT no encontrada.');
        }

        $url = trim((string) $source['source_url']);
        if ($url === '') {
            throw new \RuntimeException('Captura la URL oficial de descarga del SAT antes de sincronizar.');
        }

        $path = $this->download($url, (string) $source['catalog_key']);
        $rows = $this->parse_file($path, (string) $source['source_format'], (string) $source['sheet_name']);
        $result = $this->apply_rows($rows, $source, $definition);
        $message = 'Sincronizacion completada. Nuevos: '.$result['inserted'].', actualizados: '.$result['updated'].', omitidos: '.$result['skipped'].'.';

        \DB::update('core_sat_catalog_sync_sources')->set([
            'last_synced_at' => time(),
            'last_status' => 'ok',
            'last_message' => $message,
            'updated_at' => time(),
        ])->where('id', '=', (int) $source_id)->execute();

        $this->log($source, $path, 'ok', $result, $message, $user_id);
        \Log::info('SAT catalog sync: '.$message.' catalog='.(string) $source['catalog_key']);

        return $result + ['message' => $message, 'path' => $path];
    }

    protected function download($url, $catalog_key)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 45,
                'ignore_errors' => true,
                'header' => "User-Agent: Core-App SAT Catalog Sync\r\n",
            ],
        ]);
        $content = @file_get_contents($url, false, $context);
        if ($content === false || $content === '') {
            throw new \RuntimeException('No se pudo descargar el archivo del SAT. Revisa URL o conexion.');
        }

        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt', 'xls', 'xlsx', 'html'])) {
            $extension = 'dat';
        }

        $dir = APPPATH.'storage/sat/catalogs/'.date('Y').'/'.date('m');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/'.$catalog_key.'_'.date('Ymd_His').'.'.$extension;
        file_put_contents($path, $content);
        return $path;
    }

    protected function parse_file($path, $format, $sheet_name)
    {
        $format = $format === 'auto' ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : strtolower($format);
        $content = file_get_contents($path);

        if ($format === 'csv' || $format === 'txt') {
            return $this->parse_csv($path);
        }

        if ($format === 'xlsx') {
            return $this->parse_xlsx($path, $sheet_name);
        }

        if ($format === 'xls' || $format === 'html' || stripos($content, '<table') !== false) {
            return $this->parse_html_table($content);
        }

        throw new \RuntimeException('Formato no soportado para lectura automatica. Usa CSV, XLSX o Excel guardado como HTML.');
    }

    protected function parse_csv($path)
    {
        $rows = [];
        $sample = (string) file_get_contents($path, false, null, 0, 4096);
        $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
        $handle = fopen($path, 'r');
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    protected function parse_html_table($content)
    {
        if (!class_exists('DOMDocument')) {
            throw new \RuntimeException('PHP DOMDocument no esta disponible para leer tablas HTML.');
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML($content);
        $tables = $dom->getElementsByTagName('table');
        if ($tables->length < 1) {
            throw new \RuntimeException('El archivo no contiene una tabla legible.');
        }
        $rows = [];
        foreach ($tables->item(0)->getElementsByTagName('tr') as $tr) {
            $row = [];
            foreach ($tr->childNodes as $cell) {
                if (in_array(strtolower($cell->nodeName), ['td', 'th'])) {
                    $row[] = trim(preg_replace('/\s+/', ' ', $cell->textContent));
                }
            }
            if ($row) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    protected function parse_xlsx($path, $sheet_name)
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('PHP ZipArchive no esta disponible para leer XLSX.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('No se pudo abrir el XLSX descargado.');
        }

        $shared = $this->xlsx_shared_strings($zip);
        $sheet_path = $this->xlsx_sheet_path($zip, $sheet_name);
        $xml = $zip->getFromName($sheet_path);
        $zip->close();
        if (!$xml) {
            throw new \RuntimeException('No se encontro la hoja solicitada en el XLSX.');
        }

        $doc = simplexml_load_string($xml);
        $rows = [];
        foreach ($doc->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $index = $this->column_index(preg_replace('/\d+/', '', $ref));
                $type = (string) $cell['t'];
                $value = isset($cell->v) ? (string) $cell->v : '';
                if ($type === 's') {
                    $value = isset($shared[(int) $value]) ? $shared[(int) $value] : '';
                }
                $values[$index] = trim($value);
            }
            if ($values) {
                ksort($values);
                $rows[] = array_values($values);
            }
        }
        return $rows;
    }

    protected function xlsx_shared_strings(\ZipArchive $zip)
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (!$xml) {
            return [];
        }
        $doc = simplexml_load_string($xml);
        $strings = [];
        foreach ($doc->si as $si) {
            $strings[] = trim((string) $si->t);
        }
        return $strings;
    }

    protected function xlsx_sheet_path(\ZipArchive $zip, $sheet_name)
    {
        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $rel_map = [];
        foreach ($rels->Relationship as $rel) {
            $rel_map[(string) $rel['Id']] = 'xl/'.ltrim((string) $rel['Target'], '/');
        }
        foreach ($workbook->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            if ($sheet_name === '' || strcasecmp((string) $sheet['name'], $sheet_name) === 0) {
                return isset($rel_map[(string) $attrs['id']]) ? $rel_map[(string) $attrs['id']] : 'xl/worksheets/sheet1.xml';
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    protected function column_index($letters)
    {
        $letters = strtoupper((string) $letters);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $index - 1);
    }

    protected function apply_rows(array $rows, array $source, array $definition)
    {
        if (count($rows) < 2) {
            throw new \RuntimeException('El archivo no contiene registros suficientes.');
        }

        $header_index = $this->find_header_index($rows, (string) $source['code_column'], (string) $source['name_column']);
        $headers = $rows[$header_index];
        $code_index = $this->resolve_column($headers, (string) $source['code_column']);
        $name_index = $this->resolve_column($headers, (string) $source['name_column']);

        $class = $definition['model'];
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        for ($i = $header_index + 1; $i < count($rows); $i++) {
            $code = strtoupper(trim((string) \Arr::get($rows[$i], $code_index, '')));
            $name = trim((string) \Arr::get($rows[$i], $name_index, ''));
            if ($code === '' || $name === '') {
                $skipped++;
                continue;
            }

            $existing = $class::query()->where('code', '=', $code)->get_one();
            if ($existing) {
                if ((string) $existing->name !== $name || (int) $existing->active !== 1) {
                    $existing->set(['name' => $name, 'active' => 1]);
                    $existing->save();
                    $updated++;
                } else {
                    $skipped++;
                }
                continue;
            }

            $data = ['code' => $code, 'name' => $name, 'active' => 1];
            foreach ($definition['fields'] as $field) {
                if (!isset($data[$field['name']]) && $field['name'] !== 'id') {
                    $data[$field['name']] = \Arr::get($field, 'default', '');
                }
            }
            $class::forge($data)->save();
            $inserted++;
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
    }

    protected function find_header_index(array $rows, $code_column, $name_column)
    {
        foreach ($rows as $index => $row) {
            if ($this->resolve_column($row, $code_column, false) !== null && $this->resolve_column($row, $name_column, false) !== null) {
                return $index;
            }
        }
        return 0;
    }

    protected function resolve_column(array $headers, $column, $throw = true)
    {
        if (is_numeric($column)) {
            return max(0, (int) $column - 1);
        }
        $needle = $this->normalize($column);
        foreach ($headers as $index => $header) {
            if ($this->normalize($header) === $needle) {
                return $index;
            }
        }
        if ($throw) {
            throw new \RuntimeException('No se encontro la columna '.$column.' en el archivo.');
        }
        return null;
    }

    protected function normalize($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '', $value);
        return $value;
    }

    protected function log(array $source, $path, $status, array $result, $message, $user_id)
    {
        \DB::insert('core_sat_catalog_sync_logs')->set([
            'source_id' => (int) $source['id'],
            'catalog_key' => (string) $source['catalog_key'],
            'source_url' => (string) $source['source_url'],
            'download_path' => str_replace(APPPATH, 'fuel/app/', $path),
            'status' => $status,
            'inserted_count' => (int) $result['inserted'],
            'updated_count' => (int) $result['updated'],
            'skipped_count' => (int) $result['skipped'],
            'message' => $message,
            'created_by' => (int) $user_id,
            'created_at' => time(),
        ])->execute();
    }
}
