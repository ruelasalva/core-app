<?php

class Service_Core_Workspace_Widgets_Erp_Recentdocuments extends \Service_Core_Workspace_Widgets_Erp_Base
{
    public static function manifest()
    {
        return [
            'code' => 'recent_documents',
            'title' => 'Documentos recientes',
            'description' => 'Últimos documentos registrados sin exponer rutas físicas.',
            'category' => 'system',
            'type' => 'documents',
            'icon' => 'bi bi-file-earmark-text',
            'color' => 'secondary',
            'permission_key' => 'documents.access[view]',
            'refresh_time' => 300,
            'dependencies' => [],
            'exportable' => false,
            'configurable' => false,
            'settings_schema' => [],
            'version' => 1,
            'status' => 'active',
        ];
    }

    public function load($context, array $filters = [], array $settings = [])
    {
        $table = 'core_documents';
        if (!$this->table_exists($table)) {
            return $this->payload([]);
        }

        $name = $this->field($table, ['title', 'original_name', 'name']);
        $type = $this->field($table, ['document_type', 'mime_type', 'type']);
        $created = $this->field($table, ['created_at']);

        if (!$name || !$type) {
            return $this->payload([]);
        }

        try {
            $query = \DB::select('id', $name, $type);
            if ($created) {
                $query->select($created);
            }
            $this->active_filter($query->from($table), $table);
            $query->order_by($created ?: 'id', 'desc');
            $rows = $query->limit(5)->execute()->as_array();
        } catch (\Exception $e) {
            $this->safe_query_error('recent_documents', $e);
            return $this->payload([]);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'name' => (string) \Arr::get($row, $name, 'Documento'),
                'type' => (string) \Arr::get($row, $type, '-'),
                'created_at' => $created ? $this->date_label(\Arr::get($row, $created, 0)) : '-',
            ];
        }

        return $this->payload($items);
    }

    protected function payload(array $rows)
    {
        return $this->compact_table_payload(
            [
                ['key' => 'name', 'label' => 'Documento'],
                ['key' => 'type', 'label' => 'Tipo'],
                ['key' => 'created_at', 'label' => 'Fecha'],
            ],
            $rows,
            [
                'icon' => 'bi bi-file-earmark-text',
                'title' => 'Sin documentos recientes.',
                'message' => 'No hay documentos seguros para mostrar.',
            ],
            [
                'label' => 'Abrir Documentos',
                'url' => \Uri::create('admin/documents'),
                'icon' => 'bi bi-arrow-right',
            ]
        );
    }
}
